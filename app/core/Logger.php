<?php
namespace App\Core;

/**
 * Simple file logger for debugging
 */
class Logger
{
    private static ?string $logFile = null;

    /**
     * Get log file path — one file per day
     */
    private static function logFile(): string
    {
        if (self::$logFile === null) {
            $dir = defined('LOG_PATH') ? LOG_PATH : STORAGE_PATH . '/logs/';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            self::$logFile = $dir . 'app-' . date('Y-m-d') . '.log';
        }
        return self::$logFile;
    }

    /**
     * Write a log entry
     */
    public static function write(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $ctx = $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = "[{$timestamp}] {$level}: {$message}{$ctx}" . PHP_EOL;

        @file_put_contents(self::logFile(), $line, FILE_APPEND | LOCK_EX);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::write('DEBUG', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }
}
