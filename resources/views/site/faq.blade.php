<x-layouts.site title="FAQ" description="Answers to common questions about ordering, delivery, allergens and payments.">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <p class="eyebrow mb-2">Help</p>
                <h1 class="section-title mb-4">Frequently asked questions</h1>
                @if ($faqs->isEmpty())
                    <x-empty-state icon="patch-question" title="No questions here yet" />
                @else
                    <div class="accordion" id="faq">
                        @foreach ($faqs as $faq)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }} fw-medium" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $faq->id }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="faq{{ $faq->id }}">
                                        {{ $faq->question }}
                                    </button>
                                </h2>
                                <div id="faq{{ $faq->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#faq">
                                    <div class="accordion-body text-secondary">{!! nl2br(e($faq->answer)) !!}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                <p class="mt-4 text-secondary">Still stuck? <a href="{{ route('contact') }}">Send us a message</a>.</p>
            </div>
        </div>
    </div>
</x-layouts.site>
