@props([
    'name', 'label' => null, 'type' => 'text', 'value' => null, 'help' => null,
    'prefix' => null, 'suffix' => null, 'id' => null, 'wrapperClass' => 'mb-3',
])
@php
    // Accepts dot notation ("hero.heading") and renders the matching HTML name ("hero[heading]").
    $htmlName = preg_replace('/\.([^.]+)/', '[$1]', $name);
    $id ??= 'f_'.str_replace(['.', '[', ']'], '_', $name);
    $error = $errors->first($name);
    $current = $type === 'password' || $type === 'file' ? null : old($name, $value);
    $described = trim(($help ? "{$id}_help " : '').($error ? "{$id}_error" : ''));
@endphp
<div class="{{ $wrapperClass }}">
    @if ($label)
        <label for="{{ $id }}" class="form-label fw-medium">{{ $label }}@if ($attributes->has('required'))<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
    @endif
    @if ($prefix || $suffix)<div class="input-group has-validation">@endif
        @if ($prefix)<span class="input-group-text">{{ $prefix }}</span>@endif
        <input type="{{ $type }}" id="{{ $id }}" name="{{ $htmlName }}" @if (! is_null($current)) value="{{ $current }}" @endif
            {{ $attributes->class(['form-control', 'is-invalid' => $error]) }}
            @if ($described) aria-describedby="{{ $described }}" @endif
            @if ($error) aria-invalid="true" @endif>
        @if ($suffix)<span class="input-group-text">{{ $suffix }}</span>@endif
        @if ($error)<div id="{{ $id }}_error" class="invalid-feedback">{{ $error }}</div>@endif
    @if ($prefix || $suffix)</div>@endif
    @if ($help)<div id="{{ $id }}_help" class="form-text">{{ $help }}</div>@endif
</div>
