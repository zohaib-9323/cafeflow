<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        if ($user->can('manage-orders')) {
            return true;
        }
        if ($user->hasRole(Role::Staff)) {
            return $this->staffCanWork($user, $order) || $order->assigned_to === $user->id;
        }

        return $order->user_id === $user->id;
    }

    public function updateStatus(User $user, Order $order): bool
    {
        if ($user->can('manage-orders')) {
            return true;
        }

        return $user->hasRole(Role::Staff) && $order->assigned_to === $user->id;
    }

    /** Staff can take an unassigned active order off the queue. */
    public function claim(User $user, Order $order): bool
    {
        return $user->hasRole(Role::Staff, Role::Manager, Role::SuperAdmin)
            && $order->assigned_to === null
            && $order->status->isActive();
    }

    public function assign(User $user, Order $order): bool
    {
        return $user->can('manage-orders');
    }

    /** Back-office cancellation (any active status). */
    public function cancel(User $user, Order $order): bool
    {
        return $user->can('manage-orders') && $order->status->isActive();
    }

    public function cancelOwn(User $user, Order $order): bool
    {
        return $order->user_id === $user->id && $order->status === OrderStatus::Pending;
    }

    public function pay(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }

    private function staffCanWork(User $user, Order $order): bool
    {
        return $order->assigned_to === null && $order->status->isActive();
    }
}
