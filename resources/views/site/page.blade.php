<x-layouts.site :title="$page->seo_title ?: $page->title" :description="$page->seo_description">
    @if ($page->slug === 'about')
        @php $aboutImage = app(\App\Services\SiteContent::class)->imageUrl($site['about']['image'] ?? null, 'images/about-default.jpg'); @endphp
        <section class="container page-head" aria-labelledby="page-title">
            <p class="eyebrow mb-3">{{ $site['business']['name'] }}</p>
            <h1 id="page-title" class="page-title mb-0">{{ $page->title }}</h1>
        </section>
        <div class="container">
            <img src="{{ $aboutImage }}" alt="Inside {{ $site['business']['name'] }}" class="story-photo" fetchpriority="high" width="1600" height="800">
        </div>
        <div class="container section">
            <div class="row g-4 g-lg-5">
                <div class="col-lg-4">
                    <p class="story-aside">{{ $site['business']['tagline'] }}</p>
                </div>
                <article class="col-lg-8 prose prose-story">
                    {{-- Rendered from Markdown with raw HTML stripped (see CmsPage::renderedBody). --}}
                    {!! $page->renderedBody() !!}
                </article>
            </div>
        </div>
        <section class="bg-espresso section joins-footer" aria-label="Order ahead">
            <div class="container d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-4">
                <div>
                    <p class="eyebrow mb-2">{{ $site['contact']['address'] }}, {{ $site['contact']['city'] }}</p>
                    <p class="section-title font-serif lh-sm text-white mb-0">Order ahead for pickup or delivery.</p>
                </div>
                <a href="{{ route('menu.index') }}" class="btn btn-accent btn-lg rounded-pill px-4 flex-shrink-0">See the menu <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
            </div>
        </section>
    @else
        <div class="container py-5">
            <article class="prose mx-auto">
                <h1 class="section-title mb-2">{{ $page->title }}</h1>
                <p class="small text-secondary mb-4">Last updated {{ $page->updated_at->format('F j, Y') }}</p>
                {{-- Rendered from Markdown with raw HTML stripped (see CmsPage::renderedBody). --}}
                {!! $page->renderedBody() !!}
            </article>
        </div>
    @endif
</x-layouts.site>
