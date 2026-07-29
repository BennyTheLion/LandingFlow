<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Session;
use App\Core\Exceptions\UnauthorizedException;
use App\Core\Exceptions\ForbiddenException;

/**
 * Authentication Middleware
 */
class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (!Session::has('user')) {
            Session::set('intended_url', $request->getUri());
            throw new UnauthorizedException();
        }
    }
}

/**
 * Guest Middleware - for login/register pages
 */
class GuestMiddleware
{
    public function handle(Request $request): void
    {
        if (Session::has('user')) {
            header('Location: ' . $request->getBaseUrl() . '/admin');
            exit;
        }
    }
}

/**
 * Admin Middleware
 */
class AdminMiddleware
{
    public function handle(Request $request): void
    {
        if (!Session::has('user')) {
            Session::set('intended_url', $request->getUri());
            throw new UnauthorizedException();
        }

        $user = Session::get('user');
        if (($user['role_id'] ?? '') !== 1) {
            throw new ForbiddenException();
        }
    }
}
