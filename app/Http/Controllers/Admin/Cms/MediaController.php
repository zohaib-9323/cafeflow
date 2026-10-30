<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function index(Request $request): View
    {
        $term = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? null;
        $items = Media::query()->with('uploader:id,name')
            ->when($term, fn ($q) => $q->where(fn ($q) => $q->where('original_name', 'like', "%{$term}%")->orWhere('alt', 'like', "%{$term}%")))
            ->latest()->paginate(24)->withQueryString();

        return view('admin.cms.media', [
            'items' => $items,
            'term' => $term,
            'usage' => $items->getCollection()->mapWithKeys(fn (Media $m) => [$m->id => $this->media->usages($m->path)]),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => MediaService::rules(),
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $stored = collect($request->file('files'))->map(fn ($file) => $this->media->store($file, $request->user(), $request->input('alt')));

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => "{$stored->count()} uploaded.", 'data' => $stored->map(fn ($m) => ['id' => $m->id, 'path' => $m->path, 'url' => $m->url()])])
            : back()->with('success', $stored->count() === 1 ? 'Image uploaded.' : "{$stored->count()} images uploaded.");
    }

    public function destroy(Media $media): RedirectResponse
    {
        $this->media->delete($media); // refuses if still in use

        return back()->with('success', 'Image deleted.');
    }
}
