<?php

namespace Database\Factories;

use App\Models\Section;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'name' => fake()->unique()->word(),
            'rows' => 2,
            'seats_per_row' => 5,
            'sort_order' => 1,
        ];
    }
}
