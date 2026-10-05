<?php

namespace App\Console\Commands;

use App\Models\CourseOffering;
use App\Support\CourseSchedule;
use Illuminate\Console\Command;

/**
 * One-off after the 2026/27 upgrade: courses created before sessions existed
 * had only their first session in the calendar. This expands every scheduled
 * course's recurrence rule into session events. Safe to run more than once.
 */
class BackfillCourseSessions extends Command
{
    protected $signature = 'fakt:backfill-course-sessions';

    protected $description = 'A meglévő kurzusok összes alkalmát naptáreseménnyé bontja (egyszeri, ismételhető).';

    public function handle(): int
    {
        $courses = 0;
        $sessions = 0;

        CourseOffering::query()->where('schedule_status', 'scheduled')->chunkById(50, function ($chunk) use (&$courses, &$sessions): void {
            foreach ($chunk as $course) {
                $sessions += CourseSchedule::sync($course, (int) $course->created_by);
                $courses++;
            }
        });

        $this->info("{$courses} kurzus, összesen {$sessions} alkalom a naptárban.");

        return self::SUCCESS;
    }
}
