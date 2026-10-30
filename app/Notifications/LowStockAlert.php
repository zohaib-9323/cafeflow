<?php

namespace App\Notifications;

use App\Models\InventoryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /** @var list<array{name: string, quantity: string, threshold: string}> */
    public array $items;

    /** @param Collection<int, InventoryItem> $items */
    public function __construct(Collection $items, public readonly bool $mailOnly = false)
    {
        // Store a plain snapshot so the queued job doesn't depend on later stock changes.
        $this->items = $items->map(fn (InventoryItem $i) => [
            'name' => $i->name,
            'quantity' => $i->formatQuantity(),
            'threshold' => $i->formatQuantity($i->low_stock_threshold),
        ])->values()->all();
    }

    public function via(object $notifiable): array
    {
        return $this->mailOnly ? ['mail'] : ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Low stock: '.collect($this->items)->pluck('name')->take(3)->implode(', ').(count($this->items) > 3 ? '…' : ''))
            ->line('These ingredients are at or below their reorder level:');
        foreach ($this->items as $item) {
            $mail->line("• **{$item['name']}**: {$item['quantity']} left (reorder at {$item['threshold']})");
        }

        return $mail->action('Open inventory', route('admin.inventory.index', ['stock' => 'low']));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'inventory',
            'icon' => 'exclamation-triangle',
            'title' => count($this->items) === 1 ? "Low stock: {$this->items[0]['name']}" : count($this->items).' ingredients running low',
            'body' => collect($this->items)->map(fn ($i) => "{$i['name']} ({$i['quantity']})")->implode(', '),
            'url' => route('admin.inventory.index', ['stock' => 'low']),
        ];
    }
}
