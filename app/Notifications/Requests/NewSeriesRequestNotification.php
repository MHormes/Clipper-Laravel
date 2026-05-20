<?php

namespace App\Notifications\Requests;

use App\Models\Series;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSeriesRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Series $series,
        private readonly User $requester
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Series Request: {$this->series->name}")
            ->greeting('New series request received')
            ->line("{$this->requester->name} has submitted a new series request.")
            ->line("**{$this->series->name}**")
            ->action('Review Request', url('/admin/requests/series'))
            ->line('Log in to the admin panel to accept or decline this request.');
    }
}
