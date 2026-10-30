<div class="row g-2 align-items-center mb-2" data-repeater-row>
    <input type="hidden" name="variations[{{ $i }}][id]" value="{{ $v['id'] ?? '' }}">
    <div class="col-2 col-md-1 text-center"><input class="form-check-input" type="radio" name="default_variation" value="{{ $i }}" @checked($checked) aria-label="Default size"></div>
    <div class="col-10 col-md-4"><input type="text" name="variations[{{ $i }}][name]" value="{{ $v['name'] ?? '' }}" class="form-control @error("variations.$i.name") is-invalid @enderror" placeholder="e.g. Large" aria-label="Size name" maxlength="80"></div>
    <div class="col-5 col-md-3"><div class="input-group"><span class="input-group-text">$</span><input type="number" step="0.01" name="variations[{{ $i }}][price_adjustment]" value="{{ $v['price_adjustment'] ?? '0.00' }}" class="form-control @error("variations.$i.price_adjustment") is-invalid @enderror" aria-label="Price change"></div></div>
    <div class="col-5 col-md-3"><div class="input-group"><span class="input-group-text">×</span><input type="number" step="0.05" min="0.1" name="variations[{{ $i }}][recipe_multiplier]" value="{{ $v['recipe_multiplier'] ?? '1.00' }}" class="form-control @error("variations.$i.recipe_multiplier") is-invalid @enderror" aria-label="Ingredient multiplier"></div></div>
    <div class="col-2 col-md-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-repeater-remove aria-label="Remove size"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
</div>
