<?php
// Simple API test without function overrides
echo "=== Simple API Test ===\n";

// Test path calculation
$rootPath = __DIR__;
echo "Root path: " . $rootPath . "\n";

// Verify files exist
$dbFile = $rootPath . '/src/db/db.php';
$configFile = $rootPath . '/src/config/config.php';

echo "Checking files:\n";
echo "- DB file: " . $dbFile . " " . (file_exists($dbFile) ? "✅" : "❌") . "\n";
echo "- Config file: " . $configFile . " " . (file_exists($configFile) ? "✅" : "❌") . "\n";

if (!file_exists($dbFile) || !file_exists($configFile)) {
    echo "❌ Required files missing - cannot continue\n";
    exit;
}

echo "\n=== Testing Database Connection ===\n";
try {
    require_once $dbFile;
    if (isset($pdo)) {
        echo "✅ Database connection successful\n";
        
        // Test applications table
        $stmt = $pdo->prepare("SELECT id, name FROM applications WHERE id = ?");
        $stmt->execute([429]);
        $app = $stmt->fetch();
        
        if ($app) {
            echo "✅ Application 429 found: " . $app['name'] . "\n";
        } else {
            echo "❌ Application 429 not found\n";
        }
        
    } else {
        echo "❌ Database connection failed - \$pdo not set\n";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n=== Testing Load API (Safe Method) ===\n";

// Test load API by making actual HTTP request
$loadUrl = 'https://apptrack.no/public/api/load_drawflow_diagram.php?application_id=429';
echo "Testing URL: " . $loadUrl . "\n";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$response = file_get_contents($loadUrl, false, $context);

if ($response !== false) {
    echo "✅ Load API responded\n";
    echo "Response length: " . strlen($response) . "\n";
    
    $json = json_decode($response, true);
    if ($json !== null) {
        echo "✅ Valid JSON response\n";
        if (isset($json['success'])) {
            echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
            if (isset($json['message'])) {
                echo "Message: " . $json['message'] . "\n";
            }
            if (isset($json['diagram_data'])) {
                echo "Has diagram data: ✅\n";
            }
        }
    } else {
        echo "❌ Invalid JSON response\n";
        echo "Raw response: " . substr($response, 0, 200) . "...\n";
    }
} else {
    echo "❌ Load API request failed\n";
}

echo "\n=== Testing Save API (Safe Method) ===\n";

// Test save API with actual HTTP request
$saveUrl = 'https://apptrack.no/public/api/save_drawflow_diagram.php';
echo "Testing URL: " . $saveUrl . "\n";

$saveData = json_encode([
    'application_id' => 429,
    'diagram_data' => [
        'drawflow' => [
            'Home' => [
                'data' => [
                    '2' => [
                        'id' => 2,
                        'name' => 'api-test',
                        'data' => ['type' => 'test'],
                        'class' => 'test-node',
                        'html' => 'API Test Node',
                        'typenode' => false,
                        'inputs' => [],
                        'outputs' => [],
                        'pos_x' => 200,
                        'pos_y' => 150
                    ]
                ]
            ]
        ]
    ],
    'notes' => 'Safe API test save'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $saveData,
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$response = file_get_contents($saveUrl, false, $context);

if ($response !== false) {
    echo "✅ Save API responded\n";
    echo "Response length: " . strlen($response) . "\n";
    
    $json = json_decode($response, true);
    if ($json !== null) {
        echo "✅ Valid JSON response\n";
        if (isset($json['success'])) {
            echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
            if (isset($json['message'])) {
                echo "Message: " . $json['message'] . "\n";
            }
        }
    } else {
        echo "❌ Invalid JSON response\n";
        echo "Raw response: " . substr($response, 0, 200) . "...\n";
    }
} else {
    echo "❌ Save API request failed\n";
}

echo "\n=== Test Complete ===\n";
?>
