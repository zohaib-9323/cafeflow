<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Promotion;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsMenu;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use BuildsMenu, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->buildMenu();
    }

    public function test_percentage_code_discounts_before_tax(): void
    {
        Promotion::factory()->create(['code' => 'WELCOME10', 'value' => '10.00']);

        $order = $this->placeOrder(User::factory()->create(), [$this->largeOatLattes()], ['promo_code' => 'welcome10']);

        // 10.80 − 1.08 = 9.72; tax 8% of 9.72 = 0.78.
        $this->assertSame('1.08', $order->discount_total);
        $this->assertSame('0.78', $order->tax_total);
        $this->assertSame('10.50', $order->total);
        $this->assertDatabaseHas('promotion_usages', ['order_id' => $order->id, 'discount_amount' => '1.08']);
    }

    public function test_percentage_discount_respects_its_cap_and_fixed_never_exceeds_the_goods(): void
    {
        $service = app(PromotionService::class);

        $capped = Promotion::factory()->make(['value' => '50.00', 'max_discount' => '3.00']);
        $this->assertSame(300, $service->discountFor($capped, 2000));

        $fixed = Promotion::factory()->fixed('25.00')->make();
        $this->assertSame(1080, $service->discountFor($fixed, 1080));
    }

    #[DataProvider('invalidCodes')]
    public function test_invalid_codes_are_rejected_with_a_clear_reason(array $attributes, string $message): void
    {
        Promotion::factory()->create(['code' => 'NOPE'] + $attributes);

        try {
            app(PromotionService::class)->apply('NOPE', 1080, User::factory()->create());
            $this->fail('Invalid promotion was accepted.');
        } catch (BusinessRuleException $e) {
            $this->assertStringContainsString($message, $e->getMessage());
            $this->assertSame('promo_code', $e->field);
        }
    }

    public static function invalidCodes(): array
    {
        return [
            'inactive' => [['is_active' => false], 'not valid'],
            'expired' => [['expires_at' => now()->subDay()], 'expired'],
            'not started' => [['starts_at' => now()->addDay()], 'not active yet'],
            'below minimum' => [['min_order_amount' => '15.00'], 'minimum order of $15.00'],
        ];
    }

    public function test_usage_limits_are_enforced_and_cancelling_frees_the_use(): void
    {
        Promotion::factory()->create(['code' => 'ONCE', 'per_customer_limit' => 1]);
        $customer = User::factory()->create();

        $first = $this->placeOrder($customer, [$this->largeOatLattes()], ['promo_code' => 'ONCE']);

        try {
            $this->placeOrder($customer, [$this->largeOatLattes()], ['promo_code' => 'ONCE']);
            $this->fail('Per-customer limit was not enforced.');
        } catch (BusinessRuleException $e) {
            $this->assertStringContainsString('already used', $e->getMessage());
        }

        app(OrderService::class)->cancelByCustomer($first, $customer);
        $this->assertSame(OrderStatus::Cancelled, $first->fresh()->status);

        $again = $this->placeOrder($customer, [$this->largeOatLattes()], ['promo_code' => 'ONCE']);
        $this->assertSame('1.08', $again->discount_total);
    }

    public function test_global_usage_limit(): void
    {
        Promotion::factory()->create(['code' => 'FIRST1', 'usage_limit' => 1]);
        $this->placeOrder(User::factory()->create(), [$this->largeOatLattes()], ['promo_code' => 'FIRST1']);

        $this->expectExceptionMessage('usage limit');
        $this->placeOrder(User::factory()->create(), [$this->largeOatLattes()], ['promo_code' => 'FIRST1']);
    }
}
