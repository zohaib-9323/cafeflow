<div class="row g-2 align-items-center mb-2" data-repeater-row>
    <div class="col-7 col-md-7"><select name="recipe[{{ $i }}][inventory_item_id]" class="form-select @error("recipe.$i.inventory_item_id") is-invalid @enderror" aria-label="Ingredient">
        <option value="">Choose ingredient…</option>
        @foreach ($ingredientOptions as $id => $label)<option value="{{ $id }}" @selected((string) ($r['inventory_item_id'] ?? '') === (string) $id)>{{ $label }}</option>@endforeach
    </select></div>
    <div class="col-3 col-md-4"><input type="number" step="0.001" min="0.001" name="recipe[{{ $i }}][quantity]" value="{{ isset($r['quantity']) ? (float) $r['quantity'] : '' }}" class="form-control @error("recipe.$i.quantity") is-invalid @enderror" placeholder="Quantity" aria-label="Quantity per item"></div>
    <div class="col-2 col-md-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-repeater-remove aria-label="Remove ingredient"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
</div>
