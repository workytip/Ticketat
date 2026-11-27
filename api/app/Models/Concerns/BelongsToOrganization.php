<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Support\CurrentOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * For top-level tenant-owned models (venues, events...): the table has `organization_id`.
 *
 * - Every query is limited to the current organization (OrganizationScope).
 * - New rows get the current organization automatically.
 *
 * Child rows (sections, seats) do not use this trait: they are only reached through
 * their scoped parent, never queried on their own.
 *
 * @mixin Model
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function (Model $model): void {
            $model->organization_id ??= app(CurrentOrganization::class)->id();
        });
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
