<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterCustomer;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, RegisterCustomer $register): RedirectResponse
    {
        $user = $register($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('menu.index'))->with('success', "Welcome, {$user->name}! Your account is ready.");
    }
}
