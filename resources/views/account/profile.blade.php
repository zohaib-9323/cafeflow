<x-layouts.site title="Profile">
    <div class="container py-5">
        <h1 class="section-title mb-3">My account</h1>
        @include('account._nav')
        <div class="row g-4">
            <div class="col-lg-6">
                <form method="POST" action="{{ route('account.profile.update') }}" class="surface p-4 mb-4">
                    @csrf @method('PUT')
                    <h2 class="h5 mb-3">Profile</h2>
                    <x-input name="name" label="Name" :value="$user->name" required autocomplete="name" />
                    <x-input name="email" type="email" label="Email" :value="$user->email" required autocomplete="email" />
                    <x-input name="phone" type="tel" label="Phone" :value="$user->customer?->phone" autocomplete="tel" />
                    <x-checkbox name="marketing_opt_in" label="Seasonal specials by email" :checked="$user->customer?->marketing_opt_in" />
                    <button class="btn btn-primary">Save profile</button>
                </form>
                <form method="POST" action="{{ route('account.password') }}" class="surface p-4">
                    @csrf @method('PUT')
                    <h2 class="h5 mb-3">Change password</h2>
                    <x-input name="current_password" type="password" label="Current password" required autocomplete="current-password" />
                    <x-input name="password" type="password" label="New password" required autocomplete="new-password" />
                    <x-input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
                    <button class="btn btn-outline-primary">Update password</button>
                </form>
            </div>
            <div class="col-lg-6">
                <section class="surface p-4" aria-labelledby="addr-title">
                    <h2 id="addr-title" class="h5 mb-3">Saved addresses</h2>
                    @forelse ($user->customer?->addresses ?? [] as $address)
                        <div class="d-flex justify-content-between align-items-start border rounded-3 p-3 mb-2">
                            <div><strong>{{ $address->label }}</strong> @if ($address->is_default)<x-badge tone="accent">Default</x-badge>@endif
                                <div class="small text-secondary">{{ $address->oneLine() }}</div></div>
                            <form method="POST" action="{{ route('account.addresses.destroy', $address) }}">@csrf @method('DELETE')
                                <button class="btn btn-link btn-sm text-danger" aria-label="Delete address {{ $address->label }}"><i class="bi bi-trash" aria-hidden="true"></i></button></form>
                        </div>
                    @empty
                        <p class="text-secondary small">No saved addresses yet.</p>
                    @endforelse
                    <details class="mt-3" @if ($errors->hasAny(['line1', 'city', 'postal_code', 'label'])) open @endif>
                        <summary class="fw-medium">Add an address</summary>
                        <form method="POST" action="{{ route('account.addresses.store') }}" class="mt-3">
                            @csrf
                            <x-input name="label" label="Label" value="Home" required />
                            <x-input name="line1" label="Street address" required autocomplete="address-line1" />
                            <x-input name="line2" label="Apartment, floor" autocomplete="address-line2" />
                            <div class="row"><div class="col-7"><x-input name="city" label="City" required /></div><div class="col-5"><x-input name="postal_code" label="Postal code" required /></div></div>
                            <x-input name="instructions" label="Delivery instructions" />
                            <x-checkbox name="is_default" label="Make this my default address" :switch="false" />
                            <button class="btn btn-primary btn-sm">Save address</button>
                        </form>
                    </details>
                </section>
            </div>
        </div>
    </div>
</x-layouts.site>
