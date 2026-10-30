<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::in(['pending', 'approved'])]])['status'] ?? 'pending';

        return view('admin.reviews.index', [
            'status' => $status,
            'reviews' => Review::query()->with(['product:id,name,slug', 'user:id,name,email'])
                ->where('is_approved', $status === 'approved')->latest()->paginate(20)->withQueryString(),
            'pendingCount' => Review::query()->where('is_approved', false)->count(),
        ]);
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('moderate', $review);
        $review->forceFill(['is_approved' => $request->boolean('is_approved')])->save();

        return back()->with('success', $review->is_approved ? 'Review published.' : 'Review hidden.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('moderate', $review);
        $review->delete();

        return back()->with('success', 'Review deleted.');
    }
}
