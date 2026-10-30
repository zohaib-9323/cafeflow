<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>{{ isset($title) ? $title.' · ' : '' }}CafeFlow Admin</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @fonts
    @vite(['resources/scss/admin.scss', 'resources/js/admin.js'])
</head>
<body class="admin">
<a href="#main" class="skip-link">Skip to content</a>
@php $u = auth()->user(); @endphp

<aside class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">
    <a href="{{ route('admin.dashboard') }}" class="brand"><i class="bi bi-cup-hot-fill" aria-hidden="true"></i><span class="label">CafeFlow</span></a>
    <nav>
        <x-nav-link :href="route('admin.dashboard')" icon="speedometer2" :active="request()->routeIs('admin.dashboard')">Dashboard</x-nav-link>
        @can('view-analytics')
            <x-nav-link :href="route('admin.analytics')" icon="graph-up-arrow" :active="request()->routeIs('admin.analytics')">Analytics</x-nav-link>
        @endcan

        <div class="nav-section">Operations</div>
        @can('view-orders')
            <x-nav-link :href="route('admin.orders.index')" icon="receipt" :active="request()->routeIs('admin.orders.*')" :count="$navCounts['pending_orders']">Orders</x-nav-link>
        @endcan
        @can('view-inventory')
            <x-nav-link :href="route('admin.inventory.index')" icon="box-seam" :active="request()->routeIs('admin.inventory.*')" :count="$navCounts['low_stock']">Inventory</x-nav-link>
        @endcan
        @can('manage-inventory')
            <x-nav-link :href="route('admin.suppliers.index')" icon="truck" :active="request()->routeIs('admin.suppliers.*')">Suppliers</x-nav-link>
        @endcan

        <div class="nav-section">Catalogue</div>
        @can('view-catalog')
            <x-nav-link :href="route('admin.products.index')" icon="cup-straw" :active="request()->routeIs('admin.products.*')">Products</x-nav-link>
        @endcan
        @can('manage-catalog')
            <x-nav-link :href="route('admin.categories.index')" icon="tags" :active="request()->routeIs('admin.categories.*')">Categories</x-nav-link>
        @endcan
        @can('manage-promotions')
            <x-nav-link :href="route('admin.promotions.index')" icon="percent" :active="request()->routeIs('admin.promotions.*')">Promotions</x-nav-link>
        @endcan

        @canany(['manage-customers', 'manage-reviews'])
            <div class="nav-section">Customers</div>
            @can('manage-customers')
                <x-nav-link :href="route('admin.customers.index')" icon="people" :active="request()->routeIs('admin.customers.*')">Customers</x-nav-link>
            @endcan
            @can('manage-reviews')
                <x-nav-link :href="route('admin.reviews.index')" icon="chat-square-quote" :active="request()->routeIs('admin.reviews.*')">Reviews</x-nav-link>
            @endcan
        @endcanany

        @can('manage-content')
            <div class="nav-section">Website</div>
            <x-nav-link :href="route('admin.cms.homepage.edit')" icon="house-gear" :active="request()->routeIs('admin.cms.homepage.*')">Homepage</x-nav-link>
            <x-nav-link :href="route('admin.cms.pages.index')" icon="file-earmark-richtext" :active="request()->routeIs('admin.cms.pages.*')">Pages</x-nav-link>
            <x-nav-link :href="route('admin.cms.banners.index')" icon="megaphone" :active="request()->routeIs('admin.cms.banners.*')">Banners</x-nav-link>
            <x-nav-link :href="route('admin.cms.faqs.index')" icon="patch-question" :active="request()->routeIs('admin.cms.faqs.*')">FAQs</x-nav-link>
            <x-nav-link :href="route('admin.cms.testimonials.index')" icon="chat-heart" :active="request()->routeIs('admin.cms.testimonials.*')">Testimonials</x-nav-link>
            <x-nav-link :href="route('admin.cms.media.index')" icon="images" :active="request()->routeIs('admin.cms.media.*')">Media library</x-nav-link>
        @endcan

        @canany(['manage-team', 'manage-settings'])
            <div class="nav-section">System</div>
            @can('manage-team')
                <x-nav-link :href="route('admin.team.index')" icon="person-badge" :active="request()->routeIs('admin.team.*')">Team</x-nav-link>
            @endcan
            @can('manage-settings')
                <x-nav-link :href="route('admin.settings.edit')" icon="gear" :active="request()->routeIs('admin.settings.*')">Settings</x-nav-link>
            @endcan
        @endcanany
    </nav>
    <div class="p-3 border-top border-secondary border-opacity-25">
        <a href="{{ route('home') }}" class="nav-link small" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i><span class="label">View website</span></a>
    </div>
</aside>
<div class="sidebar-backdrop" data-sidebar-close></div>

<div class="admin-main">
    <header class="admin-topbar d-flex align-items-center gap-2 px-3 px-lg-4">
        <button class="btn btn-light border-0" type="button" data-sidebar-toggle aria-controls="adminSidebar" aria-expanded="true" aria-label="Toggle sidebar">
            <i class="bi bi-list fs-5" aria-hidden="true"></i>
        </button>
        <span class="fw-semibold d-none d-md-inline text-secondary">{{ $businessName }}</span>

        <div class="ms-auto d-flex align-items-center gap-1">
            <div class="dropdown">
                <button class="btn btn-light border-0 position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Notifications, {{ $unreadCount }} unread">
                    <i class="bi bi-bell fs-5" aria-hidden="true"></i>
                    @if ($unreadCount)<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.65rem" data-unread-count>{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>@endif
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0 shadow notif-menu">
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <span class="fw-semibold">Notifications</span>
                        @if ($unreadCount)
                            <form method="POST" action="{{ route('admin.notifications.read-all') }}">@csrf<button class="btn btn-link btn-sm p-0">Mark all read</button></form>
                        @endif
                    </div>
                    @forelse ($unreadNotifications as $n)
                        <a class="dropdown-item notif-item unread py-2 px-3 text-wrap" href="{{ route('admin.notifications.open', $n->id) }}">
                            <div class="d-flex gap-2">
                                <i class="bi bi-{{ $n->data['icon'] ?? 'bell' }} text-coffee mt-1" aria-hidden="true"></i>
                                <div>
                                    <div class="fw-medium small">{{ $n->data['title'] ?? 'Notification' }}</div>
                                    <div class="small text-secondary">{{ \Illuminate\Support\Str::limit($n->data['body'] ?? '', 90) }}</div>
                                    <div class="small text-secondary">{{ $n->created_at->diffForHumans() }}</div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="px-3 py-4 text-center small text-secondary">You're all caught up.</div>
                    @endforelse
                    <a href="{{ route('admin.notifications.index') }}" class="d-block text-center small py-2 border-top">View all</a>
                </div>
            </div>

            <div class="dropdown">
                <button class="btn btn-light border-0 d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="rounded-circle bg-primary text-white d-inline-grid" style="width:2rem;height:2rem;place-items:center;font-size:.8rem" aria-hidden="true">{{ strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                    <span class="d-none d-sm-block text-start lh-sm">
                        <span class="d-block small fw-semibold">{{ $u->name }}</span>
                        <span class="d-block small text-secondary">{{ $u->roleEnum()->label() }}</span>
                    </span>
                    <i class="bi bi-chevron-down small" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><span class="dropdown-item-text small text-secondary">{{ $u->email }}</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('home') }}"><i class="bi bi-shop me-2" aria-hidden="true"></i>Customer website</a></li>
                    <li><a class="dropdown-item" href="{{ route('docs.api') }}"><i class="bi bi-braces me-2" aria-hidden="true"></i>API docs</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="dropdown-item"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Sign out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <main id="main" class="admin-content" tabindex="-1">
        <x-flash />
        {{ $slot }}
    </main>
</div>

<x-modal id="confirmModal" title="Please confirm">
    <p class="mb-0" data-confirm-message></p>
    <x-slot:footer>
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" data-confirm-ok>Confirm</button>
    </x-slot:footer>
</x-modal>
<div class="toast-container position-fixed bottom-0 end-0 p-3" aria-live="polite" aria-atomic="true" data-toasts></div>
@stack('scripts')
</body>
</html>
