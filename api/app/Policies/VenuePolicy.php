<?php

namespace App\Policies;

use App\Enums\OrganizationPermission;
use App\Models\User;
use App\Models\Venue;

/**
 * Venue permissions inside the current organization. Tenant isolation is
 * already guaranteed by OrganizationScope (another organization's venue is a 404).
 */
final class VenuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(OrganizationPermission::ViewVenues->value);
    }

    public function view(User $user, Venue $venue): bool
    {
        return $user->can(OrganizationPermission::ViewVenues->value);
    }

    public function create(User $user): bool
    {
        return $user->can(OrganizationPermission::ManageVenues->value);
    }

    public function update(User $user, Venue $venue): bool
    {
        return $user->can(OrganizationPermission::ManageVenues->value);
    }

    public function delete(User $user, Venue $venue): bool
    {
        return $user->can(OrganizationPermission::ManageVenues->value);
    }
}
