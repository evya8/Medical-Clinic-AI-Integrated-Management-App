<?php

namespace MedicalClinic\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Exception;
use MedicalClinic\Models\User;

class JWTAuthRefresh
{
    private static string $accessSecretKey;
    private static string $refreshSecretKey;
    private static string $algorithm = 'HS256';
    private static int $accessTokenExpiry = 900;      // 15 minutes
    private static int $refreshTokenExpiry = 604800;  // 7 days

    public static function init(): void
    {
        $config = require __DIR__ . '/../../config/app.php';
        self::$accessSecretKey = $config['jwt']['access_secret'] ?? $config['jwt']['secret'] ?? 'default-access-secret';
        self::$refreshSecretKey = $config['jwt']['refresh_secret'] ?? $config['jwt']['secret'] ?? 'default-refresh-secret';
        self::$accessTokenExpiry = $config['jwt']['access_expiry'] ?? 900;
        self::$refreshTokenExpiry = $config['jwt']['refresh_expiry'] ?? 604800;
    }

    /**
     * Generate both access and refresh tokens for a user
     */
    public static function generateTokenPair(array $user): array
    {
        self::init();
        
        $accessToken = self::generateAccessToken($user);
        $refreshToken = self::generateRefreshToken($user);
        
        // Store refresh token in database
        self::storeRefreshToken($user['id'], $refreshToken);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'access_expires_in' => self::$accessTokenExpiry,
            'refresh_expires_in' => self::$refreshTokenExpiry,
            'token_type' => 'Bearer'
        ];
    }

    /**
     * Generate access token (short-lived)
     */
    private static function generateAccessToken(array $user): string
    {
        $issuedAt = time();
        $expiry = $issuedAt + self::$accessTokenExpiry;

        $payload = [
            'iss' => 'medical-clinic',
            'iat' => $issuedAt,
            'exp' => $expiry,
            'type' => 'access',
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name']
        ];

        return JWT::encode($payload, self::$accessSecretKey, self::$algorithm);
    }

    /**
     * Generate refresh token (long-lived)
     */
    private static function generateRefreshToken(array $user): string
    {
        $issuedAt = time();
        $expiry = $issuedAt + self::$refreshTokenExpiry;
        $jti = self::generateJTI();

        $payload = [
            'iss' => 'medical-clinic',
            'iat' => $issuedAt,
            'exp' => $expiry,
            'type' => 'refresh',
            'user_id' => $user['id'],
            'jti' => $jti
        ];

        return JWT::encode($payload, self::$refreshSecretKey, self::$algorithm);
    }

    /**
     * Validate access token
     */
    public static function validateAccessToken(string $token): ?array
    {
        try {
            self::init();
            $decoded = JWT::decode($token, new Key(self::$accessSecretKey, self::$algorithm));
            
            if ($decoded->type !== 'access') {
                return null;
            }
            
            return (array) $decoded;
        } catch (ExpiredException $e) {
            return null; // Token expired
        } catch (Exception $e) {
            return null; // Invalid token
        }
    }

    /**
     * Validate refresh token
     */
    public static function validateRefreshToken(string $token): ?array
    {
        try {
            self::init();
            $decoded = JWT::decode($token, new Key(self::$refreshSecretKey, self::$algorithm));
            
            if ($decoded->type !== 'refresh') {
                return null;
            }
            
            // Check if token exists and is still valid in database
            if (!self::isRefreshTokenValid($decoded->user_id, $decoded->jti)) {
                return null;
            }
            
            return (array) $decoded;
        } catch (ExpiredException $e) {
            // Token expired - clean it up from database
            if (isset($decoded->user_id, $decoded->jti)) {
                self::revokeRefreshTokenByJTI($decoded->user_id, $decoded->jti);
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Refresh tokens using a valid refresh token
     */
    public static function refreshTokens(string $refreshToken): ?array
    {
        $payload = self::validateRefreshToken($refreshToken);
        
        if (!$payload) {
            return null;
        }

        $user = User::find($payload['user_id']);
        if (!$user || !$user->isActive()) {
            return null;
        }

        // Revoke the used refresh token
        self::revokeRefreshTokenByJTI($payload['user_id'], $payload['jti']);
        
        // Generate new token pair
        return self::generateTokenPair($user->toArray());
    }

    /**
     * Store refresh token in database
     */
    private static function storeRefreshToken(int $userId, string $token): void
    {
        $db = Database::getInstance();
        $decoded = JWT::decode($token, new Key(self::$refreshSecretKey, self::$algorithm));
        
        // Clean up old expired tokens for this user
        $db->query(
            "DELETE FROM refresh_tokens 
             WHERE user_id = :user_id 
             AND (expires_at < NOW() OR revoked_at IS NOT NULL)",
            ['user_id' => $userId]
        );
        
        // Store new token
        $db->query(
            "INSERT INTO refresh_tokens (
                user_id, token_hash, jti, expires_at, 
                user_agent, ip_address, created_at
             ) VALUES (
                :user_id, :token_hash, :jti, :expires_at,
                :user_agent, :ip_address, NOW()
             )",
            [
                'user_id' => $userId,
                'token_hash' => hash('sha256', $token),
                'jti' => $decoded->jti,
                'expires_at' => date('Y-m-d H:i:s', $decoded->exp),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null
            ]
        );
    }

    /**
     * Check if refresh token is valid in database
     */
    private static function isRefreshTokenValid(int $userId, string $jti): bool
    {
        $db = Database::getInstance();
        
        $result = $db->fetch(
            "SELECT id FROM refresh_tokens 
             WHERE user_id = :user_id 
             AND jti = :jti 
             AND expires_at > NOW() 
             AND revoked_at IS NULL",
            [
                'user_id' => $userId,
                'jti' => $jti
            ]
        );
        
        return $result !== null;
    }

    /**
     * Revoke refresh token by JTI
     */
    private static function revokeRefreshTokenByJTI(int $userId, string $jti): void
    {
        $db = Database::getInstance();
        
        $db->query(
            "UPDATE refresh_tokens 
             SET revoked_at = NOW() 
             WHERE user_id = :user_id 
             AND jti = :jti",
            [
                'user_id' => $userId,
                'jti' => $jti
            ]
        );
    }

    /**
     * Revoke all refresh tokens for a user (logout from all devices)
     */
    public static function revokeAllUserTokens(int $userId): int
    {
        $db = Database::getInstance();
        
        $stmt = $db->query(
            "UPDATE refresh_tokens 
             SET revoked_at = NOW() 
             WHERE user_id = :user_id 
             AND revoked_at IS NULL",
            ['user_id' => $userId]
        );
        
        return $stmt->rowCount();
    }

    /**
     * Revoke specific refresh token by token value
     */
    public static function revokeRefreshToken(int $userId, string $token): bool
    {
        $db = Database::getInstance();
        
        $stmt = $db->query(
            "UPDATE refresh_tokens 
             SET revoked_at = NOW() 
             WHERE user_id = :user_id 
             AND token_hash = :token_hash 
             AND revoked_at IS NULL",
            [
                'user_id' => $userId,
                'token_hash' => hash('sha256', $token)
            ]
        );
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Get active refresh tokens for a user
     */
    public static function getUserActiveTokens(int $userId): array
    {
        $db = Database::getInstance();
        
        $results = $db->fetchAll(
            "SELECT jti, created_at, expires_at, user_agent, ip_address 
             FROM refresh_tokens 
             WHERE user_id = :user_id 
             AND expires_at > NOW() 
             AND revoked_at IS NULL 
             ORDER BY created_at DESC",
            ['user_id' => $userId]
        );
        
        return $results;
    }

    /**
     * Clean up expired tokens (for cron job)
     */
    public static function cleanupExpiredTokens(): int
    {
        $db = Database::getInstance();
        
        $stmt = $db->query(
            "DELETE FROM refresh_tokens 
             WHERE expires_at < NOW() 
             OR revoked_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        
        return $stmt->rowCount();
    }

    /**
     * Get refresh token statistics
     */
    public static function getTokenStats(): array
    {
        $db = Database::getInstance();
        
        $stats = $db->fetch(
            "SELECT 
                COUNT(*) as total_tokens,
                SUM(CASE WHEN revoked_at IS NULL AND expires_at > NOW() THEN 1 ELSE 0 END) as active_tokens,
                SUM(CASE WHEN revoked_at IS NOT NULL THEN 1 ELSE 0 END) as revoked_tokens,
                SUM(CASE WHEN expires_at <= NOW() THEN 1 ELSE 0 END) as expired_tokens,
                COUNT(DISTINCT user_id) as users_with_tokens
             FROM refresh_tokens"
        );
        
        return [
            'total_tokens' => (int) $stats['total_tokens'],
            'active_tokens' => (int) $stats['active_tokens'],
            'revoked_tokens' => (int) $stats['revoked_tokens'],
            'expired_tokens' => (int) $stats['expired_tokens'],
            'users_with_tokens' => (int) $stats['users_with_tokens']
        ];
    }

    /**
     * Generate unique token identifier
     */
    private static function generateJTI(): string
    {
        return bin2hex(random_bytes(16));
    }

    // Backward compatibility methods
    public static function generateToken(array $user): string
    {
        return self::generateAccessToken($user);
    }

    public static function validateToken(string $token): ?array
    {
        return self::validateAccessToken($token);
    }

    public static function getTokenFromHeader(): ?string
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            $headers = [];
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                    $headers[$header] = $value;
                }
            }
        }
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
                return trim($matches[1]);
            }
        }
        
        return $_GET['token'] ?? null;
    }
}
