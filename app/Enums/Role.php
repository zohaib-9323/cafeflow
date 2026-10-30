<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Manager = 'manager';
    case Staff = 'staff';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Manager => 'Manager',
            self::Staff => 'Staff',
            self::Customer => 'Customer',
        };
    }

    /** Roles that may enter the admin area at all. */
    public function isBackOffice(): bool
    {
        return $this !== self::Customer;
    }
}
