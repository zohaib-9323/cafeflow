<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** In-app (bell icon) notification for the team. */
class NewOrderReceived extends Notification
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'order',
            'icon' => 'bag-check',
            'title' => "New order {$this->order->number}",
            'body' => "{$this->order->customer_name} · ".Money::format($this->order->total).' · '.$this->order->fulfillment_type->label(),
            'url' => route('admin.orders.show', $this->order),
        ];
    }
}
