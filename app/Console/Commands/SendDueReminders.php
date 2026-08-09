<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\FaktNotification;
use Illuminate\Console\Command;

class SendDueReminders extends Command
{
    protected $signature = 'fakt:due-reminders';

    protected $description = 'Emailes és alkalmazáson belüli emlékeztetőt küld a 24 órán belüli feladatokról.';

    public function handle(): int
    {
        $count = 0;
        Task::query()->whereNotIn('status', ['done', 'cancelled'])->whereBetween('due_at', [now(), now()->addDay()])->with('assignees')->chunkById(100, function ($tasks) use (&$count): void {
            foreach ($tasks as $task) {
                foreach ($task->assignees as $assignee) {
                    $assignee->notify(new FaktNotification('Közelgő feladathatáridő', $task->title.' – '.$task->due_at->format('Y. m. d. H:i'), '/feladatok'));
                    $count++;
                }
            }
        });
        $this->info("{$count} emlékeztető sorba állítva.");

        return self::SUCCESS;
    }
}
