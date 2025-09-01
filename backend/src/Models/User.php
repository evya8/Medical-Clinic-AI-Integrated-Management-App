<?php

namespace MedicalClinic\Models;

class User extends BaseModel
{
    protected static string $table = 'users';

    // Relationships
    public function doctor(): ?Doctor
    {
        return $this->hasOne(Doctor::class, 'user_id');
    }

    public function createdAppointments(): array
    {
        return $this->hasMany(Appointment::class, 'created_by');
    }

    public function auditLogs(): array
    {
        return $this->hasMany(HIPAAAuditLog::class, 'user_id');
    }

    // Static finder methods
    public static function findByEmail(string $email): ?static
    {
        $results = static::where('email', $email);
        return $results[0] ?? null;
    }

    public static function findByUsername(string $username): ?static
    {
        $results = static::where('username', $username);
        return $results[0] ?? null;
    }

    public static function findByEmailOrUsername(string $login): ?static
    {
        static::initializeDatabase();
        $result = static::$db->fetch(
            "SELECT * FROM users WHERE email = :login OR username = :login",
            ['login' => $login]
        );
        return $result ? new static($result, true) : null;
    }

    public static function getActiveUsers(): array
    {
        return static::where('is_active', 1);
    }

    public static function getUsersByRole(string $role): array
    {
        return static::where('role', $role);
    }

    public static function getAdmins(): array
    {
        return static::getUsersByRole('admin');
    }

    public static function getDoctors(): array
    {
        return static::getUsersByRole('doctor');
    }

    // Instance methods
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isDoctor(): bool
    {
        return $this->hasRole('doctor');
    }

    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function activate(): bool
    {
        $this->is_active = 1;
        return $this->save();
    }

    public function deactivate(): bool
    {
        $this->is_active = 0;
        return $this->save();
    }

    public function updateLastLogin(): bool
    {
        $this->last_login_at = date('Y-m-d H:i:s');
        return $this->save();
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password_hash);
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = password_hash($password, PASSWORD_DEFAULT);
    }

    // Override toArray to exclude sensitive data
    public function toArray(): array
    {
        $data = parent::toArray();
        unset($data['password_hash']);
        return $data;
    }

    public function toArrayWithSensitive(): array
    {
        return parent::toArray();
    }
}
