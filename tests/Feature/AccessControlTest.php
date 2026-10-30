<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/account/orders')->assertRedirect(route('login'));
    }

    public function test_customers_cannot_enter_the_admin_area(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_staff_see_orders_and_inventory_but_not_management_screens(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        foreach (['/admin', '/admin/orders', '/admin/inventory', '/admin/products'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (['/admin/customers', '/admin/analytics', '/admin/promotions', '/admin/cms/homepage', '/admin/settings', '/admin/team', '/admin/products/create', '/admin/inventory/create'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_managers_run_the_shop_but_only_super_admins_manage_team_and_settings(): void
    {
        $this->actingAs(User::factory()->manager()->create());
        foreach (['/admin/customers', '/admin/promotions', '/admin/cms/faqs', '/admin/products/create'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin/team')->assertForbidden();
        $this->get('/admin/settings')->assertForbidden();

        $this->actingAs(User::factory()->superAdmin()->create());
        $this->get('/admin/team')->assertOk();
        $this->get('/admin/settings')->assertOk();
    }

    public function test_deactivated_team_members_lose_access_immediately(): void
    {
        $this->actingAs(User::factory()->manager()->inactive()->create())
            ->get('/admin/orders')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
