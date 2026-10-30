<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsService;
use App\Services\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Restaurant and system settings (super admin only). Secrets stay in .env and are never shown here. */
class SettingsController extends Controller
{
    public function edit(SiteContent $content, AnalyticsService $analytics): View
    {
        return view('admin.settings', [
            'business' => $content->get('business'),
            'pricing' => [
                'tax_rate' => config('cafeflow.tax_rate'),
                'delivery_fee' => config('cafeflow.delivery_fee'),
                'free_delivery_over' => config('cafeflow.free_delivery_over'),
                'min_delivery_order' => config('cafeflow.min_delivery_order'),
                'low_stock_email' => config('cafeflow.low_stock_email'),
            ],
            'system' => [
                'environment' => app()->environment(),
                'debug' => config('app.debug'),
                'payment_gateway' => config('cafeflow.payments.gateway'),
                'mailer' => config('mail.default'),
                'queue' => config('queue.default'),
                'analytics_url' => config('cafeflow.analytics.url'),
                'analytics' => $analytics->health(),
            ],
        ]);
    }

    public function update(Request $request, SiteContent $content): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:150'],
            'tax_rate_percent' => ['required', 'numeric', 'min:0', 'max:30'],
            'delivery_fee' => ['required', 'decimal:0,2', 'min:0', 'max:100'],
            'free_delivery_over' => ['required', 'decimal:0,2', 'min:0', 'max:1000'],
            'min_delivery_order' => ['required', 'decimal:0,2', 'min:0', 'max:1000'],
            'low_stock_email' => ['nullable', 'email', 'max:255'],
        ]);

        $content->put('business', [
            'name' => $data['name'],
            'tagline' => $data['tagline'],
            'tax_rate' => round($data['tax_rate_percent'] / 100, 4),
            'delivery_fee' => $data['delivery_fee'],
            'free_delivery_over' => $data['free_delivery_over'],
            'min_delivery_order' => $data['min_delivery_order'],
            'low_stock_email' => $data['low_stock_email'],
        ]);

        return back()->with('success', 'Settings saved. New orders use these values immediately.');
    }
}
