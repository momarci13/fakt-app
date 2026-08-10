<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Support\PersonalCalendar;
use Illuminate\Http\Response;

class CalendarFeedController extends Controller
{
    public function __invoke(string $token): Response
    {
        $user = User::query()->where('calendar_token', $token)->firstOrFail();
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//FAKT//Belső alkalmazás//HU', 'CALSCALE:GREGORIAN', 'X-WR-CALNAME:FAKT – '.$this->escape($user->name)];

        foreach (PersonalCalendar::events($user) as $event) {
            $lines = array_merge($lines, ['BEGIN:VEVENT', 'UID:event-'.$event->id.'@app.fakt.org.hu', 'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'), 'DTSTART:'.$event->starts_at->utc()->format('Ymd\THis\Z'), 'DTEND:'.$event->ends_at->utc()->format('Ymd\THis\Z'), 'SUMMARY:'.$this->escape($event->title), 'LOCATION:'.$this->escape($event->location ?? ''), 'DESCRIPTION:'.$this->escape($event->description ?? ''), 'END:VEVENT']);
        }

        foreach (Task::query()->visibleTo($user)->whereHas('assignees', fn ($q) => $q->where('users.id', $user->id))->whereNotNull('due_at')->whereNotIn('status', ['done', 'cancelled'])->get() as $task) {
            $lines = array_merge($lines, ['BEGIN:VEVENT', 'UID:task-'.$task->id.'@app.fakt.org.hu', 'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'), 'DTSTART;VALUE=DATE:'.$task->due_at->format('Ymd'), 'SUMMARY:'.$this->escape('Határidő: '.$task->title), 'END:VEVENT']);
        }

        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines)."\r\n", 200, ['Content-Type' => 'text/calendar; charset=utf-8', 'Cache-Control' => 'private, no-store']);
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\\;', '\\,', '\\n', '\\n'], $value);
    }
}
