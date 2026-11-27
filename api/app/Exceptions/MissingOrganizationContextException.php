<?php

namespace App\Exceptions;

use LogicException;

/**
 * A tenant-owned model was queried or created without a current organization.
 *
 * This is a programming error, not a user error: the route is missing the
 * `organization` middleware, or a job did not wrap its work in inOrganization().
 * Failing loudly is safer than silently returning every organization's rows.
 */
final class MissingOrganizationContextException extends LogicException
{
    public function __construct()
    {
        parent::__construct(
            'No current organization. Add the `organization` middleware to the route, '
            .'wrap the code in MembershipService::inOrganization(), '
            .'or use withoutGlobalScope(OrganizationScope::class) for intentional cross-organization queries.'
        );
    }
}
