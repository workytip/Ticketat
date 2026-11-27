<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Exceptions\Organization\AlreadyMemberException;
use App\Exceptions\Organization\LastOwnerException;
use App\Models\Organization;
use App\Models\User;
use App\Support\CurrentOrganization;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Members of an organization and their roles.
 *
 * A member is a user who has a spatie role with organization_id = the organization.
 * Each member has exactly one role per organization. Every spatie call is wrapped
 * in inOrganization() so it reads and writes the right team, whatever the current one is.
 */
final class MembershipService
{
    public function roleOf(Organization $organization, User $user): ?OrganizationRole
    {
        return $this->inOrganization($organization, function () use ($user): ?OrganizationRole {
            $name = $user->unsetRelation('roles')->roles->first()?->name;

            return $name ? OrganizationRole::from($name) : null;
        });
    }

    public function isMember(Organization $organization, User $user): bool
    {
        return $this->roleOf($organization, $user) !== null;
    }

    /**
     * @return Collection<int, User>
     */
    public function members(Organization $organization): Collection
    {
        return $this->inOrganization($organization, fn () => $organization->members()
            ->with('roles')
            ->orderBy('name')
            ->get());
    }

    /**
     * @throws ModelNotFoundException when the user is not a member (404, so we do not reveal other users)
     */
    public function findMember(Organization $organization, int|string $userId): User
    {
        $user = User::find($userId);

        if (! $user || ! $this->isMember($organization, $user)) {
            throw (new ModelNotFoundException)->setModel(User::class, [$userId]);
        }

        return $user;
    }

    /**
     * @throws AlreadyMemberException
     */
    public function add(Organization $organization, string $email, OrganizationRole $role): User
    {
        $user = User::where('email', $email)->firstOrFail();

        if ($this->isMember($organization, $user)) {
            throw new AlreadyMemberException;
        }

        $this->inOrganization($organization, fn () => $user->assignRole($role->value));

        return $user;
    }

    /**
     * @throws LastOwnerException
     */
    public function changeRole(Organization $organization, User $member, OrganizationRole $role): User
    {
        return DB::transaction(function () use ($organization, $member, $role): User {
            if ($role !== OrganizationRole::Owner) {
                $this->ensureNotLastOwner($organization, $member);
            }

            $this->inOrganization($organization, fn () => $member->syncRoles([$role->value]));

            return $member;
        });
    }

    /**
     * @throws LastOwnerException
     */
    public function remove(Organization $organization, User $member): void
    {
        DB::transaction(function () use ($organization, $member): void {
            $this->ensureNotLastOwner($organization, $member);

            $this->inOrganization($organization, fn () => $member->syncRoles([]));
        });
    }

    /**
     * Run $callback with $organization as the current organization and spatie's current team,
     * then restore the previous ones. Use it in jobs, commands and anywhere outside a request.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function inOrganization(Organization $organization, Closure $callback): mixed
    {
        $current = app(CurrentOrganization::class);
        $previousOrganization = $current->get();
        $previousTeam = getPermissionsTeamId();

        $current->set($organization);
        setPermissionsTeamId($organization->id);

        try {
            return $callback();
        } finally {
            $current->set($previousOrganization);
            setPermissionsTeamId($previousTeam);
        }
    }

    /**
     * Locks the owners' rows so two requests cannot remove the last two owners at the same time.
     */
    private function ensureNotLastOwner(Organization $organization, User $member): void
    {
        if ($this->roleOf($organization, $member) !== OrganizationRole::Owner) {
            return;
        }

        $owners = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.organization_id', $organization->id)
            ->where('roles.name', OrganizationRole::Owner->value)
            ->lockForUpdate()
            ->count();

        if ($owners <= 1) {
            throw new LastOwnerException;
        }
    }
}
