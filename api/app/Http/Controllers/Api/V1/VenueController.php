<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Venue\CreateVenue;
use App\Http\Controllers\Controller;
use App\Http\Requests\Venue\StoreVenueRequest;
use App\Http\Requests\Venue\UpdateVenueRequest;
use App\Http\Resources\VenueResource;
use App\Models\Organization;
use App\Models\Venue;
use App\Services\VenueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Venues of the organization in the URL. {venue} is resolved through the tenant
 * scope, so another organization's venue id returns 404.
 *
 * Docs: docs/api/venues.md
 */
final class VenueController extends Controller
{
    public function __construct(private readonly VenueService $venues) {}

    public function index(Organization $organization): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Venue::class);

        return VenueResource::collection($this->venues->list());
    }

    /**
     * Create a venue and generate its seats (201).
     */
    public function store(StoreVenueRequest $request, Organization $organization, CreateVenue $action): JsonResponse
    {
        $venue = $action->handle($request->prepareForDatabase());

        return (new VenueResource($this->venues->load($venue)))->response()->setStatusCode(201);
    }

    public function show(Organization $organization, Venue $venue): VenueResource
    {
        Gate::authorize('view', $venue);

        return new VenueResource($this->venues->load($venue));
    }

    public function update(UpdateVenueRequest $request, Organization $organization, Venue $venue): VenueResource
    {
        return new VenueResource($this->venues->update($venue, $request->prepareForDatabase()));
    }

    public function destroy(Organization $organization, Venue $venue): JsonResponse
    {
        Gate::authorize('delete', $venue);

        $this->venues->delete($venue);

        return response()->json(['message' => __('venues.deleted')]);
    }
}
