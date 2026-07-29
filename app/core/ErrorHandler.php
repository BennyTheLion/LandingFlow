<?php
namespace App\Core;

/**
 * Error Handler
 */
class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException(\Throwable $e): void
    {
        self::log($e);

        http_response_code(500);

        if (APP_DEBUG) {
            echo '<h1>שגיאה</h1>';
            echo '<p><strong>' . get_class($e) . ':</strong> ' . $e->getMessage() . '</p>';
            echo '<p>File: ' . $e->getFile() . ':' . $e->getLine() . '</p>';
            echo '<pre>' . $e->getTraceAsString() . '</pre>';
        } else {
            self::renderErrorPage(500);
        }
        exit;
    }

    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            self::log(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
            
            if (!APP_DEBUG) {
                self::renderErrorPage(500);
            }
        }
    }

    private static function log(\Throwable $e): void
    {
        $logDir = STORAGE_PATH . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $message = sprintf(
            "[%s] %s: %s in %s:%d\nStack trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        $logFile = $logDir . '/error_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    }

    private static function renderErrorPage(int $code): void
    {
        $viewFile = APP_PATH . '/views/errors/' . $code . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo '<!DOCTYPE html><html dir="rtl" lang="he"><head><meta charset="UTF-8"><title>שגיאה</title></head><body style="text-align:center;padding:50px;font-family:sans-serif;"><h1>אופס! משהו השתבש</h1><p>אנא נסה שנית מאוחר יותר.</p></body></html>';
        }
    }
}
