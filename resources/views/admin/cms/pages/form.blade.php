@php $editing = $page->exists; @endphp
<x-layouts.admin :title="$editing ? 'Edit '.$page->title : 'New page'">
    <x-page-header :title="$editing ? $page->title : 'New page'" :breadcrumbs="['Pages' => route('admin.cms.pages.index'), $editing ? 'Edit' : 'New']" />
    <form method="POST" action="{{ $editing ? route('admin.cms.pages.update', $page) : route('admin.cms.pages.store') }}" novalidate>
        @csrf @if ($editing) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-xl-8">
                <x-card>
                    <x-input name="title" label="Title" :value="$page->title" required maxlength="150" id="pageTitle" />
                    <x-input name="slug" label="Address" :value="$page->slug" data-slug-source="#pageTitle" prefix="/pages/" />
                    <x-textarea name="body" label="Content" :value="$page->body" rows="18" required class="font-monospace small"
                        help="Markdown: ## for headings, **bold**, - for lists, [text](https://…) for links. HTML is removed." wrapperClass="mb-0" />
                </x-card>
            </div>
            <div class="col-xl-4">
                <x-card title="Publishing" class="mb-3">
                    <x-checkbox name="is_published" label="Published" :checked="$page->is_published" />
                    <x-checkbox name="show_in_footer" label="Link in the footer" :checked="$page->show_in_footer" wrapperClass="mb-0" />
                </x-card>
                <x-card title="Search engines" class="mb-3">
                    <x-input name="seo_title" label="Title" :value="$page->seo_title" maxlength="70" help="Up to 70 characters. Defaults to the page title." />
                    <x-textarea name="seo_description" label="Description" :value="$page->seo_description" rows="3" maxlength="170" wrapperClass="mb-0" />
                </x-card>
                <div class="d-grid gap-2"><button class="btn btn-primary btn-lg">Save page</button><a href="{{ route('admin.cms.pages.index') }}" class="btn btn-light">Cancel</a></div>
            </div>
        </div>
    </form>
</x-layouts.admin>
