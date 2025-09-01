#!/usr/bin/env php
<?php

// Make all model scripts executable
$scripts = [
    __DIR__ . '/generate_model.php',
    __DIR__ . '/test_models.php', 
    __DIR__ . '/migration_guide.php'
];

foreach ($scripts as $script) {
    if (file_exists($script)) {
        chmod($script, 0755);
        echo "✅ Made {$script} executable\n";
    }
}

echo "\n🎉 All model scripts are now executable!\n";
echo "📖 Usage examples:\n";
echo "   ./scripts/test_models.php\n";
echo "   ./scripts/generate_model.php ModelName\n";
echo "   ./scripts/migration_guide.php\n\n";
echo "🏥 Models directory implementation is complete! ✨\n";
