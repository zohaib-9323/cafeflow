<x-layouts.site title="Sign in">
    <div class="container py-5">
        <div class="auth-card mx-auto surface p-4 p-lg-5">
            <h1 class="h2 mb-1">Welcome back</h1>
            <p class="text-secondary mb-4">Sign in to order and track your orders.</p>
            <form method="POST" action="{{ route('login') }}" data-once>
                @csrf
                <x-input name="email" type="email" label="Email" required autofocus autocomplete="username" />
                <x-input name="password" type="password" label="Password" required autocomplete="current-password" />
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <x-checkbox name="remember" label="Keep me signed in" :switch="false" wrapperClass="mb-0" />
                    <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
                </div>
                <button class="btn btn-primary btn-lg w-100">Sign in</button>
            </form>
            <p class="text-center mt-4 mb-0 small">New here? <a href="{{ route('register') }}">Create an account</a></p>
            @if (app()->environment('local'))
                <details class="mt-4 small text-secondary">
                    <summary>Demo accounts (local only)</summary>
                    <p class="mt-2 mb-1">Password for all: <code>{{ config('cafeflow.demo_password') }}</code></p>
                    <ul class="mb-0 ps-3">
                        <li>admin@cafeflow.demo (Super Admin)</li>
                        <li>manager@cafeflow.demo (Manager)</li>
                        <li>staff@cafeflow.demo (Staff)</li>
                        <li>customer@cafeflow.demo (Customer)</li>
                    </ul>
                </details>
            @endif
        </div>
    </div>
</x-layouts.site>
