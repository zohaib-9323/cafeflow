@php
    use App\Enums\ProductStatus;
    $editing = $product->exists;
    $variations = old('variations', $product->variations?->map->only(['id', 'name', 'price_adjustment', 'recipe_multiplier'])->all() ?? []);
    $defaultVariation = old('default_variation', $product->variations?->search(fn ($v) => $v->is_default));
    $addons = old('addons', $product->addons?->map->only(['id', 'name', 'price', 'inventory_item_id', 'inventory_quantity', 'is_active'])->all() ?? []);
    $recipe = old('recipe', $product->recipe?->map->only(['inventory_item_id', 'quantity'])->all() ?? []);
    $ingredientOptions = $ingredients->mapWithKeys(fn ($i) => [$i->id => "{$i->name} ({$i->unit->value})"]);
@endphp
<x-layouts.admin :title="$editing ? 'Edit '.$product->name : 'New product'">
    <x-page-header :title="$editing ? $product->name : 'New product'" :breadcrumbs="['Products' => route('admin.products.index'), $editing ? 'Edit' : 'New']">
        @if ($editing && $product->status === ProductStatus::Active)
            <a href="{{ route('menu.show', $product) }}" class="btn btn-light" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>View on site</a>
        @endif
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif
        @if ($errors->any())<x-alert type="danger" :dismissible="false">Please fix the highlighted fields. {{ $errors->first() }}</x-alert>@endif

        <div class="row g-3">
            <div class="col-xl-8">
                <x-card title="Details" class="mb-3">
                    <div class="row">
                        <div class="col-md-8"><x-input name="name" label="Name" :value="$product->name" required maxlength="150" id="productName" /></div>
                        <div class="col-md-4"><x-input name="sku" label="SKU" :value="$product->sku" maxlength="40" /></div>
                    </div>
                    <x-input name="slug" label="URL slug" :value="$product->slug" data-slug-source="#productName" prefix="/menu/" help="Generated from the name; change it only if you need to." />
                    <x-input name="short_description" label="Short description" :value="$product->short_description" maxlength="255" help="Shown on menu cards." />
                    <x-textarea name="description" label="Full description" :value="$product->description" rows="5" maxlength="5000" />
                </x-card>

                <x-card title="Sizes" subtitle="Optional. The base price applies to the default size; others add or subtract." class="mb-3">
                    <div data-repeater data-next="{{ count($variations) }}">
                        <div class="row g-2 small text-secondary d-none d-md-flex mb-1"><div class="col-md-1">Default</div><div class="col-md-4">Name</div><div class="col-md-3">Price change</div><div class="col-md-3">Ingredient multiplier</div></div>
                        <div data-repeater-list>
                            @foreach ($variations as $i => $v)
                                @include('admin.products._variation-row', ['i' => $i, 'v' => $v, 'checked' => (string) $defaultVariation === (string) $i])
                            @endforeach
                        </div>
                        <template>@include('admin.products._variation-row', ['i' => '__INDEX__', 'v' => [], 'checked' => false])</template>
                        <button type="button" class="btn btn-sm btn-light" data-repeater-add><i class="bi bi-plus me-1" aria-hidden="true"></i>Add size</button>
                    </div>
                </x-card>

                <x-card title="Add-ons" subtitle="Extras customers can choose. Link one to an ingredient to track its stock." class="mb-3">
                    <div data-repeater data-next="{{ count($addons) }}">
                        <div data-repeater-list>
                            @foreach ($addons as $i => $a)
                                @include('admin.products._addon-row', ['i' => $i, 'a' => $a])
                            @endforeach
                        </div>
                        <template>@include('admin.products._addon-row', ['i' => '__INDEX__', 'a' => ['is_active' => true]])</template>
                        <button type="button" class="btn btn-sm btn-light" data-repeater-add><i class="bi bi-plus me-1" aria-hidden="true"></i>Add add-on</button>
                    </div>
                </x-card>

                <x-card title="Recipe" subtitle="Ingredients used per item (for the default size). Taken from stock when an order is confirmed." class="mb-3">
                    @if ($ingredients->isEmpty())
                        <p class="small text-secondary mb-0">No inventory items yet. <a href="{{ route('admin.inventory.create') }}">Add ingredients</a> first.</p>
                    @else
                        <div data-repeater data-next="{{ count($recipe) }}">
                            <div data-repeater-list>
                                @foreach ($recipe as $i => $r)
                                    @include('admin.products._recipe-row', ['i' => $i, 'r' => $r])
                                @endforeach
                            </div>
                            <template>@include('admin.products._recipe-row', ['i' => '__INDEX__', 'r' => []])</template>
                            <button type="button" class="btn btn-sm btn-light" data-repeater-add><i class="bi bi-plus me-1" aria-hidden="true"></i>Add ingredient</button>
                        </div>
                    @endif
                </x-card>
            </div>

            <div class="col-xl-4">
                <x-card title="Publishing" class="mb-3">
                    <x-select name="status" label="Status" :options="collect(ProductStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="$product->status" required />
                    <x-checkbox name="is_available" label="Available to order today" :checked="$product->is_available" help="Turn off when sold out." />
                    <x-checkbox name="is_featured" label="Feature on the home page" :checked="$product->is_featured" />
                    <x-select name="category_id" label="Category" :options="$categories->pluck('name', 'id')" :value="$product->category_id" placeholder="Choose…" required />
                </x-card>
                <x-card title="Pricing & prep" class="mb-3">
                    <x-input name="price" label="Base price" type="number" step="0.01" min="0" :value="$product->price" prefix="$" required />
                    <div class="row">
                        <div class="col-6"><x-input name="prep_minutes" label="Prep (min)" type="number" min="0" max="240" :value="$product->prep_minutes" required /></div>
                        <div class="col-6"><x-input name="calories" label="Calories" type="number" min="0" :value="$product->calories" /></div>
                    </div>
                    <x-input name="sort_order" label="Sort order" type="number" min="0" :value="$product->sort_order" wrapperClass="mb-0" />
                </x-card>
                <x-card title="Images" class="mb-3">
                    @if ($editing && $product->images->isNotEmpty())
                        <ul class="list-unstyled d-grid gap-2 mb-3">
                            @foreach ($product->images as $image)
                                <li class="d-flex align-items-center gap-2">
                                    <img src="{{ $image->url() }}" alt="{{ $image->alt }}" width="56" height="56" class="rounded object-fit-cover">
                                    <span class="small flex-grow-1">@if ($loop->first)<x-badge tone="primary">Primary</x-badge>@endif {{ $image->alt }}</span>
                                    @unless ($loop->first)
                                        <button class="btn btn-sm btn-light" form="primary{{ $image->id }}" title="Make primary" aria-label="Make this the primary image"><i class="bi bi-star" aria-hidden="true"></i></button>
                                    @endunless
                                    <button class="btn btn-sm btn-outline-danger" form="removeImage{{ $image->id }}" aria-label="Remove image"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <x-input name="images[]" type="file" label="Upload images" accept="image/jpeg,image/png,image/webp" multiple help="JPG, PNG or WebP, up to 4 MB each. Images are re-encoded and resized." wrapperClass="mb-0" />
                </x-card>
                <div class="d-grid gap-2 sticky-actions">
                    <button class="btn btn-primary btn-lg">{{ $editing ? 'Save changes' : 'Create product' }}</button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </div>
    </form>

    {{-- Image actions are separate forms (forms can't nest); buttons above use form="…". --}}
    @if ($editing)
        @foreach ($product->images as $image)
            <form id="primary{{ $image->id }}" method="POST" action="{{ route('admin.products.images.store', $product) }}" class="d-none">@csrf<input type="hidden" name="image_id" value="{{ $image->id }}"><input type="hidden" name="make_primary" value="1"></form>
            <form id="removeImage{{ $image->id }}" method="POST" action="{{ route('admin.products.images.destroy', [$product, $image]) }}" class="d-none" data-confirm="Remove this image from the product?" data-confirm-label="Remove">@csrf @method('DELETE')</form>
        @endforeach
    @endif
</x-layouts.admin>
