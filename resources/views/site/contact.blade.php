<x-layouts.site title="Contact us">
    <div class="container py-5">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-5">
                <p class="eyebrow mb-2">Get in touch</p>
                <h1 class="section-title mb-3">Contact us</h1>
                <p class="text-secondary mb-4">Questions about catering, allergens or a past order? We usually reply within one working day.</p>
                <dl>
                    <dt>Address</dt><dd class="text-secondary">{{ $site['contact']['address'] }}, {{ $site['contact']['city'] }}</dd>
                    <dt>Phone</dt><dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact']['phone']) }}">{{ $site['contact']['phone'] }}</a></dd>
                    <dt>Email</dt><dd><a href="mailto:{{ $site['contact']['email'] }}">{{ $site['contact']['email'] }}</a></dd>
                    <dt>Hours</dt>
                    <dd class="text-secondary">@foreach ($site['hours'] as $row){{ $row['days'] }}: {{ $row['hours'] }}<br>@endforeach</dd>
                </dl>
            </div>
            <div class="col-lg-7">
                <form method="POST" action="{{ route('contact.send') }}" class="surface p-4 p-lg-5" data-once>
                    @csrf
                    <div class="row">
                        <div class="col-md-6"><x-input name="name" label="Your name" :value="auth()->user()?->name" required autocomplete="name" /></div>
                        <div class="col-md-6"><x-input name="email" label="Email" type="email" :value="auth()->user()?->email" required autocomplete="email" /></div>
                    </div>
                    <x-input name="subject" label="Subject" required maxlength="150" />
                    <x-textarea name="message" label="Message" rows="6" required maxlength="3000" />
                    {{-- Honeypot: hidden from people and assistive tech; bots fill it in. --}}
                    <div class="visually-hidden" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
                    <button class="btn btn-primary btn-lg">Send message</button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.site>
