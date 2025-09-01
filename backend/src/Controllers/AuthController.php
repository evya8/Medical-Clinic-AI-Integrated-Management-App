<?php

namespace MedicalClinic\Controllers;

use MedicalClinic\Utils\JWTAuth;

class AuthController extends BaseControllerMiddleware
{
    public function login(): void
    {
        // No auth middleware needed for login
        $input = $this->getInput();
        
        $this->validateRequired(['email', 'password'], $input);
        
        $username = $this->sanitizeString($input['email']);
        $password = $input['password'];

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

        try {
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
        } catch (\Exception $e) {
            $this->error('Login failed: ' . $e->getMessage(), 500);
        }
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
        // Auth handled by middleware - user available via getUser()
        $user = $this->getUser();
        
        if (!$user) {
            $this->error('Authentication required', 401);
        }

        // Convert User model to array for response
        $userData = $user->toArray();
        
        // Get additional user info if they're a doctor
        if ($userData['role'] === 'doctor') {
            $doctorInfo = $this->db->fetch(
                "SELECT d.specialty, d.license_number, d.working_days, d.working_hours, d.consultation_duration
                 FROM doctors d 
                 WHERE d.user_id = :user_id",
                ['user_id' => $userData['id']]
            );
            
            if ($doctorInfo) {
                // Parse JSON fields
                if ($doctorInfo['working_days']) {
                    $doctorInfo['working_days'] = json_decode($doctorInfo['working_days'], true);
                }
                if ($doctorInfo['working_hours']) {
                    $doctorInfo['working_hours'] = json_decode($doctorInfo['working_hours'], true);
                }
                
                $userData = array_merge($userData, $doctorInfo);
            }
        }
        
        // Remove sensitive data
        unset($userData['password_hash']);
        
        $this->success($userData, 'User retrieved successfully');
    }

    public function refreshToken(): void
    {
        // No auth middleware needed for token refresh
        $token = JWTAuth::getTokenFromHeader();
        
        if (!$token) {
            $this->error('No token provided', 401);
        }

        try {
            $newToken = JWTAuth::refreshToken($token);
            
            if (!$newToken) {
                $this->error('Invalid or expired token', 401);
            }

            $this->success([
                'token' => $newToken,
                'expires_in' => 86400
            ], 'Token refreshed successfully');
        } catch (\Exception $e) {
            $this->error('Token refresh failed: ' . $e->getMessage(), 401);
        }
    }

    /**
     * Register a new user (admin only for creating staff accounts)
     */
    public function register(): void
    {
        // Admin role validation handled by middleware
        $input = $this->getInput();

        $this->validateRequired([
            'username', 'email', 'password', 'role',
            'first_name', 'last_name'
        ], $input);

        // Validate email format
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email format', 400);
        }

        // Validate role
        $validRoles = ['admin', 'doctor', 'nurse', 'receptionist'];
        if (!in_array($input['role'], $validRoles)) {
            $this->error('Invalid role. Must be: ' . implode(', ', $validRoles), 400);
        }

        // Check if username or email already exists
        $existingUser = $this->db->fetch(
            "SELECT id FROM users WHERE username = :username OR email = :email",
            [
                'username' => $input['username'],
                'email' => $input['email']
            ]
        );

        if ($existingUser) {
            $this->error('Username or email already exists', 400);
        }

        try {
            // Create user data
            $userData = [
                'username' => $this->sanitizeString($input['username']),
                'email' => strtolower(trim($input['email'])),
                'password_hash' => password_hash($input['password'], PASSWORD_DEFAULT),
                'role' => $input['role'],
                'first_name' => $this->sanitizeString($input['first_name']),
                'last_name' => $this->sanitizeString($input['last_name']),
                'phone' => isset($input['phone']) ? $this->sanitizeString($input['phone']) : null,
                'is_active' => $input['is_active'] ?? true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $userId = $this->db->insert('users', $userData);

            // If user is a doctor, create doctor profile
            if ($input['role'] === 'doctor') {
                $this->createDoctorProfile($userId, $input);
            }

            $this->success(['id' => $userId], 'User registered successfully');
        } catch (\Exception $e) {
            $this->error('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Change password for current user
     */
    public function changePassword(): void
    {
        // Auth handled by middleware
        $user = $this->getUser();
        $input = $this->getInput();

        $this->validateRequired(['current_password', 'new_password'], $input);

        // Verify current password
        $userData = $this->db->fetch("SELECT password_hash FROM users WHERE id = :id", ['id' => $user->id]);
        
        if (!$userData || !password_verify($input['current_password'], $userData['password_hash'])) {
            $this->error('Current password is incorrect', 400);
        }

        // Validate new password strength (basic validation)
        if (strlen($input['new_password']) < 6) {
            $this->error('New password must be at least 6 characters long', 400);
        }

        try {
            // Update password
            $this->db->update(
                'users', 
                [
                    'password_hash' => password_hash($input['new_password'], PASSWORD_DEFAULT),
                    'updated_at' => date('Y-m-d H:i:s')
                ], 
                'id = :id', 
                ['id' => $user->id]
            );

            $this->success(null, 'Password changed successfully');
        } catch (\Exception $e) {
            $this->error('Failed to change password: ' . $e->getMessage(), 500);
        }
    }

    private function createDoctorProfile(int $userId, array $input): void
    {
        $doctorData = [
            'user_id' => $userId,
            'specialty' => isset($input['specialty']) ? $this->sanitizeString($input['specialty']) : null,
            'license_number' => isset($input['license_number']) ? $this->sanitizeString($input['license_number']) : null,
            'consultation_duration' => (int) ($input['consultation_duration'] ?? 30),
            'working_days' => json_encode($input['working_days'] ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
            'working_hours' => json_encode($input['working_hours'] ?? ['start' => '09:00', 'end' => '17:00']),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('doctors', $doctorData);
    }
}
