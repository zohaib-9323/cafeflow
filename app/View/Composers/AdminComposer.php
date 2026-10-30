<?php

namespace App\View\Composers;

use App\Enums\OrderStatus;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Services\SiteContent;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminComposer
{
    public function __construct(private readonly SiteContent $content) {}

    public function compose(View $view): void
    {
        $user = Auth::user();

        $view->with([
            'businessName' => $this->content->get('business.name'),
            'unreadNotifications' => $user->unreadNotifications()->latest()->limit(8)->get(),
            'unreadCount' => $user->unreadNotifications()->count(),
            'navCounts' => [
                'pending_orders' => $user->can('view-orders') ? Order::query()->where('status', OrderStatus::Pending)->count() : 0,
                'low_stock' => $user->can('view-inventory') ? InventoryItem::query()->lowStock()->count() : 0,
            ],
        ]);
    }
}
