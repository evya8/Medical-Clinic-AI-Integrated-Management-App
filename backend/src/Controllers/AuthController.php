<?php

namespace MedicalClinic\Controllers;

use MedicalClinic\Utils\JWTAuth;

class AuthController extends BaseController
{
    public function login(): void
    {
        $this->validateRequired(['email', 'password'], $this->input);
        
        $username = $this->sanitizeString($this->input['email']);
        $password = $this->input['password'];

        // Find user by username or email
        $user = $this->db->fetch(
            "SELECT u.*, d.specialty, d.license_number 
             FROM users u 
             LEFT JOIN doctors d ON u.id = d.user_id 
             WHERE u.username = :username OR u.email = :email",
            ['username' => $username, 'email' => $username]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->error('Invalid credentials', 401);
        }

        if (!$user['is_active']) {
            $this->error('Account is deactivated', 401);
        }

        // Generate JWT token
        $token = JWTAuth::generateToken($user);
        
        // Update last login
        $this->db->update(
            'users', 
            ['last_login_at' => date('Y-m-d H:i:s')], 
            'id = :id', 
            ['id' => $user['id']]
        );

        // Remove sensitive data
        unset($user['password_hash']);

        $this->success([
            'user' => $user,
            'token' => $token,
            'expires_in' => 86400 // 24 hours in seconds
        ], 'Login successful');
    }

    public function logout(): void
    {
        // In a more sophisticated implementation, you might:
        // - Add token to a blacklist
        // - Clear server-side sessions
        // For JWT, logout is typically handled client-side by removing the token
        
        $this->success(null, 'Logout successful');
    }

    public function getMe(): void
    {
        $user = $this->requireAuth();
        
        // Get additional user info if they're a doctor
        if ($user['role'] === 'doctor') {
            $doctorInfo = $this->db->fetch(
                "SELECT d.specialty, d.license_number, d.bio, d.qualifications 
                 FROM doctors d 
                 WHERE d.user_id = :user_id",
                ['user_id' => $user['id']]
            );
            
            if ($doctorInfo) {
                $user = array_merge($user, $doctorInfo);
            }
        }
        
        $this->success($user, 'User retrieved successfully');
    }

    public function refreshToken(): void
    {
        $token = JWTAuth::getTokenFromHeader();
        
        if (!$token) {
            $this->error('No token provided', 401);
        }

        $newToken = JWTAuth::refreshToken($token);
        
        if (!$newToken) {
            $this->error('Invalid or expired token', 401);
        }

        $this->success([
            'token' => $newToken,
            'expires_in' => 86400
        ], 'Token refreshed successfully');
    }
}
