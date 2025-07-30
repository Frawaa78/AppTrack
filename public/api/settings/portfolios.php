<?php
// Portfolio Management API
header('Content-Type: application/json');
session_start();

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

require_once '../../../src/config/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

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

function handleGet($pdo) {
    try {
        // Get distinct pre-ops portfolios
        $stmt = $pdo->query("SELECT DISTINCT pre_ops_portfolio as name FROM applications WHERE pre_ops_portfolio IS NOT NULL AND pre_ops_portfolio != ''");
        $preopsPortfolios = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Get distinct application portfolios
        $stmt = $pdo->query("SELECT DISTINCT application_portfolio as name FROM applications WHERE application_portfolio IS NOT NULL AND application_portfolio != ''");
        $appPortfolios = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Count usage for each portfolio
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

        echo json_encode([
            'success' => true,
            'preops_portfolios' => $preopsWithCounts,
            'app_portfolios' => $appWithCounts
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch portfolios']);
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
        echo json_encode(['error' => 'Failed to create portfolio']);
    }
}

function handlePut($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['type']) || !isset($input['old_name']) || !isset($input['new_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    $type = $input['type'];
    $oldName = $input['old_name'];
    $newName = trim($input['new_name']);

    if (empty($newName)) {
        http_response_code(400);
        echo json_encode(['error' => 'New portfolio name cannot be empty']);
        return;
    }

    try {
        $column = ($type === 'preops') ? 'pre_ops_portfolio' : 'application_portfolio';
        
        // Update all applications with the old portfolio name
        $stmt = $pdo->prepare("UPDATE applications SET $column = ? WHERE $column = ?");
        $result = $stmt->execute([$newName, $oldName]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Portfolio updated successfully',
                'affected_rows' => $stmt->rowCount()
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update portfolio']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update portfolio']);
    }
}

function handleDelete($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['type']) || !isset($input['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    $type = $input['type'];
    $name = $input['name'];

    try {
        $column = ($type === 'preops') ? 'pre_ops_portfolio' : 'application_portfolio';
        
        // Set portfolio to NULL for all applications using this portfolio
        $stmt = $pdo->prepare("UPDATE applications SET $column = NULL WHERE $column = ?");
        $result = $stmt->execute([$name]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Portfolio deleted successfully',
                'affected_rows' => $stmt->rowCount()
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete portfolio']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete portfolio']);
    }
}
?>