<?php
// Enhanced API debugging for one.com server
echo "=== AppTrack API Debug ===\n";

// Check current working directory
echo "Current directory: " . getcwd() . "\n";
echo "Script location: " . __DIR__ . "\n";

// Test absolute path calculation - the script is in the root, so __DIR__ is the root path
$rootPath = __DIR__;
echo "Calculated root path: " . $rootPath . "\n";

// Check if files exist with absolute paths
$dbFile = $rootPath . '/src/db/db.php';
echo "DB file path: " . $dbFile . "\n";
if (file_exists($dbFile)) {
    echo "✅ DB file exists (absolute path)\n";
} else {
    echo "❌ DB file NOT found (absolute path)\n";
}

$configFile = $rootPath . '/src/config/config.php';
echo "Config file path: " . $configFile . "\n";
if (file_exists($configFile)) {
    echo "✅ Config file exists (absolute path)\n";
} else {
    echo "❌ Config file NOT found (absolute path)\n";
}

// Check API files
$apiFile = 'public/api/save_drawflow_diagram.php';
if (file_exists($apiFile)) {
    echo "✅ Save API file exists\n";
} else {
    echo "❌ Save API file NOT found\n";
}

$loadApiFile = 'public/api/load_drawflow_diagram.php';
if (file_exists($loadApiFile)) {
    echo "✅ Load API file exists\n";
} else {
    echo "❌ Load API file NOT found\n";
}

echo "\n=== Testing Load API with Fixed Paths ===\n";
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['application_id'] = '429';

try {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    ob_start();
    include 'public/api/load_drawflow_diagram.php';
    $output = ob_get_clean();
    
    echo "Load API output length: " . strlen($output) . "\n";
    echo "Raw output: " . htmlspecialchars(substr($output, 0, 500)) . "\n";
    
    $json = json_decode($output, true);
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
        echo "JSON Error: " . json_last_error_msg() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
}

echo "\n=== Testing Save API with Fixed Paths ===\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

try {
    // Simulate POST data for save
    $postData = json_encode([
        'application_id' => 429,
        'diagram_data' => [
            'drawflow' => [
                'Home' => [
                    'data' => [
                        '1' => [
                            'id' => 1,
                            'name' => 'test',
                            'data' => [],
                            'class' => 'test',
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
        'notes' => 'Debug test save'
    ]);
    
    // Create temporary file for php://input simulation
    $tempFile = tempnam(sys_get_temp_dir(), 'debug_post');
    file_put_contents($tempFile, $postData);
    
    // Override file_get_contents for testing
    if (!function_exists('original_file_get_contents')) {
        function original_file_get_contents($filename) {
            return file_get_contents($filename);
        }
        
        // This won't work in practice, but shows the concept
        // We'll need to modify the save API for testing
    }
    
    ob_start();
    include 'public/api/save_drawflow_diagram.php';
    $output = ob_get_clean();
    
    echo "Save API output length: " . strlen($output) . "\n";
    echo "Raw output: " . htmlspecialchars(substr($output, 0, 500)) . "\n";
    
    $json = json_decode($output, true);
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
        echo "JSON Error: " . json_last_error_msg() . "\n";
    }
    
    // Cleanup
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
}

echo "\n=== Database Connection Test ===\n";
try {
    // Try to include and test database connection
    $rootPath = __DIR__;
    require_once $rootPath . '/src/db/db.php';
    
    if (isset($pdo)) {
        echo "✅ Database connection established\n";
        
        // Test query
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM applications LIMIT 1");
        if ($stmt) {
            $result = $stmt->fetch();
            echo "✅ Database query successful\n";
            echo "Applications table accessible\n";
        }
    } else {
        echo "❌ Database connection variable not set\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>
