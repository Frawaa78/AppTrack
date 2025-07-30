<?php
// Statuses Management API
header('Content-Type: application/json');
session_start();

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

require_once '../../../src/config/config.php';
require_once '../../../src/db/db.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    // Fallback to direct connection if Database class fails
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e2) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $e2->getMessage()]);
        exit();
    }
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
        error_log("=== STATUSES API START ===");
        error_log("Statuses API - handleGet called");
        
        // Get statuses from dedicated statuses table first
        $stmt = $pdo->query("SELECT name FROM statuses ORDER BY name");
        $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        error_log("Statuses from statuses table: " . json_encode($statuses));

        // Count usage for each status in applications table
        $statusesWithCounts = [];
        foreach ($statuses as $status) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status = ?");
            $stmt->execute([$status]);
            $count = $stmt->fetchColumn();
            
            // Determine badge color based on status
            $badgeColor = getBadgeColor($status);
            
            $statusesWithCounts[] = [
                'name' => $status,
                'count' => $count,
                'active' => true,
                'badge_color' => $badgeColor
            ];
        }

        // If no statuses found in statuses table, fall back to applications table
        if (empty($statusesWithCounts)) {
            error_log("No statuses found in statuses table, falling back to applications table");
            
            $stmt = $pdo->query("SELECT DISTINCT status as name FROM applications WHERE status IS NOT NULL AND status != ''");
            $statusesFromApps = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($statusesFromApps as $status) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status = ?");
                $stmt->execute([$status]);
                $count = $stmt->fetchColumn();
                
                $badgeColor = getBadgeColor($status);
                
                $statusesWithCounts[] = [
                    'name' => $status,
                    'count' => $count,
                    'active' => true,
                    'badge_color' => $badgeColor
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'statuses' => $statusesWithCounts
        ]);
        
        error_log("Statuses API - Response sent successfully with " . count($statusesWithCounts) . " statuses");
        error_log("=== STATUSES API END ===");
    } catch (Exception $e) {
        error_log("Statuses API Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch statuses']);
    }
}

function getBadgeColor($status) {
    $status = strtolower($status);
    
    switch ($status) {
        case 'completed':
        case 'done':
        case 'finished':
            return 'success';
        case 'not started':
        case 'cancelled':
        case 'failed':
            return 'danger';
        case 'ongoing work':
        case 'in progress':
        case 'pending':
            return 'warning';
        case 'on hold':
        case 'paused':
        case 'waiting':
            return 'info';
        case 'unknown':
        case 'draft':
        default:
            return 'secondary';
    }
}

function handlePost($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    $name = trim($input['name']);

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Status name cannot be empty']);
        return;
    }

    try {
        // Check if status already exists in statuses table
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM statuses WHERE name = ?");
        $stmt->execute([$name]);
        
        if ($stmt->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Status already exists']);
            return;
        }

        // Insert into statuses table
        $stmt = $pdo->prepare("INSERT INTO statuses (name) VALUES (?)");
        $stmt->execute([$name]);

        echo json_encode([
            'success' => true,
            'message' => 'Status created successfully'
        ]);
    } catch (Exception $e) {
        error_log("Status creation error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create status']);
    }
}

function handlePut($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['old_name']) || !isset($input['new_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    $oldName = trim($input['old_name']);
    $newName = trim($input['new_name']);

    if (empty($newName)) {
        http_response_code(400);
        echo json_encode(['error' => 'Status name cannot be empty']);
        return;
    }

    try {
        // Update statuses table
        $stmt = $pdo->prepare("UPDATE statuses SET name = ? WHERE name = ?");
        $stmt->execute([$newName, $oldName]);
        
        // Also update all applications with this status
        $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE status = ?");
        $stmt->execute([$newName, $oldName]);
        
        $affectedRows = $stmt->rowCount();

        echo json_encode([
            'success' => true,
            'affected_rows' => $affectedRows,
            'message' => "Status updated successfully"
        ]);
    } catch (Exception $e) {
        error_log("Status update error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update status']);
    }
}

function handleDelete($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing status name']);
        return;
    }

    $name = trim($input['name']);

    try {
        // First, set status to null for all applications using this status
        $stmt = $pdo->prepare("UPDATE applications SET status = NULL WHERE status = ?");
        $stmt->execute([$name]);
        
        $affectedRows = $stmt->rowCount();

        // Then delete from statuses table
        $stmt = $pdo->prepare("DELETE FROM statuses WHERE name = ?");
        $stmt->execute([$name]);

        echo json_encode([
            'success' => true,
            'affected_rows' => $affectedRows,
            'message' => "Status removed from $affectedRows applications"
        ]);
    } catch (Exception $e) {
        error_log("Status delete error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete status']);
    }
}
?>