<?php

namespace MedicalClinic\Middleware;

use MedicalClinic\Utils\JWTAuth;
use MedicalClinic\Models\User;

class AuthMiddleware extends BaseMiddleware
{
    public function handle(array $request, callable $next): mixed
    {
        $token = $this->getTokenFromRequest();
        
        if (!$token) {
            $this->errorResponse('Authentication token required', 401);
        }

        $payload = JWTAuth::validateToken($token);
        
        if (!$payload) {
            $this->errorResponse('Invalid or expired token', 401);
        }

        // Get full user data from database
        $user = User::find($payload['user_id']);
        
        if (!$user) {
            $this->errorResponse('User not found', 401);
        }

        if (!$user->isActive()) {
            $this->errorResponse('User account is inactive', 401);
        }

        // Add user and auth data to request context
        $request['auth_user'] = $user;
        $request['auth_payload'] = $payload;
        $request['user_id'] = $user->id;
        $request['user_role'] = $user->role;

        $this->log('User authenticated', [
            'user_id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
            'method' => $request['method'] ?? 'UNKNOWN',
            'path' => $request['path'] ?? 'UNKNOWN'
        ]);

        return $next($request);
    }

    /**
     * Extract token from various request sources
     */
    private function getTokenFromRequest(): ?string
    {
        // 1. Try Authorization header (Bearer token)
        $authHeader = $this->getHeader('Authorization');
        if ($authHeader && preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        // 2. Try query parameter
        if (isset($_GET['token']) && !empty($_GET['token'])) {
            return $_GET['token'];
        }

        // 3. Try POST data
        if (isset($_POST['token']) && !empty($_POST['token'])) {
            return $_POST['token'];
        }

        // 4. Try JSON body
        $input = $this->getInput();
        if (isset($input['token']) && !empty($input['token'])) {
            return $input['token'];
        }

        return null;
    }

    /**
     * Get the current authenticated user (static helper)
     */
    public static function getCurrentUser(): ?User
    {
        // This can be called from controllers after middleware has run
        // For now, we'll extract the token and validate it directly
        $middleware = new self();
        $token = $middleware->getTokenFromRequest();
        
        if (!$token) {
            return null;
        }

        $payload = JWTAuth::validateToken($token);
        if (!$payload) {
            return null;
        }

        return User::find($payload['user_id']);
    }

    /**
     * Require authentication and return user (static helper)
     */
    public static function requireAuth(): User
    {
        $user = self::getCurrentUser();
        
        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Authentication required',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit;
        }

        return $user;
    }

    /**
     * Check if current user has required role
     */
    public static function requireRole(array $allowedRoles): User
    {
        $user = self::requireAuth();
        
        if (!$user->hasAnyRole($allowedRoles)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Insufficient permissions. Required roles: ' . implode(', ', $allowedRoles),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit;
        }

        return $user;
    }
}
