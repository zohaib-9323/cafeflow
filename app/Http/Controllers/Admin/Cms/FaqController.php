<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(): View
    {
        return view('admin.cms.faqs.index', ['faqs' => Faq::query()->orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function create(): View
    {
        return view('admin.cms.faqs.form', ['faq' => new Faq(['is_published' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Faq::query()->create($data + ['sort_order' => (int) Faq::query()->max('sort_order') + 1]);

        return redirect()->route('admin.cms.faqs.index')->with('success', 'FAQ added.');
    }

    public function edit(Faq $faq): View
    {
        return view('admin.cms.faqs.form', ['faq' => $faq]);
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $faq->update($this->validated($request));

        return redirect()->route('admin.cms.faqs.index')->with('success', 'FAQ saved.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()->route('admin.cms.faqs.index')->with('success', 'FAQ deleted.');
    }

    /** Persist a new display order (array of ids, top to bottom). */
    public function reorder(Request $request): JsonResponse|RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'max:500'], 'ids.*' => ['integer', 'distinct', 'exists:faqs,id']])['ids'];

        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $position => $id) {
                Faq::query()->whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Order saved.'])
            : back()->with('success', 'Order saved.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:3000'],
        ]) + ['is_published' => $request->boolean('is_published')];
    }
}
