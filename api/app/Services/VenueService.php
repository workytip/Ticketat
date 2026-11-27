<?php

namespace App\Services;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Collection;

/**
 * Venues of the current organization (queries are tenant-scoped by OrganizationScope).
 */
final class VenueService
{
    /**
     * @return Collection<int, Venue>
     */
    public function list(): Collection
    {
        return Venue::query()
            ->withCount('seats')
            ->orderBy('name')
            ->get();
    }

    public function load(Venue $venue): Venue
    {
        return $venue->load(['sections' => fn ($query) => $query->withCount('seats')])->loadCount('seats');
    }

    /**
     * Only name, address and city. Sections and seats are fixed once created, because
     * events (next phase) sell tickets for those exact seats.
     *
     * @param  array{name?: string, address?: string|null, city?: string}  $data
     */
    public function update(Venue $venue, array $data): Venue
    {
        $venue->update($data);

        return $this->load($venue);
    }

    public function delete(Venue $venue): void
    {
        $venue->delete();
    }
}
