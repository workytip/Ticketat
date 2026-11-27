<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\VenueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * A place where events happen, owned by an organization.
 */
#[Fillable(['name', 'address', 'city'])]
class Venue extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<VenueFactory> */
    use HasFactory;

    /**
     * @return HasMany<Section, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('sort_order');
    }

    /**
     * @return HasManyThrough<Seat, Section, $this>
     */
    public function seats(): HasManyThrough
    {
        return $this->hasManyThrough(Seat::class, Section::class);
    }
}
