<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * An event organizer: the tenant. Users belong to it through a spatie role
 * assigned with this organization as the team (model_has_roles.organization_id).
 */
#[Fillable(['name', 'slug'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    /**
     * Route model binding uses the slug: /organizations/cairo-jazz.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * All members, whatever their role.
     *
     * @return MorphToMany<User, $this>
     */
    public function members(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'model', 'model_has_roles', 'organization_id')->distinct();
    }
}
