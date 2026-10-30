<x-layouts.admin title="Settings">
    <x-page-header title="Settings" :breadcrumbs="['Settings']" subtitle="Business details and pricing rules. Secrets and API keys live in the server's .env file, not here." />
    <div class="row g-3">
        <div class="col-xl-7">
            <x-card title="Business & pricing">
                <form method="POST" action="{{ route('admin.settings.update') }}" novalidate>
                    @csrf @method('PUT')
                    <div class="row">
                        <div class="col-md-6"><x-input name="name" label="Café name" :value="$business['name'] ?? ''" required maxlength="100" /></div>
                        <div class="col-md-6"><x-input name="tagline" label="Tagline" :value="$business['tagline'] ?? ''" maxlength="150" /></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6"><x-input name="tax_rate_percent" label="Sales tax" type="number" step="0.01" min="0" max="30" :value="round($pricing['tax_rate'] * 100, 2)" suffix="%" required help="Charged on food and drink after discounts. Delivery isn't taxed." /></div>
                        <div class="col-md-6"><x-input name="delivery_fee" label="Delivery fee" type="number" step="0.01" min="0" :value="number_format((float) $pricing['delivery_fee'], 2, '.', '')" prefix="$" required /></div>
                        <div class="col-md-6"><x-input name="free_delivery_over" label="Free delivery from" type="number" step="0.01" min="0" :value="number_format((float) $pricing['free_delivery_over'], 2, '.', '')" prefix="$" required /></div>
                        <div class="col-md-6"><x-input name="min_delivery_order" label="Minimum delivery order" type="number" step="0.01" min="0" :value="number_format((float) $pricing['min_delivery_order'], 2, '.', '')" prefix="$" required /></div>
                    </div>
                    <x-input name="low_stock_email" label="Also email low-stock alerts to" type="email" :value="$pricing['low_stock_email']" help="Optional, e.g. a shared kitchen inbox. Managers always get them." />
                    <p class="small text-secondary">Changes apply to new orders only; existing orders keep the prices they were placed with.</p>
                    <button class="btn btn-primary">Save settings</button>
                </form>
            </x-card>
        </div>
        <div class="col-xl-5">
            <x-card title="System" icon="hdd-stack" flush>
                <dl class="list-group list-group-flush mb-0 small">
                    @php
                        $rows = [
                            'Environment' => $system['environment'].($system['debug'] ? ' (debug on)' : ''),
                            'Payments' => $system['payment_gateway'] === 'mock' ? 'Test gateway (no real money moves)' : ucfirst($system['payment_gateway']),
                            'Email' => $system['mailer'] === 'log' ? 'Written to the log file only, not sent' : ucfirst($system['mailer']),
                            'Background jobs' => $system['queue'] === 'sync' ? 'Run immediately (sync)' : ucfirst($system['queue']).' queue',
                        ];
                    @endphp
                    @foreach ($rows as $k => $v)
                        <div class="list-group-item d-flex justify-content-between gap-3"><dt class="fw-normal text-secondary">{{ $k }}</dt><dd class="mb-0 text-end">{{ $v }}</dd></div>
                    @endforeach
                    <div class="list-group-item d-flex justify-content-between gap-3">
                        <dt class="fw-normal text-secondary">Analytics service</dt>
                        <dd class="mb-0 text-end">
                            @if ($system['analytics']['available'])
                                <x-badge tone="success">Online</x-badge> <span class="text-secondary">v{{ $system['analytics']['version'] ?? '?' }}</span>
                                @unless ($system['analytics']['auth_configured'] ?? false)<span class="d-block text-danger">Service key not set</span>@endunless
                            @else
                                <x-badge tone="danger">Offline</x-badge>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
</x-layouts.admin>
