<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class AcceptInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the secret token in the URL is the authorization
    }

    /** Logged-in invitees just click "Accept"; new people also pick a name and password. */
    public function rules(): array
    {
        if ($this->user()) {
            return [];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
