@props(['name', 'label', 'checked' => false, 'help' => null, 'switch' => true, 'id' => null, 'wrapperClass' => 'mb-3', 'value' => '1'])
@php
    $htmlName = preg_replace('/\.([^.]+)/', '[$1]', $name);
    $id ??= 'f_'.str_replace(['.', '[', ']'], '_', $name);
    $isChecked = old() ? (bool) old($name) : (bool) $checked;
@endphp
<div class="form-check {{ $switch ? 'form-switch' : '' }} {{ $wrapperClass }}">
    {{-- Hidden 0 so unchecking actually submits false. --}}
    <input type="hidden" name="{{ $htmlName }}" value="0">
    <input class="form-check-input @error($name) is-invalid @enderror" type="checkbox" role="{{ $switch ? 'switch' : 'checkbox' }}"
        id="{{ $id }}" name="{{ $htmlName }}" value="{{ $value }}" @checked($isChecked) {{ $attributes }}>
    <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if ($help)<div class="form-text mt-0">{{ $help }}</div>@endif
</div>
