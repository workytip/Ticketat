<?php

namespace App\Enums;

/**
 * OAuth providers users can sign in with.
 * Adding a provider: add a case here and its credentials in config/services.php.
 */
enum SocialProvider: string
{
    case Google = 'google';
}
