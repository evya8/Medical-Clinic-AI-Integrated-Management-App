<?php

namespace MedicalClinic\Models;

class RefreshToken extends BaseModel
{
    protected static string $table = 'refresh_tokens';

    // Relationships
    public function user(): ?User
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Static query methods
    public static function findByJTI(string $jti): ?static
    {
        $results = static::where('jti', $jti);
        return $results[0] ?? null;
    }

    public static function findActiveByUserAndJTI(int $userId, string $jti): ?static
    {
        static::initializeDatabase();
        $result = static::$db->fetch(
            "SELECT * FROM refresh_tokens 
             WHERE user_id = :user_id 
             AND jti = :jti 
             AND expires_at > NOW() 
             AND revoked_at IS NULL",
            [
                'user_id' => $userId,
                'jti' => $jti
            ]
        );
        
        return $result ? new static($result, true) : null;
    }

    public static function getActiveTokensForUser(int $userId): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT * FROM refresh_tokens 
             WHERE user_id = :user_id 
             AND expires_at > NOW() 
             AND revoked_at IS NULL 
             ORDER BY created_at DESC",
            ['user_id' => $userId]
        );
        
        return array_map(fn($row) => new static($row, true), $results);
    }

    public static function getExpiredTokens(): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT * FROM refresh_tokens 
             WHERE expires_at <= NOW() 
             AND revoked_at IS NULL"
        );
        
        return array_map(fn($row) => new static($row, true), $results);
    }

    public static function getRevokedTokens(int $days = 30): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT * FROM refresh_tokens 
             WHERE revoked_at IS NOT NULL 
             AND revoked_at >= DATE_SUB(NOW(), INTERVAL :days DAY) 
             ORDER BY revoked_at DESC",
            ['days' => $days]
        );
        
        return array_map(fn($row) => new static($row, true), $results);
    }

    // Instance methods
    public function isActive(): bool
    {
        return $this->revoked_at === null && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        return strtotime($this->expires_at) <= time();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function revoke(): bool
    {
        $this->revoked_at = date('Y-m-d H:i:s');
        return $this->save();
    }

    public function getAge(): int
    {
        $created = new \DateTime($this->created_at);
        $now = new \DateTime();
        return $now->diff($created)->days;
    }

    public function getAgeHours(): int
    {
        $created = new \DateTime($this->created_at);
        $now = new \DateTime();
        $diff = $now->diff($created);
        return ($diff->days * 24) + $diff->h;
    }

    public function getTimeUntilExpiry(): int
    {
        $expires = new \DateTime($this->expires_at);
        $now = new \DateTime();
        $diff = $now->diff($expires);
        
        if ($expires < $now) {
            return 0; // Already expired
        }
        
        return ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i; // Minutes until expiry
    }

    public function getFormattedAge(): string
    {
        $hours = $this->getAgeHours();
        
        if ($hours < 1) {
            return 'Just created';
        } elseif ($hours < 24) {
            return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
        } else {
            $days = floor($hours / 24);
            return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
        }
    }

    public function getFormattedExpiry(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }
        
        $minutes = $this->getTimeUntilExpiry();
        
        if ($minutes < 60) {
            return $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ' remaining';
        } elseif ($minutes < 1440) { // Less than 24 hours
            $hours = floor($minutes / 60);
            return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' remaining';
        } else {
            $days = floor($minutes / 1440);
            return $days . ' day' . ($days === 1 ? '' : 's') . ' remaining';
        }
    }

    public function getDisplayInfo(): array
    {
        $user = $this->user();
        
        return [
            'id' => $this->id,
            'jti' => $this->jti,
            'user_id' => $this->user_id,
            'user_name' => $user?->getFullName(),
            'user_email' => $user?->email,
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_revoked' => $this->isRevoked(),
            'age' => $this->getFormattedAge(),
            'expiry' => $this->getFormattedExpiry(),
            'user_agent' => $this->user_agent,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at,
            'expires_at' => $this->expires_at,
            'revoked_at' => $this->revoked_at
        ];
    }

    // Cleanup methods
    public static function cleanupExpired(): int
    {
        static::initializeDatabase();
        $stmt = static::$db->query(
            "DELETE FROM refresh_tokens 
             WHERE expires_at <= NOW()"
        );
        
        return $stmt->rowCount();
    }

    public static function cleanupOldRevoked(int $days = 30): int
    {
        static::initializeDatabase();
        $stmt = static::$db->query(
            "DELETE FROM refresh_tokens 
             WHERE revoked_at IS NOT NULL 
             AND revoked_at < DATE_SUB(NOW(), INTERVAL :days DAY)",
            ['days' => $days]
        );
        
        return $stmt->rowCount();
    }

    public static function revokeAllForUser(int $userId): int
    {
        static::initializeDatabase();
        $stmt = static::$db->query(
            "UPDATE refresh_tokens 
             SET revoked_at = NOW() 
             WHERE user_id = :user_id 
             AND revoked_at IS NULL",
            ['user_id' => $userId]
        );
        
        return $stmt->rowCount();
    }

    // Statistics
    public static function getTokenStats(): array
    {
        static::initializeDatabase();
        $stats = static::$db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN revoked_at IS NULL AND expires_at > NOW() THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN revoked_at IS NOT NULL THEN 1 ELSE 0 END) as revoked,
                SUM(CASE WHEN expires_at <= NOW() THEN 1 ELSE 0 END) as expired,
                COUNT(DISTINCT user_id) as unique_users,
                AVG(TIMESTAMPDIFF(HOUR, created_at, COALESCE(revoked_at, expires_at))) as avg_lifetime_hours
             FROM refresh_tokens"
        );
        
        return [
            'total_tokens' => (int) $stats['total'],
            'active_tokens' => (int) $stats['active'],
            'revoked_tokens' => (int) $stats['revoked'],
            'expired_tokens' => (int) $stats['expired'],
            'unique_users' => (int) $stats['unique_users'],
            'avg_lifetime_hours' => round((float) $stats['avg_lifetime_hours'], 1)
        ];
    }

    public static function getTokenTrends(int $days = 7): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as tokens_created,
                SUM(CASE WHEN revoked_at IS NOT NULL THEN 1 ELSE 0 END) as tokens_revoked
             FROM refresh_tokens 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
             GROUP BY DATE(created_at)
             ORDER BY date",
            ['days' => $days]
        );
        
        return $results;
    }
}
