<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Analytics\AnalyticsService;
use App\Support\DateRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Proxies the Python service through Laravel's auth, so the service key never leaves the server. */
class AnalyticsController extends ApiController
{
    public function __invoke(Request $request, string $report, AnalyticsService $analytics): JsonResponse
    {
        $range = DateRange::fromRequest($request);

        $result = match ($report) {
            'sales' => $analytics->sales($range),
            'products' => $analytics->products($range),
            'customers' => $analytics->customers($range),
            'forecast' => $analytics->forecast(),
        };

        return response()->json([
            'success' => $result['available'],
            'message' => $result['available'] ? 'OK' : 'Analytics service unavailable; showing fallback data where possible.',
            'data' => $result['data'],
            'meta' => ['source' => $result['source'], 'range' => ['from' => $range->from->toDateString(), 'to' => $range->to->toDateString()]],
        ], $result['available'] ? 200 : 503);
    }
}
