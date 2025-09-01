<?php

namespace MedicalClinic\Controllers;

use MedicalClinic\Utils\JWTAuthRefresh;
use MedicalClinic\Models\User;
use MedicalClinic\Models\RefreshToken;

class AuthControllerRefresh extends BaseController
{
    public function login(): void
    {
        $this->validateRequired(['email', 'password'], $this->input);
        
        $email = $this->sanitizeString($this->input['email']);
        $password = $this->input['password'];

        // Find user by email or username using the User model
        $user = User::findByEmailOrUsername($email);

        if (!$user || !password_verify($password, $user->password_hash)) {
            $this->error('Invalid credentials', 401);
        }

        if (!$user->isActive()) {
            $this->error('Account is deactivated', 401);
        }

        // Generate token pair (access + refresh)
        $tokens = JWTAuthRefresh::generateTokenPair($user->toArray());
        
        // Update last login
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->save();

        // Remove sensitive data from user response
        $userArray = $user->toArray();
        unset($userArray['password_hash']);

        $this->success([
            'user' => $userArray,
            'tokens' => $tokens
        ], 'Login successful');
    }

    public function refreshToken(): void
    {
        $this->validateRequired(['refresh_token'], $this->input);
        
        $refreshToken = $this->input['refresh_token'];
        $tokens = JWTAuthRefresh::refreshTokens($refreshToken);
        
        if (!$tokens) {
            $this->error('Invalid or expired refresh token', 401);
        }
        
        $this->success([
            'tokens' => $tokens
        ], 'Tokens refreshed successfully');
    }

    public function logout(): void
    {
        // Get the current user from token
        $token = JWTAuthRefresh::getTokenFromHeader();
        $payload = JWTAuthRefresh::validateAccessToken($token);
        
        if ($payload && isset($payload['user_id'])) {
            // Revoke all refresh tokens for this user (logout from all devices)
            $revokedCount = JWTAuthRefresh::revokeAllUserTokens($payload['user_id']);
            
            $this->success([
                'revoked_tokens' => $revokedCount
            ], 'Logged out successfully from all devices');
        } else {
            $this->success(null, 'Logged out successfully');
        }
    }

    public function logoutSingle(): void
    {
        $this->validateRequired(['refresh_token'], $this->input);
        
        $refreshToken = $this->input['refresh_token'];
        $payload = JWTAuthRefresh::validateRefreshToken($refreshToken);
        
        if ($payload && isset($payload['user_id'])) {
            $revoked = JWTAuthRefresh::revokeRefreshToken($payload['user_id'], $refreshToken);
            
            if ($revoked) {
                $this->success(null, 'Logged out from this device successfully');
            } else {
                $this->error('Token not found or already revoked', 400);
            }
        } else {
            $this->error('Invalid refresh token', 401);
        }
    }

    public function me(): void
    {
        $token = JWTAuthRefresh::getTokenFromHeader();
        $payload = JWTAuthRefresh::validateAccessToken($token);
        
        if (!$payload) {
            $this->error('Invalid or expired token', 401);
        }
        
        // Get fresh user data from database
        $user = User::find($payload['user_id']);
        
        if (!$user || !$user->isActive()) {
            $this->error('User not found or inactive', 401);
        }

        $userData = $user->toArray();
        unset($userData['password_hash']);
        
        $this->success($userData, 'User data retrieved');
    }

    public function activeSessions(): void
    {
        $token = JWTAuthRefresh::getTokenFromHeader();
        $payload = JWTAuthRefresh::validateAccessToken($token);
        
        if (!$payload) {
            $this->error('Invalid or expired token', 401);
        }

        $activeTokens = RefreshToken::getActiveTokensForUser($payload['user_id']);
        $sessions = array_map(fn($token) => $token->getDisplayInfo(), $activeTokens);
        
        $this->success([
            'sessions' => $sessions,
            'total_active' => count($sessions)
        ], 'Active sessions retrieved');
    }

    public function revokeSession(): void
    {
        $this->validateRequired(['jti'], $this->input);
        
        $token = JWTAuthRefresh::getTokenFromHeader();
        $payload = JWTAuthRefresh::validateAccessToken($token);
        
        if (!$payload) {
            $this->error('Invalid or expired token', 401);
        }

        $jti = $this->input['jti'];
        $refreshToken = RefreshToken::findActiveByUserAndJTI($payload['user_id'], $jti);
        
        if (!$refreshToken) {
            $this->error('Session not found or already revoked', 404);
        }

        if ($refreshToken->revoke()) {
            $this->success(null, 'Session revoked successfully');
        } else {
            $this->error('Failed to revoke session', 500);
        }
    }

    public function tokenStats(): void
    {
        $token = JWTAuthRefresh::getTokenFromHeader();
        $payload = JWTAuthRefresh::validateAccessToken($token);
        
        if (!$payload) {
            $this->error('Invalid or expired token', 401);
        }

        // Only admins can view system token statistics
        $user = User::find($payload['user_id']);
        if (!$user || $user->role !== 'admin') {
            $this->error('Admin access required', 403);
        }

        $stats = RefreshToken::getTokenStats();
        $trends = RefreshToken::getTokenTrends(7);
        
        $this->success([
            'statistics' => $stats,
            'trends' => $trends
        ], 'Token statistics retrieved');
    }

    public function cleanupTokens(): void
    {
        $token = JWTAuthRefresh::getTokenFromHeader();
        $payload = JWTAuthRefresh::validateAccessToken($token);
        
        if (!$payload) {
            $this->error('Invalid or expired token', 401);
        }

        // Only admins can run cleanup
        $user = User::find($payload['user_id']);
        if (!$user || $user->role !== 'admin') {
            $this->error('Admin access required', 403);
        }

        $expiredCleaned = RefreshToken::cleanupExpired();
        $revokedCleaned = RefreshToken::cleanupOldRevoked(30);
        
        $this->success([
            'expired_tokens_cleaned' => $expiredCleaned,
            'old_revoked_tokens_cleaned' => $revokedCleaned,
            'total_cleaned' => $expiredCleaned + $revokedCleaned
        ], 'Token cleanup completed');
    }

    // Legacy method for backward compatibility with existing middleware/routes
    public function getCurrentUser(): ?array
    {
        $token = JWTAuthRefresh::getTokenFromHeader();
        if (!$token) {
            return null;
        }

        $payload = JWTAuthRefresh::validateAccessToken($token);
        if (!$payload) {
            return null;
        }

        $user = User::find($payload['user_id']);
        if (!$user || !$user->isActive()) {
            return null;
        }

        return $user->toArray();
    }
}
