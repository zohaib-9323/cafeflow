<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeCustomer extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to '.config('cafeflow.business_name'))
            ->greeting("Welcome, {$notifiable->name}!")
            ->line('Your account is ready. You can now order ahead for pickup or delivery and follow every order as it is prepared.')
            ->action('Browse the menu', route('menu.index'))
            ->line('See you soon at the counter.');
    }
}
