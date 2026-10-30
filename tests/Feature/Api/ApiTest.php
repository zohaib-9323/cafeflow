<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsMenu;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use BuildsMenu, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->buildMenu();
    }

    public function test_login_returns_a_bearer_token_in_the_standard_envelope(): void
    {
        User::factory()->create(['email' => 'jordan@example.test', 'password' => 'secret-pass-1']);

        $token = $this->postJson('/api/v1/auth/login', ['email' => 'jordan@example.test', 'password' => 'secret-pass-1'])
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'data' => ['user', 'token', 'token_type']])
            ->assertJsonPath('success', true)
            ->json('data.token');

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.email', 'jordan@example.test');
    }

    public function test_wrong_password_and_deactivated_accounts_cannot_get_tokens(): void
    {
        User::factory()->create(['email' => 'a@example.test', 'password' => 'secret-pass-1']);
        User::factory()->inactive()->create(['email' => 'b@example.test', 'password' => 'secret-pass-1']);

        $this->postJson('/api/v1/auth/login', ['email' => 'a@example.test', 'password' => 'wrong'])
            ->assertStatus(422)->assertJsonPath('success', false);
        $this->postJson('/api/v1/auth/login', ['email' => 'b@example.test', 'password' => 'secret-pass-1'])
            ->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_errors_use_the_envelope(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized()->assertJsonPath('success', false)->assertJsonStructure(['message']);
        $this->getJson('/api/v1/products/does-not-exist')->assertNotFound()->assertJsonPath('success', false);

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/orders', [])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['fulfillment_type', 'payment_method', 'customer_name']]);
    }

    public function test_public_catalogue_hides_drafts(): void
    {
        Product::factory()->draft()->create(['name' => 'Secret Special', 'category_id' => $this->latte->category_id]);

        $names = collect($this->getJson('/api/v1/products')->assertOk()->assertJsonPath('success', true)->json('data'))->pluck('name');

        $this->assertContains('Latte', $names);
        $this->assertNotContains('Secret Special', $names);
    }

    public function test_customer_places_an_order_and_server_prices_it(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/orders', [
            'fulfillment_type' => 'pickup',
            'payment_method' => 'cash',
            'customer_name' => 'Jordan Reyes',
            'customer_phone' => '(555) 014-7731',
            'items' => [['product_id' => $this->latte->id, 'variation_id' => $this->large->id, 'addon_ids' => [$this->oat->id], 'quantity' => 2, 'unit_price' => '0.01']],
        ])->assertCreated()->assertJsonPath('success', true)->assertJsonPath('data.total', '11.66');
    }

    public function test_customers_cannot_change_order_status_or_see_other_orders(): void
    {
        $order = $this->placeOrder(User::factory()->create(), [$this->largeOatLattes()]);

        Sanctum::actingAs(User::factory()->create());
        $this->patchJson("/api/v1/orders/{$order->number}/status", ['status' => 'confirmed'])->assertForbidden()->assertJsonPath('success', false);
        $this->getJson("/api/v1/orders/{$order->number}")->assertForbidden();
    }

    public function test_staff_advance_only_orders_assigned_to_them_and_illegal_moves_get_422(): void
    {
        $order = $this->placeOrder(User::factory()->create(), [$this->largeOatLattes()]);
        $staff = User::factory()->staff()->create();
        Sanctum::actingAs($staff);

        $this->patchJson("/api/v1/orders/{$order->number}/status", ['status' => 'confirmed'])->assertForbidden();
        $order->forceFill(['assigned_to' => $staff->id])->save();

        $this->patchJson("/api/v1/orders/{$order->number}/status", ['status' => 'completed'])->assertStatus(422)->assertJsonPath('success', false);
        $this->patchJson("/api/v1/orders/{$order->number}/status", ['status' => 'confirmed'])->assertOk()->assertJsonPath('data.status', 'confirmed');
    }

    public function test_role_gated_endpoints(): void
    {
        Sanctum::actingAs(User::factory()->staff()->create());
        $this->getJson('/api/v1/customers')->assertForbidden();
        $this->getJson('/api/v1/analytics/sales')->assertForbidden();
        $this->postJson('/api/v1/products', [])->assertForbidden();
        $this->getJson('/api/v1/inventory')->assertOk();

        Sanctum::actingAs(User::factory()->manager()->create());
        $this->getJson('/api/v1/customers')->assertOk()->assertJsonStructure(['meta' => ['total']]);
    }
}
