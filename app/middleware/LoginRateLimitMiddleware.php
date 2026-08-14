<?php
namespace App\Middleware;

/**
 * Stricter rate limit for authentication endpoints (login/register/password reset) —
 * the generic RateLimitMiddleware's 100 req/60s default is far too loose to stop
 * password brute-forcing.
 */
class LoginRateLimitMiddleware extends RateLimitMiddleware
{
    public function __construct()
    {
        parent::__construct(10, 900, 'auth');
    }
}
