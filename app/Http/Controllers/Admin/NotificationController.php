<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $notifications = $request->user()->notifications()->latest()->paginate(20);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'data' => [
                'unread' => $request->user()->unreadNotifications()->count(),
                'items' => $notifications->getCollection()->map(fn ($n) => $n->data + ['id' => $n->id, 'read' => (bool) $n->read_at, 'at' => $n->created_at->diffForHumans()]),
            ]]);
        }

        return view('admin.notifications', ['notifications' => $notifications]);
    }

    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        // Only follow links into our own app.
        $url = (string) ($notification->data['url'] ?? '');

        return str_starts_with($url, url('/')) ? redirect()->to($url) : redirect()->route('admin.notifications.index');
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'All caught up.'])
            : back()->with('success', 'All notifications marked as read.');
    }
}
