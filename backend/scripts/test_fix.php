<?php

/**
 * Quick Fix Validation Test
 */

require_once __DIR__ . '/../bootstrap_simple.php';

echo "🔧 Testing the namedRoutes Fix\n";
echo "=============================\n\n";

try {
    $router = new \MedicalClinic\Routes\RouteRegistryEnhanced();
    
    echo "✅ RouteRegistryEnhanced instantiated\n";
    
    // Test stats method that accesses namedRoutes
    $stats = $router->getStats();
    echo "✅ getStats() method works\n";
    echo "   - Total routes: " . $stats['total_routes'] . "\n";
    echo "   - Named routes: " . $stats['named_routes'] . "\n";
    
    // Test route creation with naming
    $route = $router->get('/test', function() { return 'test'; });
    echo "✅ Route creation works\n";
    
    // Test the stats again after adding a route
    $stats = $router->getStats();
    echo "✅ Stats work after route addition\n";
    echo "   - Total routes: " . $stats['total_routes'] . "\n";
    
    echo "\n🎉 Fix successful! namedRoutes property access resolved.\n";
    
} catch (Exception $e) {
    echo "❌ Fix failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✨ Ready to run full tests again!\n";
