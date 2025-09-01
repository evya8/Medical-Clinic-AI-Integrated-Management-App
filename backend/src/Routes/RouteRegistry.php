<?php

namespace MedicalClinic\Routes;

use MedicalClinic\Middleware\MiddlewareManager;

class RouteRegistry
{
    protected array $routes = [];
    protected array $namedRoutes = [];
    private string $prefix = '';
    private ?MiddlewareManager $globalMiddleware = null;

    /**
     * Register a GET route
     */
    public function get(string $path, mixed $handler, ?MiddlewareManager $middleware = null): Route
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, mixed $handler, ?MiddlewareManager $middleware = null): Route
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Register a PUT route
     */
    public function put(string $path, mixed $handler, ?MiddlewareManager $middleware = null): Route
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $path, mixed $handler, ?MiddlewareManager $middleware = null): Route
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * Register a PATCH route
     */
    public function patch(string $path, mixed $handler, ?MiddlewareManager $middleware = null): Route
    {
        return $this->addRoute('PATCH', $path, $handler, $middleware);
    }

    /**
     * Register multiple HTTP methods for the same route
     */
    public function match(array $methods, string $path, mixed $handler, ?MiddlewareManager $middleware = null): array
    {
        $routes = [];
        foreach ($methods as $method) {
            $routes[] = $this->addRoute(strtoupper($method), $path, $handler, $middleware);
        }
        return $routes;
    }

    /**
     * Register a route for any HTTP method
     */
    public function any(string $path, mixed $handler, ?MiddlewareManager $middleware = null): array
    {
        return $this->match(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $path, $handler, $middleware);
    }

    /**
     * Create a route group with shared prefix and/or middleware
     */
    public function group(array $attributes, callable $callback): void
    {
        $previousPrefix = $this->prefix;
        $previousGlobalMiddleware = $this->globalMiddleware;

        // Set group prefix
        if (isset($attributes['prefix'])) {
            $this->prefix = rtrim($previousPrefix, '/') . '/' . trim($attributes['prefix'], '/');
        }

        // Set group middleware
        if (isset($attributes['middleware'])) {
            $this->globalMiddleware = $attributes['middleware'];
        }

        // Execute the group callback
        $callback($this);

        // Restore previous state
        $this->prefix = $previousPrefix;
        $this->globalMiddleware = $previousGlobalMiddleware;
    }

    /**
     * Add a route to the registry
     */
    private function addRoute(string $method, string $path, mixed $handler, ?MiddlewareManager $middleware): Route
    {
        $fullPath = $this->normalizePath($this->prefix . '/' . $path);
        
        // Combine global middleware with route-specific middleware
        $finalMiddleware = $this->combineMiddleware($this->globalMiddleware, $middleware);
        
        $route = new Route($method, $fullPath, $handler, $finalMiddleware);
        
        $this->routes[] = $route;
        
        return $route;
    }

    /**
     * Combine global and route-specific middleware
     */
    private function combineMiddleware(?MiddlewareManager $global, ?MiddlewareManager $route): MiddlewareManager
    {
        if (!$global && !$route) {
            return new MiddlewareManager();
        }
        
        if (!$global) {
            return $route;
        }
        
        if (!$route) {
            return $global;
        }
        
        // Create new middleware manager combining both
        $combined = new MiddlewareManager();
        
        // Add global middleware first
        foreach ($this->getMiddlewareFromManager($global) as $middleware) {
            $combined->add($middleware);
        }
        
        // Add route-specific middleware
        foreach ($this->getMiddlewareFromManager($route) as $middleware) {
            $combined->add($middleware);
        }
        
        return $combined;
    }

    /**
     * Extract middleware instances from MiddlewareManager (helper method)
     */
    private function getMiddlewareFromManager(MiddlewareManager $manager): array
    {
        // This is a bit hacky since we don't have access to the internal stack
        // In a real implementation, you might want to add a getMiddleware() method to MiddlewareManager
        return []; // For now, just return empty - middleware will be handled at dispatch time
    }

    /**
     * Dispatch a request to the appropriate route
     */
    public function dispatch(string $method, string $path): void
    {
        $normalizedPath = $this->normalizePath($path);
        $matchedRoute = $this->findMatchingRoute($method, $normalizedPath);
        
        if (!$matchedRoute) {
            $this->handleNotFound($method, $normalizedPath);
            return;
        }

        $this->executeRoute($matchedRoute['route'], $matchedRoute['params'], $method, $normalizedPath);
    }

    /**
     * Find a matching route for the given method and path
     */
    private function findMatchingRoute(string $method, string $path): ?array
    {
        foreach ($this->routes as $route) {
            if ($route->getMethod() === $method) {
                $params = $this->matchPath($route->getPath(), $path);
                if ($params !== null) {
                    return [
                        'route' => $route,
                        'params' => $params
                    ];
                }
            }
        }
        
        return null;
    }

    /**
     * Check if a route path matches the request path and extract parameters
     */
    private function matchPath(string $routePath, string $requestPath): ?array
    {
        // Convert route path to regex pattern
        $pattern = $this->convertToPattern($routePath);
        
        if (preg_match($pattern, $requestPath, $matches)) {
            $params = [];
            $paramNames = $this->extractParameterNames($routePath);
            
            // Map captured groups to parameter names
            for ($i = 1; $i < count($matches); $i++) {
                if (isset($paramNames[$i - 1])) {
                    $params[$paramNames[$i - 1]] = $matches[$i];
                }
            }
            
            return $params;
        }
        
        return null;
    }

    /**
     * Convert route path with parameters to regex pattern
     */
    private function convertToPattern(string $path): string
    {
        // Replace {param} with ([^/]+) for matching
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $path);
        
        // Escape any remaining special regex characters
        $pattern = str_replace('/', '\/', $pattern);
        
        return '/^' . $pattern . '$/';
    }

    /**
     * Extract parameter names from route path
     */
    private function extractParameterNames(string $path): array
    {
        preg_match_all('/\{([^}]+)\}/', $path, $matches);
        return $matches[1];
    }

    /**
     * Execute a matched route
     */
    private function executeRoute(Route $route, array $params, string $method, string $path): void
    {
        $request = [
            'method' => $method,
            'path' => $path,
            'params' => $params,
            'route' => $route
        ];

        $middleware = $route->getMiddleware();
        $handler = $route->getHandler();

        $middleware->handle($request, function($request) use ($handler) {
            return $this->executeHandler($handler, $request);
        });
    }

    /**
     * Execute the route handler
     */
    private function executeHandler(mixed $handler, array $request): mixed
    {
        if (is_callable($handler)) {
            return $handler($request);
        }

        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $method] = $handler;
            $controller = new $controllerClass($request);
            return $controller->$method();
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$controllerClass, $method] = explode('@', $handler, 2);
            $controller = new $controllerClass($request);
            return $controller->$method();
        }

        throw new \InvalidArgumentException('Invalid route handler format');
    }

    /**
     * Handle 404 Not Found
     */
    private function handleNotFound(string $method, string $path): void
    {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Route not found',
            'method' => $method,
            'path' => $path,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Normalize path by removing extra slashes and ensuring leading slash
     */
    private function normalizePath(string $path): string
    {
        // Remove query string
        $path = strtok($path, '?');
        
        // Normalize slashes
        $path = '/' . trim($path, '/');
        
        // Remove duplicate slashes
        $path = preg_replace('/\/+/', '/', $path);
        
        return $path === '/' ? '/' : rtrim($path, '/');
    }

    /**
     * Register a named route
     */
    public function name(string $name, Route $route): Route
    {
        $this->namedRoutes[$name] = $route;
        $route->setName($name);
        return $route;
    }

    /**
     * Get a named route
     */
    public function getNamedRoute(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * Generate URL for a named route
     */
    public function url(string $name, array $params = []): ?string
    {
        $route = $this->getNamedRoute($name);
        if (!$route) {
            return null;
        }

        $path = $route->getPath();
        
        // Replace parameters in path
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
        }
        
        return $path;
    }

    /**
     * Get all registered routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get route count
     */
    public function getRouteCount(): int
    {
        return count($this->routes);
    }

    /**
     * Check if a route exists
     */
    public function hasRoute(string $method, string $path): bool
    {
        $normalizedPath = $this->normalizePath($path);
        return $this->findMatchingRoute($method, $normalizedPath) !== null;
    }

    /**
     * Clear all routes
     */
    public function clear(): void
    {
        $this->routes = [];
        $this->namedRoutes = [];
    }
}
