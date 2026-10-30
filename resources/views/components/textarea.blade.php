@props(['name', 'label' => null, 'value' => null, 'help' => null, 'rows' => 4, 'id' => null, 'wrapperClass' => 'mb-3'])
@php
    $htmlName = preg_replace('/\.([^.]+)/', '[$1]', $name);
    $id ??= 'f_'.str_replace(['.', '[', ']'], '_', $name);
    $error = $errors->first($name);
@endphp
<div class="{{ $wrapperClass }}">
    @if ($label)
        <label for="{{ $id }}" class="form-label fw-medium">{{ $label }}@if ($attributes->has('required'))<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
    @endif
    <textarea id="{{ $id }}" name="{{ $htmlName }}" rows="{{ $rows }}"
        {{ $attributes->class(['form-control', 'is-invalid' => $error]) }}
        @if ($help || $error) aria-describedby="{{ $help ? $id.'_help' : '' }} {{ $error ? $id.'_error' : '' }}" @endif
        @if ($error) aria-invalid="true" @endif>{{ old($name, $value) }}</textarea>
    @if ($error)<div id="{{ $id }}_error" class="invalid-feedback">{{ $error }}</div>@endif
    @if ($help)<div id="{{ $id }}_help" class="form-text">{{ $help }}</div>@endif
</div>
