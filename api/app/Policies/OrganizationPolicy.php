<?php

namespace App\Policies;

use App\Enums\OrganizationPermission;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Services\MembershipService;

/**
 * Who can do what inside an organization.
 *
 * Simple checks use spatie permissions ($user->can('members.manage')), which read the
 * user's role in the current team set by ResolveOrganization. Rules that depend on the
 * other member's role (managers only manage staff) use OrganizationRole::canManage().
 */
final class OrganizationPolicy
{
    public function __construct(private readonly MembershipService $memberships) {}

    public function view(User $user, Organization $organization): bool
    {
        return $this->memberships->isMember($organization, $user);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->can(OrganizationPermission::UpdateOrganization->value);
    }

    public function viewMembers(User $user, Organization $organization): bool
    {
        return $user->can(OrganizationPermission::ViewMembers->value);
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return $user->can(OrganizationPermission::ManageMembers->value);
    }

    /**
     * Give $role to a new or existing member.
     */
    public function assignRole(User $user, Organization $organization, OrganizationRole $role): bool
    {
        return (bool) $this->memberships->roleOf($organization, $user)?->canManage($role);
    }

    /**
     * Change or remove $member, judged by the role $member has now.
     */
    public function manageMember(User $user, Organization $organization, User $member): bool
    {
        $memberRole = $this->memberships->roleOf($organization, $member);

        return $memberRole !== null
            && (bool) $this->memberships->roleOf($organization, $user)?->canManage($memberRole);
    }
}
