<?php

namespace App\Exceptions\Auth;

use App\Exceptions\DomainException;
use Throwable;

/**
 * The provider rejected the authorization code (expired, reused or invalid).
 */
final class SocialAuthenticationFailedException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(__('auth.social_failed'), previous: $previous);
    }

    public function status(): int
    {
        return 401;
    }
}
