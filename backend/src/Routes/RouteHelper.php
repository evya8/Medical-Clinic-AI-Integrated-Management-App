<?php

namespace MedicalClinic\Routes;

use MedicalClinic\Middleware\MiddlewareManager;

/**
 * Route Helper Functions for easier route registration
 * These functions provide a fluent API for common route patterns
 */

class RouteHelper
{
    private RouteRegistry $registry;

    public function __construct(RouteRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Create API resource routes (RESTful pattern)
     * Creates: GET /resource, POST /resource, GET /resource/{id}, PUT /resource/{id}, DELETE /resource/{id}
     */
    public function resource(string $resource, string $controllerClass, ?MiddlewareManager $middleware = null): void
    {
        $basePath = '/' . trim($resource, '/');
        
        // Collection routes
        $this->registry->get($basePath, [$controllerClass, 'index'], $middleware);
        $this->registry->post($basePath, [$controllerClass, 'store'], $middleware);
        
        // Individual resource routes
        $this->registry->get($basePath . '/{id:int}', [$controllerClass, 'show'], $middleware);
        $this->registry->put($basePath . '/{id:int}', [$controllerClass, 'update'], $middleware);
        $this->registry->delete($basePath . '/{id:int}', [$controllerClass, 'destroy'], $middleware);
    }

    /**
     * Create API resource routes with custom actions
     */
    public function resourceWithActions(string $resource, string $controllerClass, array $actions = [], ?MiddlewareManager $middleware = null): void
    {
        // Create standard resource routes
        $this->resource($resource, $controllerClass, $middleware);
        
        $basePath = '/' . trim($resource, '/');
        
        // Add custom actions
        foreach ($actions as $action => $config) {
            $method = $config['method'] ?? 'GET';
            $path = $config['path'] ?? "/{id:int}/$action";
            $handler = $config['handler'] ?? [$controllerClass, $action];
            $actionMiddleware = $config['middleware'] ?? $middleware;
            
            $fullPath = $basePath . $path;
            
            switch (strtoupper($method)) {
                case 'GET':
                    $this->registry->get($fullPath, $handler, $actionMiddleware);
                    break;
                case 'POST':
                    $this->registry->post($fullPath, $handler, $actionMiddleware);
                    break;
                case 'PUT':
                    $this->registry->put($fullPath, $handler, $actionMiddleware);
                    break;
                case 'DELETE':
                    $this->registry->delete($fullPath, $handler, $actionMiddleware);
                    break;
                case 'PATCH':
                    $this->registry->patch($fullPath, $handler, $actionMiddleware);
                    break;
            }
        }
    }

    /**
     * Create authentication routes
     */
    public function authRoutes(string $controllerClass = 'MedicalClinic\\Controllers\\AuthControllerRefresh'): void
    {
        $this->registry->post('/auth/login', [$controllerClass, 'login'], 
            MiddlewareManager::loginEndpoint());
            
        $this->registry->post('/auth/refresh', [$controllerClass, 'refreshToken'], 
            MiddlewareManager::publicEndpoint());
            
        $this->registry->post('/auth/logout', [$controllerClass, 'logout'], 
            MiddlewareManager::authEndpoint());
            
        $this->registry->post('/auth/logout-single', [$controllerClass, 'logoutSingle'], 
            MiddlewareManager::publicEndpoint());
            
        $this->registry->get('/auth/me', [$controllerClass, 'me'], 
            MiddlewareManager::authEndpoint());
            
        $this->registry->get('/auth/sessions', [$controllerClass, 'activeSessions'], 
            MiddlewareManager::authEndpoint());
            
        $this->registry->delete('/auth/sessions/{jti}', [$controllerClass, 'revokeSession'], 
            MiddlewareManager::authEndpoint());
    }

    /**
     * Create admin-only routes
     */
    public function adminRoutes(string $prefix = '/admin'): RouteGroupBuilder
    {
        return new RouteGroupBuilder($this->registry, [
            'prefix' => $prefix,
            'middleware' => MiddlewareManager::adminEndpoint()
        ]);
    }

    /**
     * Create doctor-access routes
     */
    public function doctorRoutes(string $prefix = '/doctor'): RouteGroupBuilder
    {
        return new RouteGroupBuilder($this->registry, [
            'prefix' => $prefix,
            'middleware' => MiddlewareManager::doctorEndpoint()
        ]);
    }

    /**
     * Create public routes (no authentication)
     */
    public function publicRoutes(string $prefix = ''): RouteGroupBuilder
    {
        return new RouteGroupBuilder($this->registry, [
            'prefix' => $prefix,
            'middleware' => MiddlewareManager::publicEndpoint()
        ]);
    }

    /**
     * Create API versioned routes
     */
    public function apiVersion(string $version, callable $callback): void
    {
        $this->registry->group([
            'prefix' => "/api/$version",
            'name' => "api.$version"
        ], $callback);
    }

    /**
     * Create CRUD operations for a model
     */
    public function crud(string $resource, string $controllerClass, array $options = []): void
    {
        $middleware = $options['middleware'] ?? MiddlewareManager::authEndpoint();
        $only = $options['only'] ?? ['index', 'show', 'store', 'update', 'destroy'];
        $except = $options['except'] ?? [];
        
        $actions = array_diff($only, $except);
        $basePath = '/' . trim($resource, '/');
        
        if (in_array('index', $actions)) {
            $this->registry->get($basePath, [$controllerClass, 'index'], $middleware);
        }
        
        if (in_array('store', $actions)) {
            $this->registry->post($basePath, [$controllerClass, 'store'], $middleware);
        }
        
        if (in_array('show', $actions)) {
            $this->registry->get($basePath . '/{id:int}', [$controllerClass, 'show'], $middleware);
        }
        
        if (in_array('update', $actions)) {
            $this->registry->put($basePath . '/{id:int}', [$controllerClass, 'update'], $middleware);
            $this->registry->patch($basePath . '/{id:int}', [$controllerClass, 'update'], $middleware);
        }
        
        if (in_array('destroy', $actions)) {
            $this->registry->delete($basePath . '/{id:int}', [$controllerClass, 'destroy'], $middleware);
        }
    }
}

/**
 * Route Group Builder for fluent group creation
 */
class RouteGroupBuilder
{
    private RouteRegistry $registry;
    private array $attributes;

    public function __construct(RouteRegistry $registry, array $attributes = [])
    {
        $this->registry = $registry;
        $this->attributes = $attributes;
    }

    public function prefix(string $prefix): self
    {
        $this->attributes['prefix'] = $prefix;
        return $this;
    }

    public function middleware(MiddlewareManager $middleware): self
    {
        $this->attributes['middleware'] = $middleware;
        return $this;
    }

    public function name(string $name): self
    {
        $this->attributes['name'] = $name;
        return $this;
    }

    public function group(callable $callback): void
    {
        $this->registry->group($this->attributes, $callback);
    }

    public function routes(callable $callback): void
    {
        $this->group($callback);
    }

    // Direct route registration methods
    public function get(string $path, callable|array $handler, ?MiddlewareManager $middleware = null): void
    {
        $this->registry->group($this->attributes, function($router) use ($path, $handler, $middleware) {
            $router->get($path, $handler, $middleware);
        });
    }

    public function post(string $path, callable|array $handler, ?MiddlewareManager $middleware = null): void
    {
        $this->registry->group($this->attributes, function($router) use ($path, $handler, $middleware) {
            $router->post($path, $handler, $middleware);
        });
    }

    public function put(string $path, callable|array $handler, ?MiddlewareManager $middleware = null): void
    {
        $this->registry->group($this->attributes, function($router) use ($path, $handler, $middleware) {
            $router->put($path, $handler, $middleware);
        });
    }

    public function delete(string $path, callable|array $handler, ?MiddlewareManager $middleware = null): void
    {
        $this->registry->group($this->attributes, function($router) use ($path, $handler, $middleware) {
            $router->delete($path, $handler, $middleware);
        });
    }

    public function resource(string $resource, string $controllerClass, array $options = []): void
    {
        $this->registry->group($this->attributes, function($router) use ($resource, $controllerClass, $options) {
            $helper = new RouteHelper($router);
            $helper->crud($resource, $controllerClass, $options);
        });
    }
}
