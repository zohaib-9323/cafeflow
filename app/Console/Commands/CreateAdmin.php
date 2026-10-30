<?php

namespace App\Console\Commands;

use App\Enums\Role as RoleEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/** Creates the first Super Admin on a real install (demo accounts are never seeded in production). */
class CreateAdmin extends Command
{
    protected $signature = 'cafeflow:create-admin {--name=} {--email=}';

    protected $description = 'Create a Super Admin account';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name');
        $email = strtolower((string) ($this->option('email') ?: $this->ask('Email')));
        $password = $this->secret('Password (min 10 chars, mixed case and a number)');

        $validator = Validator::make(compact('name', 'email', 'password'), [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::min(10)->letters()->mixedCase()->numbers()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name, 'email' => $email, 'password' => $password,
            'role_id' => Role::idFor(RoleEnum::SuperAdmin), 'is_active' => true,
        ]);
        $this->info("Super Admin {$email} created. Sign in at ".url('/login').'.');

        return self::SUCCESS;
    }
}
