<?php

namespace App\Http\Requests\Venue;

use App\Http\Requests\ApiRequest;

/**
 * Sections are not accepted here on purpose: seats are fixed once the venue exists.
 */
final class UpdateVenueRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('venue'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
        ];
    }
}
