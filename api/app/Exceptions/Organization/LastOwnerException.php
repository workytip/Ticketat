<?php

namespace App\Exceptions\Organization;

use App\Exceptions\DomainException;

/**
 * An organization must always keep at least one owner,
 * otherwise nobody could manage it anymore.
 */
final class LastOwnerException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('organizations.last_owner'));
    }

    public function status(): int
    {
        return 422;
    }
}
