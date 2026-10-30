<?php

namespace Database\Seeders;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Seeder;

/**
 * Reviews only from customers who actually ordered the product, written per kind
 * of item so they read like people rather than templates. A few wait for moderation.
 */
class ReviewSeeder extends Seeder
{
    /** category name => rating => list of [title, body] */
    private const LINES = [
        'Espresso Bar' => [
            5 => [
                ['Best in the neighbourhood', 'Sweet, balanced and never bitter. They clearly dial in the grinder every morning.'],
                ['My standing order', 'I get one on the way to work most days and it has been spot on every single time.'],
                ['Proper microfoam', 'Milk is silky, not bubbly, and the latte art is a nice touch even in a takeaway cup.'],
                ['Worth the queue', 'There is a rush at 8:15 but the drinks come out quickly and they are hot.'],
                [null, 'Chocolatey and smooth with oat milk. Exactly how I like it.'],
            ],
            4 => [
                ['Great, a touch strong', 'Really good coffee. I go for the small now because the regular is a lot of caffeine for me.'],
                ['Lovely', 'Nice drink, though the large could be a little hotter for delivery.'],
                [null, 'Good every time. Would love a decaf option for the afternoon.'],
            ],
            3 => [['Hit and miss', 'Excellent on weekdays, but on a busy Sunday it came out lukewarm.']],
        ],
        'Brew Bar & Cold Drinks' => [
            5 => [
                ['Like nothing else nearby', 'The pour-over had real fruit notes. The barista talked me through the beans without being preachy.'],
                ['Summer staple', 'Refreshing, not too sweet, and they don\'t drown it in ice.'],
                [null, 'Smooth, chocolatey cold brew. I bought a second one to take home.'],
                ['Tastes fresh', 'You can tell it\'s made to order. My kids ask for it by name now.'],
            ],
            4 => [
                ['Very good', 'Delicious, though I\'d pay for a bigger size.'],
                [null, 'Nice and bright. Slightly sweeter than I expected, but I\'d order it again.'],
            ],
            3 => [['Fine', 'Good but a bit pricey for the size.']],
        ],
        'Breakfast' => [
            5 => [
                ['Worth getting up for', 'Generous, well seasoned and the sourdough is excellent. Add the poached egg.'],
                ['Weekend ritual', 'We come every Saturday and this is always on the table.'],
                [null, 'Perfectly cooked and arrived still warm with delivery. Impressive.'],
                ['Not your average', 'Simple dish done really well. You can taste the good butter.'],
            ],
            4 => [
                ['Tasty', 'Really good, just a little heavy on the lemon for me.'],
                [null, 'Lovely, portion could be slightly bigger for the price.'],
            ],
            3 => [['Okay', 'Nice flavours but my toast had gone a bit soft by the time it arrived.']],
        ],
        'Kitchen' => [
            5 => [
                ['Changes with the season', 'Different vegetables every time I order and it has never been boring.'],
                ['Proper lunch', 'Filling without being heavy. The tahini dressing is the star.'],
                [null, 'Cooked exactly as asked and the fries were crisp even after delivery.'],
            ],
            4 => [
                ['Solid', 'Very good. A little more dressing on the side would be perfect.'],
                [null, 'Great quality, slightly slow at lunchtime but worth the wait.'],
            ],
            3 => [['Good not great', 'Tasty but I expected a bit more for the price.']],
        ],
        'Bakery' => [
            5 => [
                ['Flaky and buttery', 'Shatters properly when you bite it. Best I\'ve had outside of France.'],
                ['Get there early', 'They sell out by ten on weekends for good reason.'],
                [null, 'Gooey in the middle and the sea salt on top makes it.'],
                ['Afternoon treat', 'Perfect with a flat white. I may be ordering these too often.'],
            ],
            4 => [
                [null, 'Very good, a little sweet for me but my partner loved it.'],
                ['Nice', 'Fresh and tasty. Would be great warmed up.'],
            ],
            3 => [['A bit dry', 'Fine, but it tasted like it was from the day before.']],
        ],
        'Cakes & Desserts' => [
            5 => [
                ['Birthday saved', 'Ordered for a last-minute birthday and it was gorgeous. Rich without being sickly.'],
                [null, 'Moist, not too sweet, and the slice is huge.'],
                ['Worth the calories', 'The pistachio and raspberry together are lovely. Light texture.'],
            ],
            4 => [
                ['Rich', 'Very good but I needed a coffee to get through it.'],
                [null, 'Delicious. Would have liked it a bit colder from the fridge.'],
            ],
            3 => [['Okay', 'Nice enough but I preferred the chocolate cake.']],
        ],
    ];

    public function run(): void
    {
        $faker = fake();
        $faker->seed(7);
        $categories = Product::query()->with('category:id,name')->get(['id', 'category_id'])
            ->mapWithKeys(fn ($p) => [$p->id => $p->category->name]);

        $pairs = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'completed')
            ->selectRaw('order_items.product_id, orders.user_id, MAX(orders.completed_at) as last_at')
            ->groupBy('order_items.product_id', 'orders.user_id')
            ->orderBy('order_items.product_id')->orderBy('orders.user_id')
            ->get()
            ->shuffle(42)
            ->take(80);

        $usedPerProduct = [];
        foreach ($pairs->values() as $i => $pair) {
            $pool = self::LINES[$categories[$pair->product_id]] ?? self::LINES['Bakery'];
            $rating = $faker->randomElement([5, 5, 5, 5, 4, 4, 4, 3]);

            // Never repeat the same words on the same product.
            $options = array_values(array_filter($pool[$rating], fn ($line) => ! in_array($line[1], $usedPerProduct[$pair->product_id] ?? [], true)));
            if (! $options) {
                continue;
            }
            [$title, $body] = $faker->randomElement($options);
            $usedPerProduct[$pair->product_id][] = $body;

            $review = new Review(['rating' => $rating, 'title' => $title, 'body' => $body]);
            $review->forceFill([
                'product_id' => $pair->product_id,
                'user_id' => $pair->user_id,
                'is_approved' => $i >= 4, // the first four wait in the moderation queue
                'created_at' => $pair->last_at,
                'updated_at' => $pair->last_at,
            ])->save();
        }
    }
}
