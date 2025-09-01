<?php

namespace MedicalClinic\Controllers;

class UserController extends BaseControllerMiddleware
{
    public function getUsers(): void
    {
        // Admin role validation handled by middleware
        $input = $this->getInput();
        
        // Get query parameters for filtering/pagination
        $search = $input['search'] ?? $_GET['search'] ?? null;
        $role = $input['role'] ?? $_GET['role'] ?? null;
        $active = $input['active'] ?? $_GET['active'] ?? null;
        $limit = (int) ($input['limit'] ?? $_GET['limit'] ?? 50);
        $offset = (int) ($input['offset'] ?? $_GET['offset'] ?? 0);

        $sql = "SELECT u.*, d.specialty, d.license_number 
                FROM users u 
                LEFT JOIN doctors d ON u.id = d.user_id 
                WHERE 1=1";
        $params = [];

        // Add search filter
        if ($search) {
            $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.username LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        // Add role filter
        if ($role) {
            $sql .= " AND u.role = :role";
            $params['role'] = $role;
        }

        // Add active filter
        if ($active !== null) {
            $sql .= " AND u.is_active = :active";
            $params['active'] = (int) $active;
        }

        $sql .= " ORDER BY u.last_name, u.first_name LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $users = $this->db->fetchAll($sql, $params);

        // Remove sensitive data
        foreach ($users as &$user) {
            unset($user['password_hash']);
        }

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM users u WHERE 1=1";
        $countParams = [];
        
        if ($search) {
            $countSql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.username LIKE :search)";
            $countParams['search'] = "%{$search}%";
        }
        if ($role) {
            $countSql .= " AND u.role = :role";
            $countParams['role'] = $role;
        }
        if ($active !== null) {
            $countSql .= " AND u.is_active = :active";
            $countParams['active'] = (int) $active;
        }
        
        $totalCount = $this->db->fetch($countSql, $countParams)['total'];

        $this->paginated($users, $totalCount, ($offset / $limit) + 1, $limit);
    }

    public function getUserById(int $id): void
    {
        // Admin role validation handled by middleware
        $user = $this->db->fetch(
            "SELECT u.*, d.specialty, d.license_number, d.working_days, d.working_hours, d.consultation_duration
             FROM users u 
             LEFT JOIN doctors d ON u.id = d.user_id 
             WHERE u.id = :id",
            ['id' => $id]
        );

        if (!$user) {
            $this->error('User not found', 404);
        }

        // Remove sensitive data
        unset($user['password_hash']);

        $this->success($user, 'User retrieved successfully');
    }

    public function createUser(): void
    {
        // Admin role validation handled by middleware
        $input = $this->getInput(); // Already validated by middleware

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

        try {
            $userId = $this->db->insert('users', $userData);

            // If user is a doctor, create doctor profile
            if ($input['role'] === 'doctor') {
                $this->createDoctorProfile($userId, $input);
            }

            $this->success(['id' => $userId], 'User created successfully');
        } catch (\Exception $e) {
            $this->error('Failed to create user: ' . $e->getMessage(), 500);
        }
    }

    public function updateUser(int $id): void
    {
        // Admin role validation handled by middleware
        $input = $this->getInput();

        // Check if user exists
        $user = $this->db->fetch("SELECT * FROM users WHERE id = :id", ['id' => $id]);
        if (!$user) {
            $this->error('User not found', 404);
        }

        // Prepare update data
        $updateData = [];
        $allowedFields = ['username', 'email', 'role', 'first_name', 'last_name', 'phone', 'is_active'];

        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                if ($field === 'email' && !filter_var($input[$field], FILTER_VALIDATE_EMAIL)) {
                    $this->error('Invalid email format', 400);
                }
                if ($field === 'role' && !in_array($input[$field], ['admin', 'doctor', 'nurse', 'receptionist'])) {
                    $this->error('Invalid role. Must be: admin, doctor, nurse, or receptionist', 400);
                }
                $updateData[$field] = ($field === 'email') ? strtolower(trim($input[$field])) : $this->sanitizeString($input[$field]);
            }
        }

        // Update password if provided
        if (!empty($input['password'])) {
            $updateData['password_hash'] = password_hash($input['password'], PASSWORD_DEFAULT);
        }

        if (empty($updateData)) {
            $this->error('No valid fields to update', 400);
        }

        // Check for duplicate username/email (excluding current user)
        if (isset($updateData['username']) || isset($updateData['email'])) {
            $checkSql = "SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id";
            $checkParams = [
                'username' => $updateData['username'] ?? $user['username'],
                'email' => $updateData['email'] ?? $user['email'],
                'id' => $id
            ];
            
            $duplicate = $this->db->fetch($checkSql, $checkParams);
            if ($duplicate) {
                $this->error('Username or email already exists', 400);
            }
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        try {
            $this->db->update('users', $updateData, 'id = :id', ['id' => $id]);

            // Handle role changes
            if (isset($input['role'])) {
                $this->handleRoleChange($id, $user['role'], $input['role'], $input);
            }

            $this->success(null, 'User updated successfully');
        } catch (\Exception $e) {
            $this->error('Failed to update user: ' . $e->getMessage(), 500);
        }
    }

    public function deleteUser(int $id): void
    {
        // Admin role validation handled by middleware
        
        // Don't allow deletion of current user
        $currentUser = $this->getUser();
        if ($currentUser && $currentUser->id == $id) {
            $this->error('Cannot delete your own account', 400);
        }

        // Check if user exists
        $user = $this->db->fetch("SELECT * FROM users WHERE id = :id", ['id' => $id]);
        if (!$user) {
            $this->error('User not found', 404);
        }

        try {
            // Soft delete - deactivate instead of deleting
            $this->db->update(
                'users', 
                ['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')], 
                'id = :id', 
                ['id' => $id]
            );

            $this->success(null, 'User deactivated successfully');
        } catch (\Exception $e) {
            $this->error('Failed to deactivate user: ' . $e->getMessage(), 500);
        }
    }

    public function activateUser(int $id): void
    {
        // Admin role validation handled by middleware
        
        try {
            $this->db->update(
                'users', 
                ['is_active' => 1, 'updated_at' => date('Y-m-d H:i:s')], 
                'id = :id', 
                ['id' => $id]
            );

            $this->success(null, 'User activated successfully');
        } catch (\Exception $e) {
            $this->error('Failed to activate user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get current user profile
     */
    public function getProfile(): void
    {
        // Auth handled by middleware
        $user = $this->getUser();
        
        $profile = $this->db->fetch(
            "SELECT u.*, d.specialty, d.license_number, d.working_days, d.working_hours, d.consultation_duration
             FROM users u 
             LEFT JOIN doctors d ON u.id = d.user_id 
             WHERE u.id = :id",
            ['id' => $user->id]
        );

        // Remove sensitive data
        unset($profile['password_hash']);

        $this->success($profile, 'Profile retrieved successfully');
    }

    /**
     * Update current user profile
     */
    public function updateProfile(): void
    {
        // Auth handled by middleware
        $user = $this->getUser();
        $input = $this->getInput();

        $updateData = [];
        $allowedFields = ['first_name', 'last_name', 'phone', 'email'];

        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                if ($field === 'email' && !filter_var($input[$field], FILTER_VALIDATE_EMAIL)) {
                    $this->error('Invalid email format', 400);
                }
                $updateData[$field] = ($field === 'email') ? strtolower(trim($input[$field])) : $this->sanitizeString($input[$field]);
            }
        }

        // Update password if provided
        if (!empty($input['password'])) {
            $updateData['password_hash'] = password_hash($input['password'], PASSWORD_DEFAULT);
        }

        if (empty($updateData)) {
            $this->error('No valid fields to update', 400);
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        try {
            $this->db->update('users', $updateData, 'id = :id', ['id' => $user->id]);
            $this->success(null, 'Profile updated successfully');
        } catch (\Exception $e) {
            $this->error('Failed to update profile: ' . $e->getMessage(), 500);
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

    private function handleRoleChange(int $userId, string $oldRole, string $newRole, array $input): void
    {
        if ($oldRole === $newRole) {
            return;
        }

        // If changing from doctor to another role, remove doctor profile
        if ($oldRole === 'doctor' && $newRole !== 'doctor') {
            $this->db->delete('doctors', 'user_id = :user_id', ['user_id' => $userId]);
        }

        // If changing to doctor from another role, create doctor profile
        if ($oldRole !== 'doctor' && $newRole === 'doctor') {
            $this->createDoctorProfile($userId, $input);
        }
    }
}
