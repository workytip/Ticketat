<?php

namespace App\Http\Requests\Organization;

use App\Enums\OrganizationRole;
use App\Http\Requests\ApiRequest;
use App\Models\Organization;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Changing a role needs both: managing the member's current role
 * and being allowed to give the new one (a manager cannot promote staff to owner).
 */
final class UpdateMemberRequest extends ApiRequest
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
            'role' => ['required', Rule::enum(OrganizationRole::class)],
        ];
    }

    protected function passedValidation(): void
    {
        Gate::authorize('manageMember', [$this->organization(), $this->member()]);
        Gate::authorize('assignRole', [$this->organization(), $this->role()]);
    }

    public function role(): OrganizationRole
    {
        return OrganizationRole::from($this->validated('role'));
    }

    public function member(): User
    {
        return app(MembershipService::class)->findMember($this->organization(), $this->route('member'));
    }

    private function organization(): Organization
    {
        return $this->route('organization');
    }
}
