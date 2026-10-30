{{-- Date range filter shared by the dashboard and analytics. --}}
<form method="GET" class="d-flex flex-wrap align-items-end gap-2" aria-label="Date range">
    <div>
        <label for="range" class="form-label small mb-1">Period</label>
        <select id="range" name="range" class="form-select form-select-sm" data-range-select>
            @foreach (\App\Support\DateRange::PRESETS as $value => $label)
                <option value="{{ $value }}" @selected($range->preset === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div data-custom @class(['d-flex gap-2 align-items-end', 'd-none' => $range->preset !== 'custom'])>
        <div><label for="from" class="form-label small mb-1">From</label><input type="date" id="from" name="from" value="{{ $range->from->toDateString() }}" max="{{ now()->toDateString() }}" class="form-control form-control-sm"></div>
        <div><label for="to" class="form-label small mb-1">To</label><input type="date" id="to" name="to" value="{{ $range->to->toDateString() }}" max="{{ now()->toDateString() }}" class="form-control form-control-sm"></div>
        <button class="btn btn-sm btn-primary">Apply</button>
    </div>
</form>
