<x-layouts.site title="Create an account">
    <div class="container py-5">
        <div class="auth-card mx-auto surface p-4 p-lg-5">
            <h1 class="h2 mb-1">Create your account</h1>
            <p class="text-secondary mb-4">Order ahead, save addresses and follow every order live.</p>
            <form method="POST" action="{{ route('register') }}" data-once>
                @csrf
                <x-input name="name" label="Full name" required autocomplete="name" />
                <x-input name="email" type="email" label="Email" required autocomplete="email" />
                <x-input name="phone" type="tel" label="Phone (optional)" autocomplete="tel" />
                <x-input name="password" type="password" label="Password" required autocomplete="new-password" help="At least 8 characters, with letters and numbers." />
                <x-input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" />
                <x-checkbox name="marketing_opt_in" label="Email me about seasonal specials (no more than twice a month)" :switch="false" />
                <x-checkbox name="terms" :switch="false">
                    <x-slot:label>I agree to the <a href="{{ route('pages.terms-and-conditions') }}" target="_blank">terms</a> and <a href="{{ route('pages.privacy-policy') }}" target="_blank">privacy policy</a></x-slot:label>
                </x-checkbox>
                <button class="btn btn-primary btn-lg w-100">Create account</button>
            </form>
            <p class="text-center mt-4 mb-0 small">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
        </div>
    </div>
</x-layouts.site>
