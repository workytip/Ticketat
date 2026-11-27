<?php

namespace App\Http\Requests\Venue;

use App\Http\Requests\ApiRequest;
use App\Models\Venue;

final class StoreVenueRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Venue::class);
    }

    /**
     * Limits keep one request bounded: at most 20 sections x 26 rows x 100 seats.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'sections' => ['required', 'array', 'min:1', 'max:20'],
            'sections.*.name' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
            'sections.*.rows' => ['required', 'integer', 'min:1', 'max:26'],
            'sections.*.seatsPerRow' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Readable names in messages: "The rows field..." instead of "The sections.0.rows field...".
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sections.*.name' => 'section name',
            'sections.*.rows' => 'rows',
            'sections.*.seatsPerRow' => 'seats per row',
        ];
    }
}
