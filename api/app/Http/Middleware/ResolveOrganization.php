<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Services\MembershipService;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The single place where the current tenant is set, for routes under
 * /organizations/{organization}:
 *
 * 1. Non-members get 404 (not 403), so we do not reveal that the organization exists.
 * 2. The organization becomes spatie's current team, so every $user->can(...)
 *    after this point uses the user's role in this organization only.
 */
final class ResolveOrganization
{
    public function __construct(private readonly MembershipService $memberships) {}

    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->route('organization');

        if (! $organization instanceof Organization || ! $this->memberships->isMember($organization, $request->user())) {
            throw (new ModelNotFoundException)->setModel(Organization::class);
        }

        setPermissionsTeamId($organization->id);
        $request->user()->unsetRelation('roles')->unsetRelation('permissions');

        return $next($request);
    }
}
