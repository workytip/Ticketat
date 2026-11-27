<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the response language from the Accept-Language header.
 */
final class SetLocaleFromHeader
{
    private const SUPPORTED = ['en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->headers->has('Accept-Language')) {
            app()->setLocale($request->getPreferredLanguage(self::SUPPORTED) ?? config('app.locale'));
        }

        return $next($request);
    }
}
