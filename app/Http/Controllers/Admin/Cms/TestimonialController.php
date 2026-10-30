<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function index(): View
    {
        return view('admin.cms.testimonials.index', ['testimonials' => Testimonial::query()->orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function create(): View
    {
        return view('admin.cms.testimonials.form', ['testimonial' => new Testimonial(['rating' => 5, 'is_published' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Testimonial::query()->create($this->validated($request));

        return redirect()->route('admin.cms.testimonials.index')->with('success', 'Testimonial added.');
    }

    public function edit(Testimonial $testimonial): View
    {
        return view('admin.cms.testimonials.form', ['testimonial' => $testimonial]);
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update($this->validated($request));

        return redirect()->route('admin.cms.testimonials.index')->with('success', 'Testimonial saved.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return redirect()->route('admin.cms.testimonials.index')->with('success', 'Testimonial deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:100'],
            'author_title' => ['nullable', 'string', 'max:120'],
            'quote' => ['required', 'string', 'max:600'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'avatar' => MediaService::rules(required: false),
        ]);
        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $this->media->store($request->file('avatar'), $request->user(), $data['author_name'])->path;
        }
        unset($data['avatar']);

        return $data + ['is_published' => $request->boolean('is_published'), 'sort_order' => $data['sort_order'] ?? 0];
    }
}
