<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Analytics\AnalyticsService;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request, AnalyticsService $analytics): View
    {
        $range = DateRange::fromRequest($request);
        if ($request->boolean('refresh')) {
            $analytics->forget();
        }

        $customers = $analytics->customers($range);
        // Python only sees ids; names are looked up here so personal data never leaves Laravel.
        $topIds = array_column($customers['data']['top_customers'] ?? [], 'customer_id');

        return view('admin.analytics', [
            'range' => $range,
            'sales' => $analytics->sales($range),
            'products' => $analytics->products($range, 8),
            'customers' => $customers,
            'customerNames' => User::query()->whereIn('id', $topIds)->pluck('name', 'id'),
            'forecast' => $analytics->forecast(),
            'health' => $analytics->health(),
        ]);
    }
}
