<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;

final class SocialCallbackRequest extends ApiRequest
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
            'code' => ['required', 'string'],
            'deviceName' => ['required', 'string', 'max:255'],
        ];
    }
}
