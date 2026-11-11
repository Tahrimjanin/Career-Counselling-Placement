<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === Role::Admin;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => strtolower(trim($this->email))]);
        }
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:120', 'email' => 'required|email|max:255|unique:users,email', 'password' => ['required', Password::min(10)->letters()->numbers()], 'role' => ['required', Rule::enum(Role::class)], 'company_id' => 'required_if:role,recruiter|prohibited_unless:role,recruiter|nullable|exists:companies,id'];
    }
}
