<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Support\IcsCalendar;
use App\Support\PersonalCalendar;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CalendarFeedController extends Controller
{
    public function __invoke(Request $request, string $token): Response
    {
        $user = User::query()->where('approval_status', 'approved')->where('calendar_token', $token)->firstOrFail();
        $statuses = PersonalCalendar::courseStatuses($user)->all();
        $calendar = new IcsCalendar('FAKT – '.$user->name);

        foreach (PersonalCalendar::events($user) as $event) {
            $calendar->addEvent($event, $statuses);
        }

        foreach (Task::query()->visibleTo($user)->whereHas('assignees', fn ($q) => $q->where('users.id', $user->id))->whereNotNull('due_at')->whereNotIn('status', ['done', 'cancelled'])->get() as $task) {
            $calendar->addTaskDeadline($task);
        }

        $body = $calendar->render();
        // DTSTAMP changes every second, so hash the content without it.
        $etag = '"'.sha1((string) preg_replace('/^DTSTAMP:.*$/m', '', $body)).'"';
        if (trim((string) $request->header('If-None-Match')) === $etag) {
            return response('', 304, ['ETag' => $etag, 'Cache-Control' => 'private, no-cache']);
        }

        return response($body, 200, ['Content-Type' => 'text/calendar; charset=utf-8', 'Cache-Control' => 'private, no-cache', 'ETag' => $etag]);
    }
}
