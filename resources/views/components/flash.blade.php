{{-- Session flash messages + general errors not tied to a visible field. --}}
@foreach (['success' => 'success', 'warning' => 'warning', 'info' => 'info', 'error' => 'danger'] as $key => $type)
    @if (session($key))
        <x-alert :type="$type">{{ session($key) }}</x-alert>
    @endif
@endforeach
@foreach (['general', 'cart', 'stock', 'status', 'promo_code', 'media', 'category', 'page', 'role', 'assigned_to'] as $field)
    @error($field)
        <x-alert type="danger">{{ $message }}</x-alert>
    @enderror
@endforeach
