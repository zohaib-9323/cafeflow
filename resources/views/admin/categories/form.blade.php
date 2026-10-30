@php $editing = $category->exists; @endphp
<x-layouts.admin :title="$editing ? 'Edit '.$category->name : 'New category'">
    <x-page-header :title="$editing ? $category->name : 'New category'" :breadcrumbs="['Categories' => route('admin.categories.index'), $editing ? 'Edit' : 'New']" />
    <div class="row"><div class="col-lg-7">
        <x-card>
            <form method="POST" action="{{ $editing ? route('admin.categories.update', $category) : route('admin.categories.store') }}" enctype="multipart/form-data" novalidate>
                @csrf @if ($editing) @method('PUT') @endif
                <x-input name="name" label="Name" :value="$category->name" required maxlength="100" id="catName" />
                <x-input name="slug" label="URL slug" :value="$category->slug" data-slug-source="#catName" help="Used in menu filter links." />
                <x-textarea name="description" label="Description" :value="$category->description" rows="3" maxlength="500" />
                <div class="row">
                    <div class="col-md-4"><x-input name="sort_order" label="Sort order" type="number" min="0" :value="$category->sort_order ?? 0" /></div>
                    <div class="col-md-8 pt-md-4"><x-checkbox name="is_active" label="Visible on the menu" :checked="$category->is_active" /></div>
                </div>
                @if ($category->image_path)<img src="{{ Storage::disk('public')->url($category->image_path) }}" alt="" class="rounded mb-2" width="120" id="catPreview">@else<img alt="" class="rounded mb-2 d-none" width="120" id="catPreview">@endif
                <x-input name="image" type="file" label="Image" accept="image/jpeg,image/png,image/webp" data-preview="#catPreview" />
                <div class="d-flex gap-2"><button class="btn btn-primary">{{ $editing ? 'Save' : 'Create category' }}</button><a href="{{ route('admin.categories.index') }}" class="btn btn-light">Cancel</a></div>
            </form>
        </x-card>
    </div></div>
</x-layouts.admin>
