<?php

namespace Database\Factories;

use App\Models\Seat;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seat>
 */
class SeatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'row' => 'A',
            'number' => fake()->unique()->numberBetween(1, 500),
        ];
    }
}
