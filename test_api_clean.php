<?php
// Clean API test without header conflicts
echo "=== Clean API Test ===\n";

// Test path calculation
$rootPath = __DIR__;
echo "Root path: " . $rootPath . "\n";

// Verify files exist
$dbFile = $rootPath . '/src/db/db.php';
$configFile = $rootPath . '/src/config/config.php';

if (file_exists($dbFile) && file_exists($configFile)) {
    echo "✅ All required files found\n";
} else {
    echo "❌ Missing files - DB: " . (file_exists($dbFile) ? 'OK' : 'MISSING') . 
         ", Config: " . (file_exists($configFile) ? 'OK' : 'MISSING') . "\n";
    exit;
}

echo "\n=== Testing Load API ===\n";

// Set up environment for load test
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['application_id'] = '429';

// Capture load API output
ob_start();
$headers_sent = false;

// Mock header function to prevent actual headers
function header($string, $replace = true, $response_code = null) {
    global $headers_sent;
    echo "Header: " . $string . "\n";
    $headers_sent = true;
}

// Include the load API
try {
    include $rootPath . '/public/api/load_drawflow_diagram.php';
} catch (Error $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$loadOutput = ob_get_clean();

// Extract just the JSON part (after any headers/warnings)
$jsonStart = strpos($loadOutput, '{');
if ($jsonStart !== false) {
    $jsonOutput = substr($loadOutput, $jsonStart);
    $json = json_decode($jsonOutput, true);
    
    if ($json !== null) {
        echo "✅ Load API successful\n";
        echo "Response: " . json_encode($json, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Invalid JSON in load response\n";
        echo "Raw output: " . $loadOutput . "\n";
    }
} else {
    echo "❌ No JSON found in load response\n";
    echo "Raw output: " . $loadOutput . "\n";
}

echo "\n=== Testing Save API ===\n";

// Reset environment for save test
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';
unset($_GET);

// Create test data
$testData = [
    'application_id' => 429,
    'diagram_data' => [
        'drawflow' => [
            'Home' => [
                'data' => [
                    '1' => [
                        'id' => 1,
                        'name' => 'test-node',
                        'data' => ['type' => 'test'],
                        'class' => 'test-class',
                        'html' => 'Test Node',
                        'typenode' => false,
                        'inputs' => [],
                        'outputs' => [],
                        'pos_x' => 100,
                        'pos_y' => 100
                    ]
                ]
            ]
        ]
    ],
    'notes' => 'API test save'
];

// Create a temporary way to simulate POST input
$originalInput = json_encode($testData);
file_put_contents('php://temp', $originalInput);

// Mock file_get_contents for php://input
$GLOBALS['mock_post_data'] = $originalInput;

// Override file_get_contents temporarily
function file_get_contents($filename, $use_include_path = false, $context = null, $offset = 0, $length = null) {
    if ($filename === 'php://input') {
        return $GLOBALS['mock_post_data'];
    }
    return call_user_func_array('file_get_contents', func_get_args());
}

// Capture save API output
ob_start();

try {
    include $rootPath . '/public/api/save_drawflow_diagram.php';
} catch (Error $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$saveOutput = ob_get_clean();

// Extract JSON from save response
$jsonStart = strpos($saveOutput, '{');
if ($jsonStart !== false) {
    $jsonOutput = substr($saveOutput, $jsonStart);
    $json = json_decode($jsonOutput, true);
    
    if ($json !== null) {
        echo "✅ Save API response received\n";
        echo "Response: " . json_encode($json, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Invalid JSON in save response\n";
        echo "Raw output: " . $saveOutput . "\n";
    }
} else {
    echo "❌ No JSON found in save response\n";
    echo "Raw output: " . $saveOutput . "\n";
}

echo "\n=== Test Complete ===\n";
?>
