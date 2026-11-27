<?php

namespace App\Actions\Venue;

use App\Models\Section;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;

/**
 * Creates a venue with its sections and generates every seat:
 * rows are lettered A, B, C... and seats numbered 1..seatsPerRow in each row.
 * A section with 3 rows of 10 seats gets A1..A10, B1..B10, C1..C10.
 */
final class CreateVenue
{
    /** Seats inserted per query, to keep queries small for large halls. */
    private const INSERT_CHUNK = 500;

    /**
     * @param  array{name: string, address?: string|null, city: string, sections: list<array{name: string, rows: int, seats_per_row: int}>}  $data
     */
    public function handle(array $data): Venue
    {
        return DB::transaction(function () use ($data): Venue {
            $venue = Venue::create([
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
                'city' => $data['city'],
            ]);

            foreach (array_values($data['sections']) as $index => $sectionData) {
                $section = $venue->sections()->create([
                    'name' => $sectionData['name'],
                    'rows' => $sectionData['rows'],
                    'seats_per_row' => $sectionData['seats_per_row'],
                    'sort_order' => $index + 1,
                ]);

                $this->generateSeats($section);
            }

            return $venue;
        });
    }

    private function generateSeats(Section $section): void
    {
        $seats = [];

        foreach (self::rowLabels($section->rows) as $row) {
            for ($number = 1; $number <= $section->seats_per_row; $number++) {
                $seats[] = ['section_id' => $section->id, 'row' => $row, 'number' => $number];
            }
        }

        foreach (array_chunk($seats, self::INSERT_CHUNK) as $chunk) {
            DB::table('seats')->insert($chunk);
        }
    }

    /**
     * @return list<string> A, B, C... (max 26 rows, enforced by validation)
     */
    public static function rowLabels(int $rows): array
    {
        return array_slice(range('A', 'Z'), 0, $rows);
    }
}
