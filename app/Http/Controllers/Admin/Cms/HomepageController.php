<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\MediaService;
use App\Services\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomepageController extends Controller
{
    public function __construct(private readonly SiteContent $content, private readonly MediaService $media) {}

    public function edit(): View
    {
        return view('admin.cms.homepage', [
            'content' => $this->content->all(),
            'products' => Product::query()->listed()->orderBy('name')->get(['id', 'name', 'is_featured']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $url = ['nullable', 'string', 'max:255', 'regex:#^(/|https?://)#']; // site-relative or http(s) only, so no javascript: links
        $data = $request->validate([
            'hero.eyebrow' => ['nullable', 'string', 'max:80'],
            'hero.heading' => ['required', 'string', 'max:120'],
            'hero.description' => ['required', 'string', 'max:400'],
            'hero.cta_label' => ['required', 'string', 'max:40'],
            'hero.cta_url' => ['required', ...array_slice($url, 1)],
            'hero.secondary_label' => ['nullable', 'string', 'max:40'],
            'hero.secondary_url' => $url,
            'hero_image' => MediaService::rules(required: false),
            'about.heading' => ['required', 'string', 'max:120'],
            'about.body' => ['required', 'string', 'max:2000'],
            'about_image' => MediaService::rules(required: false),
            'home.featured_heading' => ['required', 'string', 'max:80'],
            'home.categories_heading' => ['required', 'string', 'max:80'],
            'home.testimonials_heading' => ['required', 'string', 'max:80'],
            'contact.phone' => ['required', 'string', 'max:32'],
            'contact.email' => ['required', 'email', 'max:255'],
            'contact.address' => ['required', 'string', 'max:150'],
            'contact.city' => ['required', 'string', 'max:100'],
            'contact.map_url' => ['nullable', 'url:https', 'max:500'],
            'hours' => ['required', 'array', 'min:1', 'max:7'],
            'hours.*.days' => ['required', 'string', 'max:40'],
            'hours.*.hours' => ['required', 'string', 'max:40'],
            'featured' => ['array', 'max:12'],
            'featured.*' => ['integer', 'exists:products,id'],
        ]);

        $current = $this->content->all();
        $hero = $data['hero'] + ['image' => $current['hero']['image'] ?? null];
        $about = $data['about'] + ['image' => $current['about']['image'] ?? null];
        if ($request->hasFile('hero_image')) {
            $hero['image'] = $this->media->store($request->file('hero_image'), $request->user(), 'Homepage hero')->path;
        }
        if ($request->hasFile('about_image')) {
            $about['image'] = $this->media->store($request->file('about_image'), $request->user(), 'About section')->path;
        }

        DB::transaction(function () use ($data, $hero, $about) {
            $this->content->put('hero', $hero);
            $this->content->put('about', $about);
            $this->content->put('home', $data['home']);
            $this->content->put('contact', $data['contact']);
            $this->content->put('hours', array_values($data['hours']));

            Product::query()->update(['is_featured' => false]);
            Product::query()->whereIn('id', $data['featured'] ?? [])->update(['is_featured' => true]);
        });

        return back()->with('success', 'Homepage updated. Changes are live.');
    }
}
