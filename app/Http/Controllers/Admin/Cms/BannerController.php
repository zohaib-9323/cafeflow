<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\CmsBanner;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function index(): View
    {
        return view('admin.cms.banners.index', ['banners' => CmsBanner::query()->orderBy('placement')->orderBy('sort_order')->get()]);
    }

    public function create(): View
    {
        return view('admin.cms.banners.form', ['banner' => new CmsBanner(['is_active' => true, 'placement' => 'home'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        CmsBanner::query()->create($this->validated($request, required: true));

        return redirect()->route('admin.cms.banners.index')->with('success', 'Banner created.');
    }

    public function edit(CmsBanner $banner): View
    {
        return view('admin.cms.banners.form', ['banner' => $banner]);
    }

    public function update(Request $request, CmsBanner $banner): RedirectResponse
    {
        $banner->update($this->validated($request, required: false));

        return redirect()->route('admin.cms.banners.index')->with('success', 'Banner saved.');
    }

    public function destroy(CmsBanner $banner): RedirectResponse
    {
        $banner->delete();

        return redirect()->route('admin.cms.banners.index')->with('success', 'Banner deleted.');
    }

    private function validated(Request $request, bool $required): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'cta_label' => ['nullable', 'string', 'max:40', 'required_with:cta_url'],
            'cta_url' => ['nullable', 'string', 'max:255', 'regex:#^(/|https?://)#'],
            'placement' => ['required', Rule::in(['home', 'menu'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'image' => MediaService::rules($required),
        ], ['cta_url.regex' => 'Links must start with / or http(s)://.']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->media->store($request->file('image'), $request->user(), $data['title'])->path;
        }
        unset($data['image']);

        return $data + ['is_active' => $request->boolean('is_active'), 'sort_order' => $data['sort_order'] ?? 0];
    }
}
