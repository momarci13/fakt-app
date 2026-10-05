<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Task;

/**
 * RFC 5545 writer for the personal feed and single-event downloads.
 *
 * - SEQUENCE and LAST-MODIFIED let clients notice moved sessions.
 * - STATUS:CANCELLED removes a cancelled session instead of leaving it.
 * - VALARM reminds 60 minutes before each event.
 * - Lines are folded at 75 octets without splitting a UTF-8 character.
 */
final class IcsCalendar
{
    /** @var list<string> */
    private array $lines;

    public function __construct(string $name)
    {
        $this->lines = [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//FAKT//Belső alkalmazás//HU', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
            'X-WR-CALNAME:'.self::escape($name),
            'X-WR-TIMEZONE:'.config('app.timezone'),
            'REFRESH-INTERVAL;VALUE=DURATION:PT1H',
            'X-PUBLISHED-TTL:PT1H',
        ];
    }

    /** @param  array<int, string>  $courseStatuses  course id => enrollment status */
    public function addEvent(Event $event, array $courseStatuses = []): self
    {
        $waitlisted = $event->course_offering_id && ($courseStatuses[$event->course_offering_id] ?? null) === 'waitlisted';
        $summary = ($waitlisted ? '[Várólista] ' : '').$event->title;
        $cancelled = $event->status === 'cancelled';

        $this->lines = array_merge($this->lines, [
            'BEGIN:VEVENT',
            'UID:event-'.$event->id.'@app.fakt.org.hu',
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'LAST-MODIFIED:'.($event->updated_at ?? now())->utc()->format('Ymd\THis\Z'),
            'SEQUENCE:'.(int) $event->sequence,
            'DTSTART:'.$event->starts_at->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$event->ends_at->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.self::escape($summary),
            'LOCATION:'.self::escape($event->location ?? ''),
            'DESCRIPTION:'.self::escape($event->description ?? ''),
            'STATUS:'.($cancelled ? 'CANCELLED' : 'CONFIRMED'),
            'URL:'.url('/naptar'),
        ]);
        if (! $cancelled) {
            $this->lines = array_merge($this->lines, ['BEGIN:VALARM', 'ACTION:DISPLAY', 'DESCRIPTION:'.self::escape($summary), 'TRIGGER:-PT60M', 'END:VALARM']);
        }
        $this->lines[] = 'END:VEVENT';

        return $this;
    }

    public function addTaskDeadline(Task $task): self
    {
        $this->lines = array_merge($this->lines, [
            'BEGIN:VEVENT',
            'UID:task-'.$task->id.'@app.fakt.org.hu',
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART;VALUE=DATE:'.$task->due_at->format('Ymd'),
            'SUMMARY:'.self::escape('Határidő: '.$task->title),
            'END:VEVENT',
        ]);

        return $this;
    }

    public function render(): string
    {
        $lines = array_merge($this->lines, ['END:VCALENDAR']);

        return implode("\r\n", array_map(self::fold(...), $lines))."\r\n";
    }

    public static function escape(string $value): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n", "\r"], ['\\\\', '\;', '\\,', '\\n', '\\n', '\\n'], $value);
    }

    public static function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $out = '';
        $current = '';
        $limit = 75;
        foreach (mb_str_split($line) as $char) {
            if (strlen($current) + strlen($char) > $limit) {
                $out .= $current."\r\n ";
                $current = '';
                $limit = 74; // continuation lines start with a space
            }
            $current .= $char;
        }

        return $out.$current;
    }
}
