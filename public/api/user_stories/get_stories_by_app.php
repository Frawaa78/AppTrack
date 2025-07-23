<?php
// public/api/user_stories/get_stories_by_app.php

header('Content-Type: application/json');
session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../../src/controllers/UserStoryController.php';

try {
    if (!isset($_GET['application_id']) || empty($_GET['application_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Application ID is required']);
        exit;
    }
    
    $applicationId = (int)$_GET['application_id'];
    $controller = new UserStoryController();
    
    $result = $controller->getByApplication($applicationId);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $result['error']]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
