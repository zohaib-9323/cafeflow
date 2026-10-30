<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\RegisterCustomer;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function register(RegisterRequest $request, RegisterCustomer $register): JsonResponse
    {
        $user = $register($request->validated());

        return $this->ok([
            'user' => (new UserResource($user))->resolve($request),
            'token' => $user->createToken($request->input('device_name', 'api'), ['*'], now()->addDays(30))->plainTextToken,
        ], 'Account created', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->input('email'))->first();

        // Hash check even when the user is missing, so response timing doesn't reveal which emails exist.
        $valid = Hash::check($request->input('password'), $user?->password ?? '$2y$12$'.str_repeat('a', 53));
        if (! $user || ! $valid) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }
        if (! $user->is_active) {
            throw ValidationException::withMessages(['email' => 'This account has been deactivated.']);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $this->ok([
            'user' => (new UserResource($user))->resolve($request),
            'token' => $user->createToken($request->input('device_name', 'api'), ['*'], now()->addDays(30))->plainTextToken,
            'token_type' => 'Bearer',
        ], 'Signed in');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->ok(new UserResource($request->user()->load('customer')));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok(null, 'Signed out');
    }
}
