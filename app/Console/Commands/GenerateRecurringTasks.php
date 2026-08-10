<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Support\Audit;
use Illuminate\Console\Command;

class GenerateRecurringTasks extends Command
{
    protected $signature = 'fakt:recurring-tasks';

    protected $description = 'Létrehozza a lezárt ismétlődő feladatok következő példányát.';

    public function handle(): int
    {
        $created = 0;
        Task::query()->where('status', 'done')->whereNotNull('due_at')->whereNotNull('recurrence_rule')->where('due_at', '<=', now())->with('assignees:id')->chunkById(100, function ($tasks) use (&$created): void {
            foreach ($tasks as $task) {
                switch (strtoupper((string) $task->recurrence_rule)) {
                    case 'DAILY':
                    case 'FREQ=DAILY':
                        $nextDue = $task->due_at->copy()->addDay();
                        break;
                    case 'MONTHLY':
                    case 'FREQ=MONTHLY':
                        $nextDue = $task->due_at->copy()->addMonthNoOverflow();
                        break;
                    default:
                        $nextDue = $task->due_at->copy()->addWeek();
                        break;
                }
                if (Task::query()->where('parent_id', $task->id)->where('due_at', $nextDue)->exists()) {
                    continue;
                }
                $next = Task::query()->create([
                    'parent_id' => $task->id,
                    'semester_id' => $task->semester_id,
                    'org_unit_id' => $task->org_unit_id,
                    'project_id' => $task->project_id,
                    'created_by' => $task->created_by,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => 'todo',
                    'priority' => $task->priority,
                    'due_at' => $nextDue,
                    'recurrence_rule' => $task->recurrence_rule,
                    'visibility' => $task->visibility,
                ]);
                $next->assignees()->sync($task->assignees->modelKeys());
                Audit::record($next, 'recurring_task_created');
                $created++;
            }
        });
        $this->info("{$created} ismétlődő feladatpéldány készült.");

        return self::SUCCESS;
    }
}
