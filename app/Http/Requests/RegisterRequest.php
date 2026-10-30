<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9 +()\-.]{6,32}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'marketing_opt_in' => ['sometimes', 'boolean'],
            'terms' => [$this->is('api/*') ? 'sometimes' : 'accepted'],
        ];
    }

    public function messages(): array
    {
        return ['terms.accepted' => 'Please accept the terms to create an account.'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }
}
