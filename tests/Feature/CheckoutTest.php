<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\HmacSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsMenu;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use BuildsMenu, RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->buildMenu();
        $this->customer = User::factory()->create();
    }

    private function fillCart(): void
    {
        $this->postJson(route('cart.store'), [
            'product_id' => $this->latte->id,
            'variation_id' => $this->large->id,
            'addon_ids' => [$this->oat->id],
            'quantity' => 2,
            'price' => '0.01', // ignored: prices never come from the client
        ])->assertOk()->assertJsonPath('success', true);
    }

    private function checkout(array $overrides = [])
    {
        return $this->post(route('checkout.store'), $overrides + [
            'fulfillment_type' => 'pickup',
            'payment_method' => 'cash',
            'customer_name' => 'Jordan Reyes',
            'customer_phone' => '(555) 014-7731',
        ]);
    }

    public function test_guests_must_sign_in_to_check_out(): void
    {
        $this->get(route('checkout.show'))->assertRedirect(route('login'));
    }

    public function test_cash_checkout_creates_the_order_and_empties_the_cart(): void
    {
        $this->actingAs($this->customer);
        $this->fillCart();

        $response = $this->checkout();

        $order = Order::query()->sole();
        $response->assertRedirect(route('account.orders.show', $order));
        $this->assertSame('11.66', $order->total);
        $this->assertSame($this->customer->id, $order->user_id);
        $this->getJson(route('cart.summary'))->assertJsonPath('data.item_count', 0);
    }

    public function test_back_office_users_cannot_place_customer_orders(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        $this->get(route('checkout.show'))->assertRedirect(route('admin.dashboard'));
        $this->postJson(route('checkout.store'), [])->assertForbidden();
    }

    public function test_card_checkout_goes_to_the_test_gateway_and_a_signed_webhook_marks_it_paid(): void
    {
        $this->actingAs($this->customer);
        $this->fillCart();

        $redirect = $this->checkout(['payment_method' => 'card'])->headers->get('Location');
        $payment = Payment::query()->sole();
        $this->assertStringContainsString('/payments/mock/'.$payment->id, $redirect);
        $this->assertSame(PaymentStatus::Pending, $payment->order->payment_status);

        $payload = json_encode(['id' => 'evt_1', 'type' => 'payment.succeeded', 'data' => ['reference' => $payment->provider_reference]]);
        $this->call('POST', route('webhooks.payments', 'mock'), [], [], [], [
            'HTTP_CAFEFLOW_SIGNATURE' => HmacSignature::sign($payload, 'test-webhook-secret'),
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertOk()->assertJsonPath('message', 'Processed');

        $this->assertSame(PaymentStatus::Paid, $payment->order->fresh()->payment_status);
    }

    public function test_another_customers_order_is_not_visible(): void
    {
        $order = $this->placeOrder(User::factory()->create(), [$this->largeOatLattes()]);

        $this->actingAs($this->customer)->get(route('account.orders.show', $order))->assertForbidden();
    }
}
