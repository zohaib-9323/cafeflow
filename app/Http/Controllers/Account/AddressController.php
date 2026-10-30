<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'instructions' => ['nullable', 'string', 'max:500'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $customer = $request->user()->customer()->firstOrCreate([]);
        if ($request->boolean('is_default') || $customer->addresses()->doesntExist()) {
            $customer->addresses()->update(['is_default' => false]);
            $data['is_default'] = true;
        }
        $customer->addresses()->create($data);

        return back()->with('success', 'Address saved.');
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->customer_id === $request->user()->customer?->id, 404);
        $address->delete();

        return back()->with('success', 'Address removed.');
    }
}
