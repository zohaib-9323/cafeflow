<div class="row g-2 mb-2" data-repeater-row>
    <div class="col-6"><input type="text" name="hours[{{ $i }}][days]" value="{{ $h['days'] ?? '' }}" class="form-control form-control-sm @error("hours.$i.days") is-invalid @enderror" placeholder="Saturday" aria-label="Days" maxlength="40"></div>
    <div class="col-4"><input type="text" name="hours[{{ $i }}][hours]" value="{{ $h['hours'] ?? '' }}" class="form-control form-control-sm @error("hours.$i.hours") is-invalid @enderror" placeholder="8:00 – 16:00" aria-label="Hours" maxlength="40"></div>
    <div class="col-2 text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-repeater-remove aria-label="Remove row"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
</div>
