<?php

namespace App\Http\Requests\Organization;

use App\Enums\OrganizationRole;
use App\Http\Requests\ApiRequest;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Authorization runs twice: before validation (may this user manage members at all?)
 * and after it (may this user give this role?), because the second check needs a valid role.
 */
final class AddMemberRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageMembers', $this->organization());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'exists:users,email'],
            'role' => ['required', Rule::enum(OrganizationRole::class)],
        ];
    }

    protected function passedValidation(): void
    {
        Gate::authorize('assignRole', [$this->organization(), $this->role()]);
    }

    public function role(): OrganizationRole
    {
        return OrganizationRole::from($this->validated('role'));
    }

    private function organization(): Organization
    {
        return $this->route('organization');
    }
}
