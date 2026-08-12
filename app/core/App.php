<?php
namespace App\Core;

use App\Core\Logger;

/**
 * Application Core - Router & Request Handler
 */
class App
{
    private Router $router;
    private Request $request;
    private Response $response;

    private array $globalMiddleware = [
        \App\Middleware\CsrfMiddleware::class,
        \App\Middleware\SecurityHeadersMiddleware::class,
    ];

    public function __construct()
    {
        $this->router = new Router();
        $this->request = new Request();
        $this->response = new Response();
    }

    public function run(): void
    {
        try {
            Logger::debug('app: resolving route', [
                'method' => $this->request->getMethod(),
                'uri' => $this->request->getUri(),
                'baseUrl' => $this->request->getBaseUrl(),
            ]);
            $route = $this->router->resolve($this->request);
            Logger::debug('app: route resolved', [
                'controller' => $route['controller'] ?? '?',
                'action' => $route['action'] ?? '?',
                'params' => $route['params'] ?? [],
            ]);

            // Execute global middleware
            $this->executeGlobalMiddleware();
            // Execute route middleware
            $this->executeMiddleware($route['middleware'] ?? []);

            $controller = $route['controller'];
            $action = $route['action'];
            $params = $route['params'] ?? [];

            $controllerInstance = new $controller($this->request);
            $result = call_user_func_array([$controllerInstance, $action], $params);

            $this->response->send($result);

        } catch (\App\Core\Exceptions\HttpException $e) {
            $this->response->setStatusCode($e->getStatusCode());
            $this->response->send($this->renderError($e));
        } catch (\Exception $e) {
            Logger::error('app: uncaught exception', [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            if (APP_DEBUG) {
                throw $e;
            }
            $this->response->setStatusCode(500);
            $this->response->send($this->renderError($e));
        }
    }

    private function executeGlobalMiddleware(): void
    {
        foreach ($this->globalMiddleware as $m) {
            $instance = new $m();
            $instance->handle($this->request);
        }
    }

    private function executeMiddleware(array $middleware): void
    {
        foreach ($middleware as $m) {
            $instance = new $m();
            $instance->handle($this->request);
        }
    }

    private function renderError(\Exception $e): string
    {
        ob_start();
        $code = $e instanceof \App\Core\Exceptions\HttpException ? $e->getStatusCode() : 500;
        $message = APP_DEBUG ? $e->getMessage() : 'אירעה שגיאה. אנא נסו שוב מאוחר יותר.';
        $viewFile = APP_PATH . '/views/errors/' . $code . '.php';
        
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            include APP_PATH . '/views/errors/500.php';
        }
        
        return ob_get_clean();
    }
}