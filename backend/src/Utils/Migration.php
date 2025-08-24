<?php

namespace MedicalClinic\Utils;

class Migration
{
    private $db;
    private $migrationsPath;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->migrationsPath = __DIR__ . '/../../database/migrations/';
    }

    public function run(): void
    {
        echo "Starting database migrations...\n";

        // Create migrations table if it doesn't exist
        $this->createMigrationsTable();

        // Get all migration files
        $migrations = $this->getMigrationFiles();

        foreach ($migrations as $migration) {
            if (!$this->isMigrationRun($migration)) {
                echo "Running migration: {$migration}\n";
                $this->runMigration($migration);
                $this->recordMigration($migration);
                echo "Migration {$migration} completed successfully.\n";
            } else {
                echo "Migration {$migration} already run, skipping.\n";
            }
        }

        echo "All migrations completed!\n";
    }

    public function rollback(int $steps = 1): void
    {
        echo "Rolling back {$steps} migration(s)...\n";

        $migrations = $this->getCompletedMigrations($steps);

        foreach (array_reverse($migrations) as $migration) {
            echo "Rolling back migration: {$migration['migration']}\n";
            $this->rollbackMigration($migration['migration']);
            $this->removeMigrationRecord($migration['migration']);
            echo "Rollback of {$migration['migration']} completed.\n";
        }

        echo "Rollback completed!\n";
    }

    private function createMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $this->db->query($sql);
    }

    private function getMigrationFiles(): array
    {
        $files = scandir($this->migrationsPath);
        $migrations = [];

        foreach ($files as $file) {
            if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_.*\.php$/', $file)) {
                $migrations[] = $file;
            }
        }

        sort($migrations);
        return $migrations;
    }

    private function isMigrationRun(string $migration): bool
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM migrations WHERE migration = :migration",
            ['migration' => $migration]
        );

        return $result['count'] > 0;
    }

    private function runMigration(string $migration): void
    {
        $migrationPath = $this->migrationsPath . $migration;
        
        if (!file_exists($migrationPath)) {
            throw new \Exception("Migration file not found: {$migrationPath}");
        }

        // Extract the class name from filename
        $className = $this->getMigrationClassName($migration);
        
        require_once $migrationPath;
        
        if (!class_exists($className)) {
            throw new \Exception("Migration class {$className} not found in {$migration}");
        }

        $migrationInstance = new $className($this->db);
        $migrationInstance->up();
    }

    private function rollbackMigration(string $migration): void
    {
        $migrationPath = $this->migrationsPath . $migration;
        $className = $this->getMigrationClassName($migration);
        
        require_once $migrationPath;
        
        $migrationInstance = new $className($this->db);
        $migrationInstance->down();
    }

    private function getMigrationClassName(string $filename): string
    {
        // Convert filename to class name
        // 2024_01_01_000001_create_users_table.php -> CreateUsersTable
        $parts = explode('_', pathinfo($filename, PATHINFO_FILENAME));
        $classParts = array_slice($parts, 4); // Remove date and time parts
        
        return implode('', array_map('ucfirst', $classParts));
    }

    private function recordMigration(string $migration): void
    {
        $batch = $this->getNextBatchNumber();
        
        $this->db->insert('migrations', [
            'migration' => $migration,
            'batch' => $batch
        ]);
    }

    private function removeMigrationRecord(string $migration): void
    {
        $this->db->delete('migrations', 'migration = :migration', ['migration' => $migration]);
    }

    private function getNextBatchNumber(): int
    {
        $result = $this->db->fetch("SELECT MAX(batch) as max_batch FROM migrations");
        return ($result['max_batch'] ?? 0) + 1;
    }

    private function getCompletedMigrations(int $limit): array
    {
        $sql = "SELECT migration FROM migrations ORDER BY id DESC LIMIT {$limit}";
        return $this->db->fetchAll($sql);
    }
}
