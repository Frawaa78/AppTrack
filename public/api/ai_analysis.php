<?php
// public/api/ai_analysis.php
session_start();
require_once __DIR__ . '/../../src/services/AIService.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $application_id = $input['application_id'] ?? null;
    $analysis_type = $input['analysis_type'] ?? null;
    $force_refresh = $input['force_refresh'] ?? false;
    
    // Validate required parameters
    if (!$application_id || !$analysis_type) {
        echo json_encode([
            'success' => false, 
            'error' => 'Application ID and analysis type are required'
        ]);
        exit;
    }
    
    // Validate application access (user should be able to view the application)
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare('SELECT id FROM applications WHERE id = ?');
    $stmt->execute([$application_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Application not found']);
        exit;
    }
    
    // Initialize AI service and perform analysis
    $aiService = new AIService();
    $result = $aiService->analyzeApplication($application_id, $analysis_type, $force_refresh);
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
    
} catch (Exception $e) {
    // Log the error for debugging
    error_log('AI Analysis Error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
