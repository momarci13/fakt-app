<?php

namespace App\Support;

use App\Models\CourseOffering;
use App\Models\EnrollmentRequest;
use App\Models\Event;
use App\Models\Semester;
use App\Models\User;
use App\Notifications\FaktNotification;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Turns a course's first session and recurrence rule into one calendar event
 * per session.
 *
 * Session k starts at τ_k = t₀ ⊕ kΔ, where ⊕ is applied in local wall-clock
 * time (config app.timezone, Europe/Budapest). A course at 18:00 therefore
 * stays at 18:00 across the October and March DST changes; adding k·7·86400 s
 * in UTC would move it by an hour. Monthly steps never overflow (31 Jan + 1
 * month = 28/29 Feb).
 *
 * Sessions are materialised rather than exported as an RRULE so that each one
 * has its own attendance, can be moved or cancelled on its own, and the ICS
 * feed stays exact in every client.
 */
final class CourseSchedule
{
    /** Hard cap so a rule without COUNT can't generate an unbounded series. */
    public const MAX_SESSIONS = 60;

    /**
     * @return array{freq: string, interval: int, count: int|null}|null
     */
    public static function parseRule(?string $rule): ?array
    {
        if ($rule === null || trim($rule) === '') {
            return null;
        }

        if (! preg_match('/^FREQ=(DAILY|WEEKLY|MONTHLY)(?:;INTERVAL=([1-9][0-9]?))?(?:;COUNT=([1-9][0-9]{0,2}))?$/D', strtoupper(trim($rule)), $m)) {
            return null;
        }

        return [
            'freq' => $m[1],
            'interval' => isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 1,
            'count' => isset($m[3]) && $m[3] !== '' ? (int) $m[3] : null,
        ];
    }

    /**
     * @return list<array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    public static function occurrences(CarbonInterface $start, CarbonInterface $end, ?string $rule, ?CarbonInterface $until = null): array
    {
        $timezone = config('app.timezone');
        $first = CarbonImmutable::parse($start->format('Y-m-d H:i:s'), $timezone);
        $duration = $first->diffInMinutes(CarbonImmutable::parse($end->format('Y-m-d H:i:s'), $timezone));
        $parsed = self::parseRule($rule);

        if ($parsed === null) {
            return [[$first, $first->addMinutes($duration)]];
        }

        $limit = min($parsed['count'] ?? self::MAX_SESSIONS, self::MAX_SESSIONS);
        $untilLocal = $until ? CarbonImmutable::parse($until->format('Y-m-d'), $timezone)->endOfDay() : null;
        $sessions = [];

        for ($k = 0; count($sessions) < $limit; $k++) {
            $step = $k * $parsed['interval'];
            $sessionStart = match ($parsed['freq']) {
                'DAILY' => $first->addDays($step),
                'WEEKLY' => $first->addWeeks($step),
                default => $first->addMonthsNoOverflow($step),
            };

            // Without COUNT the series runs to the end of the semester.
            if ($parsed['count'] === null && $untilLocal && $sessionStart->gt($untilLocal)) {
                break;
            }

            $sessions[] = [$sessionStart, $sessionStart->addMinutes($duration)];
        }

        return $sessions;
    }

    /**
     * Create or update the course's session events to match its first session
     * and rule. Sessions that disappear are deleted, or marked cancelled if
     * someone's attendance is already recorded on them.
     *
     * @return int Number of sessions in the schedule.
     */
    public static function sync(CourseOffering $course, int $organizerId): int
    {
        $semester = Semester::query()->findOrFail($course->semester_id);
        $occurrences = self::occurrences($course->starts_at, $course->ends_at, $course->recurrence_rule, $semester->ends_at);

        DB::transaction(function () use ($course, $organizerId, $occurrences): void {
            // Rows from before sessions existed: the first-session event.
            Event::query()->where('course_offering_id', $course->id)->whereNull('session_number')->update(['session_number' => 1]);
            $existing = Event::query()->where('course_offering_id', $course->id)->get()->keyBy('session_number');

            foreach ($occurrences as $index => [$start, $end]) {
                $number = $index + 1;
                $attributes = [
                    'title' => count($occurrences) > 1 ? "{$course->title} ({$number}. alkalom)" : $course->title,
                    'starts_at' => $start->format('Y-m-d H:i:s'),
                    'ends_at' => $end->format('Y-m-d H:i:s'),
                    'location' => $course->location,
                    'description' => $course->description,
                    'status' => 'scheduled',
                ];
                $event = $existing->get($number);

                if ($event) {
                    $event->fill($attributes);
                    if ($event->isDirty(['starts_at', 'ends_at', 'location', 'status'])) {
                        $event->sequence++;
                    }
                    $event->save();

                    continue;
                }

                Event::query()->create(array_merge($attributes, [
                    'semester_id' => $course->semester_id,
                    'course_offering_id' => $course->id,
                    'session_number' => $number,
                    'organizer_id' => $organizerId,
                    'type' => 'course',
                    'visibility' => 'scope',
                    'obligation' => 'required',
                ]));
            }

            foreach ($existing->filter(fn (Event $event, $number) => $number > count($occurrences)) as $event) {
                if ($event->attendances()->exists()) {
                    $event->update(['status' => 'cancelled', 'sequence' => $event->sequence + 1]);
                } else {
                    $event->delete();
                }
            }
        });

        return count($occurrences);
    }

    /**
     * Close the date poll: fix the first session and create the sessions.
     * Enrolled members (approved or waitlisted) now see the course in their
     * calendar, so they are told so.
     */
    public static function fixDate(CourseOffering $course, CarbonInterface $start, CarbonInterface $end, ?string $location, User $actor): int
    {
        $course->update([
            'starts_at' => $start,
            'ends_at' => $end,
            'location' => $location ?: $course->location,
            'schedule_status' => 'scheduled',
        ]);
        $count = self::sync($course, $actor->id);

        $userIds = EnrollmentRequest::query()
            ->where('course_offering_id', $course->id)
            ->whereIn('status', PersonalCalendar::COURSE_CALENDAR_STATUSES)
            ->pluck('user_id');
        User::query()->whereIn('id', $userIds)->get()->each->notify(new FaktNotification(
            'Kurzusidőpont kijelölve',
            $course->title.': '.$start->format('Y. m. d. H:i').' – bekerült a naptáradba.',
            '/naptar'
        ));

        return $count;
    }
}
