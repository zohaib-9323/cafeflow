@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'help' => null, 'id' => null, 'wrapperClass' => 'mb-3'])
@php
    $htmlName = preg_replace('/\.([^.]+)/', '[$1]', $name);
    $id ??= 'f_'.str_replace(['.', '[', ']'], '_', $name);
    $error = $errors->first($name);
    $current = (string) old($name, $value instanceof \BackedEnum ? $value->value : $value);
@endphp
<div class="{{ $wrapperClass }}">
    @if ($label)
        <label for="{{ $id }}" class="form-label fw-medium">{{ $label }}@if ($attributes->has('required'))<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
    @endif
    <select id="{{ $id }}" name="{{ $htmlName }}" {{ $attributes->class(['form-select', 'is-invalid' => $error]) }} @if ($error) aria-invalid="true" aria-describedby="{{ $id }}_error" @endif>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $optValue => $optLabel)
            <option value="{{ $optValue }}" @selected($current === (string) $optValue)>{{ $optLabel }}</option>
        @endforeach
    </select>
    @if ($error)<div id="{{ $id }}_error" class="invalid-feedback">{{ $error }}</div>@endif
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
</div>
