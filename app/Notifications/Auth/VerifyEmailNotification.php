<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify your Clipper-MS account')
            ->greeting('Verify your email address')
            ->line('Your Clipper-MS account is locked until you verify your email address.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('If you did not create a Clipper-MS account, you can ignore this message.')
            ->line("Verification link: {$verificationUrl}");
    }
}
