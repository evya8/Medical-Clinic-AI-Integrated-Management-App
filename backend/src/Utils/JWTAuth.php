<?php

namespace MedicalClinic\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Exception;

class JWTAuth
{
    private static string $secretKey;
    private static string $algorithm = 'HS256';
    private static int $expiryTime;

    public static function init(): void
    {
        $config = require __DIR__ . '/../../config/app.php';
        self::$secretKey = $config['jwt']['secret'];
        self::$expiryTime = $config['jwt']['expiry'];
    }

    public static function generateToken(array $user): string
    {
        self::init();

        $issuedAt = time();
        $expiry = $issuedAt + self::$expiryTime;

        $payload = [
            'iss' => 'medical-clinic',           // Issuer
            'iat' => $issuedAt,                  // Issued at
            'exp' => $expiry,                    // Expiry
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name']
        ];

        return JWT::encode($payload, self::$secretKey, self::$algorithm);
    }

    public static function validateToken(string $token): ?array
    {
        try {
            self::init();
            
            $decoded = JWT::decode($token, new Key(self::$secretKey, self::$algorithm));
            
            // Convert to array
            return [
                'id' => $decoded->user_id,
                'username' => $decoded->username,
                'email' => $decoded->email,
                'role' => $decoded->role,
                'first_name' => $decoded->first_name,
                'last_name' => $decoded->last_name
            ];
            
        } catch (ExpiredException $e) {
            return null; // Token expired
        } catch (Exception $e) {
            return null; // Invalid token
        }
    }

    public static function getTokenFromHeader(): ?string
    {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            return null;
        }

        $authHeader = $headers['Authorization'];
        
        // Expected format: "Bearer <token>"
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return null;
        }

        return $matches[1];
    }

    public static function refreshToken(string $token): ?string
    {
        $user = self::validateToken($token);
        
        if (!$user) {
            return null;
        }

        // Generate new token with fresh expiry
        return self::generateToken($user);
    }

    public static function isTokenExpiringSoon(string $token): bool
    {
        try {
            self::init();
            
            $decoded = JWT::decode($token, new Key(self::$secretKey, self::$algorithm));
            $timeUntilExpiry = $decoded->exp - time();
            
            // Return true if token expires in less than 1 hour
            return $timeUntilExpiry < 3600;
            
        } catch (Exception $e) {
            return true; // If can't decode, consider it expiring soon
        }
    }
}
