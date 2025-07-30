<?php
// Phases Management API
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
        error_log("=== PHASES API START ===");
        error_log("Phases API - handleGet called");
        error_log("Phases API - Current file: " . __FILE__);
        error_log("Phases API - Request URI: " . $_SERVER['REQUEST_URI']);
        
        // Get phases from dedicated phases table first
        $stmt = $pdo->query("SELECT name FROM phases ORDER BY name");
        $phases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        error_log("Phases from phases table: " . json_encode($phases));

        // Count usage for each phase in applications table
        $phasesWithCounts = [];
        foreach ($phases as $phase) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE phase = ?");
            $stmt->execute([$phase]);
            $count = $stmt->fetchColumn();
            $phasesWithCounts[] = [
                'name' => $phase,
                'count' => $count,
                'active' => true
            ];
        }

        // If no phases found in phases table, fall back to applications table
        if (empty($phasesWithCounts)) {
            error_log("No phases found in phases table, falling back to applications table");
            
            $stmt = $pdo->query("SELECT DISTINCT phase as name FROM applications WHERE phase IS NOT NULL AND phase != ''");
            $phasesFromApps = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($phasesFromApps as $phase) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE phase = ?");
                $stmt->execute([$phase]);
                $count = $stmt->fetchColumn();
                $phasesWithCounts[] = [
                    'name' => $phase,
                    'count' => $count,
                    'active' => true
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'phases' => $phasesWithCounts
        ]);
        
        error_log("Phases API - Response sent successfully with " . count($phasesWithCounts) . " phases");
        error_log("=== PHASES API END ===");
    } catch (Exception $e) {
        error_log("Phases API Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch phases']);
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
        echo json_encode(['error' => 'Phase name cannot be empty']);
        return;
    }

    try {
        // Check if phase already exists in phases table
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM phases WHERE name = ?");
        $stmt->execute([$name]);
        
        if ($stmt->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Phase already exists']);
            return;
        }

        // Insert into phases table
        $stmt = $pdo->prepare("INSERT INTO phases (name) VALUES (?)");
        $stmt->execute([$name]);

        echo json_encode([
            'success' => true,
            'message' => 'Phase created successfully'
        ]);
    } catch (Exception $e) {
        error_log("Phase creation error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create phase']);
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
        echo json_encode(['error' => 'Phase name cannot be empty']);
        return;
    }

    try {
        // Update phases table
        $stmt = $pdo->prepare("UPDATE phases SET name = ? WHERE name = ?");
        $stmt->execute([$newName, $oldName]);
        
        // Also update all applications with this phase
        $stmt = $pdo->prepare("UPDATE applications SET phase = ? WHERE phase = ?");
        $stmt->execute([$newName, $oldName]);
        
        $affectedRows = $stmt->rowCount();

        echo json_encode([
            'success' => true,
            'affected_rows' => $affectedRows,
            'message' => "Phase updated successfully"
        ]);
    } catch (Exception $e) {
        error_log("Phase update error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update phase']);
    }
}

function handleDelete($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing phase name']);
        return;
    }

    $name = trim($input['name']);

    try {
        // First, set phase to null for all applications using this phase
        $stmt = $pdo->prepare("UPDATE applications SET phase = NULL WHERE phase = ?");
        $stmt->execute([$name]);
        
        $affectedRows = $stmt->rowCount();

        // Then delete from phases table
        $stmt = $pdo->prepare("DELETE FROM phases WHERE name = ?");
        $stmt->execute([$name]);

        echo json_encode([
            'success' => true,
            'affected_rows' => $affectedRows,
            'message' => "Phase removed from $affectedRows applications"
        ]);
    } catch (Exception $e) {
        error_log("Phase delete error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete phase']);
    }
}
?>
