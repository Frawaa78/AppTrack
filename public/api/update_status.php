<?php
// public/api/update_status.php - Update Application Status API
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
    
    if (!$input || !isset($input['app_id']) || !isset($input['status'])) {
        throw new Exception('Missing required fields: app_id and status');
    }
    
    $appId = (int)$input['app_id'];
    $status = trim($input['status']);
    
    // Validate status
    $validStatuses = ['Not Started', 'Ongoing Work', 'On Hold', 'Completed', 'Unknown'];
    if (!in_array($status, $validStatuses)) {
        throw new Exception('Invalid status. Must be one of: ' . implode(', ', $validStatuses));
    }
    
    // Get database connection
    $db = Database::getInstance()->getConnection();
    
    // Check if application exists and get current status
    $checkStmt = $db->prepare('SELECT id, status FROM applications WHERE id = ?');
    $checkStmt->execute([$appId]);
    $application = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if (!$application) {
        throw new Exception('Application not found');
    }
    
    $oldStatus = $application['status'];
    
    // Only update if status is actually changing
    if ($oldStatus === $status) {
        echo json_encode([
            'success' => true,
            'message' => 'Status is already set to ' . $status,
            'app_id' => $appId,
            'new_status' => $status
        ]);
        exit;
    }
    
    // Update the application status
    $updateStmt = $db->prepare('UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?');
    $success = $updateStmt->execute([$status, $appId]);
    
    if (!$success) {
        throw new Exception('Failed to update application status');
    }
    
    // Log the change to audit_log using ActivityManager
    try {
        $activityManager = new ActivityManager();
        $activityManager->logFieldChange($appId, 'status', $oldStatus, $status, $_SESSION['user_id'], 'UPDATE');
    } catch (Exception $e) {
        // Log error but don't fail the main operation
        error_log("Failed to log audit entry: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Application status updated successfully',
        'app_id' => $appId,
        'new_status' => $status
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
