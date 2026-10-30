<x-modal id="adjustModal" title="Record stock change">
    <form method="POST" action="" id="adjustForm" novalidate>
        @csrf
        <p class="mb-3"><strong data-adjust-name></strong> · currently <span data-adjust-current></span></p>
        <x-select name="type" label="What happened?" :options="collect($movementTypes)->mapWithKeys(fn ($t) => [$t->value => $t->label()])" required />
        <x-input name="quantity" label="Quantity" type="number" step="0.001" min="0" required />
        <p class="form-text mt-n2 mb-3"><span data-adjust-hint></span> Unit: <span data-adjust-unit></span></p>
        <x-input name="reason" label="Reason / reference" maxlength="255" help="Required for waste and corrections, e.g. “Milk expired” or “Weekly count”." wrapperClass="mb-0" />
    </form>
    <x-slot:footer>
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="adjustForm" class="btn btn-primary">Record</button>
    </x-slot:footer>
</x-modal>
