@props(['stack' => true])
{{-- Responsive table: scrolls on tablets, stacks into labelled rows on phones (add data-label to each td). --}}
<div class="table-responsive">
    <table {{ $attributes->class(['table align-middle', 'table-stack' => $stack]) }}>
        @isset($head)<thead><tr>{{ $head }}</tr></thead>@endisset
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
