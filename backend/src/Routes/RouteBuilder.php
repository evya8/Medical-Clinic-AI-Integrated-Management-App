<?php

namespace MedicalClinic\Routes;

use MedicalClinic\Middleware\MiddlewareManager;

class RouteBuilder
{
    private RouteRegistry $registry;

    public function __construct(RouteRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Create API resource routes (RESTful endpoints)
     */
    public function apiResource(string $name, string $controller, ?MiddlewareManager $middleware = null): array
    {
        $routes = [];
        $basePath = '/' . trim($name, '/');
        
        // Index - GET /resource
        $routes['index'] = $this->registry->get($basePath, [$controller, 'index'], $middleware)
            ->setName($name . '.index');
            
        // Store - POST /resource
        $routes['store'] = $this->registry->post($basePath, [$controller, 'store'], $middleware)
            ->setName($name . '.store');
            
        // Show - GET /resource/{id}
        $routes['show'] = $this->registry->get($basePath . '/{id}', [$controller, 'show'], $middleware)
            ->whereNumber('id')
            ->setName($name . '.show');
            
        // Update - PUT /resource/{id}
        $routes['update'] = $this->registry->put($basePath . '/{id}', [$controller, 'update'], $middleware)
            ->whereNumber('id')
            ->setName($name . '.update');
            
        // Destroy - DELETE /resource/{id}
        $routes['destroy'] = $this->registry->delete($basePath . '/{id}', [$controller, 'destroy'], $middleware)
            ->whereNumber('id')
            ->setName($name . '.destroy');

        return $routes;
    }

    /**
     * Create authentication routes
     */
    public function authRoutes(string $controller = 'AuthController'): array
    {
        $routes = [];
        
        // Login
        $routes['login'] = $this->registry->post('/auth/login', [$controller, 'login'])
            ->setName('auth.login');
            
        // Logout
        $routes['logout'] = $this->registry->post('/auth/logout', [$controller, 'logout'])
            ->setName('auth.logout');
            
        // Me
        $routes['me'] = $this->registry->get('/auth/me', [$controller, 'me'])
            ->setName('auth.me');
            
        // Refresh Token
        $routes['refresh'] = $this->registry->post('/auth/refresh', [$controller, 'refreshToken'])
            ->setName('auth.refresh');

        return $routes;
    }

    /**
     * Create admin routes with admin middleware
     */
    public function adminRoutes(callable $callback): void
    {
        $this->registry->group([
            'prefix' => 'admin',
            'middleware' => MiddlewareManager::adminEndpoint()
        ], $callback);
    }

    /**
     * Create API routes with auth middleware
     */
    public function apiRoutes(callable $callback): void
    {
        $this->registry->group([
            'prefix' => 'api',
            'middleware' => MiddlewareManager::authEndpoint()
        ], $callback);
    }

    /**
     * Create public routes (no authentication)
     */
    public function publicRoutes(callable $callback): void
    {
        $this->registry->group([
            'middleware' => MiddlewareManager::publicEndpoint()
        ], $callback);
    }

    /**
     * Create doctor-only routes
     */
    public function doctorRoutes(callable $callback): void
    {
        $this->registry->group([
            'prefix' => 'doctor',
            'middleware' => MiddlewareManager::doctorEndpoint()
        ], $callback);
    }

    /**
     * Bulk register routes from array configuration
     */
    public function registerRoutes(array $routeDefinitions): void
    {
        foreach ($routeDefinitions as $definition) {
            $method = strtolower($definition['method']);
            $path = $definition['path'];
            $handler = $definition['handler'];
            $middleware = $definition['middleware'] ?? null;
            $name = $definition['name'] ?? null;
            $constraints = $definition['constraints'] ?? [];

            $route = $this->registry->$method($path, $handler, $middleware);
            
            if ($name) {
                $route->setName($name);
            }
            
            if (!empty($constraints)) {
                $route->where($constraints);
            }
        }
    }

    /**
     * Create health check routes
     */
    public function healthRoutes(): array
    {
        $routes = [];
        
        // Basic health check
        $routes['health'] = $this->registry->get('/health', function() {
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0.0'
            ]);
        })->setName('health');
        
        // Database health check
        $routes['health.db'] = $this->registry->get('/health/database', function() {
            try {
                $db = \MedicalClinic\Utils\Database::getInstance();
                $result = $db->fetch('SELECT 1 as test');
                
                http_response_code(200);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'healthy',
                    'database' => 'connected',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } catch (Exception $e) {
                http_response_code(503);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'unhealthy',
                    'database' => 'disconnected',
                    'error' => $e->getMessage(),
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }
        })->setName('health.database');

        return $routes;
    }

    /**
     * Create CORS preflight route
     */
    public function corsRoutes(): void
    {
        // Handle OPTIONS requests for CORS (catch-all for any path)
        $route = $this->registry->match(['OPTIONS'], '/{path}', function() {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            header('Access-Control-Max-Age: 86400');
            http_response_code(200);
        });
        
        // Apply constraint to the first (and only) route
        if (!empty($route) && is_array($route)) {
            $route[0]->where(['path' => '.*']);
        }
    }

    /**
     * Get route statistics
     */
    public function getStats(): array
    {
        $routes = $this->registry->getRoutes();
        $stats = [
            'total' => count($routes),
            'by_method' => [],
            'with_parameters' => 0,
            'with_middleware' => 0,
            'named_routes' => 0
        ];

        foreach ($routes as $route) {
            // Count by method
            $method = $route->getMethod();
            $stats['by_method'][$method] = ($stats['by_method'][$method] ?? 0) + 1;
            
            // Count routes with parameters
            if ($route->hasParameters()) {
                $stats['with_parameters']++;
            }
            
            // Count routes with middleware
            if ($route->getMiddleware()->getMiddlewareCount() > 0) {
                $stats['with_middleware']++;
            }
            
            // Count named routes
            if ($route->getName()) {
                $stats['named_routes']++;
            }
        }

        return $stats;
    }

    /**
     * Generate route list for documentation
     */
    public function generateRouteList(): array
    {
        $routes = $this->registry->getRoutes();
        $routeList = [];

        foreach ($routes as $route) {
            $routeList[] = [
                'method' => $route->getMethod(),
                'path' => $route->getPath(),
                'name' => $route->getName(),
                'handler' => $route->getHandlerString(),
                'parameters' => $route->getParameters(),
                'middleware_count' => $route->getMiddleware()->getMiddlewareCount(),
                'constraints' => $route->getConstraints()
            ];
        }

        // Sort by method and path
        usort($routeList, function($a, $b) {
            $methodCompare = strcmp($a['method'], $b['method']);
            if ($methodCompare !== 0) {
                return $methodCompare;
            }
            return strcmp($a['path'], $b['path']);
        });

        return $routeList;
    }

    /**
     * Export routes to various formats
     */
    public function exportRoutes(string $format = 'json'): string
    {
        $routes = $this->generateRouteList();
        
        switch (strtolower($format)) {
            case 'json':
                return json_encode($routes, JSON_PRETTY_PRINT);
                
            case 'yaml':
                // Simple YAML export (requires yaml extension for full support)
                $yaml = "routes:\n";
                foreach ($routes as $route) {
                    $yaml .= "  - method: {$route['method']}\n";
                    $yaml .= "    path: {$route['path']}\n";
                    $yaml .= "    name: {$route['name']}\n";
                    $yaml .= "    handler: {$route['handler']}\n";
                    $yaml .= "\n";
                }
                return $yaml;
                
            case 'markdown':
                $md = "# API Routes\n\n";
                $md .= "| Method | Path | Name | Handler | Parameters |\n";
                $md .= "|--------|------|------|---------|------------|\n";
                
                foreach ($routes as $route) {
                    $params = implode(', ', $route['parameters']);
                    $md .= "| {$route['method']} | {$route['path']} | {$route['name']} | {$route['handler']} | {$params} |\n";
                }
                
                return $md;
                
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }
}
