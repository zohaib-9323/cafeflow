<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\WelcomeCustomer;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_customer_account_only(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Priya Nair',
            'email' => 'priya@example.test',
            'password' => 'good-pass-123',
            'password_confirmation' => 'good-pass-123',
            'terms' => '1',
            'role_id' => 1, // attempt to self-promote is ignored
        ])->assertRedirect();

        $user = User::query()->where('email', 'priya@example.test')->sole();
        $this->assertSame(Role::Customer, $user->roleEnum());
        $this->assertNotNull($user->customer);
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, WelcomeCustomer::class);
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'good-pass-123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => $user->email, 'password' => 'good-pass-123'])->assertRedirect();
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect();
        $this->assertGuest();
    }

    public function test_team_members_land_on_the_dashboard(): void
    {
        $staff = User::factory()->staff()->create(['password' => 'good-pass-123']);

        $this->post('/login', ['email' => $staff->email, 'password' => 'good-pass-123'])->assertRedirect(route('admin.dashboard'));
    }

    public function test_deactivated_accounts_cannot_sign_in(): void
    {
        $user = User::factory()->inactive()->create(['password' => 'good-pass-123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'good-pass-123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_demo_accounts_are_never_seeded_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(\RuntimeException::class);
        // Called directly: `db:seed` in production would stop at its confirmation prompt first.
        $this->app->call([new DemoUserSeeder, 'run']);
    }
}
