<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('verify', $this->route('user'));
    }

    public function rules(): array
    {
        return ['status' => 'required|in:verified,rejected', 'reason' => 'required_if:status,rejected|nullable|string|max:2000'];
    }
}
