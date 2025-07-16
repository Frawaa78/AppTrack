<?php
// public/api/add_work_note.php
session_start();
require_once __DIR__ . '/../../src/managers/ActivityManager.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$application_id = $_POST['application_id'] ?? null;
$note = trim($_POST['note'] ?? '');
$type = $_POST['type'] ?? 'comment';
$priority = $_POST['priority'] ?? 'medium';

if (!$application_id || empty($note)) {
    echo json_encode(['success' => false, 'error' => 'Application ID and note are required']);
    exit;
}

try {
    $activityManager = new ActivityManager();
    $attachment = null;
    
    // Håndter vedlegg hvis det finnes
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $validation = $activityManager->validateFile($_FILES['attachment']);
        
        if (!$validation['valid']) {
            echo json_encode(['success' => false, 'error' => $validation['error']]);
            exit;
        }
        
        $attachment = [
            'data' => file_get_contents($_FILES['attachment']['tmp_name']),
            'filename' => $_FILES['attachment']['name'],
            'size' => $_FILES['attachment']['size'],
            'mime_type' => $_FILES['attachment']['type']
        ];
    }
    
    $success = $activityManager->addWorkNote(
        $application_id,
        $_SESSION['user_id'],
        $note,
        $type,
        $priority,
        $attachment
    );
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add work note']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error adding work note: ' . $e->getMessage()
    ]);
}
?>
