<?php

use MedicalClinic\Utils\Database;

class CreateRefreshTokensTable
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS refresh_tokens (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            jti VARCHAR(32) NOT NULL UNIQUE,
            expires_at TIMESTAMP NOT NULL,
            revoked_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            user_agent TEXT,
            ip_address VARCHAR(45),
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            
            INDEX idx_user_id (user_id),
            INDEX idx_token_hash (token_hash),
            INDEX idx_jti (jti),
            INDEX idx_expires_at (expires_at),
            INDEX idx_revoked_at (revoked_at),
            INDEX idx_user_active (user_id, revoked_at, expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
        
        echo "✅ Created refresh_tokens table\n";
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS refresh_tokens";
        $this->db->query($sql);
        
        echo "❌ Dropped refresh_tokens table\n";
    }
}
