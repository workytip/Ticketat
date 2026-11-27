<?php

namespace App\Exceptions\Auth;

use App\Exceptions\DomainException;

final class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('auth.failed'));
    }

    public function status(): int
    {
        return 401;
    }
}
