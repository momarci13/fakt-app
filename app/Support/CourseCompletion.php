<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\CourseOffering;
use App\Models\Event;
use App\Models\ObligationWaiver;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Course completion from attendance.
 *
 * With S the course's scheduled sessions and a_u the number of sessions where
 * member u's final attendance is "absent" or "excused", the member completes
 * the course iff a_u ≤ allowed_absences (default 2), or the Elnökség waived
 * the obligation (ObligationWaivers, unanimous).
 */
final class CourseCompletion
{
    /** Final statuses that use up one of the allowed absences. */
    public const COUNTED_ABSENCES = ['absent', 'excused'];

    /**
     * @return Collection<int, array{course_id: int, title: string, sessions: int, held: int, absences: int, allowed: int, status: string, waiver_status: string|null}>
     */
    public static function forUser(User $user, ?Semester $semester = null): Collection
    {
        $semester ??= Semester::active();
        if (! $semester) {
            return collect();
        }

        $courses = CourseOffering::query()
            ->where('semester_id', $semester->id)
            ->whereHas('enrollments', fn ($q) => $q->where('user_id', $user->id)->where('status', 'approved'))
            ->orderBy('starts_at')
            ->get(['id', 'title', 'allowed_absences', 'schedule_status']);

        if ($courses->isEmpty()) {
            return collect();
        }

        $sessions = Event::query()
            ->whereIn('course_offering_id', $courses->modelKeys())
            ->where('status', 'scheduled')
            ->get(['id', 'course_offering_id', 'ends_at']);
        $absences = Attendance::query()
            ->where('user_id', $user->id)
            ->whereIn('event_id', $sessions->modelKeys())
            ->whereIn('final_status', self::COUNTED_ABSENCES)
            ->pluck('event_id');
        $waivers = ObligationWaiver::query()
            ->where('user_id', $user->id)
            ->whereIn('course_offering_id', $courses->modelKeys())
            ->latest()
            ->get()
            ->unique('course_offering_id')
            ->keyBy('course_offering_id');

        return $courses->map(function (CourseOffering $course) use ($sessions, $absences, $waivers) {
            $own = $sessions->where('course_offering_id', $course->id);
            $missed = $own->whereIn('id', $absences)->count();
            $held = $own->filter(fn (Event $event) => $event->ends_at->isPast())->count();
            $waiver = $waivers->get($course->id);

            return [
                'course_id' => $course->id,
                'title' => $course->title,
                'sessions' => $own->count(),
                'held' => $held,
                'absences' => $missed,
                'allowed' => (int) $course->allowed_absences,
                'status' => self::status($own->count(), $held, $missed, (int) $course->allowed_absences, $waiver?->status === 'approved'),
                'waiver_status' => $waiver?->status,
            ];
        })->values();
    }

    public static function status(int $sessions, int $held, int $absences, int $allowed, bool $waived): string
    {
        return match (true) {
            $waived => 'waived',
            $absences > $allowed => 'failed',
            $sessions > 0 && $held >= $sessions => 'completed',
            $absences === $allowed && $allowed > 0 => 'at_risk',
            default => 'on_track',
        };
    }
}
