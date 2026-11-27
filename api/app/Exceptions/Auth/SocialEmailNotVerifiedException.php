<?php

namespace App\Exceptions\Auth;

use App\Exceptions\DomainException;

/**
 * The provider did not confirm the email, so it cannot be trusted
 * to create an account or link to an existing one.
 */
final class SocialEmailNotVerifiedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('auth.social_email_unverified'));
    }

    public function status(): int
    {
        return 422;
    }
}
