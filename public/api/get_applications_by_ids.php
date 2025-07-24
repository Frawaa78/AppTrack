<?php
// public/api/get_applications_by_ids.php

header('Content-Type: application/json');
session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../src/db/db.php';

try {
    if (!isset($_GET['ids']) || empty($_GET['ids'])) {
        echo json_encode([]);
        exit;
    }

    $ids = explode(',', $_GET['ids']);
    $ids = array_map('trim', $ids);
    $ids = array_filter($ids, 'is_numeric');

    if (empty($ids)) {
        echo json_encode([]);
        exit;
    }

    $db = Database::getInstance()->getConnection();
    
    // Create placeholders for IN clause
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    $sql = "SELECT id, short_description FROM applications WHERE id IN ($placeholders) ORDER BY short_description";
    $stmt = $db->prepare($sql);
    $stmt->execute($ids);
    
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($applications);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
?>
