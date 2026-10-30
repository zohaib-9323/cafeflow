<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'fulfillment' => ['nullable', Rule::in(['pickup', 'delivery'])],
            'scope' => ['nullable', Rule::in(['mine', 'queue'])],
        ]);
        $user = $request->user();

        $orders = Order::query()
            ->with('assignee:id,name')
            ->withCount('items')
            // Staff only see their own orders and the unassigned queue.
            ->when(! $user->can('manage-orders'), fn (Builder $q) => $q->where(fn ($q) => $q
                ->where('assigned_to', $user->id)
                ->orWhere(fn ($q) => $q->whereNull('assigned_to')->whereNotIn('status', ['completed', 'cancelled']))))
            ->when(($filters['scope'] ?? null) === 'mine', fn ($q) => $q->where('assigned_to', $user->id))
            ->when(($filters['scope'] ?? null) === 'queue', fn ($q) => $q->whereNull('assigned_to'))
            ->search($filters['q'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['fulfillment'] ?? null, fn ($q, $f) => $q->where('fulfillment_type', $f))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->where('placed_at', '>=', Carbon::parse($d)->startOfDay()))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->where('placed_at', '<=', Carbon::parse($d)->endOfDay()))
            ->latest('placed_at')
            ->paginate(20)
            ->withQueryString();

        if ($request->expectsJson() || $request->boolean('partial')) {
            return response()->json([
                'success' => true,
                'html' => view('admin.orders._table', ['orders' => $orders])->render(),
                'total' => $orders->total(),
            ]);
        }

        return view('admin.orders.index', ['orders' => $orders, 'filters' => $filters, 'statuses' => OrderStatus::cases()]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorize('view', $order);
        $order->load(['items.addons', 'payments', 'statusHistory.changedBy:id,name', 'user.customer', 'assignee:id,name',
            'promotion', 'inventoryMovements.inventoryItem']);

        return view('admin.orders.show', [
            'order' => $order,
            'next' => $order->status->nextStatuses($order->fulfillment_type),
            'team' => $request->user()->can('manage-orders')
                ? User::query()->withRole(Role::Staff, Role::Manager)->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
            'customerOrderCount' => Order::query()->where('user_id', $order->user_id)->count(),
        ]);
    }

    public function receipt(Order $order): View
    {
        $this->authorize('view', $order);

        return view('admin.orders.receipt', ['order' => $order->load('items.addons')]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note' => ['nullable', 'string', 'max:500', Rule::requiredIf($request->input('status') === 'cancelled')],
        ], ['note.required' => 'Please give a reason for cancelling.']);

        $to = OrderStatus::from($data['status']);
        $this->authorize($to === OrderStatus::Cancelled ? 'cancel' : 'updateStatus', $order);

        $order = $this->orders->transition($order, $to, $request->user(), $data['note'] ?? null);
        $message = "Order {$order->number} is now {$order->status->label()}.";

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'data' => [
                'status' => $order->status->value,
                'label' => $order->status->label(),
                'tone' => $order->status->tone(),
                'payment_status' => $order->payment_status->value,
                'next' => array_map(fn (OrderStatus $s) => ['value' => $s->value, 'label' => $s->label()], $order->status->nextStatuses($order->fulfillment_type)),
            ]]);
        }

        return back()->with('success', $message);
    }

    public function claim(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('claim', $order);
        $this->orders->assign($order, $request->user(), $request->user());

        return redirect()->route('admin.orders.show', $order)->with('success', "You're now handling {$order->number}.");
    }

    public function assign(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('assign', $order);
        $data = $request->validate(['assigned_to' => ['nullable', 'integer', 'exists:users,id']]);
        $staff = isset($data['assigned_to']) ? User::query()->find($data['assigned_to']) : null;

        $this->orders->assign($order, $staff, $request->user());

        return back()->with('success', $staff ? "Assigned to {$staff->name}." : 'Order unassigned.');
    }
}
