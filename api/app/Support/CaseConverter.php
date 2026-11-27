<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Converts array keys between the API convention (camelCase)
 * and the database convention (snake_case).
 */
final class CaseConverter
{
    /**
     * Recursively convert string keys to snake_case.
     * Numeric keys (list indexes) are kept as they are.
     *
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function toSnake(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $newKey = is_string($key) ? Str::snake($key) : $key;
            $result[$newKey] = is_array($value) ? self::toSnake($value) : $value;
        }

        return $result;
    }
}
