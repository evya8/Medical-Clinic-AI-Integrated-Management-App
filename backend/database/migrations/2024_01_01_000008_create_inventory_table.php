<?php

use MedicalClinic\Utils\Database;

class CreateInventoryTable
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS inventory (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_name VARCHAR(200) NOT NULL,
            item_code VARCHAR(50) UNIQUE,
            item_type ENUM('medication', 'equipment', 'supply', 'consumable') NOT NULL,
            description TEXT,
            quantity INT UNSIGNED NOT NULL DEFAULT 0,
            unit VARCHAR(20) NOT NULL DEFAULT 'pieces',
            unit_price DECIMAL(10,2),
            supplier VARCHAR(100),
            supplier_contact VARCHAR(255),
            batch_number VARCHAR(50),
            expiry_date DATE NULL,
            minimum_stock_level INT UNSIGNED DEFAULT 10,
            maximum_stock_level INT UNSIGNED,
            reorder_point INT UNSIGNED,
            storage_location VARCHAR(100),
            storage_requirements TEXT COMMENT 'Temperature, humidity, etc.',
            barcode VARCHAR(100),
            manufacturer VARCHAR(100),
            prescription_required BOOLEAN DEFAULT FALSE,
            controlled_substance BOOLEAN DEFAULT FALSE,
            last_restocked_at TIMESTAMP NULL,
            last_updated_by INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (last_updated_by) REFERENCES users(id) ON DELETE RESTRICT,
            
            INDEX idx_name (item_name),
            INDEX idx_code (item_code),
            INDEX idx_type (item_type),
            INDEX idx_expiry (expiry_date),
            INDEX idx_low_stock (quantity, minimum_stock_level),
            INDEX idx_barcode (barcode),
            INDEX idx_supplier (supplier),
            FULLTEXT idx_search (item_name, description, manufacturer)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS inventory";
        $this->db->query($sql);
    }
}
