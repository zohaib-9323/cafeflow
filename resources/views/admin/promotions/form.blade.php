@php use App\Enums\PromotionType; $editing = $promotion->exists; @endphp
<x-layouts.admin :title="$editing ? 'Edit '.$promotion->code : 'New promo code'">
    <x-page-header :title="$editing ? $promotion->code : 'New promo code'" :breadcrumbs="['Promotions' => route('admin.promotions.index'), $editing ? 'Edit' : 'New']"
        :subtitle="$editing ? 'Used '.$promotion->usages_count.' times' : null" />
    <div class="row"><div class="col-lg-8"><x-card>
        <form method="POST" action="{{ $editing ? route('admin.promotions.update', $promotion) : route('admin.promotions.store') }}" novalidate>
            @csrf @if ($editing) @method('PUT') @endif
            <div class="row">
                <div class="col-md-5"><x-input name="code" label="Code" :value="$promotion->code" required maxlength="40" class="text-uppercase" help="Letters, numbers, dashes." /></div>
                <div class="col-md-7"><x-input name="name" label="Internal name" :value="$promotion->name" required maxlength="120" /></div>
            </div>
            <x-textarea name="description" label="Description (shown to customers)" :value="$promotion->description" rows="2" maxlength="500" />
            <div class="row">
                <div class="col-md-4"><x-select name="type" label="Type" :options="collect(PromotionType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])" :value="$promotion->type" required /></div>
                <div class="col-md-4"><x-input name="value" label="Value" type="number" step="0.01" min="0.01" :value="$promotion->value" required help="Percent or dollar amount." /></div>
                <div class="col-md-4"><x-input name="max_discount" label="Max discount" type="number" step="0.01" min="0" :value="$promotion->max_discount" prefix="$" help="Caps percentage codes." /></div>
                <div class="col-md-4"><x-input name="min_order_amount" label="Minimum order" type="number" step="0.01" min="0" :value="$promotion->min_order_amount" prefix="$" /></div>
                <div class="col-md-4"><x-input name="usage_limit" label="Total uses" type="number" min="1" :value="$promotion->usage_limit" help="Blank = unlimited." /></div>
                <div class="col-md-4"><x-input name="per_customer_limit" label="Uses per customer" type="number" min="1" :value="$promotion->per_customer_limit" /></div>
                <div class="col-md-6"><x-input name="starts_at" label="Starts" type="datetime-local" :value="$promotion->starts_at?->format('Y-m-d\TH:i')" /></div>
                <div class="col-md-6"><x-input name="expires_at" label="Expires" type="datetime-local" :value="$promotion->expires_at?->format('Y-m-d\TH:i')" /></div>
            </div>
            <x-checkbox name="is_active" label="Active" :checked="$promotion->is_active" />
            <x-checkbox name="show_on_site" label="Advertise on the home page" :checked="$promotion->show_on_site" />
            <div class="d-flex gap-2"><button class="btn btn-primary">{{ $editing ? 'Save' : 'Create code' }}</button><a href="{{ route('admin.promotions.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </x-card></div></div>
</x-layouts.admin>
