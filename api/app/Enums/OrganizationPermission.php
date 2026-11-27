<?php

namespace App\Enums;

/**
 * Permissions inside an organization. Stored in spatie's permissions table
 * by RolesAndPermissionsSeeder; checked with $user->can($permission->value)
 * while the organization is the current spatie team.
 */
enum OrganizationPermission: string
{
    case UpdateOrganization = 'organization.update';
    case ViewMembers = 'members.view';
    case ManageMembers = 'members.manage';
    case ViewVenues = 'venues.view';
    case ManageVenues = 'venues.manage';
}
