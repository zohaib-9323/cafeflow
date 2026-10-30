<?php

namespace App\Actions;

use App\Enums\Role as RoleEnum;
use App\Models\Role;
use App\Models\User;
use App\Notifications\WelcomeCustomer;
use Illuminate\Support\Facades\DB;

/** Shared by web registration and the API. */
class RegisterCustomer
{
    public function __invoke(array $data): User
    {
        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
                'role_id' => Role::idFor(RoleEnum::Customer),
            ]);
            $user->customer()->create([
                'phone' => $data['phone'] ?? null,
                'marketing_opt_in' => (bool) ($data['marketing_opt_in'] ?? false),
            ]);

            return $user;
        });

        $user->notify(new WelcomeCustomer);

        return $user;
    }
}
