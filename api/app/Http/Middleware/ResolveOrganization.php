<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Services\MembershipService;
use App\Support\CurrentOrganization;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The single place where the current tenant is set, for routes under
 * /organizations/{organization}:
 *
 * 1. Non-members get 404 (not 403), so we do not reveal that the organization exists.
 * 2. The organization becomes the current organization (OrganizationScope) and
 *    spatie's current team, so every $user->can(...) uses the role in this organization.
 *
 * It runs before SubstituteBindings (see bootstrap/app.php), so nested route models
 * like {venue} are already resolved through the tenant scope: another organization's
 * venue id returns 404.
 */
final class ResolveOrganization
{
    public function __construct(
        private readonly MembershipService $memberships,
        private readonly CurrentOrganization $current,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('organization');
        $organization = $slug instanceof Organization ? $slug : Organization::where('slug', $slug)->first();

        if (! $organization || ! $this->memberships->isMember($organization, $request->user())) {
            throw (new ModelNotFoundException)->setModel(Organization::class);
        }

        $request->route()->setParameter('organization', $organization);
        $this->current->set($organization);
        setPermissionsTeamId($organization->id);
        $request->user()->unsetRelation('roles')->unsetRelation('permissions');

        return $next($request);
    }
}
