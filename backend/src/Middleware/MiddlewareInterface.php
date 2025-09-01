<?php

namespace MedicalClinic\Middleware;

interface MiddlewareInterface
{
    /**
     * Handle an incoming request through the middleware
     *
     * @param array $request The request data including method, path, and parameters
     * @param callable $next The next middleware in the stack
     * @return mixed The response from the middleware chain
     */
    public function handle(array $request, callable $next): mixed;
}
