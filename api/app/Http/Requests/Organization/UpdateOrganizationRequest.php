<?php

namespace App\Http\Requests\Organization;

use App\Http\Requests\ApiRequest;
use App\Models\Organization;
use Illuminate\Validation\Rule;

final class UpdateOrganizationRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->organization());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'alpha_dash:ascii', 'lowercase', 'max:100',
                Rule::unique('organizations', 'slug')->ignore($this->organization())],
        ];
    }

    private function organization(): Organization
    {
        return $this->route('organization');
    }
}
