<?php
// Simple test update API
header('Content-Type: application/json');
session_start();

// Log everything
error_log('Simple Update API - Method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Simple Update API - URI: ' . $_SERVER['REQUEST_URI']);
error_log('Simple Update API - Input: ' . file_get_contents('php://input'));

// Check if we have a story ID
$storyId = $_GET['id'] ?? 'no-id';

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

// Just return success for now
echo json_encode([
    'success' => true,
    'message' => 'Test API called successfully',
    'method' => $_SERVER['REQUEST_METHOD'],
    'story_id' => $storyId,
    'input_received' => $input,
    'session_user_id' => $_SESSION['user_id'] ?? 'not-logged-in'
]);
?>
