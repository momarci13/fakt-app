<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FaktNotification extends Notification implements ShouldQueue
{
    public string $title;
    public string $message;
    public string $url = '/dashboard';
    use Queueable;

    public function __construct(string $title, string $message, string $url = '/dashboard')
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
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
