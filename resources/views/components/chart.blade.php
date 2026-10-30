{{-- Chart.js canvas; resources/js/admin.js reads the config. json_encode output is HTML-escaped by {{ }}. --}}
@props(['config', 'label', 'size' => null])
<div @class(['chart-box', $size])>
    <canvas aria-label="{{ $label }}" data-chart="{{ json_encode($config) }}"></canvas>
</div>
