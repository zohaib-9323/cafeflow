<?php

namespace App\Policies;

use App\Models\User;

/** Back-office team accounts. Only Super Admins manage them. */
class UserPolicy
{
    public function manage(User $actor, User $target): bool
    {
        return $actor->can('manage-team');
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->can('manage-team') && $actor->id !== $target->id;
    }

    /** Managers can suspend customers; nobody can suspend themselves. */
    public function toggleCustomer(User $actor, User $target): bool
    {
        return $actor->can('manage-customers') && $actor->id !== $target->id && ! $target->isBackOffice();
    }
}
