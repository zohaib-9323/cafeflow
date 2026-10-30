<div class="row g-2 align-items-center mb-2 pb-2 border-bottom" data-repeater-row>
    <input type="hidden" name="addons[{{ $i }}][id]" value="{{ $a['id'] ?? '' }}">
    <div class="col-md-4"><input type="text" name="addons[{{ $i }}][name]" value="{{ $a['name'] ?? '' }}" class="form-control @error("addons.$i.name") is-invalid @enderror" placeholder="e.g. Extra shot" aria-label="Add-on name" maxlength="80"></div>
    <div class="col-5 col-md-2"><div class="input-group"><span class="input-group-text">$</span><input type="number" step="0.01" min="0" name="addons[{{ $i }}][price]" value="{{ $a['price'] ?? '0.00' }}" class="form-control @error("addons.$i.price") is-invalid @enderror" aria-label="Add-on price"></div></div>
    <div class="col-7 col-md-3"><select name="addons[{{ $i }}][inventory_item_id]" class="form-select @error("addons.$i.inventory_item_id") is-invalid @enderror" aria-label="Uses ingredient">
        <option value="">No stock tracking</option>
        @foreach ($ingredientOptions as $id => $label)<option value="{{ $id }}" @selected((string) ($a['inventory_item_id'] ?? '') === (string) $id)>{{ $label }}</option>@endforeach
    </select></div>
    <div class="col-5 col-md-1"><input type="number" step="0.001" min="0" name="addons[{{ $i }}][inventory_quantity]" value="{{ isset($a['inventory_quantity']) ? (float) $a['inventory_quantity'] : '' }}" class="form-control @error("addons.$i.inventory_quantity") is-invalid @enderror" placeholder="Qty" aria-label="Ingredient quantity"></div>
    <div class="col-5 col-md-1"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input" role="switch" name="addons[{{ $i }}][is_active]" value="1" @checked(! empty($a['is_active'])) aria-label="Add-on active"></div></div>
    <div class="col-2 col-md-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-repeater-remove aria-label="Remove add-on"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
</div>
