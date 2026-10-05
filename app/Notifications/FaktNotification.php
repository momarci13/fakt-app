<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * In-app notification, plus an email when it is urgent or the member chose
 * "immediate" mail. Everyone else gets the day's notifications in one
 * DailyDigest email (fakt:daily-digest), which keeps the Gmail sending volume
 * at most one message per member per day.
 */
class FaktNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $title;

    public string $message;

    public string $url = '/dashboard';

    public bool $urgent = false;

    public function __construct(string $title, string $message, string $url = '/dashboard', bool $urgent = false)
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
        $this->urgent = $urgent;
    }

    public function via(object $notifiable): array
    {
        $immediate = $this->urgent || ($notifiable->notification_mode ?? 'digest') === 'immediate';

        return $immediate ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('FAKT · '.$this->title)->greeting('Szia, '.$notifiable->name.'!')->line($this->message)->action('Megnyitás', url($this->url))->line('Ezt az üzenetet a FAKT belső alkalmazása küldte.');
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'message' => $this->message, 'url' => $this->url];
    }
}
