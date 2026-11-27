<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Seat;
use App\Models\Venue;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->owner = memberOf($this->organization, OrganizationRole::Owner);
    $this->url = "/api/v1/organizations/{$this->organization->slug}/venues";
});

function venuePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Cairo Opera House',
        'address' => 'Gezira',
        'city' => 'Cairo',
        'sections' => [
            ['name' => 'VIP', 'rows' => 2, 'seatsPerRow' => 3],
            ['name' => 'Balcony', 'rows' => 3, 'seatsPerRow' => 4],
        ],
    ], $overrides);
}

describe('create', function () {
    it('creates the venue and generates every seat', function () {
        $this->actingAs($this->owner)
            ->postJson($this->url, venuePayload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Cairo Opera House')
            ->assertJsonPath('data.seatCount', 18)
            ->assertJsonPath('data.sections.0.name', 'VIP')
            ->assertJsonPath('data.sections.0.rows', ['A', 'B'])
            ->assertJsonPath('data.sections.0.seatCount', 6)
            ->assertJsonPath('data.sections.1.name', 'Balcony')
            ->assertJsonPath('data.sections.1.seatCount', 12);

        $vipSeats = Seat::whereHas('section', fn ($q) => $q->where('name', 'VIP'))->get()->pluck('label')->sort()->values()->all();
        expect($vipSeats)->toBe(['A1', 'A2', 'A3', 'B1', 'B2', 'B3']);
    });

    it('assigns the venue to the organization in the URL', function () {
        $this->actingAs($this->owner)->postJson($this->url, venuePayload())->assertCreated();

        expect(Venue::withoutGlobalScopes()->sole()->organization_id)->toBe($this->organization->id);
    });

    it('validates the seat layout', function (array $sections, string $error) {
        $this->actingAs($this->owner)
            ->postJson($this->url, venuePayload(['sections' => $sections]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$error]);
    })->with([
        'no sections' => [[], 'sections'],
        'more than 26 rows' => [[['name' => 'A', 'rows' => 27, 'seatsPerRow' => 1]], 'sections.0.rows'],
        'zero seats per row' => [[['name' => 'A', 'rows' => 1, 'seatsPerRow' => 0]], 'sections.0.seatsPerRow'],
        'duplicate section names' => [[
            ['name' => 'VIP', 'rows' => 1, 'seatsPerRow' => 1],
            ['name' => 'vip', 'rows' => 1, 'seatsPerRow' => 1],
        ], 'sections.1.name'],
    ]);
});

describe('permissions', function () {
    it('lets every member view venues', function () {
        $venue = Venue::factory()->for($this->organization)->create();
        $staff = memberOf($this->organization, OrganizationRole::Staff);

        $this->actingAs($staff)->getJson($this->url)->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($staff)->getJson("{$this->url}/{$venue->id}")->assertOk();
    });

    it('lets owners and managers manage venues', function (OrganizationRole $role) {
        $user = memberOf($this->organization, $role);

        $id = $this->actingAs($user)->postJson($this->url, venuePayload())->assertCreated()->json('data.id');
        $this->actingAs($user)->patchJson("{$this->url}/{$id}", ['name' => 'Renamed'])->assertOk();
        $this->actingAs($user)->deleteJson("{$this->url}/{$id}")->assertOk();
    })->with([OrganizationRole::Owner, OrganizationRole::Manager]);

    it('forbids staff from managing venues', function () {
        $venue = Venue::factory()->for($this->organization)->create();
        $staff = memberOf($this->organization, OrganizationRole::Staff);

        $this->actingAs($staff)->postJson($this->url, venuePayload())->assertForbidden();
        $this->actingAs($staff)->patchJson("{$this->url}/{$venue->id}", ['name' => 'X'])->assertForbidden();
        $this->actingAs($staff)->deleteJson("{$this->url}/{$venue->id}")->assertForbidden();
    });
});

describe('tenant isolation', function () {
    beforeEach(function () {
        $this->otherVenue = Venue::factory()->for(Organization::factory())->create();
    });

    it('does not list venues of other organizations', function () {
        Venue::factory()->for($this->organization)->create();

        $this->actingAs($this->owner)->getJson($this->url)->assertJsonCount(1, 'data');
    });

    it('returns 404 for another organization\'s venue', function (string $method) {
        $this->actingAs($this->owner)
            ->json($method, "{$this->url}/{$this->otherVenue->id}", ['name' => 'Hijacked'])
            ->assertNotFound()
            ->assertExactJson(['message' => 'Not found.']);

        expect(Venue::withoutGlobalScopes()->find($this->otherVenue->id)->name)->not->toBe('Hijacked');
    })->with(['GET', 'PATCH', 'DELETE']);
});

it('updates details but ignores sections', function () {
    $id = $this->actingAs($this->owner)->postJson($this->url, venuePayload())->json('data.id');

    $this->actingAs($this->owner)
        ->patchJson("{$this->url}/{$id}", ['city' => 'Giza', 'sections' => [['name' => 'New', 'rows' => 1, 'seatsPerRow' => 1]]])
        ->assertOk()
        ->assertJsonPath('data.city', 'Giza')
        ->assertJsonPath('data.seatCount', 18)
        ->assertJsonCount(2, 'data.sections');
});

it('deletes the venue with its sections and seats', function () {
    $id = $this->actingAs($this->owner)->postJson($this->url, venuePayload())->json('data.id');

    $this->actingAs($this->owner)
        ->deleteJson("{$this->url}/{$id}")
        ->assertOk()
        ->assertJsonPath('message', 'Venue deleted.');

    expect(Seat::count())->toBe(0);
});
