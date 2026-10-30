<x-layouts.site :title="$page->seo_title ?: $page->title" :description="$page->seo_description">
    <div class="container py-5">
        <article class="prose mx-auto">
            <h1 class="section-title mb-2">{{ $page->title }}</h1>
            <p class="small text-secondary mb-4">Last updated {{ $page->updated_at->format('F j, Y') }}</p>
            {{-- Rendered from Markdown with raw HTML stripped (see CmsPage::renderedBody). --}}
            {!! $page->renderedBody() !!}
        </article>
    </div>
</x-layouts.site>
