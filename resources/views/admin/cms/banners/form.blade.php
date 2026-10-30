@php $editing = $banner->exists; @endphp
<x-layouts.admin :title="$editing ? 'Edit banner' : 'New banner'">
    <x-page-header :title="$editing ? $banner->title : 'New banner'" :breadcrumbs="['Banners' => route('admin.cms.banners.index'), $editing ? 'Edit' : 'New']" />
    <div class="row"><div class="col-lg-8"><x-card>
        <form method="POST" action="{{ $editing ? route('admin.cms.banners.update', $banner) : route('admin.cms.banners.store') }}" enctype="multipart/form-data" novalidate>
            @csrf @if ($editing) @method('PUT') @endif
            <x-input name="title" label="Title" :value="$banner->title" required maxlength="120" />
            <x-input name="subtitle" label="Subtitle" :value="$banner->subtitle" maxlength="255" />
            <div class="row">
                <div class="col-md-6"><x-input name="cta_label" label="Button text" :value="$banner->cta_label" maxlength="40" /></div>
                <div class="col-md-6"><x-input name="cta_url" label="Button link" :value="$banner->cta_url" help="/menu or https://…" /></div>
                <div class="col-md-6"><x-select name="placement" label="Show on" :options="['home' => 'Home page', 'menu' => 'Menu page']" :value="$banner->placement" required /></div>
                <div class="col-md-6"><x-input name="sort_order" label="Order" type="number" min="0" :value="$banner->sort_order ?? 0" /></div>
                <div class="col-md-6"><x-input name="starts_at" label="Start" type="datetime-local" :value="$banner->starts_at?->format('Y-m-d\TH:i')" /></div>
                <div class="col-md-6"><x-input name="ends_at" label="End" type="datetime-local" :value="$banner->ends_at?->format('Y-m-d\TH:i')" /></div>
            </div>
            <img src="{{ $banner->imageUrl() }}" alt="" class="rounded mb-2 object-fit-cover @unless ($banner->imageUrl()) d-none @endunless" width="240" height="120" id="bannerPreview">
            <x-input name="image" type="file" label="Image" accept="image/jpeg,image/png,image/webp" data-preview="#bannerPreview" :required="! $editing" />
            <x-checkbox name="is_active" label="Active" :checked="$banner->is_active" />
            <div class="d-flex gap-2"><button class="btn btn-primary">{{ $editing ? 'Save' : 'Create banner' }}</button><a href="{{ route('admin.cms.banners.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </x-card></div></div>
</x-layouts.admin>
