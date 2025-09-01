<?php

namespace MedicalClinic\Models;

use MedicalClinic\Utils\Database;

abstract class BaseModel
{
    protected static Database $db;
    protected static string $table;
    protected static string $primaryKey = 'id';
    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;

    public function __construct(array $attributes = [], bool $exists = false)
    {
        static::initializeDatabase();
        $this->fill($attributes, $exists);
    }

    // Initialize database connection if not already done
    protected static function initializeDatabase(): void
    {
        if (!isset(static::$db)) {
            static::$db = Database::getInstance();
        }
    }

    // Static query methods
    public static function find(int $id): ?static
    {
        static::initializeDatabase();
        $result = static::$db->fetch(
            "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id",
            ['id' => $id]
        );
        
        return $result ? new static($result, true) : null;
    }

    public static function findOrFail(int $id): static
    {
        $model = static::find($id);
        if (!$model) {
            throw new \Exception(static::class . " not found with ID: {$id}", 404);
        }
        return $model;
    }

    public static function all(array $orderBy = []): array
    {
        static::initializeDatabase();
        $sql = "SELECT * FROM " . static::$table;
        
        if (!empty($orderBy)) {
            $orderPairs = [];
            foreach ($orderBy as $column => $direction) {
                $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
                $orderPairs[] = "{$column} {$direction}";
            }
            $sql .= " ORDER BY " . implode(', ', $orderPairs);
        }
        
        $results = static::$db->fetchAll($sql);
        return array_map(fn($row) => new static($row, true), $results);
    }

    public static function where(string $column, mixed $value, string $operator = '='): array
    {
        static::initializeDatabase();
        $results = static::$db->fetchAll(
            "SELECT * FROM " . static::$table . " WHERE {$column} {$operator} :value",
            ['value' => $value]
        );
        return array_map(fn($row) => new static($row, true), $results);
    }

    public static function whereIn(string $column, array $values): array
    {
        static::initializeDatabase();
        if (empty($values)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($values) - 1) . '?';
        $results = static::$db->fetchAll(
            "SELECT * FROM " . static::$table . " WHERE {$column} IN ({$placeholders})",
            $values
        );
        return array_map(fn($row) => new static($row, true), $results);
    }

    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    public static function count(array $conditions = []): int
    {
        static::initializeDatabase();
        $sql = "SELECT COUNT(*) as count FROM " . static::$table;
        $params = [];
        
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $column => $value) {
                $whereClauses[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        $result = static::$db->fetch($sql, $params);
        return (int) $result['count'];
    }

    // Instance methods
    public function fill(array $attributes, bool $exists = false): void
    {
        $this->attributes = $attributes;
        $this->original = $attributes;
        $this->exists = $exists;
    }

    public function save(): bool
    {
        if ($this->exists) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }

    protected function insert(): bool
    {
        // Add timestamps
        if (!isset($this->attributes['created_at'])) {
            $this->attributes['created_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($this->attributes['updated_at'])) {
            $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        }

        $columns = array_keys($this->attributes);
        $placeholders = ':' . implode(', :', $columns);
        
        $sql = "INSERT INTO " . static::$table . " (" . implode(', ', $columns) . ") 
                VALUES ({$placeholders})";
        
        $result = static::$db->query($sql, $this->attributes);
        
        if ($result) {
            $this->attributes[static::$primaryKey] = static::$db->lastInsertId();
            $this->exists = true;
            $this->original = $this->attributes;
        }
        
        return $result;
    }

    protected function update(): bool
    {
        $changed = array_diff_assoc($this->attributes, $this->original);
        if (empty($changed)) {
            return true; // No changes
        }

        // Add updated timestamp
        if (!isset($changed['updated_at'])) {
            $changed['updated_at'] = date('Y-m-d H:i:s');
            $this->attributes['updated_at'] = $changed['updated_at'];
        }

        $setPairs = array_map(fn($key) => "{$key} = :{$key}", array_keys($changed));
        $sql = "UPDATE " . static::$table . " SET " . implode(', ', $setPairs) . 
               " WHERE " . static::$primaryKey . " = :id";
        
        $params = array_merge($changed, ['id' => $this->attributes[static::$primaryKey]]);
        
        $result = static::$db->query($sql, $params);
        if ($result) {
            $this->original = $this->attributes;
        }
        
        return $result;
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $result = static::$db->query(
            "DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id",
            ['id' => $this->attributes[static::$primaryKey]]
        );

        if ($result) {
            $this->exists = false;
        }

        return $result;
    }

    public function refresh(): static
    {
        if (!$this->exists) {
            throw new \Exception('Cannot refresh non-persisted model');
        }

        $fresh = static::find($this->attributes[static::$primaryKey]);
        if (!$fresh) {
            throw new \Exception('Model no longer exists in database');
        }

        $this->attributes = $fresh->attributes;
        $this->original = $fresh->original;
        return $this;
    }

    // Magic methods
    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function toJson(): string
    {
        return json_encode($this->attributes);
    }

    // Relationships
    public function hasOne(string $related, string $foreignKey, ?string $localKey = null): ?BaseModel
    {
        $localKey = $localKey ?: static::$primaryKey;
        $results = $related::where($foreignKey, $this->attributes[$localKey]);
        return $results[0] ?? null;
    }

    public function hasMany(string $related, string $foreignKey, ?string $localKey = null): array
    {
        $localKey = $localKey ?: static::$primaryKey;
        return $related::where($foreignKey, $this->attributes[$localKey]);
    }

    public function belongsTo(string $related, string $foreignKey, string $ownerKey = 'id'): ?BaseModel
    {
        if (!isset($this->attributes[$foreignKey])) {
            return null;
        }
        return $related::find($this->attributes[$foreignKey]);
    }

    // Utility methods
    public function isDirty(): bool
    {
        return $this->attributes !== $this->original;
    }

    public function getDirty(): array
    {
        return array_diff_assoc($this->attributes, $this->original);
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function getId(): ?int
    {
        return $this->attributes[static::$primaryKey] ?? null;
    }
}
