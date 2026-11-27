<?php

use App\Exceptions\MissingOrganizationContextException;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\Venue;
use App\Services\MembershipService;

/**
 * The tenant scope must fail loudly when there is no current organization,
 * instead of returning (or writing) rows of every organization.
 */
it('refuses to query tenant data without a current organization', function () {
    Venue::factory()->create();

    Venue::count();
})->throws(MissingOrganizationContextException::class);

it('refuses to create tenant data without a current organization', function () {
    Venue::create(['name' => 'X', 'city' => 'Cairo']);
})->throws(MissingOrganizationContextException::class);

it('scopes queries and fills organization_id inside inOrganization()', function () {
    [$mine, $other] = Organization::factory()->count(2)->create();
    Venue::factory()->for($other)->create();

    app(MembershipService::class)->inOrganization($mine, function () use ($mine) {
        $venue = Venue::create(['name' => 'Mine', 'city' => 'Cairo']);

        expect($venue->organization_id)->toBe($mine->id)
            ->and(Venue::pluck('name')->all())->toBe(['Mine']);
    });
});

it('restores the previous organization after inOrganization()', function () {
    $organization = Organization::factory()->create();

    app(MembershipService::class)->inOrganization($organization, fn () => null);

    Venue::count();
})->throws(MissingOrganizationContextException::class);

it('allows intentional cross-organization queries', function () {
    Venue::factory()->count(2)->create();

    expect(Venue::withoutGlobalScope(OrganizationScope::class)->count())->toBe(2);
});
