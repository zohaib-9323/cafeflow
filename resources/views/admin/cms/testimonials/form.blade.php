@php $editing = $testimonial->exists; @endphp
<x-layouts.admin :title="$editing ? 'Edit testimonial' : 'New testimonial'">
    <x-page-header :title="$editing ? 'Edit testimonial' : 'New testimonial'" :breadcrumbs="['Testimonials' => route('admin.cms.testimonials.index'), $editing ? 'Edit' : 'New']" />
    <div class="row"><div class="col-lg-8"><x-card>
        <form method="POST" action="{{ $editing ? route('admin.cms.testimonials.update', $testimonial) : route('admin.cms.testimonials.store') }}" enctype="multipart/form-data" novalidate>
            @csrf @if ($editing) @method('PUT') @endif
            <x-textarea name="quote" label="Quote" :value="$testimonial->quote" rows="4" required maxlength="600" help="Only publish quotes you have permission to use." />
            <div class="row">
                <div class="col-md-6"><x-input name="author_name" label="Name" :value="$testimonial->author_name" required maxlength="100" /></div>
                <div class="col-md-6"><x-input name="author_title" label="Description" :value="$testimonial->author_title" maxlength="120" help="e.g. Regular since 2023" /></div>
                <div class="col-md-6"><x-select name="rating" label="Rating" :options="[5 => '5 stars', 4 => '4 stars', 3 => '3 stars', 2 => '2 stars', 1 => '1 star']" :value="$testimonial->rating" required /></div>
                <div class="col-md-6"><x-input name="sort_order" label="Order" type="number" min="0" :value="$testimonial->sort_order ?? 0" /></div>
            </div>
            <x-input name="avatar" type="file" label="Photo (optional)" accept="image/jpeg,image/png,image/webp" />
            <x-checkbox name="is_published" label="Published" :checked="$testimonial->is_published" />
            <div class="d-flex gap-2"><button class="btn btn-primary">Save</button><a href="{{ route('admin.cms.testimonials.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </x-card></div></div>
</x-layouts.admin>
