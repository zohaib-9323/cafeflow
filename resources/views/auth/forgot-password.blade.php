<x-layouts.site title="Reset your password">
    <div class="container py-5">
        <div class="auth-card mx-auto surface p-4 p-lg-5">
            <h1 class="h2 mb-1">Forgot your password?</h1>
            <p class="text-secondary mb-4">Enter your email and we'll send you a link to choose a new one.</p>
            <form method="POST" action="{{ route('password.email') }}" data-once>
                @csrf
                <x-input name="email" type="email" label="Email" required autofocus autocomplete="email" />
                <button class="btn btn-primary btn-lg w-100">Email me a reset link</button>
            </form>
            <p class="text-center mt-4 mb-0 small"><a href="{{ route('login') }}">Back to sign in</a></p>
        </div>
    </div>
</x-layouts.site>
