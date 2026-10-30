<?php

namespace App\Notifications;

use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlaced extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Order $order)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing('items.addons');

        return (new MailMessage)
            ->subject("Order {$order->number} received")
            ->markdown('mail.order-placed', [
                'order' => $order,
                'awaitingPayment' => $order->payment_method === PaymentMethod::Card && $order->payment_status->value !== 'paid',
                'url' => route('account.orders.show', $order),
            ]);
    }
}
