<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageController extends Controller
{
    /** Pages the site links to directly; they can be edited and unpublished, not deleted. */
    public const SYSTEM_SLUGS = ['about', 'privacy-policy', 'terms-and-conditions'];

    public function index(): View
    {
        return view('admin.cms.pages.index', ['pages' => CmsPage::query()->orderBy('title')->get()]);
    }

    public function create(): View
    {
        return view('admin.cms.pages.form', ['page' => new CmsPage(['show_in_footer' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $page = new CmsPage($this->validated($request));
        $this->stamp($page, $request)->save();

        return redirect()->route('admin.cms.pages.edit', $page)->with('success', 'Page created.');
    }

    public function edit(CmsPage $page): View
    {
        return view('admin.cms.pages.form', ['page' => $page]);
    }

    public function update(Request $request, CmsPage $page): RedirectResponse
    {
        $data = $this->validated($request, $page);
        if (in_array($page->slug, self::SYSTEM_SLUGS, true)) {
            $data['slug'] = $page->slug; // links depend on it
        }
        $this->stamp($page->fill($data), $request)->save();

        return back()->with('success', 'Page saved.');
    }

    public function destroy(CmsPage $page): RedirectResponse
    {
        if (in_array($page->slug, self::SYSTEM_SLUGS, true)) {
            return back()->withErrors(['page' => 'This page is linked from the site. Unpublish it instead.']);
        }
        $page->delete();
        Cache::forget('footer_pages');

        return redirect()->route('admin.cms.pages.index')->with('success', 'Page deleted.');
    }

    private function validated(Request $request, ?CmsPage $page = null): array
    {
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('title'))]);

        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'alpha_dash', 'max:160', Rule::unique('cms_pages', 'slug')->ignore($page), Rule::notIn(['admin', 'menu', 'cart', 'checkout', 'account', 'api'])],
            'body' => ['required', 'string', 'max:60000'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
        ]);
    }

    private function stamp(CmsPage $page, Request $request): CmsPage
    {
        $publish = $request->boolean('is_published');
        $page->is_published = $publish;
        $page->show_in_footer = $request->boolean('show_in_footer');
        $page->published_at = $publish ? ($page->published_at ?? now()) : null;
        $page->updated_by = $request->user()->id;
        Cache::forget('footer_pages');

        return $page;
    }
}
