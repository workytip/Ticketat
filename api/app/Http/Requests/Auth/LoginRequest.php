<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;

final class LoginRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'deviceName' => ['required', 'string', 'max:255'],
        ];
    }
}
