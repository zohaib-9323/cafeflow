{{-- A form whose submit asks for confirmation in the shared modal (no browser confirm()). --}}
@props(['action', 'method' => 'POST', 'message' => 'Are you sure?', 'confirmLabel' => 'Confirm', 'tone' => 'danger'])
<form method="POST" action="{{ $action }}" data-confirm="{{ $message }}" data-confirm-label="{{ $confirmLabel }}" data-confirm-tone="{{ $tone }}" {{ $attributes->only('class') }}>
    @csrf
    @if (strtoupper($method) !== 'POST') @method($method) @endif
    {{ $slot }}
</form>
