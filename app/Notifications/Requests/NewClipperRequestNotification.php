<?php

namespace App\Notifications\Requests;

use App\Models\Series;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewClipperRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Series $series,
        private readonly User $requester,
        private readonly int $clipperCount
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->clipperCount;
        $label = $count === 1 ? 'clipper' : 'clippers';

        return (new MailMessage)
            ->subject("New Clipper Request for: {$this->series->name}")
            ->greeting('New clipper request received')
            ->line("{$this->requester->name} has requested {$count} {$label} for **{$this->series->name}**.")
            ->action('Review Requests', url('/admin/requests/clippers'))
            ->line('Log in to the admin panel to accept or decline these requests.');
    }
}
