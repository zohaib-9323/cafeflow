<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\HmacSignature;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BuildsMenu;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use BuildsMenu, RefreshDatabase;

    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->buildMenu();
        $order = $this->placeOrder(User::factory()->create(), [$this->largeOatLattes()], ['payment_method' => 'card']);
        app(PaymentService::class)->startCheckout($order);
        $this->payment = $order->payments()->sole();
    }

    private function event(string $id, string $type = 'payment.succeeded'): string
    {
        return json_encode(['id' => $id, 'type' => $type, 'data' => ['reference' => $this->payment->provider_reference, 'failure_reason' => 'Card declined (test)']]);
    }

    private function deliver(string $payload, ?string $signature): TestResponse
    {
        return $this->call('POST', route('webhooks.payments', 'mock'), [], [], [], array_filter([
            'HTTP_CAFEFLOW_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ]), $payload);
    }

    public function test_unsigned_or_wrongly_signed_webhooks_are_rejected(): void
    {
        $payload = $this->event('evt_forged');

        $this->deliver($payload, null)->assertStatus(400)->assertJsonPath('success', false);
        $this->deliver($payload, HmacSignature::sign($payload, 'not-the-secret'))->assertStatus(400);
        $this->deliver($payload, HmacSignature::sign($payload, 'test-webhook-secret', time() - 3600))->assertStatus(400); // replayed

        $this->assertSame(PaymentStatus::Pending, $this->payment->order->fresh()->payment_status);
        $this->assertDatabaseCount('payment_webhook_events', 0);
    }

    public function test_a_tampered_body_fails_verification(): void
    {
        $signature = HmacSignature::sign($this->event('evt_1', 'payment.failed'), 'test-webhook-secret');

        $this->deliver($this->event('evt_1', 'payment.succeeded'), $signature)->assertStatus(400);
    }

    public function test_redelivered_events_are_processed_once(): void
    {
        $payload = $this->event('evt_dup');
        $signature = HmacSignature::sign($payload, 'test-webhook-secret');

        $this->deliver($payload, $signature)->assertOk()->assertJsonPath('message', 'Processed');
        $this->deliver($payload, $signature)->assertOk()->assertJsonPath('message', 'Duplicate');

        $this->assertDatabaseCount('payment_webhook_events', 1);
        $this->assertSame(TransactionStatus::Succeeded, $this->payment->fresh()->status);
        $this->assertSame(PaymentStatus::Paid, $this->payment->order->fresh()->payment_status);
    }

    public function test_a_late_failure_does_not_undo_a_successful_payment(): void
    {
        $ok = $this->event('evt_ok');
        $this->deliver($ok, HmacSignature::sign($ok, 'test-webhook-secret'))->assertOk();

        $late = $this->event('evt_late', 'payment.failed');
        $this->deliver($late, HmacSignature::sign($late, 'test-webhook-secret'))->assertOk()->assertJsonPath('message', 'Ignored');

        $this->assertSame(PaymentStatus::Paid, $this->payment->order->fresh()->payment_status);
    }

    public function test_no_card_data_is_stored(): void
    {
        $columns = Schema::getColumnListing('payments');

        foreach (['card_number', 'pan', 'cvc', 'cvv', 'expiry'] as $forbidden) {
            $this->assertNotContains($forbidden, $columns);
        }
    }
}
