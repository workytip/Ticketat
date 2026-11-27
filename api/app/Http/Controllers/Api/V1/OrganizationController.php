<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\MembershipService;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Docs: docs/api/organizations.md
 */
final class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizations,
        private readonly MembershipService $memberships,
    ) {}

    /**
     * Organizations the signed-in user belongs to, with their role in each.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return OrganizationResource::collection($this->organizations->forUser($request->user()));
    }

    /**
     * Create an organization; the creator becomes its owner (201).
     */
    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $organization = $this->organizations->create($request->user(), $request->prepareForDatabase());

        return (new OrganizationResource($organization))->response()->setStatusCode(201);
    }

    /**
     * Membership is already checked by the ResolveOrganization middleware.
     */
    public function show(Request $request, Organization $organization): OrganizationResource
    {
        return new OrganizationResource($this->withUserRole($organization, $request));
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): OrganizationResource
    {
        $organization = $this->organizations->update($organization, $request->prepareForDatabase());

        return new OrganizationResource($this->withUserRole($organization, $request));
    }

    private function withUserRole(Organization $organization, Request $request): Organization
    {
        return $organization->setAttribute(
            'current_user_role',
            $this->memberships->roleOf($organization, $request->user())?->value,
        );
    }
}
