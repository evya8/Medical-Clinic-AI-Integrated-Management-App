<?php

namespace MedicalClinic\Middleware;

class RoleMiddleware extends BaseMiddleware
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles)
    {
        parent::__construct();
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(array $request, callable $next): mixed
    {
        // Check if user was authenticated by previous middleware
        if (!isset($request['auth_user'])) {
            $this->errorResponse('Authentication required for role check', 401);
        }

        $user = $request['auth_user'];
        
        if (!$user->hasAnyRole($this->allowedRoles)) {
            $this->log('Role authorization failed', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'required_roles' => $this->allowedRoles,
                'method' => $request['method'] ?? 'UNKNOWN',
                'path' => $request['path'] ?? 'UNKNOWN'
            ]);

            $this->errorResponse(
                'Insufficient permissions. Required roles: ' . implode(', ', $this->allowedRoles),
                403
            );
        }

        $this->log('Role authorization passed', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'allowed_roles' => $this->allowedRoles
        ]);

        return $next($request);
    }

    /**
     * Static factory method for easy usage
     */
    public static function roles(array $roles): self
    {
        return new self($roles);
    }

    /**
     * Static factory for admin-only access
     */
    public static function adminOnly(): self
    {
        return new self(['admin']);
    }

    /**
     * Static factory for doctor access (admin + doctor)
     */
    public static function doctorAccess(): self
    {
        return new self(['admin', 'doctor']);
    }

    /**
     * Static factory for any authenticated user
     */
    public static function anyAuthenticated(): self
    {
        return new self(['admin', 'doctor', 'nurse', 'receptionist']); // All possible roles
    }
}
