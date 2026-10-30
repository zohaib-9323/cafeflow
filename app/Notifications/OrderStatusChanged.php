<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification implements ShouldQueue
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
        $order = $this->order;
        $message = match ($order->status) {
            OrderStatus::Confirmed => "Good news: we've confirmed your order and it's in the queue.",
            OrderStatus::Preparing => 'Our baristas are preparing your order now.',
            OrderStatus::ReadyForPickup => 'Your order is ready! Come by the counter whenever suits you.',
            OrderStatus::OutForDelivery => 'Your order is on its way to you.',
            OrderStatus::Completed => 'Your order is complete. Thanks for choosing us. Enjoy!',
            OrderStatus::Cancelled => 'Your order has been cancelled.'.($order->cancellation_reason ? " Reason: {$order->cancellation_reason}." : '')
                .($order->payment_status->value === 'refunded' ? ' Your online payment has been refunded.' : ''),
            default => "Your order status is now {$order->status->label()}.",
        };

        return (new MailMessage)
            ->subject("Order {$order->number}: {$order->status->label()}")
            ->greeting("Hi {$order->customer_name},")
            ->line($message)
            ->action('Track your order', route('account.orders.show', $order));
    }
}
