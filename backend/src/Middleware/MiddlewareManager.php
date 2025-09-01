<?php

namespace MedicalClinic\Middleware;

class MiddlewareManager
{
    private array $middlewareStack = [];

    /**
     * Add middleware to the stack
     */
    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewareStack[] = $middleware;
        return $this;
    }

    /**
     * Add authentication middleware
     */
    public function auth(): self
    {
        return $this->add(new AuthMiddleware());
    }

    /**
     * Add role-based authorization middleware
     */
    public function roles(array $roles): self
    {
        return $this->add(RoleMiddleware::roles($roles));
    }

    /**
     * Add admin-only access middleware
     */
    public function adminOnly(): self
    {
        return $this->add(RoleMiddleware::adminOnly());
    }

    /**
     * Add doctor access middleware (admin + doctor)
     */
    public function doctorAccess(): self
    {
        return $this->add(RoleMiddleware::doctorAccess());
    }

    /**
     * Add validation middleware
     */
    public function validate(array $rules): self
    {
        return $this->add(ValidationMiddleware::rules($rules));
    }

    /**
     * Add user validation middleware
     */
    public function validateUser(): self
    {
        return $this->add(ValidationMiddleware::userValidation());
    }

    /**
     * Add patient validation middleware
     */
    public function validatePatient(): self
    {
        return $this->add(ValidationMiddleware::patientValidation());
    }

    /**
     * Add appointment validation middleware
     */
    public function validateAppointment(): self
    {
        return $this->add(ValidationMiddleware::appointmentValidation());
    }

    /**
     * Add login validation middleware
     */
    public function validateLogin(): self
    {
        return $this->add(ValidationMiddleware::loginValidation());
    }

    /**
     * Execute the middleware stack
     */
    public function handle(array $request, callable $finalHandler): mixed
    {
        return $this->executeStack($request, $finalHandler, 0);
    }

    /**
     * Recursively execute middleware stack
     */
    private function executeStack(array $request, callable $finalHandler, int $index): mixed
    {
        // If we've reached the end of the middleware stack, execute the final handler
        if ($index >= count($this->middlewareStack)) {
            return $finalHandler($request);
        }

        $middleware = $this->middlewareStack[$index];
        
        // Execute current middleware with next middleware in chain
        return $middleware->handle($request, function($request) use ($finalHandler, $index) {
            return $this->executeStack($request, $finalHandler, $index + 1);
        });
    }

    /**
     * Get middleware stack count
     */
    public function getMiddlewareCount(): int
    {
        return count($this->middlewareStack);
    }

    /**
     * Check if stack is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->middlewareStack);
    }

    /**
     * Clear all middleware
     */
    public function clear(): self
    {
        $this->middlewareStack = [];
        return $this;
    }

    /**
     * Static factory methods for common middleware combinations
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Create middleware for admin-only endpoints
     */
    public static function adminEndpoint(): self
    {
        return (new self())
            ->auth()
            ->adminOnly();
    }

    /**
     * Create middleware for doctor endpoints
     */
    public static function doctorEndpoint(): self
    {
        return (new self())
            ->auth()
            ->doctorAccess();
    }

    /**
     * Create middleware for authenticated endpoints (any role)
     */
    public static function authEndpoint(): self
    {
        return (new self())
            ->auth();
    }

    /**
     * Create middleware for public endpoints (no auth required)
     */
    public static function publicEndpoint(): self
    {
        return new self(); // Empty middleware stack
    }

    /**
     * Create middleware for login endpoint
     */
    public static function loginEndpoint(): self
    {
        return (new self())
            ->validateLogin();
    }

    /**
     * Create middleware for user creation (admin only)
     */
    public static function userCreationEndpoint(): self
    {
        return (new self())
            ->auth()
            ->adminOnly()
            ->validateUser();
    }

    /**
     * Create middleware for patient operations
     */
    public static function patientEndpoint(): self
    {
        return (new self())
            ->auth()
            ->doctorAccess()
            ->validatePatient();
    }

    /**
     * Create middleware for appointment operations
     */
    public static function appointmentEndpoint(): self
    {
        return (new self())
            ->auth()
            ->doctorAccess()
            ->validateAppointment();
    }
}
