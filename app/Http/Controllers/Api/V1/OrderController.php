<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Cart\CartLine;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends ApiController
{
    public function __construct(private readonly OrderService $orders) {}

    /** Customers get their own orders; managers get everything; staff get their queue. */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $user = $request->user();

        $orders = Order::query()->withCount('items')
            ->when(! $user->isBackOffice(), fn ($q) => $q->where('user_id', $user->id))
            ->when($user->isBackOffice() && ! $user->can('manage-orders'), fn ($q) => $q->where(fn ($q) => $q
                ->where('assigned_to', $user->id)
                ->orWhere(fn ($q) => $q->whereNull('assigned_to')->whereNotIn('status', ['completed', 'cancelled']))))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($user->isBackOffice(), fn ($q) => $q->search($filters['q'] ?? null))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->where('placed_at', '>=', Carbon::parse($d)->startOfDay()))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->where('placed_at', '<=', Carbon::parse($d)->endOfDay()))
            ->latest('placed_at')
            ->paginate($this->perPage())->withQueryString();

        return $this->ok(OrderResource::collection($orders));
    }

    public function store(CheckoutRequest $request): JsonResponse
    {
        $lines = CartController::validateLines($request)['items'];
        $order = $this->orders->place(
            $request->user(),
            array_map(fn ($l) => CartLine::fromArray($l), $lines),
            $request->validated(),
        );

        return $this->ok(new OrderResource($order->load(['items.addons', 'statusHistory'])), 'Order created successfully', 201);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return $this->ok(new OrderResource($order->load(['items.addons', 'statusHistory'])));
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note' => ['nullable', 'string', 'max:500', Rule::requiredIf($request->input('status') === 'cancelled')],
        ]);
        $to = OrderStatus::from($data['status']);
        $this->authorize($to === OrderStatus::Cancelled ? 'cancel' : 'updateStatus', $order);

        $order = $this->orders->transition($order, $to, $request->user(), $data['note'] ?? null);

        return $this->ok(new OrderResource($order), "Order is now {$order->status->label()}");
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        $this->authorize('cancelOwn', $order);

        return $this->ok(new OrderResource($this->orders->cancelByCustomer($order, $request->user())), 'Order cancelled');
    }
}
