<?php

namespace Tests\Feature\Admin;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->manager()->create());
    }

    public function test_manager_creates_a_product_with_sizes_addons_and_recipe(): void
    {
        $category = Category::factory()->create();
        $beans = InventoryItem::factory()->create();

        $this->post(route('admin.products.store'), [
            'name' => 'Cortado',
            'category_id' => $category->id,
            'price' => '3.80',
            'status' => 'active',
            'prep_minutes' => 4,
            'is_available' => '1',
            'variations' => [['name' => 'Single', 'price_adjustment' => '0', 'recipe_multiplier' => '1'], ['name' => 'Double', 'price_adjustment' => '0.70', 'recipe_multiplier' => '2']],
            'default_variation' => 0,
            'addons' => [['name' => 'Extra hot', 'price' => '0']],
            'recipe' => [['inventory_item_id' => $beans->id, 'quantity' => '18']],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $product = Product::query()->where('slug', 'cortado')->sole();
        $this->assertSame('3.80', $product->price);
        $this->assertSame(['Single', 'Double'], $product->variations()->orderBy('sort_order')->pluck('name')->all());
        $this->assertTrue($product->variations()->where('name', 'Single')->value('is_default'));
        $this->assertSame('18.000', $product->recipe()->value('quantity'));
        $this->assertSame(1, $product->addons()->count());
    }

    public function test_product_validation(): void
    {
        $this->post(route('admin.products.store'), ['name' => '', 'price' => '-1', 'status' => 'bogus'])
            ->assertSessionHasErrors(['name', 'price', 'status', 'category_id']);
    }

    public function test_updating_and_archiving_a_product(): void
    {
        $product = Product::factory()->create(['price' => '4.00']);

        $this->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'slug' => $product->slug,
            'category_id' => $product->category_id,
            'price' => '4.25',
            'status' => 'archived',
            'prep_minutes' => 5,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('4.25', $product->fresh()->price);
        $this->assertSame(ProductStatus::Archived, $product->fresh()->status);

        $this->delete(route('admin.products.destroy', $product))->assertRedirect();
        $this->assertSoftDeleted($product);
    }

    public function test_category_crud_and_a_category_with_products_cannot_be_deleted(): void
    {
        $this->post(route('admin.categories.store'), ['name' => 'Cold Brew', 'is_active' => '1'])->assertRedirect();
        $category = Category::query()->where('slug', 'cold-brew')->sole();

        $this->put(route('admin.categories.update', $category), ['name' => 'Cold Brew & Iced', 'slug' => 'cold-brew'])->assertRedirect();
        $this->assertSame('Cold Brew & Iced', $category->fresh()->name);

        Product::factory()->create(['category_id' => $category->id]);
        $this->delete(route('admin.categories.destroy', $category))->assertSessionHasErrors('category');
        $this->assertNotSoftDeleted($category);
    }

    public function test_staff_cannot_change_the_catalogue(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->staff()->create());

        $this->put(route('admin.products.update', $product), ['name' => 'Hacked'])->assertForbidden();
        $this->delete(route('admin.products.destroy', $product))->assertForbidden();
        $this->post(route('admin.categories.store'), ['name' => 'Nope'])->assertForbidden();
    }
}
