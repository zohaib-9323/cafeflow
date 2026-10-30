<nav class="nav nav-pills gap-2 mb-4" aria-label="Account">
    <a @class(['nav-link', 'active' => request()->routeIs('account.orders.*')]) href="{{ route('account.orders.index') }}" @if (request()->routeIs('account.orders.*')) aria-current="page" @endif><i class="bi bi-receipt me-1" aria-hidden="true"></i>Orders</a>
    <a @class(['nav-link', 'active' => request()->routeIs('account.profile')]) href="{{ route('account.profile') }}" @if (request()->routeIs('account.profile')) aria-current="page" @endif><i class="bi bi-person me-1" aria-hidden="true"></i>Profile &amp; addresses</a>
</nav>
