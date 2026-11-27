<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

it('creates an organization and makes the creator its owner', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/organizations', ['name' => 'Cairo Jazz'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'cairo-jazz')
        ->assertJsonPath('data.role', 'owner');

    $organization = Organization::sole();
    setPermissionsTeamId($organization->id);
    expect($user->hasRole('owner'))->toBeTrue();
});

it('generates a unique slug when the name is taken', function () {
    Organization::factory()->create(['slug' => 'cairo-jazz']);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/organizations', ['name' => 'Cairo Jazz'])
        ->assertJsonPath('data.slug', 'cairo-jazz-2');
});

it('rejects a taken or invalid custom slug', function (string $slug) {
    Organization::factory()->create(['slug' => 'taken']);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/organizations', ['name' => 'X', 'slug' => $slug])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);
})->with(['taken', 'Has Spaces', 'UPPER']);

it('requires authentication', function () {
    $this->postJson('/api/v1/organizations', ['name' => 'X'])->assertUnauthorized();
});

it('lists only my organizations with my role in each', function () {
    $user = User::factory()->create();
    Organization::factory()->withMember($user, OrganizationRole::Owner)->create(['name' => 'Alpha']);
    Organization::factory()->withMember($user, OrganizationRole::Staff)->create(['name' => 'Beta']);
    Organization::factory()->create(['name' => 'Not mine']);

    $this->actingAs($user)
        ->getJson('/api/v1/organizations')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Alpha')
        ->assertJsonPath('data.0.role', 'owner')
        ->assertJsonPath('data.1.role', 'staff');
});

it('shows an organization to its members', function () {
    $organization = Organization::factory()->create();
    $staff = memberOf($organization, OrganizationRole::Staff);

    $this->actingAs($staff)
        ->getJson("/api/v1/organizations/{$organization->slug}")
        ->assertOk()
        ->assertJsonPath('data.role', 'staff');
});

it('returns 404 to non-members, as if the organization did not exist', function () {
    $organization = Organization::factory()->create();

    $this->actingAs(User::factory()->create())
        ->getJson("/api/v1/organizations/{$organization->slug}")
        ->assertNotFound()
        ->assertExactJson(['message' => 'Not found.']);
});

it('lets owners and managers update the organization', function (OrganizationRole $role) {
    $organization = Organization::factory()->create();

    $this->actingAs(memberOf($organization, $role))
        ->patchJson("/api/v1/organizations/{$organization->slug}", ['name' => 'Renamed'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed');
})->with([OrganizationRole::Owner, OrganizationRole::Manager]);

it('forbids staff from updating the organization', function () {
    $organization = Organization::factory()->create();

    $this->actingAs(memberOf($organization, OrganizationRole::Staff))
        ->patchJson("/api/v1/organizations/{$organization->slug}", ['name' => 'Renamed'])
        ->assertForbidden();
});

it('uses the role of the organization in the URL, not another one', function () {
    $user = User::factory()->create();
    $ownedOrg = Organization::factory()->withMember($user, OrganizationRole::Owner)->create();
    $staffOrg = Organization::factory()->withMember($user, OrganizationRole::Staff)->create();

    $this->actingAs($user)
        ->patchJson("/api/v1/organizations/{$ownedOrg->slug}", ['name' => 'Mine'])
        ->assertOk();

    $this->actingAs($user)
        ->patchJson("/api/v1/organizations/{$staffOrg->slug}", ['name' => 'Not allowed'])
        ->assertForbidden();
});
