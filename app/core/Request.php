<?php
namespace App\Core;

/**
 * HTTP Request Handler
 */
class Request
{
    private array $params = [];
    private array $body = [];
    private array $headers = [];
    private string $method;
    private string $uri;
    private string $baseUrl;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $this->parseUri();
        $this->baseUrl = $this->detectBaseUrl();
        $this->headers = $this->collectHeaders();
        $this->body = $this->sanitizeInput();
    }

    private function parseUri(): string
    {
        // Prefer the ?url= param set by .htaccess rewrite
        if (!empty($_GET['url'])) {
            $uri = trim($_GET['url'], '/');
            return '/' . $uri;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = trim($uri, '/');
        
        // Remove base path if exists (e.g., /landingflow/public)
        // dirname() returns '\' instead of '/' on Windows when there's no
        // subdirectory - normalize so path comparisons work on both OSes.
        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptName !== '/' && str_starts_with($uri, trim($scriptName, '/'))) {
            $uri = substr($uri, strlen(trim($scriptName, '/')));
        }
        
        // Remove index.php from the start of the URI (e.g., /index.php/register → /register)
        $uri = trim($uri, '/');
        if (str_starts_with($uri, 'index.php/')) {
            $uri = substr($uri, strlen('index.php/'));
        } elseif ($uri === 'index.php' || str_ends_with($uri, '/index.php')) {
            $uri = '';
        }

        return '/' . trim($uri, '/');
    }

    private function detectBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // dirname() returns '\' instead of '/' on Windows when there's no
        // subdirectory - normalize so it doesn't leak into generated URLs.
        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

        return rtrim($protocol . '://' . $host . $scriptName, '/');
    }

    private function collectHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerKey = str_replace('_', '-', substr($key, 5));
                $headerKey = ucwords(strtolower($headerKey), '-');
                $headers[$headerKey] = $value;
            }
        }
        return $headers;
    }

    private function sanitizeInput(): array
    {
        $input = [];
        
        if ($this->method === 'GET') {
            $input = $_GET;
        } elseif ($this->method === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (str_contains($contentType, 'application/json')) {
                $json = json_decode(file_get_contents('php://input'), true);
                if (is_array($json)) {
                    $input = array_merge($_GET, $json);
                }
            } else {
                $input = $_POST;
            }
        }

        return $this->recursiveSanitize($input);
    }

    public function recursiveSanitize(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map([$this, 'recursiveSanitize'], $data);
        }
        
        if (is_string($data)) {
            $data = trim($data);
            $data = stripslashes($data);
            // NOTE: Do NOT htmlspecialchars here — that's the view's job.
            // Controllers need raw values (e.g., password hashing).
            return $data;
        }
        
        return $data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->params[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }

    public function all(): array
    {
        return array_merge($this->params, $this->body);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getHeader(string $name): ?string
    {
        // Normalize header name to match collectHeaders format (e.g. X-Csrf-Token)
        $name = ucwords(strtolower($name), '-');
        return $this->headers[$name] ?? null;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function isAjax(): bool
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function getClientIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['HTTP_CLIENT_IP'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? '0.0.0.0';
    }

    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}
