<?php
// public/api/user_stories/get_stories.php

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
    $controller = new UserStoryController();
    
    // Build filters from GET parameters
    $filters = [];
    
    if (isset($_GET['application_id']) && !empty($_GET['application_id'])) {
        $filters['application_id'] = (int)$_GET['application_id'];
    }
    
    if (isset($_GET['priority']) && !empty($_GET['priority'])) {
        $filters['priority'] = $_GET['priority'];
    }
    
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $filters['status'] = $_GET['status'];
    }
    
    if (isset($_GET['created_by']) && !empty($_GET['created_by'])) {
        $filters['created_by'] = (int)$_GET['created_by'];
    }
    
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $filters['search'] = $_GET['search'];
    }
    
    // Check for "show mine only" filter
    if (isset($_GET['show_mine_only']) && $_GET['show_mine_only'] === 'true') {
        $filters['created_by'] = $_SESSION['user_id'];
    }
    
    $result = $controller->index($filters);
    
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
