<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.profile', ['user' => $request->user()->load('customer.addresses')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'regex:/^[0-9 +()\-.]{6,32}$/'],
            'marketing_opt_in' => ['sometimes', 'boolean'],
        ]);

        $user->update(['name' => $data['name'], 'email' => strtolower($data['email'])]);
        $user->customer()->updateOrCreate([], [
            'phone' => $data['phone'] ?? null,
            'marketing_opt_in' => $request->boolean('marketing_opt_in'),
        ]);

        return back()->with('success', 'Profile updated.');
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => $request->input('password')]);
        $request->session()->regenerate();

        return back()->with('success', 'Password changed.');
    }
}
