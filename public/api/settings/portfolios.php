<?php
// Portfolio Management API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display in output
ini_set('log_errors', 1);

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
    
    // Try to include config file, but continue with fallback if it fails
    $configLoaded = false;
    if (file_exists('../../../src/config/config.php')) {
        try {
            require_once '../../../src/config/config.php';
            $configLoaded = true;
            error_log("Portfolio API - Config loaded successfully");
        } catch (Exception $e) {
            error_log("Portfolio API - Config load failed: " . $e->getMessage());
        }
    }
    
    // Fallback database connection if config fails
    if (!$configLoaded || !defined('DB_HOST')) {
        // Use default values - you should update these for your server
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'apptrack');
        define('DB_USER', 'apptrack_user');
        define('DB_PASS', 'your_password');
        error_log("Portfolio API - Using fallback database config");
    }
    
    // Direct database connection
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        error_log("Portfolio API - Database connection successful");
    } catch (PDOException $e) {
        throw new Exception('Database connection failed: ' . $e->getMessage());
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
        
        // Check if applications table exists and has data
        $stmt = $pdo->query("SHOW TABLES LIKE 'applications'");
        if ($stmt->rowCount() == 0) {
            throw new Exception("Applications table does not exist");
        }
        
        // Count total applications
        $stmt = $pdo->query("SELECT COUNT(*) FROM applications");
        $totalApps = $stmt->fetchColumn();
        error_log("Portfolio API - Total applications in database: " . $totalApps);
        
        // Check if we should use portfolios table instead
        $stmt = $pdo->query("SHOW TABLES LIKE 'portfolios'");
        if ($stmt->rowCount() > 0) {
            error_log("Portfolio API - Found portfolios table, checking data");
            
            // Get data from portfolios table
            $stmt = $pdo->query("SELECT name, type FROM portfolios ORDER BY type, name");
            $portfolioTableData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Portfolio API - Portfolios table data: " . json_encode($portfolioTableData));
            
            // Separate by type
            $preopsFromTable = [];
            $appFromTable = [];
            
            foreach ($portfolioTableData as $portfolio) {
                if ($portfolio['type'] === 'preops') {
                    // Count usage in applications
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE preops_portfolio = ?");
                    $stmt->execute([$portfolio['name']]);
                    $count = $stmt->fetchColumn();
                    $preopsFromTable[] = ['name' => $portfolio['name'], 'count' => $count];
                } elseif ($portfolio['type'] === 'application') {
                    // Count usage in applications
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE application_portfolio = ?");
                    $stmt->execute([$portfolio['name']]);
                    $count = $stmt->fetchColumn();
                    $appFromTable[] = ['name' => $portfolio['name'], 'count' => $count];
                }
            }
            
            error_log("Portfolio API - Preops from table: " . json_encode($preopsFromTable));
            error_log("Portfolio API - App from table: " . json_encode($appFromTable));
            
            // Use table data if available, otherwise fall back to applications table
            if (count($preopsFromTable) > 0 || count($appFromTable) > 0) {
                $preopsWithCounts = $preopsFromTable;
                $appWithCounts = $appFromTable;
                error_log("Portfolio API - Using portfolios table data");
            } else {
                error_log("Portfolio API - Portfolios table empty, using applications table");
                // Continue with applications table logic below
            }
        } else {
            error_log("Portfolio API - No portfolios table found, using applications table");
        }
        
        // Only run this if we haven't already set the data from portfolios table
        if (!isset($preopsWithCounts)) {
            // Debug: Show all portfolio values (including empty ones)
            $stmt = $pdo->query("SELECT DISTINCT preops_portfolio FROM applications WHERE preops_portfolio IS NOT NULL ORDER BY preops_portfolio");
            $allPreops = $stmt->fetchAll(PDO::FETCH_COLUMN);
            error_log("Portfolio API - ALL preops values (including empty): " . json_encode($allPreops));
            
            $stmt = $pdo->query("SELECT DISTINCT application_portfolio FROM applications WHERE application_portfolio IS NOT NULL ORDER BY application_portfolio");
            $allApp = $stmt->fetchAll(PDO::FETCH_COLUMN);
            error_log("Portfolio API - ALL app values (including empty): " . json_encode($allApp));
            
            // Get distinct pre-ops portfolios (improved filtering)
            $stmt = $pdo->query("SELECT DISTINCT preops_portfolio as name FROM applications WHERE preops_portfolio IS NOT NULL AND preops_portfolio != '' AND preops_portfolio != 'NULL' AND TRIM(preops_portfolio) != ''");
            $preopsPortfolios = $stmt->fetchAll(PDO::FETCH_COLUMN);
            error_log("Portfolio API - Found " . count($preopsPortfolios) . " preops portfolios: " . json_encode($preopsPortfolios));

            // Get distinct application portfolios (improved filtering)
            $stmt = $pdo->query("SELECT DISTINCT application_portfolio as name FROM applications WHERE application_portfolio IS NOT NULL AND application_portfolio != '' AND application_portfolio != 'NULL' AND TRIM(application_portfolio) != ''");
            $appPortfolios = $stmt->fetchAll(PDO::FETCH_COLUMN);
            error_log("Portfolio API - Found " . count($appPortfolios) . " app portfolios: " . json_encode($appPortfolios));

            // Count usage for each pre-ops portfolio
            $preopsWithCounts = [];
            foreach ($preopsPortfolios as $portfolio) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE preops_portfolio = ?");
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
        }

        $response = [
            'success' => true,
            'preops_portfolios' => $preopsWithCounts,
            'app_portfolios' => $appWithCounts,
            'debug' => [
                'total_applications' => $totalApps,
                'preops_count' => count($preopsWithCounts),
                'app_count' => count($appWithCounts)
            ]
        ];
        
        error_log("Portfolio API - Sending response with " . count($preopsWithCounts) . " preops and " . count($appWithCounts) . " app portfolios");
        
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

    // Convert 'app' to 'application' for database consistency
    $dbType = ($type === 'app') ? 'application' : $type;

    try {
        // Check if portfolios table exists and use it if available
        $tablesResult = $pdo->query("SHOW TABLES LIKE 'portfolios'");
        $portfoliosTableExists = $tablesResult->rowCount() > 0;
        
        if ($portfoliosTableExists) {
            error_log("Portfolio POST - Using portfolios table for creation");
            
            // Check if portfolio already exists in portfolios table
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM portfolios WHERE name = ? AND type = ?");
            $stmt->execute([$name, $dbType]);
            
            if ($stmt->fetchColumn() > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'Portfolio already exists']);
                return;
            }
            
            // Insert new portfolio into portfolios table
            $stmt = $pdo->prepare("INSERT INTO portfolios (name, type) VALUES (?, ?)");
            $stmt->execute([$name, $dbType]);
            
            error_log("Portfolio POST - Successfully created portfolio: $name ($dbType) with ID: " . $pdo->lastInsertId());
            
            echo json_encode([
                'success' => true,
                'message' => 'Portfolio created successfully',
                'name' => $name,
                'type' => $type,
                'id' => $pdo->lastInsertId()
            ]);
        } else {
            error_log("Portfolio POST - Portfolios table not found, portfolio creation not supported without dedicated table");
            
            // For backward compatibility, we could check applications table but not create
            // Check if portfolio already exists in applications table
            $column = ($type === 'preops') ? 'preops_portfolio' : 'application_portfolio';
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE $column = ?");
            $stmt->execute([$name]);
            
            if ($stmt->fetchColumn() > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'Portfolio already exists']);
                return;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Portfolio ready to be used (will be created when first application uses it)',
                'name' => $name,
                'type' => $type
            ]);
        }
    } catch (Exception $e) {
        error_log("Portfolio POST - Error: " . $e->getMessage());
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
        $column = ($type === 'preops') ? 'preops_portfolio' : 'application_portfolio';
        
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
        $column = ($type === 'preops') ? 'preops_portfolio' : 'application_portfolio';
        
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
