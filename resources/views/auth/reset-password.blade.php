<x-layouts.site title="Choose a new password">
    <div class="container py-5">
        <div class="auth-card mx-auto surface p-4 p-lg-5">
            <h1 class="h2 mb-4">Choose a new password</h1>
            <form method="POST" action="{{ route('password.update') }}" data-once>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <x-input name="email" type="email" label="Email" :value="$email" required autocomplete="username" />
                <x-input name="password" type="password" label="New password" required autocomplete="new-password" />
                <x-input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
                <button class="btn btn-primary btn-lg w-100">Reset password</button>
            </form>
        </div>
    </div>
</x-layouts.site>
