<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->owner = memberOf($this->organization, OrganizationRole::Owner);
    $this->url = "/api/v1/organizations/{$this->organization->slug}/members";
});

describe('list', function () {
    it('shows members with their roles to every member', function (OrganizationRole $role) {
        $this->actingAs(memberOf($this->organization, $role))
            ->getJson($this->url)
            ->assertOk()
            ->assertJsonFragment(['email' => $this->owner->email, 'role' => 'owner']);
    })->with(OrganizationRole::cases());

    it('does not include members of other organizations', function () {
        memberOf(Organization::factory()->create(), OrganizationRole::Owner);

        $this->actingAs($this->owner)->getJson($this->url)->assertJsonCount(1, 'data');
    });
});

describe('add', function () {
    /**
     * Who can give which role to a new member.
     */
    it('follows the role matrix', function (OrganizationRole $actor, OrganizationRole $newRole, int $status) {
        $user = $actor === OrganizationRole::Owner ? $this->owner : memberOf($this->organization, $actor);
        $newcomer = User::factory()->create();

        $this->actingAs($user)
            ->postJson($this->url, ['email' => $newcomer->email, 'role' => $newRole->value])
            ->assertStatus($status);
    })->with([
        'owner adds owner' => [OrganizationRole::Owner, OrganizationRole::Owner, 201],
        'owner adds manager' => [OrganizationRole::Owner, OrganizationRole::Manager, 201],
        'owner adds staff' => [OrganizationRole::Owner, OrganizationRole::Staff, 201],
        'manager adds owner' => [OrganizationRole::Manager, OrganizationRole::Owner, 403],
        'manager adds manager' => [OrganizationRole::Manager, OrganizationRole::Manager, 403],
        'manager adds staff' => [OrganizationRole::Manager, OrganizationRole::Staff, 201],
        'staff adds staff' => [OrganizationRole::Staff, OrganizationRole::Staff, 403],
    ]);

    it('rejects an existing member', function () {
        $this->actingAs($this->owner)
            ->postJson($this->url, ['email' => $this->owner->email, 'role' => 'staff'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This user is already a member of the organization.');
    });

    it('validates the email and role', function () {
        $this->actingAs($this->owner)
            ->postJson($this->url, ['email' => 'nobody@example.com', 'role' => 'admin'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'role']);
    });
});

describe('change role', function () {
    it('lets the owner promote staff to manager', function () {
        $staff = memberOf($this->organization, OrganizationRole::Staff);

        $this->actingAs($this->owner)
            ->patchJson("{$this->url}/{$staff->id}", ['role' => 'manager'])
            ->assertOk()
            ->assertJsonPath('data.role', 'manager');
    });

    it('forbids a manager from promoting staff to owner', function () {
        $staff = memberOf($this->organization, OrganizationRole::Staff);

        $this->actingAs(memberOf($this->organization, OrganizationRole::Manager))
            ->patchJson("{$this->url}/{$staff->id}", ['role' => 'owner'])
            ->assertForbidden();
    });

    it('forbids a manager from changing another manager', function () {
        $otherManager = memberOf($this->organization, OrganizationRole::Manager);

        $this->actingAs(memberOf($this->organization, OrganizationRole::Manager))
            ->patchJson("{$this->url}/{$otherManager->id}", ['role' => 'staff'])
            ->assertForbidden();
    });

    it('keeps at least one owner', function () {
        $this->actingAs($this->owner)
            ->patchJson("{$this->url}/{$this->owner->id}", ['role' => 'manager'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The organization must keep at least one owner.');
    });

    it('lets an owner step down when another owner exists', function () {
        memberOf($this->organization, OrganizationRole::Owner);

        $this->actingAs($this->owner)
            ->patchJson("{$this->url}/{$this->owner->id}", ['role' => 'manager'])
            ->assertOk();
    });

    it('returns 404 for a user who is not a member', function () {
        $outsider = User::factory()->create();

        $this->actingAs($this->owner)
            ->patchJson("{$this->url}/{$outsider->id}", ['role' => 'staff'])
            ->assertNotFound();
    });
});

describe('remove', function () {
    it('lets a manager remove staff', function () {
        $staff = memberOf($this->organization, OrganizationRole::Staff);

        $this->actingAs(memberOf($this->organization, OrganizationRole::Manager))
            ->deleteJson("{$this->url}/{$staff->id}")
            ->assertOk();

        $this->actingAs($staff)
            ->getJson("/api/v1/organizations/{$this->organization->slug}")
            ->assertNotFound();
    });

    it('forbids a manager from removing the owner', function () {
        $this->actingAs(memberOf($this->organization, OrganizationRole::Manager))
            ->deleteJson("{$this->url}/{$this->owner->id}")
            ->assertForbidden();
    });

    it('does not remove the last owner', function () {
        $this->actingAs($this->owner)
            ->deleteJson("{$this->url}/{$this->owner->id}")
            ->assertUnprocessable();
    });

    it('only removes the membership in this organization', function () {
        $other = Organization::factory()->create();
        $staff = memberOf($this->organization, OrganizationRole::Staff);
        $previous = getPermissionsTeamId();
        setPermissionsTeamId($other->id);
        $staff->assignRole('manager');
        setPermissionsTeamId($previous);

        $this->actingAs($this->owner)->deleteJson("{$this->url}/{$staff->id}")->assertOk();

        $this->actingAs($staff)
            ->getJson("/api/v1/organizations/{$other->slug}")
            ->assertOk()
            ->assertJsonPath('data.role', 'manager');
    });
});

it('returns 404 when an outsider manages members', function () {
    $this->actingAs(User::factory()->create())
        ->postJson($this->url, ['email' => $this->owner->email, 'role' => 'staff'])
        ->assertNotFound();
});
