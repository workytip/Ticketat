<?php

namespace App\Http\Resources;

use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Venue
 */
final class VenueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'seatCount' => $this->whenCounted('seats'),
            'sections' => SectionResource::collection($this->whenLoaded('sections')),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
