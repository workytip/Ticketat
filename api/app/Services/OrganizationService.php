<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class OrganizationService
{
    public function __construct(private readonly MembershipService $memberships) {}

    /**
     * Organizations the user belongs to, each with the user's role in
     * `current_user_role`, in a single query.
     *
     * @return Collection<int, Organization>
     */
    public function forUser(User $user): Collection
    {
        return Organization::query()
            ->join('model_has_roles', function ($join) use ($user) {
                $join->on('model_has_roles.organization_id', '=', 'organizations.id')
                    ->where('model_has_roles.model_type', $user->getMorphClass())
                    ->where('model_has_roles.model_id', $user->getKey());
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select('organizations.*', 'roles.name as current_user_role')
            ->orderBy('organizations.name')
            ->get();
    }

    /**
     * Create an organization; the creator becomes its owner.
     *
     * @param  array{name: string, slug?: string|null}  $data
     */
    public function create(User $creator, array $data): Organization
    {
        return DB::transaction(function () use ($creator, $data): Organization {
            $organization = Organization::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? $this->uniqueSlug($data['name']),
            ]);

            $this->memberships->inOrganization($organization, fn () => $creator->assignRole(OrganizationRole::Owner->value));

            return $organization->setAttribute('current_user_role', OrganizationRole::Owner->value);
        });
    }

    /**
     * @param  array{name?: string, slug?: string}  $data
     */
    public function update(Organization $organization, array $data): Organization
    {
        $organization->update($data);

        return $organization;
    }

    /**
     * "Cairo Jazz" → cairo-jazz, then cairo-jazz-2, cairo-jazz-3... if taken.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'organization';
        $slug = $base;

        for ($i = 2; Organization::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
