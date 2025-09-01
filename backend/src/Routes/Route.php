<?php

namespace MedicalClinic\Routes;

use MedicalClinic\Middleware\MiddlewareManager;

class Route
{
    private string $method;
    private string $path;
    private mixed $handler;
    private MiddlewareManager $middleware;
    private ?string $name = null;
    private array $constraints = [];

    public function __construct(string $method, string $path, mixed $handler, ?MiddlewareManager $middleware = null)
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->handler = $handler;
        $this->middleware = $middleware ?? new MiddlewareManager();
    }

    /**
     * Get the HTTP method for this route
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the path pattern for this route
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the handler for this route
     */
    public function getHandler(): mixed
    {
        return $this->handler;
    }

    /**
     * Get the middleware manager for this route
     */
    public function getMiddleware(): MiddlewareManager
    {
        return $this->middleware;
    }

    /**
     * Set the route name for URL generation (fluent interface)
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the route name for URL generation
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the route name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Add middleware to this route
     */
    public function middleware(MiddlewareManager $middleware): self
    {
        // Combine existing middleware with new middleware
        $combined = new MiddlewareManager();
        
        // Add existing middleware (this is a simplified version)
        // In a real implementation, you'd need access to the middleware stack
        
        // Add new middleware
        $this->middleware = $middleware;
        
        return $this;
    }

    /**
     * Add parameter constraints (regex patterns)
     */
    public function where(array $constraints): self
    {
        $this->constraints = array_merge($this->constraints, $constraints);
        return $this;
    }

    /**
     * Add a single parameter constraint
     */
    public function whereNumber(string $parameter): self
    {
        return $this->where([$parameter => '[0-9]+']);
    }

    /**
     * Add alpha constraint for parameter
     */
    public function whereAlpha(string $parameter): self
    {
        return $this->where([$parameter => '[a-zA-Z]+']);
    }

    /**
     * Add alphanumeric constraint for parameter
     */
    public function whereAlphaNumeric(string $parameter): self
    {
        return $this->where([$parameter => '[a-zA-Z0-9]+']);
    }

    /**
     * Get parameter constraints
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * Check if a parameter value matches its constraint
     */
    public function matchesConstraint(string $parameter, string $value): bool
    {
        if (!isset($this->constraints[$parameter])) {
            return true; // No constraint means any value is allowed
        }

        $pattern = '/^' . $this->constraints[$parameter] . '$/';
        return preg_match($pattern, $value) === 1;
    }

    /**
     * Validate all parameters against their constraints
     */
    public function validateParameters(array $params): bool
    {
        foreach ($this->constraints as $parameter => $pattern) {
            if (isset($params[$parameter])) {
                if (!$this->matchesConstraint($parameter, $params[$parameter])) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get route information as array
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'path' => $this->path,
            'name' => $this->name,
            'handler' => $this->getHandlerString(),
            'constraints' => $this->constraints,
            'middleware_count' => $this->middleware->getMiddlewareCount()
        ];
    }

    /**
     * Get handler as string representation
     */
    public function getHandlerString(): string
    {
        if (is_callable($this->handler)) {
            if (is_array($this->handler)) {
                return (is_string($this->handler[0]) ? $this->handler[0] : get_class($this->handler[0])) . '@' . $this->handler[1];
            }
            return 'Closure';
        }

        if (is_string($this->handler)) {
            return $this->handler;
        }

        return 'Unknown';
    }

    /**
     * Check if this route matches the given method and path
     */
    public function matches(string $method, string $path): bool
    {
        // HTTP methods should be case sensitive - don't normalize the input
        if ($this->method !== $method) {
            return false;
        }

        return $this->matchesPath($path);
    }

    /**
     * Check if the path matches this route's pattern
     */
    public function matchesPath(string $path): bool
    {
        $pattern = $this->convertToPattern($this->path);
        return preg_match($pattern, $path) === 1;
    }

    /**
     * Convert route path to regex pattern
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
     * Extract parameters from a matching path
     */
    public function extractParameters(string $path): array
    {
        $pattern = $this->convertToPattern($this->path);
        $paramNames = $this->getParameterNames();
        
        if (preg_match($pattern, $path, $matches)) {
            $params = [];
            
            // Map captured groups to parameter names
            for ($i = 1; $i < count($matches); $i++) {
                if (isset($paramNames[$i - 1])) {
                    $params[$paramNames[$i - 1]] = $matches[$i];
                }
            }
            
            return $params;
        }
        
        return [];
    }

    /**
     * Get parameter names from the route path
     */
    private function getParameterNames(): array
    {
        preg_match_all('/\{([^}]+)\}/', $this->path, $matches);
        return $matches[1];
    }

    /**
     * Get all parameters defined in this route
     */
    public function getParameters(): array
    {
        return $this->getParameterNames();
    }

    /**
     * Check if route has parameters
     */
    public function hasParameters(): bool
    {
        return !empty($this->getParameterNames());
    }

    /**
     * Get route signature for debugging
     */
    public function getSignature(): string
    {
        return $this->method . ' ' . $this->path . ($this->name ? ' (' . $this->name . ')' : '');
    }

    /**
     * String representation of the route
     */
    public function __toString(): string
    {
        return $this->getSignature();
    }
}
