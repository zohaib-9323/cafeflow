<?php

namespace Database\Seeders;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role as RoleEnum;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use App\Services\Cart\CartLine;
use App\Services\OrderService;
use App\Services\Payments\PaymentService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * About 90 days of order history so the dashboard, reports and forecast have
 * something real to work with.
 *
 * Past days are bulk-inserted for speed (a few thousand orders), priced with the
 * same rules as checkout. They don't create stock movements: the opening stock
 * represents what's on the shelf today. Today's orders are placed through the
 * real OrderService, so they deduct stock, write history and behave exactly like
 * live orders.
 */
class OrderHistorySeeder extends Seeder
{
    private const DAYS = 90;

    private \Faker\Generator $faker;

    /** @var Collection<int, Product> */
    private Collection $products;

    /** @var array<int, float> product id => relative popularity */
    private array $weights = [];

    private Collection $customers;

    private Collection $team;

    public function run(OrderService $orders): void
    {
        $this->faker = fake('en_US');
        $this->faker->seed(90);
        $this->products = Product::query()->listed()->with(['variations', 'addons', 'category'])->get()->keyBy('id');
        $this->customers = User::query()->withRole(RoleEnum::Customer)->with('customer.addresses')->get();
        $this->team = User::query()->withRole(RoleEnum::Staff, RoleEnum::Manager)->get();
        $this->weights = $this->popularity();

        $promotions = Promotion::query()->whereIn('code', ['WELCOME10', 'MORNING2', 'SUMMER5'])->get()->keyBy('code');
        $firstOrderSeen = [];

        $this->command?->getOutput()->progressStart(self::DAYS);
        for ($ago = self::DAYS; $ago >= 1; $ago--) {
            $this->seedDay(CarbonImmutable::today()->subDays($ago), $ago, $promotions, $firstOrderSeen);
            $this->command?->getOutput()->progressAdvance();
        }
        $this->command?->getOutput()->progressFinish();

        // Final order numbers follow the same CF-1000xx scheme as live orders.
        DB::statement("UPDATE orders SET number = CONCAT('CF-', 100000 + id) WHERE number LIKE 'SEED-%'");
        DB::statement("UPDATE payments p JOIN orders o ON o.id = p.order_id SET p.idempotency_key = CONCAT(o.number, '-', p.provider) WHERE p.idempotency_key LIKE 'SEED-%'");

        Notification::fake(); // don't email 60 fictional customers while seeding
        $this->seedToday($orders);
    }

    // ---------------------------------------------------------------- past days

    private function seedDay(CarbonImmutable $day, int $ago, Collection $promotions, array &$firstOrderSeen): void
    {
        // Busier at weekends, a gentle upward trend over the quarter, plus day-to-day noise.
        $base = [0 => 46, 1 => 26, 2 => 28, 3 => 29, 4 => 31, 5 => 37, 6 => 52][$day->dayOfWeek];
        $count = (int) round($base * (0.82 + (self::DAYS - $ago) / self::DAYS * 0.3) * $this->faker->randomFloat(2, 0.85, 1.15));

        $orders = [];
        $children = [];
        for ($n = 0; $n < $count; $n++) {
            $customer = $this->pickCustomer();
            $placedAt = $this->timeOfDay($day);
            $lines = $this->randomLines($placedAt);
            $subtotal = array_sum(array_column($lines, 'line_cents'));

            $delivery = $this->faker->boolean(28) && $subtotal >= Money::toCents(config('cafeflow.min_delivery_order'));
            $promo = null;
            $discount = 0;
            if (! isset($firstOrderSeen[$customer->id]) && $this->faker->boolean(35)) {
                $promo = $promotions['WELCOME10'];
                $discount = min((int) round($subtotal * 0.10), 800);
            } elseif ($ago > 35 && $ago < 120 && $subtotal >= 3000 && $this->faker->boolean(25)) {
                $promo = $promotions['SUMMER5'];
                $discount = 500;
            } elseif ($day->isWeekday() && $placedAt->hour < 11 && $subtotal >= 1200 && $this->faker->boolean(12)) {
                $promo = $promotions['MORNING2'];
                $discount = 200;
            }
            $firstOrderSeen[$customer->id] = true;

            $after = $subtotal - $discount;
            $tax = (int) round($after * (float) config('cafeflow.tax_rate'));
            $fee = $delivery && $after < Money::toCents(config('cafeflow.free_delivery_over')) ? Money::toCents(config('cafeflow.delivery_fee')) : 0;
            $total = $after + $tax + $fee;

            $cancelled = $this->faker->boolean(4);
            $method = $this->faker->boolean(62) ? PaymentMethod::Card : PaymentMethod::Cash;
            $address = $customer->customer?->addresses->first();
            $doneAt = $placedAt->addMinutes($this->faker->numberBetween(8, 35));
            $tmp = 'SEED-'.Str::random(16);

            $orders[] = [
                'number' => $tmp,
                'user_id' => $customer->id,
                'assigned_to' => $cancelled ? null : $this->team->random()->id,
                'status' => $cancelled ? OrderStatus::Cancelled->value : OrderStatus::Completed->value,
                'fulfillment_type' => $delivery ? 'delivery' : 'pickup',
                'payment_method' => $method->value,
                'payment_status' => $cancelled ? ($method === PaymentMethod::Card ? 'refunded' : 'unpaid') : 'paid',
                'subtotal' => Money::fromCents($subtotal),
                'discount_total' => Money::fromCents($discount),
                'tax_total' => Money::fromCents($tax),
                'delivery_fee' => Money::fromCents($fee),
                'total' => Money::fromCents($total),
                'currency' => config('cafeflow.currency'),
                'promotion_id' => $cancelled ? null : $promo?->id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->customer?->phone ?? '(555) 010-0000',
                'delivery_line1' => $delivery ? ($address?->line1 ?? '12 Canal Street') : null,
                'delivery_city' => $delivery ? 'Riverton' : null,
                'delivery_postal_code' => $delivery ? ($address?->postal_code ?? '10014') : null,
                'notes' => $this->faker->boolean(6) ? $this->faker->randomElement(['Extra hot please', 'No napkins needed', 'Birthday, could you add a candle?', 'Will be 5 min late', 'Oat milk in everything']) : null,
                'placed_at' => $placedAt,
                'confirmed_at' => $cancelled ? null : $placedAt->addMinutes(2),
                'completed_at' => $cancelled ? null : $doneAt,
                'cancelled_at' => $cancelled ? $placedAt->addMinutes(4) : null,
                'cancellation_reason' => $cancelled ? $this->faker->randomElement(['Customer changed their mind', 'Item ran out', 'Duplicate order', 'Courier unavailable']) : null,
                'created_at' => $placedAt,
                'updated_at' => $cancelled ? $placedAt->addMinutes(4) : $doneAt,
            ];
            $children[$tmp] = compact('lines', 'method', 'cancelled', 'placedAt', 'doneAt', 'customer', 'promo', 'discount', 'total');
        }

        DB::table('orders')->insert($orders);
        $ids = DB::table('orders')->whereIn('number', array_keys($children))->pluck('id', 'number');

        $items = $histories = $payments = $usages = [];
        $addonRows = [];
        foreach ($children as $tmp => $c) {
            $orderId = $ids[$tmp];
            foreach ($c['lines'] as $line) {
                $items[] = [
                    'order_id' => $orderId, 'product_id' => $line['product_id'], 'product_variation_id' => $line['variation_id'],
                    'product_name' => $line['product_name'], 'variation_name' => $line['variation_name'],
                    'unit_price' => Money::fromCents($line['unit_cents']), 'quantity' => $line['quantity'],
                    'line_total' => Money::fromCents($line['line_cents']), 'recipe_multiplier' => $line['multiplier'],
                    'created_at' => $c['placedAt'], 'updated_at' => $c['placedAt'],
                ];
                $addonRows[] = $line['addons'];
            }

            $histories[] = ['order_id' => $orderId, 'from_status' => null, 'to_status' => 'pending', 'changed_by' => $c['customer']->id, 'note' => null, 'created_at' => $c['placedAt']];
            if ($c['cancelled']) {
                $histories[] = ['order_id' => $orderId, 'from_status' => 'pending', 'to_status' => 'cancelled', 'changed_by' => $this->team->random()->id, 'note' => null, 'created_at' => $c['placedAt']->addMinutes(4)];
            } else {
                $histories[] = ['order_id' => $orderId, 'from_status' => 'pending', 'to_status' => 'confirmed', 'changed_by' => null, 'note' => null, 'created_at' => $c['placedAt']->addMinutes(2)];
                $histories[] = ['order_id' => $orderId, 'from_status' => 'confirmed', 'to_status' => 'completed', 'changed_by' => null, 'note' => null, 'created_at' => $c['doneAt']];
            }

            $card = $c['method'] === PaymentMethod::Card;
            $payments[] = [
                'order_id' => $orderId,
                'provider' => $card ? 'mock' : 'cash',
                'provider_reference' => $card ? 'mock_cs_'.Str::lower(Str::random(24)) : null,
                'idempotency_key' => $tmp,
                'amount' => Money::fromCents($c['total']),
                'currency' => config('cafeflow.currency'),
                'status' => $c['cancelled'] ? ($card ? 'refunded' : 'cancelled') : 'succeeded',
                'paid_at' => $c['cancelled'] && ! $card ? null : ($card ? $c['placedAt'] : $c['doneAt']),
                'created_at' => $c['placedAt'], 'updated_at' => $c['placedAt'],
            ];

            if ($c['promo'] && ! $c['cancelled']) {
                $usages[] = [
                    'promotion_id' => $c['promo']->id, 'order_id' => $orderId, 'user_id' => $c['customer']->id,
                    'discount_amount' => Money::fromCents($c['discount']), 'created_at' => $c['placedAt'], 'updated_at' => $c['placedAt'],
                ];
            }
        }

        DB::table('order_items')->insert($items);
        $itemIds = DB::table('order_items')->whereIn('order_id', $ids->values())->orderBy('id')->pluck('id')->all();
        $addons = [];
        foreach ($itemIds as $i => $itemId) {
            foreach ($addonRows[$i] ?? [] as $a) {
                $addons[] = ['order_item_id' => $itemId, 'product_addon_id' => $a['id'], 'name' => $a['name'], 'price' => $a['price']];
            }
        }
        if ($addons) {
            DB::table('order_item_addons')->insert($addons);
        }
        DB::table('order_status_histories')->insert($histories);
        DB::table('payments')->insert($payments);
        if ($usages) {
            DB::table('promotion_usages')->insert($usages);
        }
    }

    // ---------------------------------------------------------------- today

    /** A realistic live queue: a few waiting, some in progress, some already done. */
    private function seedToday(OrderService $orders): void
    {
        $priya = User::query()->where('email', 'staff@cafeflow.demo')->first();
        $marcus = User::query()->where('email', 'manager@cafeflow.demo')->first();
        $jordan = User::query()->with('customer.addresses')->where('email', 'customer@cafeflow.demo')->first();
        $payments = app(PaymentService::class);

        $plan = [
            // [customer, fulfillment, final status, assignee, minutes ago]
            [$jordan, 'pickup', OrderStatus::Preparing, $priya, 12],
            [null, 'delivery', OrderStatus::OutForDelivery, $marcus, 40],
            [null, 'pickup', OrderStatus::ReadyForPickup, $priya, 18],
            [null, 'pickup', OrderStatus::Confirmed, null, 6],
            [null, 'pickup', OrderStatus::Pending, null, 3],
            [null, 'delivery', OrderStatus::Pending, null, 2],
            [null, 'pickup', OrderStatus::Completed, $priya, 95],
            [null, 'pickup', OrderStatus::Completed, $marcus, 130],
            [null, 'delivery', OrderStatus::Completed, $priya, 160],
            [null, 'pickup', OrderStatus::Cancelled, null, 75],
        ];

        foreach ($plan as [$customer, $fulfillment, $target, $assignee, $minutesAgo]) {
            $customer ??= $this->customers->where('is_active', true)->random();
            $address = $customer->customer?->addresses->first();
            $lines = collect($this->randomLines(now()->toImmutable(), max: 3))
                ->map(fn ($l) => CartLine::make($l['product_id'], $l['variation_id'], array_column($l['addons'], 'id'), $l['quantity']));
            if ($fulfillment === 'delivery') {
                // Make sure the basket clears the delivery minimum.
                $lines->push(CartLine::make($this->products->firstWhere('slug', 'harvest-bowl')->id, null, [], 1));
            }

            $this->travelTo(now()->subMinutes($minutesAgo), function () use ($orders, $customer, $lines, $fulfillment, $address, $target, $assignee) {
                $order = $orders->place($customer, $lines->all(), [
                    'fulfillment_type' => $fulfillment,
                    'payment_method' => 'cash',
                    'customer_name' => $customer->name,
                    'customer_phone' => $customer->customer?->phone ?? '(555) 010-0000',
                    'delivery_line1' => $address?->line1 ?? '12 Canal Street',
                    'delivery_city' => 'Riverton',
                    'delivery_postal_code' => $address?->postal_code ?? '10014',
                ]);
                if ($assignee) {
                    $orders->assign($order, $assignee, $assignee);
                }
                foreach ($this->path($target, FulfillmentType::from($fulfillment)) as $step) {
                    $order = $orders->transition($order, $step, $assignee, $step === OrderStatus::Cancelled ? 'Customer called to cancel' : null);
                }
            });
        }
    }

    /** @return list<OrderStatus> */
    private function path(OrderStatus $target, FulfillmentType $type): array
    {
        if ($target === OrderStatus::Cancelled) {
            return [OrderStatus::Cancelled];
        }
        $flow = [OrderStatus::Confirmed, OrderStatus::Preparing, $type === FulfillmentType::Delivery ? OrderStatus::OutForDelivery : OrderStatus::ReadyForPickup, OrderStatus::Completed];
        $index = array_search($target, $flow, true);

        return $index === false ? [] : array_slice($flow, 0, $index + 1);
    }

    private function travelTo(\DateTimeInterface $time, callable $callback): void
    {
        $now = now();
        \Illuminate\Support\Carbon::setTestNow($time);
        CarbonImmutable::setTestNow($time);
        try {
            $callback();
        } finally {
            \Illuminate\Support\Carbon::setTestNow();
            CarbonImmutable::setTestNow();
        }
    }

    // ---------------------------------------------------------------- helpers

    /** Most orders come from a core of regulars; the rest are spread thin. */
    private function pickCustomer(): User
    {
        $active = $this->customers->where('is_active', true)->values();
        $regulars = $active->take(25);

        return $this->faker->boolean(60) ? $regulars->random() : $active->random();
    }

    private function timeOfDay(CarbonImmutable $day): CarbonImmutable
    {
        // Morning rush, a lunch bump, a quiet afternoon.
        $hour = $this->faker->randomElement([7, 7, 8, 8, 8, 9, 9, 9, 10, 10, 11, 12, 12, 13, 13, 14, 15, 16, 17, 18]);
        if ($day->isWeekend() && $hour < 8) {
            $hour = 9;
        }

        return $day->setTime($hour, $this->faker->numberBetween(0, 59), $this->faker->numberBetween(0, 59));
    }

    /** @return list<array{product_id:int, variation_id:?int, product_name:string, variation_name:?string, unit_cents:int, quantity:int, line_cents:int, multiplier:string, addons:array}> */
    private function randomLines(CarbonImmutable $at, int $max = 4): array
    {
        $lineCount = $this->faker->randomElement([1, 1, 2, 2, 2, 3, 3, 4]);
        $lineCount = min($lineCount, $max);
        $lines = [];
        $used = [];

        for ($i = 0; $i < $lineCount; $i++) {
            $product = $this->weightedProduct($at, $used);
            $used[] = $product->id;

            $variation = null;
            if ($product->variations->isNotEmpty()) {
                $variation = $this->faker->boolean(60)
                    ? $product->variations->firstWhere('is_default', true)
                    : $product->variations->random();
            }
            $addons = $product->addons->isNotEmpty() && $this->faker->boolean(30) ? $product->addons->random(1) : collect();

            $unit = Money::toCents($product->price) + ($variation ? Money::toCents($variation->price_adjustment) : 0)
                + $addons->sum(fn ($a) => Money::toCents($a->price));
            $qty = $this->faker->randomElement([1, 1, 1, 1, 2, 2, 3]);

            $lines[] = [
                'product_id' => $product->id,
                'variation_id' => $variation?->id,
                'product_name' => $product->name,
                'variation_name' => $variation?->name,
                'unit_cents' => $unit,
                'quantity' => $qty,
                'line_cents' => $unit * $qty,
                'multiplier' => (string) ($variation?->recipe_multiplier ?? '1.000'),
                'addons' => $addons->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'price' => $a->price])->values()->all(),
            ];
        }

        return $lines;
    }

    private function weightedProduct(CarbonImmutable $at, array $exclude): Product
    {
        $morning = $at->hour < 11;
        $weights = [];
        foreach ($this->products as $id => $p) {
            if (in_array($id, $exclude, true)) {
                continue;
            }
            $w = $this->weights[$id];
            $cat = $p->category->name;
            if ($morning && in_array($cat, ['Kitchen', 'Cakes & Desserts'], true)) {
                $w *= 0.15;
            }
            if (! $morning && $cat === 'Breakfast') {
                $w *= 0.5;
            }
            $weights[$id] = $w;
        }

        $roll = $this->faker->randomFloat(4, 0, array_sum($weights));
        foreach ($weights as $id => $w) {
            if (($roll -= $w) <= 0) {
                return $this->products[$id];
            }
        }

        return $this->products[array_key_last($weights)];
    }

    /** Coffee and pastries dominate, like in a real café. */
    private function popularity(): array
    {
        $boost = [
            'flat-white' => 9, 'caffe-latte' => 8, 'cappuccino' => 7, 'croissant' => 7, 'iced-latte' => 5, 'americano' => 5,
            'cold-brew' => 4, 'avocado-toast' => 4, 'chocolate-chunk-cookie' => 4, 'vanilla-latte' => 3, 'harvest-bowl' => 3,
            'espresso' => 3, 'cortado' => 3, 'banana-smoothie' => 2, 'ham-cheese-toastie' => 2.5,
        ];
        $quiet = ['steak-frites' => 0.6, 'cookie-box' => 0.3, 'pour-over' => 0.8, 'raspberry-pistachio-cake' => 0.8];

        return $this->products->mapWithKeys(fn (Product $p) => [$p->id => $boost[$p->slug] ?? $quiet[$p->slug] ?? 1.5])->all();
    }
}
