<?php
// public/api/handover/toggle_step_completion.php
require_once __DIR__ . '/../../../src/db/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $document_id = $input['document_id'] ?? null;
    $step_number = $input['step_number'] ?? null;
    $complete = $input['complete'] ?? false;
    
    if (!$document_id || !$step_number) {
        throw new Exception('Missing document ID or step number');
    }
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Verify user has access to this document
    $stmt = $conn->prepare('SELECT id, created_by, completed_steps FROM handover_documents WHERE id = ?');
    $stmt->execute([$document_id]);
    $doc = $stmt->fetch();
    
    if (!$doc) {
        throw new Exception('Document not found');
    }
    
    // Allow access if user created the document OR if user is admin
    $user_role = $_SESSION['user_role'] ?? 'viewer';
    if ($doc['created_by'] != $_SESSION['user_id'] && $user_role !== 'admin') {
        throw new Exception('Access denied');
    }
    
    // Parse current completed steps
    $completed_steps = [];
    if (!empty($doc['completed_steps'])) {
        $completed_steps = json_decode($doc['completed_steps'], true) ?: [];
    }
    
    // Update completed steps array
    if ($complete) {
        // Add step to completed list
        if (!in_array($step_number, $completed_steps)) {
            $completed_steps[] = $step_number;
        }
    } else {
        // Remove step from completed list
        $completed_steps = array_values(array_filter($completed_steps, function($step) use ($step_number) {
            return $step != $step_number;
        }));
    }
    
    // Sort the array
    sort($completed_steps);
    
    // Update database
    $stmt = $conn->prepare('UPDATE handover_documents SET completed_steps = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->execute([json_encode($completed_steps), $document_id]);
    
    echo json_encode([
        'success' => true,
        'completed_steps' => $completed_steps,
        'message' => $complete ? 'Step completed and locked' : 'Step reopened for editing'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
