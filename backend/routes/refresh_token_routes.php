<?php

use MedicalClinic\Controllers\AuthControllerRefresh;
use MedicalClinic\Middleware\MiddlewareManager;

/**
 * Refresh Token System Routes
 * 
 * Add these routes to your existing api.php or use this as a reference
 * for integrating refresh token endpoints into your routing system
 */

// Authentication routes with refresh token support
$authRoutes = [
    'POST /auth/login' => [
        'controller' => AuthControllerRefresh::class,
        'method' => 'login',
        'middleware' => MiddlewareManager::loginEndpoint()
    ],
    
    'POST /auth/refresh' => [
        'controller' => AuthControllerRefresh::class,
        'method' => 'refreshToken',
        'middleware' => MiddlewareManager::publicEndpoint() // No auth required for refresh
    ],
    
    'POST /auth/logout' => [
        'controller' => AuthControllerRefresh::class,
        'method' => 'logout',
        'middleware' => MiddlewareManager::authEndpoint() // Requires valid access token
    ],
    
    'POST /auth/logout-single' => [
        'controller' => AuthControllerRefresh::class,
        'method' => 'logoutSingle',
        'middleware' => MiddlewareManager::publicEndpoint() // Uses refresh token
    ],
    
    'GET /auth/me' => [
        'controller' => AuthControllerRefresh::class,
        'method' => 'me',
        'middleware' => MiddlewareManager::authEndpoint()
    ],
    
    'GET /auth/sessions' => [
        'controller' => AuthControllerRefresh::class,
        'method' => 'activeSessions',
        'middleware' => MiddlewareManager::authEndpoint()
    ],
    
    'DELETE /auth/sessions/{jti}' => [
        'controller' => AuthControllerRefresh::class,
        'method' => 'revokeSession',
        'middleware' => MiddlewareManager::authEndpoint()
    ],
    
    // Admin-only token management
    'GET /auth/admin/token-stats' => [
        'controller' => AuthControllerRefresh::class,
        'method' => 'tokenStats',
        'middleware' => MiddlewareManager::adminEndpoint()
    ],
    
    'POST /auth/admin/cleanup-tokens' => [
        'controller' => AuthControllerRefresh::class,
        'method' => 'cleanupTokens',
        'middleware' => MiddlewareManager::adminEndpoint()
    ]
];

/**
 * Example of how to integrate these routes into your existing routing system:
 */

function handleRefreshTokenRoute(string $method, string $path): bool
{
    global $authRoutes;
    
    $normalizedPath = '/' . trim($path, '/');
    $routeKey = $method . ' ' . ltrim($normalizedPath, '/');
    
    // Check for exact route match
    if (isset($authRoutes[$routeKey])) {
        $route = $authRoutes[$routeKey];
        executeRefreshTokenRoute($route, []);
        return true;
    }
    
    // Check for parameterized routes
    foreach ($authRoutes as $pattern => $route) {
        if (matchesParameterizedRoute($pattern, $method, $normalizedPath)) {
            $params = extractRouteParameters($pattern, $method, $normalizedPath);
            executeRefreshTokenRoute($route, $params);
            return true;
        }
    }
    
    return false; // Route not handled
}

function executeRefreshTokenRoute(array $route, array $params): void
{
    $request = [
        'method' => $_SERVER['REQUEST_METHOD'],
        'path' => $_SERVER['REQUEST_URI'],
        'params' => $params
    ];
    
    $middleware = $route['middleware'];
    $controllerClass = $route['controller'];
    $method = $route['method'];
    
    $middleware->handle($request, function($request) use ($controllerClass, $method) {
        $controller = new $controllerClass($request);
        return $controller->$method();
    });
}

function matchesParameterizedRoute(string $pattern, string $method, string $path): bool
{
    list($routeMethod, $routePath) = explode(' ', $pattern, 2);
    
    if ($routeMethod !== $method) {
        return false;
    }
    
    $routeSegments = explode('/', trim($routePath, '/'));
    $pathSegments = explode('/', trim($path, '/'));
    
    if (count($routeSegments) !== count($pathSegments)) {
        return false;
    }
    
    foreach ($routeSegments as $i => $segment) {
        if (strpos($segment, '{') === 0 && strpos($segment, '}') === strlen($segment) - 1) {
            continue; // Parameter segment
        }
        if ($segment !== $pathSegments[$i]) {
            return false;
        }
    }
    
    return true;
}

function extractRouteParameters(string $pattern, string $method, string $path): array
{
    list($routeMethod, $routePath) = explode(' ', $pattern, 2);
    
    $routeSegments = explode('/', trim($routePath, '/'));
    $pathSegments = explode('/', trim($path, '/'));
    $params = [];
    
    foreach ($routeSegments as $i => $segment) {
        if (strpos($segment, '{') === 0 && strpos($segment, '}') === strlen($segment) - 1) {
            $paramName = trim($segment, '{}');
            $params[$paramName] = $pathSegments[$i];
        }
    }
    
    return $params;
}

// Usage example:
// Add this to your main api.php routing logic:
/*
if (handleRefreshTokenRoute($_SERVER['REQUEST_METHOD'], $path)) {
    return; // Route was handled by refresh token system
}
*/
