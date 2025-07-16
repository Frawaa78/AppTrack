<?php
// public/api/download_attachment.php
session_start();
require_once __DIR__ . '/../../src/managers/ActivityManager.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Access denied');
}

$work_note_id = $_GET['id'] ?? null;

if (!$work_note_id) {
    http_response_code(400);
    exit('Work note ID required');
}

try {
    $activityManager = new ActivityManager();
    $attachment = $activityManager->getAttachment($work_note_id);
    
    if (!$attachment) {
        http_response_code(404);
        exit('Attachment not found');
    }
    
    // Set headers for file download
    header('Content-Type: ' . $attachment['attachment_mime_type']);
    header('Content-Disposition: attachment; filename="' . $attachment['attachment_filename'] . '"');
    header('Content-Length: ' . strlen($attachment['attachment_data']));
    
    // Output file data
    echo $attachment['attachment_data'];
    
} catch (Exception $e) {
    http_response_code(500);
    exit('Error downloading attachment: ' . $e->getMessage());
}
?>
