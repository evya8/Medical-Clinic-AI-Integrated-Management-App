<?php

namespace MedicalClinic\Routes;

use MedicalClinic\Middleware\MiddlewareManager;
use MedicalClinic\Middleware\AuthMiddleware;
use MedicalClinic\Middleware\RoleMiddleware;
use MedicalClinic\Middleware\ValidationMiddleware;

class RouteRegistryEnhanced extends RouteRegistry
{
    private array $middlewareCache = [];

    /**
     * Enhanced middleware combination with proper stacking
     */
    protected function combineMiddleware(?MiddlewareManager $global, ?MiddlewareManager $route): MiddlewareManager
    {
        $combined = new MiddlewareManager();
        
        // Add global middleware first (if exists)
        if ($global) {
            $this->addMiddlewareToManager($combined, $this->extractMiddlewareList($global));
        }
        
        // Add route-specific middleware second (if exists)
        if ($route) {
            $this->addMiddlewareToManager($combined, $this->extractMiddlewareList($route));
        }
        
        return $combined;
    }

    /**
     * Extract middleware instances from a MiddlewareManager
     * This is a workaround since MiddlewareManager doesn't expose its stack
     */
    private function extractMiddlewareList(MiddlewareManager $manager): array
    {
        // Cache the middleware to avoid recreating them
        $managerId = spl_object_id($manager);
        
        if (isset($this->middlewareCache[$managerId])) {
            return $this->middlewareCache[$managerId];
        }
        
        // For now, return empty array - this would need to be implemented
        // based on how MiddlewareManager stores its middleware internally
        $this->middlewareCache[$managerId] = [];
        return [];
    }

    /**
     * Add multiple middleware instances to a manager
     */
    private function addMiddlewareToManager(MiddlewareManager $manager, array $middlewareList): void
    {
        foreach ($middlewareList as $middleware) {
            $manager->add($middleware);
        }
    }

    /**
     * Enhanced route execution with proper parameter injection
     */
    protected function executeRoute(Route $route, array $params, string $method, string $path): void
    {
        // Prepare the request data with all necessary information
        $request = [
            'method' => $method,
            'path' => $path,
            'params' => $params,
            'query' => $_GET,
            'body' => $this->getRequestBody(),
            'headers' => $this->getRequestHeaders(),
            'route' => $route,
            'auth_user' => null, // Will be set by AuthMiddleware
            'validated_input' => null, // Will be set by ValidationMiddleware
            'user_role' => null, // Will be set by RoleMiddleware
        ];

        // Execute middleware chain
        $middleware = $route->getMiddleware();
        
        try {
            $middleware->handle($request, function($request) use ($route, $params) {
                return $this->executeHandler($route->getHandler(), $request, $params);
            });
        } catch (\Exception $e) {
            $this->handleRouteException($e, $method, $path);
        }
    }

    /**
     * Enhanced handler execution with parameter injection
     */
    protected function executeHandler(mixed $handler, array $request, array $params): mixed
    {
        if (is_callable($handler)) {
            return $handler($request, $params);
        }

        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $method] = $handler;
            
            // Instantiate controller with the processed request
            $controller = new $controllerClass($request);
            
            // Call method with parameters if it has them
            if (!empty($params) && $this->methodHasParameters($controllerClass, $method)) {
                return $this->callMethodWithParams($controller, $method, $params);
            } else {
                return $controller->$method();
            }
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$controllerClass, $method] = explode('@', $handler, 2);
            
            $controller = new $controllerClass($request);
            
            if (!empty($params) && $this->methodHasParameters($controllerClass, $method)) {
                return $this->callMethodWithParams($controller, $method, $params);
            } else {
                return $controller->$method();
            }
        }

        throw new \InvalidArgumentException('Invalid route handler format');
    }

    /**
     * Check if a method expects parameters
     */
    private function methodHasParameters(string $className, string $methodName): bool
    {
        try {
            $reflection = new \ReflectionMethod($className, $methodName);
            return $reflection->getNumberOfRequiredParameters() > 0;
        } catch (\ReflectionException $e) {
            return false;
        }
    }

    /**
     * Call method with parameters in the correct order
     */
    private function callMethodWithParams(object $controller, string $method, array $params): mixed
    {
        try {
            $reflection = new \ReflectionMethod($controller, $method);
            $methodParams = $reflection->getParameters();
            $args = [];

            foreach ($methodParams as $param) {
                $paramName = $param->getName();
                
                if (isset($params[$paramName])) {
                    // Type casting for numeric parameters
                    $value = $params[$paramName];
                    if ($param->hasType() && $param->getType()->getName() === 'int') {
                        $value = (int) $value;
                    }
                    $args[] = $value;
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    throw new \InvalidArgumentException("Missing required parameter: {$paramName}");
                }
            }

            return $reflection->invokeArgs($controller, $args);
        } catch (\ReflectionException $e) {
            throw new \InvalidArgumentException("Method reflection error: " . $e->getMessage());
        }
    }

    /**
     * Get request body content
     */
    private function getRequestBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            return json_decode($json, true) ?? [];
        }
        
        return array_merge($_POST, $_GET);
    }

    /**
     * Get request headers
     */
    private function getRequestHeaders(): array
    {
        $headers = [];
        
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(['HTTP_', '_'], ['', '-'], $key);
                $headers[strtolower($header)] = $value;
            }
        }
        
        return $headers;
    }

    /**
     * Handle route execution exceptions
     */
    private function handleRouteException(\Exception $e, string $method, string $path): void
    {
        $statusCode = $this->getExceptionStatusCode($e);
        
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'method' => $method,
            'path' => $path,
            'timestamp' => date('Y-m-d H:i:s'),
            'error_code' => $e->getCode()
        ]);
    }

    /**
     * Determine HTTP status code from exception
     */
    private function getExceptionStatusCode(\Exception $e): int
    {
        $code = $e->getCode();
        
        // Common HTTP status codes
        if (in_array($code, [400, 401, 403, 404, 405, 409, 422, 429, 500, 503])) {
            return $code;
        }
        
        // Default to 500 for unknown codes
        return 500;
    }

    /**
     * Create middleware manager with common middleware
     */
    public static function createAuthMiddleware(array $roles = []): MiddlewareManager
    {
        $middleware = new MiddlewareManager();
        $middleware->add(new AuthMiddleware());
        
        if (!empty($roles)) {
            $middleware->add(new RoleMiddleware($roles));
        }
        
        return $middleware;
    }

    /**
     * Create middleware manager with validation
     */
    public static function createValidationMiddleware(array $rules = []): MiddlewareManager
    {
        $middleware = new MiddlewareManager();
        
        if (!empty($rules)) {
            $middleware->add(new ValidationMiddleware($rules));
        }
        
        return $middleware;
    }

    /**
     * Create full middleware stack
     */
    public static function createFullMiddleware(array $roles = [], array $validationRules = []): MiddlewareManager
    {
        $middleware = new MiddlewareManager();
        
        // Add in order: Auth -> Role -> Validation
        $middleware->add(new AuthMiddleware());
        
        if (!empty($roles)) {
            $middleware->add(new RoleMiddleware($roles));
        }
        
        if (!empty($validationRules)) {
            $middleware->add(new ValidationMiddleware($validationRules));
        }
        
        return $middleware;
    }

    /**
     * Quick method to add authenticated routes
     */
    public function authGet(string $path, mixed $handler, array $roles = []): Route
    {
        return $this->get($path, $handler, self::createAuthMiddleware($roles));
    }

    public function authPost(string $path, mixed $handler, array $roles = [], array $validationRules = []): Route
    {
        return $this->post($path, $handler, self::createFullMiddleware($roles, $validationRules));
    }

    public function authPut(string $path, mixed $handler, array $roles = [], array $validationRules = []): Route
    {
        return $this->put($path, $handler, self::createFullMiddleware($roles, $validationRules));
    }

    public function authDelete(string $path, mixed $handler, array $roles = []): Route
    {
        return $this->delete($path, $handler, self::createAuthMiddleware($roles));
    }

    /**
     * Register CRUD routes for a resource
     */
    public function resource(string $name, string $controller, array $options = []): void
    {
        $roles = $options['roles'] ?? [];
        $prefix = $options['prefix'] ?? '';
        $path = $prefix ? "{$prefix}/{$name}" : $name;

        // GET /resource - index
        if (!isset($options['except']) || !in_array('index', $options['except'])) {
            $this->authGet("/{$path}", [$controller, 'get' . ucfirst($name)], $roles);
        }

        // GET /resource/{id} - show
        if (!isset($options['except']) || !in_array('show', $options['except'])) {
            $this->authGet("/{$path}/{id}", [$controller, 'get' . rtrim(ucfirst($name), 's')], $roles)
                 ->whereNumber('id');
        }

        // POST /resource - store
        if (!isset($options['except']) || !in_array('store', $options['except'])) {
            $validationRules = $options['store_validation'] ?? [];
            $this->authPost("/{$path}", [$controller, 'create' . rtrim(ucfirst($name), 's')], $roles, $validationRules);
        }

        // PUT /resource/{id} - update
        if (!isset($options['except']) || !in_array('update', $options['except'])) {
            $validationRules = $options['update_validation'] ?? [];
            $this->authPut("/{$path}/{id}", [$controller, 'update' . rtrim(ucfirst($name), 's')], $roles, $validationRules)
                 ->whereNumber('id');
        }

        // DELETE /resource/{id} - destroy
        if (!isset($options['except']) || !in_array('destroy', $options['except'])) {
            $this->authDelete("/{$path}/{id}", [$controller, 'delete' . rtrim(ucfirst($name), 's')], $roles)
                 ->whereNumber('id');
        }
    }

    /**
     * Get route statistics
     */
    public function getStats(): array
    {
        $methodStats = [];
        $middlewareStats = [];
        
        foreach ($this->getRoutes() as $route) {
            $method = $route->getMethod();
            $methodStats[$method] = ($methodStats[$method] ?? 0) + 1;
            
            $middlewareCount = $route->getMiddleware()->getMiddlewareCount();
            $middlewareStats['total'] = ($middlewareStats['total'] ?? 0) + $middlewareCount;
        }
        
        return [
            'total_routes' => $this->getRouteCount(),
            'methods' => $methodStats,
            'middleware' => $middlewareStats,
            'named_routes' => count($this->namedRoutes),
        ];
    }
}
