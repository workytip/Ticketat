<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\AddMemberRequest;
use App\Http\Requests\Organization\UpdateMemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Organization;
use App\Services\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Members of an organization. {member} is the member's user id.
 *
 * Docs: docs/api/organizations.md
 */
final class OrganizationMemberController extends Controller
{
    public function __construct(private readonly MembershipService $memberships) {}

    public function index(Organization $organization): AnonymousResourceCollection
    {
        Gate::authorize('viewMembers', $organization);

        return MemberResource::collection($this->memberships->members($organization));
    }

    /**
     * Add an existing user by email with a role (201).
     */
    public function store(AddMemberRequest $request, Organization $organization): JsonResponse
    {
        $member = $this->memberships->add($organization, $request->validated('email'), $request->role());

        return (new MemberResource($member->load('roles')))->response()->setStatusCode(201);
    }

    public function update(UpdateMemberRequest $request, Organization $organization): MemberResource
    {
        $member = $this->memberships->changeRole($organization, $request->member(), $request->role());

        return new MemberResource($member->load('roles'));
    }

    public function destroy(Organization $organization, string $member): JsonResponse
    {
        Gate::authorize('manageMembers', $organization);
        $user = $this->memberships->findMember($organization, $member);
        Gate::authorize('manageMember', [$organization, $user]);

        $this->memberships->remove($organization, $user);

        return response()->json(['message' => __('organizations.member_removed')]);
    }
}
