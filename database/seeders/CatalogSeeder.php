<?php

namespace Database\Seeders;

use App\Enums\InventoryUnit;
use App\Enums\MovementType;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\CmsBanner;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Media;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The Larkspur sample menu: suppliers, ingredients with opening stock, categories,
 * products with photos, sizes, add-ons and recipes, plus two banners.
 */
class CatalogSeeder extends Seeder
{
    private const IMAGE_DIR = 'media/seed';

    /** @var array<string, InventoryItem> */
    private array $items = [];

    private ?User $admin = null;

    public function run(): void
    {
        $this->admin = User::query()->where('email', 'admin@cafeflow.demo')->first();
        $this->seedInventory();
        $this->seedMenu();
        $this->seedBanners();
    }

    // ---------------------------------------------------------------- inventory

    private function seedInventory(): void
    {
        $suppliers = collect([
            'roastery' => ['Northfield Roasting Co.', 'Elena Marsh', 'orders@northfield.test', '(555) 013-4410', 3],
            'dairy' => ['Brookside Dairy', 'Ken Adeyemi', 'dispatch@brookside.test', '(555) 018-2207', 1],
            'market' => ['Riverton Market Produce', 'Lucia Ferraro', 'lucia@rivertonmarket.test', '(555) 016-9032', 1],
            'dry' => ['Harbour Dry Goods', 'Owen Pratt', 'sales@harbourdry.test', '(555) 011-5566', 4],
            'pack' => ['GreenLoop Packaging', null, 'hello@greenloop.test', null, 7],
        ])->map(fn ($s) => Supplier::query()->create([
            'name' => $s[0], 'contact_name' => $s[1], 'email' => $s[2], 'phone' => $s[3], 'lead_time_days' => $s[4], 'is_active' => true,
        ]));

        // key => [name, sku, unit, supplier, opening stock, reorder at, cost per unit, allow negative]
        $rows = [
            'espresso_beans' => ['House espresso beans', 'BEAN-ESP', 'g', 'roastery', 14000, 3000, 0.032],
            'filter_beans' => ['Single-origin filter beans', 'BEAN-FLT', 'g', 'roastery', 4200, 1500, 0.041],
            'milk' => ['Whole milk', 'DAIRY-MILK', 'ml', 'dairy', 48000, 12000, 0.0012],
            'oat_milk' => ['Oat milk', 'DAIRY-OAT', 'ml', 'dairy', 5200, 6000, 0.0024], // deliberately low for the demo
            'cream' => ['Double cream', 'DAIRY-CRM', 'ml', 'dairy', 3500, 1000, 0.0065],
            'butter' => ['Cultured butter', 'DAIRY-BTR', 'g', 'dairy', 6000, 1500, 0.014],
            'yogurt' => ['Greek yogurt', 'DAIRY-YOG', 'g', 'dairy', 7000, 2000, 0.006],
            'cheese' => ['Aged cheddar', 'DAIRY-CHD', 'g', 'dairy', 4000, 1000, 0.018],
            'eggs' => ['Free-range eggs', 'FRESH-EGG', 'pcs', 'market', 180, 48, 0.32],
            'vanilla_syrup' => ['Vanilla syrup', 'SYR-VAN', 'ml', 'dry', 900, 1000, 0.011], // low
            'caramel' => ['Salted caramel sauce', 'SYR-CAR', 'ml', 'dry', 2500, 600, 0.013],
            'chocolate' => ['Dark chocolate 70%', 'DRY-CHOC', 'g', 'dry', 5000, 1200, 0.021],
            'flour' => ['Bread flour', 'DRY-FLR', 'g', 'dry', 25000, 6000, 0.0018],
            'granola' => ['House granola', 'DRY-GRN', 'g', 'dry', 4000, 1000, 0.012],
            'maple' => ['Maple syrup', 'DRY-MPL', 'ml', 'dry', 3000, 800, 0.028],
            'tea_peach' => ['White peach tea leaves', 'TEA-PCH', 'g', 'dry', 800, 200, 0.09],
            'sourdough' => ['Sourdough loaf (slices)', 'BAKE-SRD', 'pcs', 'market', 140, 40, 0.35],
            'avocado' => ['Avocados', 'FRESH-AVO', 'pcs', 'market', 60, 18, 1.10],
            'berries' => ['Mixed berries', 'FRESH-BRY', 'g', 'market', 4500, 1200, 0.016],
            'banana' => ['Bananas', 'FRESH-BAN', 'pcs', 'market', 70, 20, 0.25],
            'oranges' => ['Juicing oranges', 'FRESH-ORG', 'pcs', 'market', 150, 40, 0.38],
            'lemons' => ['Lemons', 'FRESH-LEM', 'pcs', 'market', 60, 15, 0.30],
            'greens' => ['Salad greens', 'FRESH-GRN', 'g', 'market', 5000, 1200, 0.011],
            'grains' => ['Cooked farro & quinoa', 'FRESH-GRA', 'g', 'market', 8000, 2000, 0.006],
            'salmon' => ['Smoked salmon', 'FRESH-SAL', 'g', 'market', 3000, 800, 0.042],
            'ham' => ['Smoked ham', 'FRESH-HAM', 'g', 'market', 3500, 800, 0.022],
            'steak' => ['Flat iron steak', 'FRESH-STK', 'g', 'market', 6000, 1800, 0.028],
            'potatoes' => ['Potatoes (for fries)', 'FRESH-POT', 'g', 'market', 15000, 4000, 0.003],
            'croissant' => ['Croissants (baked today)', 'BAKE-CRO', 'pcs', null, 40, 10, 0.55],
            'cookie' => ['Chocolate chunk cookies', 'BAKE-COO', 'pcs', null, 48, 12, 0.40],
            'brownie' => ['Brownies', 'BAKE-BRW', 'pcs', null, 24, 6, 0.70],
            'cupcake' => ['Vanilla cupcakes', 'BAKE-CUP', 'pcs', null, 18, 6, 0.80],
            'cake_choc' => ['Chocolate cake (slices)', 'BAKE-CKC', 'pcs', null, 16, 4, 1.20],
            'cake_rasp' => ['Raspberry pistachio cake (slices)', 'BAKE-CKR', 'pcs', null, 12, 4, 1.45],
            'ice_cream' => ['Vanilla ice cream', 'DAIRY-ICE', 'g', 'dairy', 5000, 1500, 0.009],
            'cups' => ['Takeaway cups', 'PACK-CUP', 'pcs', 'pack', 900, 200, 0.12],
            'ice' => ['Ice', 'MISC-ICE', 'g', null, 20000, 0, 0.0, true],
        ];

        foreach ($rows as $key => $r) {
            $item = InventoryItem::query()->create([
                'name' => $r[0], 'sku' => $r[1], 'unit' => InventoryUnit::from($r[2]),
                'supplier_id' => $r[3] ? $suppliers[$r[3]]->id : null,
                'low_stock_threshold' => $r[5], 'cost_per_unit' => $r[6], 'allow_negative' => $r[7] ?? false,
            ]);
            // Opening stock goes through a movement row, the same way a real restock would.
            $item->forceFill(['quantity' => $r[4]])->save();
            InventoryMovement::query()->create([
                'inventory_item_id' => $item->id, 'type' => MovementType::Restock, 'quantity_change' => $r[4],
                'quantity_after' => $r[4], 'user_id' => $this->admin?->id, 'reason' => 'Opening stock',
                'created_at' => now()->subDays(91),
            ]);
            $this->items[$key] = $item;
        }
    }

    // ---------------------------------------------------------------- menu

    private function seedMenu(): void
    {
        $milkSizes = [['Small (8 oz)', -0.50, 0.75], ['Regular (12 oz)', 0, 1, true], ['Large (16 oz)', 0.80, 1.35]];
        $coldSizes = [['Regular (12 oz)', 0, 1, true], ['Large (16 oz)', 0.90, 1.4]];
        $shot = fn () => ['Extra shot', 1.00, 'espresso_beans', 9];
        $oat = fn () => ['Oat milk', 0.70, 'oat_milk', 220];
        $vanilla = fn () => ['Vanilla syrup', 0.60, 'vanilla_syrup', 15];
        $caramel = fn () => ['Caramel drizzle', 0.60, 'caramel', 15];

        $menu = [
            ['Espresso Bar', 'Our house blend: chocolate, stone fruit and a long caramel finish. Pulled as a 36 g double.', [
                ['Espresso', 'espresso', 3.20, 'A double shot of the house blend. Short, sweet and syrupy.', 2, 5, [], [$shot()], ['espresso_beans' => 18, 'cups' => 1]],
                ['Americano', 'americano', 3.60, 'Double espresso over hot water, for a longer, lighter cup.', 3, 10, $coldSizes, [$shot()], ['espresso_beans' => 18, 'cups' => 1]],
                ['Cortado', 'cortado', 3.90, 'Equal parts espresso and silky steamed milk. Small and strong.', 3, 60, [], [$shot(), $oat()], ['espresso_beans' => 18, 'milk' => 60, 'cups' => 1]],
                ['Flat White', 'flat-white', 4.40, 'Double ristretto with a thin layer of velvety microfoam. The one regulars order.', 3, 110, $milkSizes, [$shot(), $oat(), $vanilla()], ['espresso_beans' => 18, 'milk' => 160, 'cups' => 1], true],
                ['Cappuccino', 'cappuccino', 4.40, 'Espresso with steamed milk and a deep cap of foam, dusted with cocoa if you like.', 3, 120, $milkSizes, [$shot(), $oat(), $vanilla()], ['espresso_beans' => 18, 'milk' => 180, 'cups' => 1], true],
                ['Caffè Latte', 'caffe-latte', 4.60, 'Espresso and plenty of steamed milk, smooth and mellow.', 3, 170, $milkSizes, [$shot(), $oat(), $vanilla(), $caramel()], ['espresso_beans' => 18, 'milk' => 240, 'cups' => 1], true],
                ['Vanilla Latte', 'vanilla-latte', 5.10, 'Our latte with house vanilla syrup made from real pods.', 3, 220, $milkSizes, [$shot(), $oat()], ['espresso_beans' => 18, 'milk' => 240, 'vanilla_syrup' => 20, 'cups' => 1]],
                ['Iced Latte', 'iced-latte', 4.90, 'Double espresso poured over cold milk and ice.', 2, 140, $coldSizes, [$shot(), $oat(), $vanilla(), $caramel()], ['espresso_beans' => 18, 'milk' => 200, 'ice' => 120, 'cups' => 1]],
            ]],
            ['Brew Bar & Cold Drinks', 'Filter coffee by the cup, slow cold brew and fresh-pressed juices.', [
                ['Pour-Over', 'pour-over', 5.20, 'Hand-brewed single origin. Ask what\'s on this week; right now it\'s a washed Ethiopian with notes of bergamot and peach.', 6, 5, [], [], ['filter_beans' => 22, 'cups' => 1]],
                ['Cold Brew', 'cold-brew', 4.80, 'Steeped for 18 hours, served over ice. Smooth, low in acidity, dangerously drinkable.', 1, 10, $coldSizes, [$oat(), $vanilla()], ['filter_beans' => 20, 'ice' => 150, 'cups' => 1], true],
                ['White Peach Iced Tea', 'peach-iced-tea', 4.20, 'Cold-steeped white tea with ripe peach. Lightly sweetened.', 1, 60, $coldSizes, [], ['tea_peach' => 6, 'ice' => 150, 'cups' => 1]],
                ['Berry Lemonade', 'berry-lemonade', 4.50, 'Fresh lemons, crushed berries and a little cane sugar.', 2, 120, $coldSizes, [], ['lemons' => 1, 'berries' => 40, 'ice' => 150, 'cups' => 1]],
                ['Fresh Orange Juice', 'orange-juice', 5.00, 'Pressed to order. Nothing added.', 3, 110, [], [], ['oranges' => 3, 'cups' => 1]],
                ['Banana Oat Smoothie', 'banana-smoothie', 6.20, 'Banana, oats, Greek yogurt and a touch of maple, blended thick.', 4, 310, [], [], ['banana' => 1, 'yogurt' => 80, 'maple' => 10, 'oat_milk' => 150, 'cups' => 1]],
            ]],
            ['Breakfast', 'Served all day, because breakfast shouldn\'t have a curfew.', [
                ['Avocado Toast', 'avocado-toast', 11.50, 'Smashed avocado on toasted sourdough with lemon, chilli flakes and toasted seeds.', 8, 420, [], [['Poached egg', 2.00, 'eggs', 1], ['Smoked salmon', 4.50, 'salmon', 60]], ['sourdough' => 2, 'avocado' => 1, 'lemons' => 0.25], true],
                ['Brioche French Toast', 'french-toast', 12.00, 'Thick-cut brioche, berries, mascarpone cream and maple syrup.', 10, 640, [], [['Extra berries', 1.50, 'berries', 50]], ['eggs' => 2, 'milk' => 60, 'berries' => 60, 'maple' => 25, 'cream' => 40, 'butter' => 15]],
                ['Buttermilk Pancakes', 'pancakes', 11.00, 'A stack of three with cultured butter and maple.', 10, 580, [], [['Bacon-style smoked ham', 3.00, 'ham', 50], ['Extra berries', 1.50, 'berries', 50]], ['flour' => 120, 'eggs' => 1, 'milk' => 150, 'butter' => 20, 'maple' => 30]],
                ['Yogurt & Granola', 'yogurt-parfait', 7.50, 'Greek yogurt layered with our granola, berries and honey.', 3, 360, [], [], ['yogurt' => 180, 'granola' => 50, 'berries' => 40]],
                ['Ham & Cheese Toastie', 'ham-cheese-toastie', 9.50, 'Smoked ham and aged cheddar, pressed in buttered sourdough until crisp.', 7, 520, [], [['Fried egg', 2.00, 'eggs', 1]], ['sourdough' => 2, 'ham' => 70, 'cheese' => 50, 'butter' => 10]],
            ]],
            ['Kitchen', 'Lunch plates from 11 a.m. The bowls follow the market.', [
                ['Harvest Bowl', 'harvest-bowl', 14.50, 'Farro and quinoa, roasted seasonal vegetables, greens, herbs and tahini dressing.', 10, 540, [], [['Soft-boiled egg', 2.00, 'eggs', 1], ['Avocado', 2.50, 'avocado', 1]], ['grains' => 180, 'greens' => 60], true],
                ['Smoked Salmon Bowl', 'salmon-bowl', 16.50, 'Smoked salmon, grains, pickled cucumber, avocado and a soft egg.', 10, 610, [], [], ['salmon' => 90, 'grains' => 160, 'avocado' => 0.5, 'eggs' => 1, 'greens' => 30]],
                ['Steak Frites', 'steak-frites', 22.00, 'Flat iron steak cooked medium-rare, hand-cut fries and herb butter.', 18, 890, [], [['Side salad', 3.50, 'greens', 60]], ['steak' => 200, 'potatoes' => 250, 'butter' => 20]],
                ['Sourdough & Butter', 'sourdough', 5.00, 'Two thick slices of our naturally leavened loaf with cultured butter.', 3, 330, [], [], ['sourdough' => 2, 'butter' => 20]],
            ]],
            ['Bakery', 'Baked here from 5 a.m. When they\'re gone, they\'re gone.', [
                ['Butter Croissant', 'croissant', 4.00, 'Laminated over three days with cultured butter. Shatters properly.', 1, 310, [], [], ['croissant' => 1], true],
                ['Chocolate Chunk Cookie', 'chocolate-chunk-cookie', 3.50, 'Brown butter, dark chocolate and a pinch of sea salt. Chewy in the middle.', 1, 280, [], [], ['cookie' => 1]],
                ['Fudge Brownie', 'brownie', 4.00, 'Dense, dark and just underbaked, the way it should be.', 1, 350, [], [], ['brownie' => 1]],
                ['Vanilla Cupcake', 'vanilla-cupcake', 4.25, 'Light vanilla sponge with whipped vanilla bean buttercream.', 1, 330, [], [], ['cupcake' => 1]],
                ['Cookie Box (6)', 'cookie-box', 18.00, 'Six chocolate chunk cookies in a box. Good for sharing, or not.', 2, null, [], [], ['cookie' => 6]],
            ]],
            ['Cakes & Desserts', 'For the afternoon slump, or after the steak.', [
                ['Chocolate Fudge Cake', 'chocolate-cake', 6.50, 'Three layers of chocolate sponge with ganache.', 2, 520, [], [['Scoop of ice cream', 2.00, 'ice_cream', 70]], ['cake_choc' => 1], true],
                ['Raspberry Pistachio Cake', 'raspberry-pistachio-cake', 7.00, 'Pistachio sponge, raspberry jam and a light mascarpone cream.', 2, 460, [], [], ['cake_rasp' => 1]],
                ['Salted Caramel Sundae', 'caramel-sundae', 7.50, 'Vanilla ice cream, salted caramel, brownie pieces and whipped cream.', 4, 610, [], [], ['ice_cream' => 150, 'caramel' => 30, 'brownie' => 0.5, 'cream' => 30]],
            ]],
        ];

        // Category tile photos, reusing bundled product shots that aren't among the home-page favourites.
        $categoryImages = [
            'Espresso Bar' => 'espresso',
            'Brew Bar & Cold Drinks' => 'pour-over',
            'Breakfast' => 'pancakes',
            'Kitchen' => 'steak-frites',
            'Bakery' => 'sourdough',
            'Cakes & Desserts' => 'raspberry-pistachio-cake',
        ];

        foreach ($menu as $sort => [$categoryName, $description, $products]) {
            $category = Category::query()->create([
                'name' => $categoryName, 'slug' => Str::slug($categoryName), 'description' => $description,
                'image_path' => isset($categoryImages[$categoryName]) ? $this->publishImage($categoryImages[$categoryName], $categoryName) : null,
                'sort_order' => $sort, 'is_active' => true,
            ]);

            foreach ($products as $i => $p) {
                [$name, $image, $price, $about, $prep, $calories, $sizes, $addons, $recipe] = $p;
                $product = Product::query()->create([
                    'category_id' => $category->id,
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'sku' => strtoupper(Str::substr(Str::slug($categoryName, ''), 0, 3)).'-'.str_pad((string) ($sort * 10 + $i + 1), 3, '0', STR_PAD_LEFT),
                    'short_description' => rtrim(Str::before($about, '. '), '.').'.',
                    'description' => $about,
                    'price' => $price,
                    'status' => ProductStatus::Active,
                    'is_available' => true,
                    'is_featured' => $p[9] ?? false,
                    'prep_minutes' => $prep,
                    'calories' => $calories,
                    'sort_order' => $i,
                ]);

                $product->images()->create(['path' => $this->publishImage($image, $name), 'alt' => $name, 'sort_order' => 0]);

                foreach ($sizes as $j => $s) {
                    $product->variations()->create([
                        'name' => $s[0], 'price_adjustment' => $s[1], 'recipe_multiplier' => $s[2],
                        'is_default' => $s[3] ?? false, 'sort_order' => $j,
                    ]);
                }
                foreach ($addons as $j => [$addonName, $addonPrice, $itemKey, $qty]) {
                    $product->addons()->create([
                        'name' => $addonName, 'price' => $addonPrice, 'inventory_item_id' => $this->items[$itemKey]->id,
                        'inventory_quantity' => $qty, 'is_active' => true, 'sort_order' => $j,
                    ]);
                }
                foreach ($recipe as $itemKey => $qty) {
                    $product->recipe()->create(['inventory_item_id' => $this->items[$itemKey]->id, 'quantity' => $qty]);
                }
            }
        }

        // One draft so the admin filter has something to show.
        Product::query()->create([
            'category_id' => Category::query()->where('slug', 'brew-bar-cold-drinks')->value('id'),
            'name' => 'Spiced Pumpkin Cold Foam', 'slug' => 'spiced-pumpkin-cold-foam', 'sku' => 'BRE-099',
            'short_description' => 'Autumn special in development.', 'description' => 'Cold brew under a pumpkin-spice cold foam. Recipe still being tested.',
            'price' => 5.60, 'status' => ProductStatus::Draft, 'is_available' => false, 'prep_minutes' => 3, 'sort_order' => 99,
        ]);
    }

    private function seedBanners(): void
    {
        CmsBanner::query()->create([
            'title' => 'New-season single origin on the brew bar',
            'subtitle' => 'A washed Ethiopian with bergamot and white peach. Try it as a pour-over this month.',
            'image_path' => $this->publishImage('banner-espresso-beans', 'Coffee beans'),
            'cta_label' => 'See the brew bar', 'cta_url' => '/menu?category=brew-bar-cold-drinks',
            'placement' => 'home', 'sort_order' => 0, 'is_active' => true,
        ]);
        CmsBanner::query()->create([
            'title' => 'Whole cakes to order',
            'subtitle' => 'Give us 48 hours\' notice and we\'ll bake any of our cakes whole. Ask at the counter or send us a message.',
            'image_path' => $this->publishImage('banner-cakes', 'Cakes on the counter'),
            'cta_label' => 'Get in touch', 'cta_url' => '/contact',
            'placement' => 'menu', 'sort_order' => 0, 'is_active' => true,
        ]);
    }

    /** Copy a bundled seed photo to the public disk and register it in the media library. */
    private function publishImage(string $name, string $alt): string
    {
        $source = database_path("seeders/images/{$name}.jpg");
        $path = self::IMAGE_DIR."/{$name}.jpg";
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            $disk->put($path, file_get_contents($source));
        }
        [$width, $height] = getimagesize($source) ?: [null, null];

        Media::query()->firstOrCreate(['path' => $path], [
            'disk' => 'public', 'original_name' => "{$name}.jpg", 'mime_type' => 'image/jpeg',
            'size' => filesize($source), 'width' => $width, 'height' => $height, 'alt' => $alt,
            'uploaded_by' => $this->admin?->id,
        ]);

        return $path;
    }
}
