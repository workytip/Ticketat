<?php

namespace App\Http\Resources;

use App\Actions\Venue\CreateVenue;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Section
 */
final class SectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'rows' => CreateVenue::rowLabels($this->rows),
            'seatsPerRow' => $this->seats_per_row,
            'seatCount' => $this->whenCounted('seats'),
        ];
    }
}
