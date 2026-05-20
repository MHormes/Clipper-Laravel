<?php

namespace App\Notifications\Requests;

use App\Models\Series;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClipperRequestAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Series $series) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/series/' . $this->series->id . '/' . $this->series->slug);

        return (new MailMessage)
            ->subject("Your clipper request has been accepted")
            ->greeting('Great news!')
            ->line("Your clipper request for **{$this->series->name}** has been accepted.")
            ->action('View Series', $url)
            ->line('Your clippers are now live in the catalog.');
    }
}
