<?php
// public/api/update_phase.php - Update Application Phase API
require_once __DIR__ . '/../../src/db/db.php';
require_once __DIR__ . '/../../src/managers/ActivityManager.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Authentication required'
    ]);
    exit;
}

// Check if user has permission to edit
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'editor'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Insufficient permissions. Editor or Admin role required.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['app_id']) || !isset($input['phase'])) {
        throw new Exception('Missing required fields: app_id and phase');
    }
    
    $appId = (int)$input['app_id'];
    $phase = trim($input['phase']);
    
    // Validate phase
    $validPhases = ['Need', 'Solution', 'Build', 'Implement', 'Operate'];
    if (!in_array($phase, $validPhases)) {
        throw new Exception('Invalid phase. Must be one of: ' . implode(', ', $validPhases));
    }
    
    // Get database connection
    $db = Database::getInstance()->getConnection();
    
    // Check if application exists and get current phase
    $checkStmt = $db->prepare('SELECT id, phase FROM applications WHERE id = ?');
    $checkStmt->execute([$appId]);
    $application = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if (!$application) {
        throw new Exception('Application not found');
    }
    
    $oldPhase = $application['phase'];
    
    // Only update if phase is actually changing
    if ($oldPhase === $phase) {
        echo json_encode([
            'success' => true,
            'message' => 'Phase is already set to ' . $phase,
            'app_id' => $appId,
            'new_phase' => $phase
        ]);
        exit;
    }
    
    // Update the application phase
    $updateStmt = $db->prepare('UPDATE applications SET phase = ?, updated_at = NOW() WHERE id = ?');
    $success = $updateStmt->execute([$phase, $appId]);
    
    if (!$success) {
        throw new Exception('Failed to update application phase');
    }
    
    // Log the change to audit_log using ActivityManager
    try {
        $activityManager = new ActivityManager();
        $activityManager->logFieldChange($appId, 'phase', $oldPhase, $phase, $_SESSION['user_id'], 'UPDATE');
    } catch (Exception $e) {
        // Log error but don't fail the main operation
        error_log("Failed to log audit entry: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Application phase updated successfully',
        'app_id' => $appId,
        'new_phase' => $phase
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
