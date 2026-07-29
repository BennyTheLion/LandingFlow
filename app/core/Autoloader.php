<?php
namespace App\Core;

/**
 * PSR-4 Autoloader
 */
class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register([self::class, 'load']);
    }

    public static function load(string $class): void
    {
        // Remove namespace prefix
        $prefix = 'App\\';

        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $parts = explode('\\', $relativeClass);
        $fileName = array_pop($parts) . '.php';

        // Namespace casing (e.g. App\Core) doesn't always match directory
        // casing on disk (e.g. app/core) - harmless on case-insensitive
        // filesystems (Windows) but fatal on case-sensitive ones (Linux).
        // Try the exact path first, then fall back to a case-insensitive scan.
        $dir = rtrim(APP_PATH, '/');
        foreach ($parts as $part) {
            $dir = self::resolveEntry($dir, $part, false);
            if ($dir === null) {
                return;
            }
        }

        $file = self::resolveEntry($dir, $fileName, true);
        if ($file !== null) {
            require $file;
        }
    }

    private static function resolveEntry(string $dir, string $name, bool $isFile): ?string
    {
        $direct = $dir . '/' . $name;
        if ($isFile ? is_file($direct) : is_dir($direct)) {
            return $direct;
        }

        $entries = @scandir($dir);
        if ($entries === false) {
            return null;
        }

        foreach ($entries as $entry) {
            if (strcasecmp($entry, $name) === 0) {
                $candidate = $dir . '/' . $entry;
                if ($isFile ? is_file($candidate) : is_dir($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }
}
