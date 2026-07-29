<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Session;

/**
 * CSRF Protection Middleware
 * Validates CSRF tokens on all POST requests.
 */
class CsrfMiddleware
{
    public function handle(Request $request): void
    {
        if ($request->isPost()) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (str_contains($uri, '/demo/build') || str_contains($uri, '/demo/request')) {
                return;
            }
            $token = $request->get(CSRF_TOKEN_NAME) 
                  ?? $request->getHeader('X-CSRF-Token');
            
            $sessionToken = Session::get(CSRF_TOKEN_NAME);
            
            if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
                throw new \App\Core\Exceptions\HttpException('אימות CSRF נכשל', 419);
            }
        }
    }
}
