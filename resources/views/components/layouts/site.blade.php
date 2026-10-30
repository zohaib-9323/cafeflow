<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ $site['business']['name'] }}</title>
    <meta name="description" content="{{ $description ?? $site['business']['tagline'] }}">
    <meta property="og:title" content="{{ $title ?? $site['business']['name'] }}">
    <meta property="og:description" content="{{ $description ?? $site['business']['tagline'] }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @fonts
    @vite(['resources/scss/site.scss', 'resources/js/site.js'])
    @stack('head')
</head>
<body class="d-flex flex-column min-vh-100">
<a href="#main" class="skip-link">Skip to content</a>

<header class="site-nav sticky-top">
    <nav class="navbar navbar-expand-lg py-3" aria-label="Main">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
                <i class="bi bi-cup-hot-fill brand-mark" aria-hidden="true"></i>{{ $site['business']['name'] }}
            </a>
            <div class="d-flex align-items-center gap-2 order-lg-last">
                <a href="{{ route('cart.index') }}" class="btn btn-link text-body position-relative px-2" aria-label="Cart, {{ $cartCount }} {{ Str::plural('item', $cartCount) }}">
                    <i class="bi bi-bag fs-5" aria-hidden="true"></i>
                    <span class="badge rounded-pill bg-primary cart-count {{ $cartCount ? '' : 'd-none' }}" data-cart-count>{{ $cartCount }}</span>
                </a>
                @auth
                    <div class="dropdown">
                        <button class="btn btn-link text-body px-2 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                            <i class="bi bi-person-circle fs-5" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><h2 class="dropdown-header">{{ auth()->user()->name }}</h2></li>
                            @if (auth()->user()->isBackOffice())
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2" aria-hidden="true"></i>Dashboard</a></li>
                            @else
                                <li><a class="dropdown-item" href="{{ route('account.orders.index') }}"><i class="bi bi-receipt me-2" aria-hidden="true"></i>My orders</a></li>
                                <li><a class="dropdown-item" href="{{ route('account.profile') }}"><i class="bi bi-person me-2" aria-hidden="true"></i>Profile</a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">@csrf
                                    <button class="dropdown-item"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Sign out</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm d-none d-sm-inline-flex">Sign in</a>
                @endauth
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-controls="siteNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse justify-content-center" id="siteNav">
                <ul class="navbar-nav">
                    @foreach (['home' => 'Home', 'menu.index' => 'Menu', 'pages.about' => 'About', 'faq' => 'FAQ', 'contact' => 'Contact'] as $routeName => $label)
                        <li class="nav-item">
                            <a @class(['nav-link', 'active' => request()->routeIs($routeName === 'menu.index' ? 'menu.*' : $routeName)]) href="{{ route($routeName) }}"
                               @if (request()->routeIs($routeName)) aria-current="page" @endif>{{ $label }}</a>
                        </li>
                    @endforeach
                    @guest
                        <li class="nav-item d-sm-none"><a class="nav-link" href="{{ route('login') }}">Sign in</a></li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>
</header>

<main id="main" class="flex-grow-1" tabindex="-1">
    @if (session()->hasAny(['success', 'warning', 'info', 'error']) || $errors->hasAny(['general', 'cart', 'stock', 'status']))
        <div class="container pt-4"><x-flash /></div>
    @endif
    {{ $slot }}
</main>

<footer class="site-footer mt-5 pt-5 pb-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <p class="h4 font-serif text-white mb-2"><i class="bi bi-cup-hot-fill text-accent me-2" aria-hidden="true"></i>{{ $site['business']['name'] }}</p>
                <p class="mb-0">{{ $site['business']['tagline'] }}</p>
            </div>
            <div class="col-6 col-lg-2">
                <h2 class="h6 text-uppercase small">Visit</h2>
                <address class="mb-0 small">{{ $site['contact']['address'] }}<br>{{ $site['contact']['city'] }}<br>
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact']['phone']) }}">{{ $site['contact']['phone'] }}</a></address>
            </div>
            <div class="col-6 col-lg-3">
                <h2 class="h6 text-uppercase small">Hours</h2>
                <ul class="list-unstyled small mb-0">
                    @foreach ($site['hours'] as $row)<li>{{ $row['days'] }}: {{ $row['hours'] }}</li>@endforeach
                </ul>
            </div>
            <div class="col-lg-3">
                <h2 class="h6 text-uppercase small">Information</h2>
                <ul class="list-unstyled small mb-0">
                    <li><a href="{{ route('menu.index') }}">Menu &amp; ordering</a></li>
                    <li><a href="{{ route('faq') }}">FAQ</a></li>
                    <li><a href="{{ route('contact') }}">Contact us</a></li>
                    @foreach ($footerPages as $footerPage)<li><a href="{{ route('pages.show', $footerPage['slug']) }}">{{ $footerPage['title'] }}</a></li>@endforeach
                </ul>
            </div>
        </div>
        <hr class="border-secondary my-4">
        <div class="d-flex flex-column flex-md-row justify-content-between small gap-2">
            <span>&copy; {{ now()->year }} {{ $site['business']['name'] }}. A fictional café, for demonstration.</span>
            <span>Powered by CafeFlow</span>
        </div>
    </div>
</footer>

<div class="toast-container position-fixed bottom-0 end-0 p-3" aria-live="polite" aria-atomic="true" data-toasts></div>
@stack('scripts')
</body>
</html>
