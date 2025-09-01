#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

if ($argc < 2) {
    echo "Usage: php generate_model.php ModelName [table_name]\n";
    echo "Example: php generate_model.php MedicalRecord medical_records\n";
    exit(1);
}

$modelName = $argv[1];
$tableName = $argv[2] ?? strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName)) . 's';

// Validate model name
if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $modelName)) {
    echo "Error: Model name must start with uppercase letter and contain only letters and numbers\n";
    exit(1);
}

$template = "<?php

namespace MedicalClinic\\Models;

class {$modelName} extends BaseModel
{
    protected static string \$table = '{$tableName}';

    // Relationships
    // public function relatedModel(): ?RelatedModel
    // {
    //     return \$this->belongsTo(RelatedModel::class, 'foreign_key');
    // }

    // public function relatedModels(): array
    // {
    //     return \$this->hasMany(RelatedModel::class, 'foreign_key');
    // }

    // Static query methods
    // public static function findByField(string \$value): ?static
    // {
    //     \$results = static::where('field_name', \$value);
    //     return \$results[0] ?? null;
    // }

    // Instance methods
    // public function getDisplayName(): string
    // {
    //     return \$this->name ?? 'Unknown';
    // }

    // public function getDisplayInfo(): array
    // {
    //     return [
    //         'id' => \$this->id,
    //         'name' => \$this->getDisplayName(),
    //         'created_at' => \$this->created_at
    //     ];
    // }

    // Status checks
    // public function isActive(): bool
    // {
    //     return (bool) \$this->is_active;
    // }

    // Validation methods
    // public function validateField(): bool
    // {
    //     return !empty(\$this->field_name);
    // }
}
";

$filePath = __DIR__ . "/../src/Models/{$modelName}.php";

if (file_exists($filePath)) {
    echo "❌ Model {$modelName} already exists at {$filePath}\n";
    exit(1);
}

file_put_contents($filePath, $template);
echo "✅ Model {$modelName} created successfully!\n";
echo "📁 Location: {$filePath}\n";
echo "🗄️  Table: {$tableName}\n\n";
echo "Next steps:\n";
echo "1. Add relationships to other models\n";
echo "2. Add custom query methods\n";
echo "3. Add validation and business logic\n";
echo "4. Test the model with your controllers\n";
