<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
        }
    }

    public function rules(): array
    {
        $email = ['required', 'string', 'email', 'max:255'];
        $password = ['required', 'string', Password::min(10)->letters()->numbers(), 'confirmed'];

        return match ($this->route()->getActionMethod()) {
            'register' => ['name' => ['required', 'string', 'max:120'], 'email' => [...$email, 'unique:users,email'], 'department' => ['required_without:role', 'required_if:role,student', 'nullable', 'string', 'max:100'], 'role' => ['sometimes', Rule::enum(Role::class)], 'company_name' => ['required_if:role,recruiter', 'nullable', 'string', 'max:255'], 'password' => $password],
            'login' => ['email' => $email, 'password' => ['required', 'string'], 'role' => ['required', Rule::enum(Role::class)]],
            'forgot' => ['email' => $email],
            'reset' => ['email' => $email, 'token' => ['required', 'string'], 'password' => $password],
            'changePassword' => ['current_password' => ['required', 'current_password:sanctum'], 'password' => [...$password, 'different:current_password']],
            default => [],
        };
    }
}
