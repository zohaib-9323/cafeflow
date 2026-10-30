@php $editing = $faq->exists; @endphp
<x-layouts.admin :title="$editing ? 'Edit question' : 'New question'">
    <x-page-header :title="$editing ? 'Edit question' : 'New question'" :breadcrumbs="['FAQs' => route('admin.cms.faqs.index'), $editing ? 'Edit' : 'New']" />
    <div class="row"><div class="col-lg-8"><x-card>
        <form method="POST" action="{{ $editing ? route('admin.cms.faqs.update', $faq) : route('admin.cms.faqs.store') }}" novalidate>
            @csrf @if ($editing) @method('PUT') @endif
            <x-input name="question" label="Question" :value="$faq->question" required maxlength="255" />
            <x-textarea name="answer" label="Answer" :value="$faq->answer" rows="6" required maxlength="3000" />
            <x-checkbox name="is_published" label="Show on the FAQ page" :checked="$faq->is_published" />
            <div class="d-flex gap-2"><button class="btn btn-primary">Save</button><a href="{{ route('admin.cms.faqs.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </x-card></div></div>
</x-layouts.admin>
