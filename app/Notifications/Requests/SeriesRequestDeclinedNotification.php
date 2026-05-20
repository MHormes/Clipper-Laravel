<?php

namespace App\Notifications\Requests;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SeriesRequestDeclinedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // Receives string, not model — series is deleted before this fires
    public function __construct(private readonly string $seriesName) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your series request was not accepted")
            ->greeting('Series request update')
            ->line("Your series request for **{$this->seriesName}** was not accepted at this time.")
            ->line('If you have questions, please contact an administrator.');
    }
}
