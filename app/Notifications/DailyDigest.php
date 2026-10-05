<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param  list<array{title: string, message: string}>  $items */
    public function __construct(public array $items)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('FAKT · Napi összesítő ('.count($this->items).' új értesítés)')
            ->greeting('Szia, '.$notifiable->name.'!')
            ->line('Az elmúlt nap értesítései:');

        foreach (array_slice($this->items, 0, 20) as $item) {
            $mail->line('• '.$item['title'].': '.$item['message']);
        }
        if (count($this->items) > 20) {
            $mail->line('…és további '.(count($this->items) - 20).' értesítés.');
        }

        return $mail->action('Megnyitás', url('/dashboard'))
            ->line('Ha minden értesítést azonnal szeretnél megkapni, állítsd át a Beállítások → Profil oldalon.');
    }
}
