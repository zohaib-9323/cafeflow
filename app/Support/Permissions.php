<?php

namespace App\Support;

use App\Enums\Role;

/**
 * Role → ability map. Every admin route and action is gated by one of these
 * abilities on the server; the UI only hides what a role can't use.
 * Super Admins pass every check (see AppServiceProvider::boot).
 */
final class Permissions
{
    public const MAP = [
        'access-admin' => [Role::Manager, Role::Staff],
        'view-dashboard' => [Role::Manager, Role::Staff],
        'view-catalog' => [Role::Manager, Role::Staff],
        'manage-catalog' => [Role::Manager],
        'view-orders' => [Role::Manager, Role::Staff],
        'manage-orders' => [Role::Manager],          // see all, assign, cancel
        'view-inventory' => [Role::Manager, Role::Staff],
        'record-waste' => [Role::Manager, Role::Staff],
        'manage-inventory' => [Role::Manager],       // items, suppliers, restock, counts
        'manage-customers' => [Role::Manager],
        'manage-promotions' => [Role::Manager],
        'manage-reviews' => [Role::Manager],
        'manage-content' => [Role::Manager],
        'view-analytics' => [Role::Manager],
        'manage-team' => [],                          // super admin only
        'manage-settings' => [],                      // super admin only
    ];

    public static function allows(Role $role, string $ability): bool
    {
        return $role === Role::SuperAdmin || in_array($role, self::MAP[$ability] ?? [], true);
    }
}
