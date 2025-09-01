#!/usr/bin/env php
<?php

$modelsPath = __DIR__ . '/../src/Models/';
$models = ['Doctor.php', 'Appointment.php', 'Reminder.php', 'AppointmentSummary.php', 'AIAlert.php', 'HIPAAAuditLog.php'];

echo "🔧 Fixing remaining database initialization issues in models...\n\n";

foreach ($models as $modelFile) {
    $filePath = $modelsPath . $modelFile;
    if (!file_exists($filePath)) {
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    // Add static::initializeDatabase(); before static::$db->fetch calls
    $patterns = [
        '/(\s+)(\$result = static::\$db->fetch\()/m',
        '/(\s+)(\$results = static::\$db->fetchAll\()/m',
        '/(\s+)(static::\$db->query\()/m'
    ];
    
    $replacements = [
        '$1static::initializeDatabase();$1$2',
        '$1static::initializeDatabase();$1$2', 
        '$1static::initializeDatabase();$1$2'
    ];
    
    $originalContent = $content;
    $content = preg_replace($patterns, $replacements, $content);
    
    // Remove duplicate initializations
    $content = preg_replace('/(\s+static::initializeDatabase\(\);\s*static::initializeDatabase\(\);)/m', '$1static::initializeDatabase();', $content);
    
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "✅ Fixed database initialization in {$modelFile}\n";
    } else {
        echo "ℹ️  No changes needed in {$modelFile}\n";
    }
}

echo "\n🎉 Database initialization fixes complete!\n";
echo "🧪 Now try running: php scripts/test_models.php\n";
