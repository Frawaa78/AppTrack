<?php
// Portfolio Management API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Start output buffering to catch any errors
ob_start();

try {
    session_start();
    
    // Debug logging
    error_log("=== PORTFOLIO API START ===");
    error_log("Portfolio API - Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
    error_log("Portfolio API - Session user_role: " . ($_SESSION['user_role'] ?? 'not set'));
    error_log("Portfolio API - Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Portfolio API - Request URI: " . $_SERVER['REQUEST_URI']);
    
    // Check admin access
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        ob_clean();
        http_response_code(403);
        echo json_encode(['error' => 'Access denied', 'debug' => [
            'user_id' => $_SESSION['user_id'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? null
        ]]);
        exit();
    }
    
    // Include required files with error checking
    $configPath = '../../../src/config/config.php';
    $dbPath = '../../../src/db/db.php';
    
    if (!file_exists($configPath)) {
        throw new Exception("Config file not found at: $configPath");
    }
    if (!file_exists($dbPath)) {
        throw new Exception("Database file not found at: $dbPath");
    }
    
    require_once $configPath;
    require_once $dbPath;
    
    // Database connection with fallback
    $pdo = null;
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        error_log("Portfolio API - Database connection via Database class successful");
    } catch (Exception $e) {
        error_log("Portfolio API - Database class failed: " . $e->getMessage());
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            error_log("Portfolio API - Direct PDO connection successful");
        } catch (PDOException $e2) {
            throw new Exception('Database connection failed: ' . $e2->getMessage());
        }
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    error_log("Portfolio API - Handling method: $method");
    
    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
        case 'POST':
            handlePost($pdo);
            break;
        case 'PUT':
            handlePut($pdo);
            break;
        case 'DELETE':
            handleDelete($pdo);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    ob_clean();
    error_log("Portfolio API - Fatal error: " . $e->getMessage());
    error_log("Portfolio API - Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'debug' => $e->getMessage()]);
}

function handleGet($pdo) {
    try {
        error_log("Portfolio API - handleGet called");
        
        // Test database connection
        $pdo->query("SELECT 1");
        error_log("Portfolio API - Database test successful");
        
        // Get distinct pre-ops portfolios
        $stmt = $pdo->query("SELECT DISTINCT pre_ops_portfolio as name FROM applications WHERE pre_ops_portfolio IS NOT NULL AND pre_ops_portfolio != ''");
        $preopsPortfolios = $stmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("Portfolio API - Found " . count($preopsPortfolios) . " preops portfolios");

        // Get distinct application portfolios
        $stmt = $pdo->query("SELECT DISTINCT application_portfolio as name FROM applications WHERE application_portfolio IS NOT NULL AND application_portfolio != ''");
        $appPortfolios = $stmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("Portfolio API - Found " . count($appPortfolios) . " app portfolios");

        // Count usage for each pre-ops portfolio
        $preopsWithCounts = [];
        foreach ($preopsPortfolios as $portfolio) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE pre_ops_portfolio = ?");
            $stmt->execute([$portfolio]);
            $count = $stmt->fetchColumn();
            $preopsWithCounts[] = [
                'name' => $portfolio,
                'count' => $count
            ];
        }

        // Count usage for each app portfolio
        $appWithCounts = [];
        foreach ($appPortfolios as $portfolio) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE application_portfolio = ?");
            $stmt->execute([$portfolio]);
            $count = $stmt->fetchColumn();
            $appWithCounts[] = [
                'name' => $portfolio,
                'count' => $count
            ];
        }

        $response = [
            'success' => true,
            'preops_portfolios' => $preopsWithCounts,
            'app_portfolios' => $appWithCounts
        ];
        
        error_log("Portfolio API - Sending response: " . json_encode($response));
        
        // Clean any buffered output and send response
        ob_clean();
        echo json_encode($response);
        
        error_log("Portfolio API - Response sent successfully");
        error_log("=== PORTFOLIO API END ===");
        
    } catch (Exception $e) {
        error_log("Portfolio API - handleGet error: " . $e->getMessage());
        ob_clean();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch portfolios', 'debug' => $e->getMessage()]);
    }
}

function handlePost($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['type']) || !isset($input['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    $type = $input['type']; // 'preops' or 'app'
    $name = trim($input['name']);

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Portfolio name cannot be empty']);
        return;
    }

    try {
        // Check if portfolio already exists
        $column = ($type === 'preops') ? 'pre_ops_portfolio' : 'application_portfolio';
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE $column = ?");
        $stmt->execute([$name]);
        
        if ($stmt->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Portfolio already exists']);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Portfolio can be created',
            'name' => $name
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create portfolio', 'debug' => $e->getMessage()]);
    }
}

function handlePut($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['type']) || !isset($input['old_name']) || !isset($input['new_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    $type = $input['type']; // 'preops' or 'app'
    $oldName = trim($input['old_name']);
    $newName = trim($input['new_name']);

    if (empty($newName)) {
        http_response_code(400);
        echo json_encode(['error' => 'Portfolio name cannot be empty']);
        return;
    }

    try {
        $column = ($type === 'preops') ? 'pre_ops_portfolio' : 'application_portfolio';
        
        // Update all applications with this portfolio
        $stmt = $pdo->prepare("UPDATE applications SET $column = ? WHERE $column = ?");
        $stmt->execute([$newName, $oldName]);
        
        $affectedRows = $stmt->rowCount();

        echo json_encode([
            'success' => true,
            'affected_rows' => $affectedRows,
            'message' => "Portfolio updated successfully"
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update portfolio', 'debug' => $e->getMessage()]);
    }
}

function handleDelete($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['type']) || !isset($input['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    $type = $input['type']; // 'preops' or 'app'
    $name = trim($input['name']);

    try {
        $column = ($type === 'preops') ? 'pre_ops_portfolio' : 'application_portfolio';
        
        // Set portfolio to null for all applications using this portfolio
        $stmt = $pdo->prepare("UPDATE applications SET $column = NULL WHERE $column = ?");
        $stmt->execute([$name]);
        
        $affectedRows = $stmt->rowCount();

        echo json_encode([
            'success' => true,
            'affected_rows' => $affectedRows,
            'message' => "Portfolio removed from $affectedRows applications"
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete portfolio', 'debug' => $e->getMessage()]);
    }
}
?>
