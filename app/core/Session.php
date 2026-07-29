<?php
namespace App\Core;

/**
 * Session Manager
 */
class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started) {
            return;
        }

        // Session configuration
        $sessionPath = STORAGE_PATH . '/sessions';
        if (!is_dir($sessionPath)) {
            mkdir($sessionPath, 0755, true);
        }

        ini_set('session.save_path', $sessionPath);
        ini_set('session.gc_maxlifetime', defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 86400);
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        self::$started = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        $flash = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $flash;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        self::$started = false;
    }
}
