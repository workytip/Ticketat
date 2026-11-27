<?php

namespace App\Support;

use App\Exceptions\MissingOrganizationContextException;
use App\Models\Organization;

/**
 * The tenant of the current request or job.
 *
 * Registered as a scoped singleton, so it is reset for every request and queued job.
 * Set by the ResolveOrganization middleware (requests) or MembershipService::inOrganization()
 * (jobs, commands, tests). Read by OrganizationScope to filter tenant queries.
 */
final class CurrentOrganization
{
    private ?Organization $organization = null;

    public function set(?Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function get(): ?Organization
    {
        return $this->organization;
    }

    /**
     * @throws MissingOrganizationContextException
     */
    public function id(): int
    {
        return $this->organization?->id ?? throw new MissingOrganizationContextException;
    }
}
