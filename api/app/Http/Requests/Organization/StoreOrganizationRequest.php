<?php

namespace App\Http\Requests\Organization;

use App\Http\Requests\ApiRequest;

final class StoreOrganizationRequest extends ApiRequest
{
    /**
     * Any signed-in user can create an organization and becomes its owner.
     */
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'alpha_dash:ascii', 'lowercase', 'max:100', 'unique:organizations,slug'],
        ];
    }
}
