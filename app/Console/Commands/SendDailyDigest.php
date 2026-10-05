<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\DailyDigest;
use App\Notifications\FaktNotification;
use Illuminate\Console\Command;

class SendDailyDigest extends Command
{
    protected $signature = 'fakt:daily-digest';

    protected $description = 'Egyetlen összesítő emailt küld az összesítést választó tagoknak az új, olvasatlan értesítéseikről.';

    public function handle(): int
    {
        $sent = 0;

        User::query()
            ->where('approval_status', 'approved')
            ->where('notification_mode', 'digest')
            ->chunkById(100, function ($users) use (&$sent): void {
                foreach ($users as $user) {
                    $since = $user->digest_sent_at ?? now()->subDay();
                    $items = $user->unreadNotifications()
                        ->where('type', FaktNotification::class)
                        ->where('created_at', '>', $since)
                        ->oldest()
                        ->get()
                        ->map(fn ($notification) => [
                            'title' => (string) ($notification->data['title'] ?? ''),
                            'message' => (string) ($notification->data['message'] ?? ''),
                        ])->all();

                    $user->forceFill(['digest_sent_at' => now()])->save();
                    if ($items === []) {
                        continue;
                    }

                    $user->notify(new DailyDigest($items));
                    $sent++;
                }
            });

        $this->info("{$sent} napi összesítő sorba állítva.");

        return self::SUCCESS;
    }
}
