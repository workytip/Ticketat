<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
| Bind every Feature test to the Laravel TestCase so they can use the
| application helpers ($this->get(), $this->actingAs(), ...), and reset
| the database between tests.
*/
pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

/**
 * A new user who is a member of $organization with $role.
 */
function memberOf(Organization $organization, OrganizationRole $role): User
{
    $user = User::factory()->create();
    $previous = getPermissionsTeamId();
    setPermissionsTeamId($organization->id);
    $user->assignRole($role->value);
    setPermissionsTeamId($previous);

    return $user;
}
