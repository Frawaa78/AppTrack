<?php
// public/api/delete_attachment.php
session_start();
require_once __DIR__ . '/../../src/db/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$work_note_id = $input['work_note_id'] ?? null;

if (!$work_note_id) {
    echo json_encode(['success' => false, 'error' => 'Work note ID is required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // First verify that the work note exists and has an attachment
    $stmt = $db->prepare('SELECT id, attachment_filename FROM work_notes WHERE id = ?');
    $stmt->execute([$work_note_id]);
    $work_note = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$work_note) {
        echo json_encode(['success' => false, 'error' => 'Work note not found']);
        exit;
    }
    
    if (!$work_note['attachment_filename']) {
        echo json_encode(['success' => false, 'error' => 'No attachment found for this work note']);
        exit;
    }
    
    // Remove attachment data from database
    $stmt = $db->prepare('UPDATE work_notes SET 
        attachment_data = NULL, 
        attachment_filename = NULL, 
        attachment_size = NULL, 
        attachment_mime_type = NULL 
        WHERE id = ?');
    
    $success = $stmt->execute([$work_note_id]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Attachment deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete attachment']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error deleting attachment: ' . $e->getMessage()
    ]);
}
?>
