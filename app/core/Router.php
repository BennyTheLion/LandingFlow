<?php
namespace App\Core;

/**
 * Router - URL Routing System
 */
class Router
{
    private array $routes = [];

    public function __construct()
    {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        // Public Pages
        $this->get('/', 'HomeController@index');
        $this->get('/home', 'HomeController@index');
        $this->get('/services', 'ServiceController@index');
        $this->get('/services/website-development', 'ServiceController@websiteDevelopment');
        $this->get('/services/landing-pages', 'ServiceController@landingPages');
        $this->get('/services/hosting', 'ServiceController@hosting');
        $this->get('/services/monitoring', 'ServiceController@monitoring');
        $this->get('/services/crm', 'ServiceController@crm');
        $this->get('/services/reports', 'ServiceController@reports');
        $this->get('/pricing', 'PageController@pricing');
        $this->get('/about', 'PageController@about');
        $this->get('/portfolio', 'PageController@portfolio');
        $this->get('/demo', 'DemoController@index');
        $this->post('/demo/request', 'DemoController@request');
        $this->post('/demo/build', 'DemoController@build');
        $this->get('/blog', 'BlogController@index');
        $this->get('/blog/{slug}', 'BlogController@show');
        $this->get('/contact', 'ContactController@index');
        $this->post('/contact', 'ContactController@submit');
        
        // Free Website Audit
        $this->get('/audit', 'AuditController@index');
        $this->post('/audit/check', 'AuditController@check');
        $this->get('/audit/pdf/{id}', 'AuditController@pdf');
        $this->post('/audit/report', 'AuditController@report');

        // Landing Page Tester Agent
        $this->get('/landing-tester', 'LandingPageTesterController@index');
        $this->post('/landing-tester/test', 'LandingPageTesterController@test');
        $this->post('/landing-tester/test-html', 'LandingPageTesterController@testHtml');
        
        // Authentication
        $this->get('/login', 'AuthController@loginForm');
        $this->post('/login', 'AuthController@login');
        $this->get('/register', 'AuthController@registerForm');
        $this->post('/register', 'AuthController@register');
        $this->get('/logout', 'AuthController@logout');
        $this->get('/forgot-password', 'AuthController@forgotPasswordForm');
        $this->post('/forgot-password', 'AuthController@forgotPassword');
        $this->get('/reset-password/{token}', 'AuthController@resetPasswordForm');
        $this->post('/reset-password', 'AuthController@resetPassword');
        $this->get('/verify-email/{token}', 'AuthController@verifyEmail');

        // Admin Area
        $this->get('/admin', 'AdminController@dashboard', ['AuthMiddleware']);
        // Moved to DashboardController
        
        // CRM Routes
        $this->get('/admin/leads', 'LeadController@index', ['AuthMiddleware']);
        $this->get('/admin/leads/create', 'LeadController@create', ['AuthMiddleware']);
        $this->post('/admin/leads', 'LeadController@store', ['AuthMiddleware']);
        $this->get('/admin/leads/{id}', 'LeadController@show', ['AuthMiddleware']);
        $this->get('/admin/leads/{id}/edit', 'LeadController@edit', ['AuthMiddleware']);
        $this->post('/admin/leads/{id}', 'LeadController@update', ['AuthMiddleware']);
        $this->post('/admin/leads/{id}/note', 'LeadController@addNote', ['AuthMiddleware']);
        $this->post('/admin/leads/{id}/status', 'LeadController@updateStatus', ['AuthMiddleware']);
        $this->delete('/admin/leads/{id}', 'LeadController@delete', ['AuthMiddleware']);

        // Project Routes
        $this->get('/admin/projects', 'ProjectController@index', ['AuthMiddleware']);
        $this->get('/admin/projects/create', 'ProjectController@create', ['AuthMiddleware']);
        $this->post('/admin/projects', 'ProjectController@store', ['AuthMiddleware']);
        $this->get('/admin/projects/{id}', 'ProjectController@show', ['AuthMiddleware']);
        $this->post('/admin/projects/{id}', 'ProjectController@update', ['AuthMiddleware']);
        $this->post('/admin/projects/{id}/status', 'ProjectController@updateStatus', ['AuthMiddleware']);

        // Hosting Routes
        $this->get('/admin/hosting', 'HostingController@index', ['AuthMiddleware']);
        $this->get('/admin/hosting/create', 'HostingController@create', ['AuthMiddleware']);
        $this->post('/admin/hosting', 'HostingController@store', ['AuthMiddleware']);
        $this->get('/admin/hosting/{id}', 'HostingController@show', ['AuthMiddleware']);
        $this->post('/admin/hosting/{id}', 'HostingController@update', ['AuthMiddleware']);

        // Monitoring Routes
        $this->get('/admin/monitoring', 'MonitoringController@index', ['AuthMiddleware']);
        $this->post('/admin/monitoring/add', 'MonitoringController@add', ['AuthMiddleware']);
        $this->get('/admin/monitoring/{id}', 'MonitoringController@show', ['AuthMiddleware']);
        $this->post('/admin/monitoring/{id}', 'MonitoringController@update', ['AuthMiddleware']);
        $this->get('/admin/monitoring/{id}/check', 'MonitoringController@checkNow', ['AuthMiddleware']);
        $this->get('/admin/monitoring/{id}/delete', 'MonitoringController@delete', ['AuthMiddleware']);
        $this->get('/admin/monitoring/{id}/solutions', 'MonitoringController@solutions', ['AuthMiddleware']);

        // Audit Reports

        // Audit Reports
        $this->get('/admin/audit-reports', 'AuditController@adminIndex', ['AuthMiddleware']);
        $this->get('/admin/audit-reports/{id}', 'AuditController@adminShow', ['AuthMiddleware']);
        $this->get('/admin/audit-reports/{id}/detail', 'AuditController@adminDetail', ['AuthMiddleware']);
        $this->get('/admin/audit-reports/{id}/delete', 'AuditController@adminDelete', ['AuthMiddleware']);

        // Receipts
        $this->get('/admin/receipts', 'ReceiptController@index', ['AuthMiddleware']);
        $this->get('/admin/receipts/create', 'ReceiptController@create', ['AuthMiddleware']);
        $this->post('/admin/receipts', 'ReceiptController@store', ['AuthMiddleware']);
        $this->get('/admin/receipts/{id}/download', 'ReceiptController@download', ['AuthMiddleware']);
        $this->get('/admin/receipts/{id}/resend', 'ReceiptController@resend', ['AuthMiddleware']);

        // Demo Sites Stats (merged into Monitoring)
        $this->get('/admin/dashboard', 'DashboardController@index', ['AuthMiddleware']);
        $this->get('/admin/dashboard/report/{id}', 'DashboardController@report', ['AuthMiddleware']);
        $this->get('/admin/dashboard/lead/{leadId}', 'DashboardController@leadReports', ['AuthMiddleware']);

        // Legal Pages
        $this->get('/privacy-policy', 'LegalController@privacyPolicy');
        $this->get('/terms-of-service', 'LegalController@termsOfService');
        $this->get('/cookie-policy', 'LegalController@cookiePolicy');
        $this->get('/accessibility-statement', 'LegalController@accessibilityStatement');
        $this->get('/fair-disclosure', 'LegalController@fairDisclosure');
        $this->get('/data-deletion', 'LegalController@dataDeletion');
        $this->get('/data-retention', 'LegalController@dataRetention');
        $this->get('/service-agreement', 'LegalController@serviceAgreement');
        $this->get('/maintenance-agreement', 'LegalController@maintenanceAgreement');

        // API Routes
        $this->get('/api/leads/search', 'ApiController@searchLeads', ['AuthMiddleware']);
        $this->get('/api/stats', 'ApiController@stats', ['AuthMiddleware']);
        $this->post('/api/scan', 'ApiController@scan');
        $this->get('/api/report/{id}', 'ApiController@report');
        $this->get('/api/leads/{id}/reports', 'ApiController@leadReports');
        $this->get('/api/dashboard', 'ApiController@dashboard');
    }

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, string $handler, array $middleware): void
    {
        // Convert {param} to regex
        $pattern = preg_replace('/\{([a-zA-Z]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function resolve(Request $request): array
    {
        $method = $request->getMethod();
        $uri = $request->getUri();

        // Handle OPTIONS preflight
        if ($method === 'OPTIONS') {
            return [
                'controller' => 'App\Controllers\ApiController',
                'action' => 'options',
                'params' => [],
                'middleware' => [],
            ];
        }

        // Allow method override via _method parameter
        if ($method === 'POST' && $request->input('_method')) {
            $overrideMethod = strtoupper($request->input('_method'));
            if (in_array($overrideMethod, ['PUT', 'DELETE'])) {
                $method = $overrideMethod;
            }
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Parse handler
                [$controller, $action] = explode('@', $route['handler']);
                $controller = 'App\\Controllers\\' . $controller;

                // Build middleware namespace
                $middleware = array_map(function ($m) {
                    return 'App\\Middleware\\' . $m;
                }, $route['middleware']);

                return [
                    'controller' => $controller,
                    'action' => $action,
                    'params' => $params,
                    'middleware' => $middleware,
                ];
            }
        }

        throw new Exceptions\HttpException('הדף לא נמצא', 404);
    }
}
