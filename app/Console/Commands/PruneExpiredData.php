<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\AuditEntry;
use App\Models\MemberRequest;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Storage;

class PruneExpiredData extends Command
{
    protected $signature = 'fakt:retention {--dry-run : Csak az érintett rekordszámokat mutatja}';

    protected $description = 'Alkalmazza a FAKT jóváhagyott adatmegőrzési alapértékeit.';

    public function handle(): int
    {
        $taskIds = Task::query()->whereIn('status', ['done', 'cancelled'])->where('updated_at', '<', now()->subMonths(24))->pluck('id');
        $evidence = MemberRequest::query()->whereNotNull('evidence_path')->where('reviewed_at', '<', now()->subMonths(12))->get();
        $counts = [
            'task_comments' => TaskComment::query()->whereIn('task_id', $taskIds)->count(),
            'tasks' => $taskIds->count(),
            'attendances' => Attendance::query()->whereHas('event', fn ($q) => $q->where('ends_at', '<', now()->subMonths(24)))->count(),
            'audit_entries' => AuditEntry::query()->where('created_at', '<', now()->subMonths(24))->count(),
            'evidence_files' => $evidence->count(),
            'read_notifications' => DatabaseNotification::query()->whereNotNull('read_at')->where('read_at', '<', now()->subDays(90))->count(),
        ];
        $this->table(['Adatkör', 'Törlendő'], collect($counts)->map(fn ($count, $name) => [$name, $count]));
        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        TaskComment::query()->whereIn('task_id', $taskIds)->delete();
        Task::query()->whereIn('id', $taskIds)->delete();
        Attendance::query()->whereHas('event', fn ($q) => $q->where('ends_at', '<', now()->subMonths(24)))->delete();
        AuditEntry::query()->where('created_at', '<', now()->subMonths(24))->delete();
        DatabaseNotification::query()->whereNotNull('read_at')->where('read_at', '<', now()->subDays(90))->delete();
        foreach ($evidence as $request) {
            Storage::disk('local')->delete($request->evidence_path);
            $request->update(['evidence_path' => null]);
        }
        $this->info('Az adatmegőrzési szabályok alkalmazása befejeződött.');

        return self::SUCCESS;
    }
}
