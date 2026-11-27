<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Base class for business-rule errors.
 *
 * Each subclass defines its HTTP status and translated message,
 * so controllers never need try/catch to build error responses.
 */
abstract class DomainException extends Exception
{
    abstract public function status(): int;

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], $this->status());
    }
}
