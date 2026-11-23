<?php

namespace App\Http\Requests\Supplier;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class InviteStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageStaff', $this->user()->supplier);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            $existing = User::where('email', $this->input('email'))->first();

            if ($existing?->supplier_id === $this->user()->supplier_id) {
                $validator->errors()->add('email', 'This person already works for your store.');
            } elseif ($existing?->supplier_id !== null) {
                $validator->errors()->add('email', 'This person already belongs to another store.');
            }
        }];
    }
}
