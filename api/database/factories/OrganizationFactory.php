<?php

namespace Database\Factories;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }

    /**
     * Add a member with the given role: Organization::factory()->withMember($user, OrganizationRole::Owner).
     */
    public function withMember(User $user, OrganizationRole $role): static
    {
        return $this->afterCreating(function (Organization $organization) use ($user, $role) {
            $previous = getPermissionsTeamId();
            setPermissionsTeamId($organization->id);
            $user->unsetRelation('roles')->assignRole($role->value);
            setPermissionsTeamId($previous);
        });
    }
}
