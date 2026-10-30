@php
    $hero = $content['hero']; $about = $content['about']; $home = $content['home']; $contact = $content['contact'];
    $hours = old('hours', $content['hours']);
    $featured = collect(old('featured', $products->where('is_featured', true)->pluck('id')->all()))->map(fn ($id) => (int) $id);
    $img = fn ($path, $fallback) => $path ? Storage::disk('public')->url($path) : asset($fallback);
@endphp
<x-layouts.admin title="Homepage">
    <x-page-header title="Homepage" :breadcrumbs="['Content', 'Homepage']" subtitle="What visitors see first. Saving publishes immediately.">
        <a href="{{ route('home') }}" class="btn btn-light" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>Open site</a>
    </x-page-header>

    <form method="POST" action="{{ route('admin.cms.homepage.update') }}" enctype="multipart/form-data" novalidate>
        @csrf @method('PUT')
        @if ($errors->any())<x-alert type="danger" :dismissible="false">Some fields need attention. {{ $errors->first() }}</x-alert>@endif
        <div class="row g-3">
            <div class="col-xl-8">
                <x-card title="Hero" subtitle="The big welcome at the top of the page." class="mb-3">
                    <x-input name="hero.eyebrow" label="Small line above the heading" :value="$hero['eyebrow'] ?? ''" maxlength="80" />
                    <x-input name="hero.heading" label="Heading" :value="$hero['heading']" required maxlength="120" />
                    <x-textarea name="hero.description" label="Intro" :value="$hero['description']" rows="3" required maxlength="400" />
                    <div class="row">
                        <div class="col-md-6"><x-input name="hero.cta_label" label="Main button" :value="$hero['cta_label']" required maxlength="40" /></div>
                        <div class="col-md-6"><x-input name="hero.cta_url" label="Main button link" :value="$hero['cta_url']" required help="e.g. /menu" /></div>
                        <div class="col-md-6"><x-input name="hero.secondary_label" label="Second button" :value="$hero['secondary_label'] ?? ''" maxlength="40" /></div>
                        <div class="col-md-6"><x-input name="hero.secondary_url" label="Second button link" :value="$hero['secondary_url'] ?? ''" /></div>
                    </div>
                    <div class="d-flex gap-3 align-items-end">
                        <img src="{{ $img($hero['image'] ?? null, 'images/hero-default.jpg') }}" alt="Current hero image" width="160" height="110" class="rounded object-fit-cover" id="heroPreview">
                        <x-input name="hero_image" type="file" label="Replace image" accept="image/jpeg,image/png,image/webp" data-preview="#heroPreview" wrapperClass="mb-0 flex-grow-1" help="Landscape works best, at least 1600px wide." />
                    </div>
                </x-card>

                <x-card title="Our story" class="mb-3">
                    <x-input name="about.heading" label="Heading" :value="$about['heading']" required maxlength="120" />
                    <x-textarea name="about.body" label="Text" :value="$about['body']" rows="5" required maxlength="2000" />
                    <div class="d-flex gap-3 align-items-end">
                        <img src="{{ $img($about['image'] ?? null, 'images/about-default.jpg') }}" alt="Current story image" width="160" height="110" class="rounded object-fit-cover" id="aboutPreview">
                        <x-input name="about_image" type="file" label="Replace image" accept="image/jpeg,image/png,image/webp" data-preview="#aboutPreview" wrapperClass="mb-0 flex-grow-1" />
                    </div>
                </x-card>

                <x-card title="Featured products" subtitle="Pick up to 12 for the “favourites” row." class="mb-3">
                    <div class="row row-cols-1 row-cols-md-2 g-1">
                        @foreach ($products as $p)
                            <div class="col"><div class="form-check">
                                <input class="form-check-input" type="checkbox" name="featured[]" value="{{ $p->id }}" id="feat{{ $p->id }}" @checked($featured->contains($p->id))>
                                <label class="form-check-label" for="feat{{ $p->id }}">{{ $p->name }}</label>
                            </div></div>
                        @endforeach
                    </div>
                    @error('featured')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                </x-card>
            </div>

            <div class="col-xl-4">
                <x-card title="Section headings" class="mb-3">
                    <x-input name="home.featured_heading" label="Favourites" :value="$home['featured_heading']" required maxlength="80" />
                    <x-input name="home.categories_heading" label="Categories" :value="$home['categories_heading']" required maxlength="80" />
                    <x-input name="home.testimonials_heading" label="Testimonials" :value="$home['testimonials_heading']" required maxlength="80" wrapperClass="mb-0" />
                </x-card>
                <x-card title="Contact details" subtitle="Also used in the footer and on receipts." class="mb-3">
                    <x-input name="contact.phone" label="Phone" :value="$contact['phone']" required />
                    <x-input name="contact.email" label="Email" type="email" :value="$contact['email']" required />
                    <x-input name="contact.address" label="Street address" :value="$contact['address']" required />
                    <x-input name="contact.city" label="City" :value="$contact['city']" required />
                    <x-input name="contact.map_url" label="Map link" :value="$contact['map_url'] ?? ''" help="An https:// link to your map listing." wrapperClass="mb-0" />
                </x-card>
                <x-card title="Opening hours" class="mb-3">
                    <div data-repeater data-next="{{ count($hours) }}">
                        <div data-repeater-list>
                            @foreach ($hours as $i => $h)
                                @include('admin.cms._hours-row', ['i' => $i, 'h' => $h])
                            @endforeach
                        </div>
                        <template>@include('admin.cms._hours-row', ['i' => '__INDEX__', 'h' => []])</template>
                        <button type="button" class="btn btn-sm btn-light" data-repeater-add><i class="bi bi-plus me-1" aria-hidden="true"></i>Add row</button>
                    </div>
                </x-card>
                <div class="d-grid sticky-actions"><button class="btn btn-primary btn-lg">Publish changes</button></div>
            </div>
        </div>
    </form>
</x-layouts.admin>
