<?php

namespace App\Exceptions\Organization;

use App\Exceptions\DomainException;

final class AlreadyMemberException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('organizations.already_member'));
    }

    public function status(): int
    {
        return 422;
    }
}
