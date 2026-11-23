<?php

namespace App\Http\Requests\Supplier;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // guests only — enforced by the `guest` middleware on the route
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'store_name' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
