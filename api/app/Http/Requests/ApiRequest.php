<?php

namespace App\Http\Requests;

use App\Support\CaseConverter;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base class for every API request.
 *
 * Rules are written in camelCase (the API convention), and
 * prepareForDatabase() returns the validated input in snake_case
 * (the database convention), including nested arrays.
 */
abstract class ApiRequest extends FormRequest
{
    /**
     * @return array<array-key, mixed>
     */
    public function prepareForDatabase(): array
    {
        return CaseConverter::toSnake($this->validated());
    }
}
