<?php
// public/api/user_stories/get_story.php

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
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Story ID is required']);
        exit;
    }
    
    $id = (int)$_GET['id'];
    $controller = new UserStoryController();
    
    $result = $controller->show($id);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        if ($result['error'] === 'User story not found') {
            http_response_code(404);
        } else {
            http_response_code(500);
        }
        echo json_encode(['error' => $result['error']]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
