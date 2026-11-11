<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlatformMail extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public function __construct(public array $payload)
    {
        $this->afterCommit();
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->payload['title'])->line($this->payload['body'] ?: $this->payload['title'])->action('Open workspace', rtrim(config('app.frontend_url'), '/').$this->payload['url']);
    }
}
