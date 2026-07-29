<?php
namespace App\Core;

/**
 * Base Controller with view rendering and security helpers
 */
abstract class Controller
{
    protected Request $request;
    protected Response $response;

    public function __construct(?Request $request = null)
    {
        // Accept an existing Request (e.g. from App) so php://input is only read once.
        // When called without a Request the controller creates its own.
        $this->request = $request ?? new Request();
        $this->response = new Response();
    }

    /**
     * Render a view file
     */
    protected function render(string $view, array $data = []): string
    {
        $viewFile = APP_PATH . '/views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        // Extract data for the view
        extract($data);
        
        // Make helper functions available
        $helpers = [
            'url' => fn(string $path = '') => $this->request->getBaseUrl() . '/' . ltrim($path, '/'),
            'asset' => fn(string $path) => $this->request->getBaseUrl() . '/assets/' . ltrim($path, '/'),
            'csrf' => fn() => $this->generateCsrfField(),
            'old' => fn(string $key, string $default = '') => htmlspecialchars($this->request->get($key, $default)),
            'flash' => fn(string $key) => \App\Core\Session::flash($key),
            'auth' => fn() => \App\Core\Session::get('user'),
            'isAuthenticated' => fn() => \App\Core\Session::has('user'),
        ];
        extract($helpers);

        ob_start();
        include $viewFile;
        return ob_get_clean();
    }

    /**
     * Return JSON response
     */
    protected function json(array $data, int $status = 200): never
    {
        $this->response->json($data, $status);
    }

    /**
     * Redirect to a URL
     */
    protected function redirect(string $path): never
    {
        $url = $this->request->getBaseUrl() . '/' . ltrim($path, '/');
        $this->response->redirect($url);
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf(): bool
    {
        $token = $this->request->get(CSRF_TOKEN_NAME) 
              ?? $this->request->getHeader('X-CSRF-Token');
        
        $sessionToken = Session::get(CSRF_TOKEN_NAME);
        
        if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
            return false;
        }

        return true;
    }

    /**
     * Generate CSRF field
     */
    protected function generateCsrfField(): string
    {
        $token = $this->generateCsrfToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
    }

    /**
     * Generate CSRF token
     */
    protected function generateCsrfToken(): string
    {
        $token = Session::get(CSRF_TOKEN_NAME);
        
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::set(CSRF_TOKEN_NAME, $token);
        }
        
        return $token;
    }

    /**
     * Get CSRF token value
     */
    protected function getCsrfToken(): string
    {
        return $this->generateCsrfToken();
    }
}
