<?php
// public/api/get_ai_analysis.php
session_start();
require_once __DIR__ . '/../../src/services/AIService.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    $application_id = $_GET['application_id'] ?? null;
    $analysis_id = $_GET['analysis_id'] ?? null;
    $limit = min(($_GET['limit'] ?? 10), 50); // Max 50 results
    
    if (!$application_id && !$analysis_id) {
        echo json_encode([
            'success' => false, 
            'error' => 'Application ID or Analysis ID is required'
        ]);
        exit;
    }
    
    $aiService = new AIService();
    
    if ($analysis_id) {
        // Get specific analysis
        $result = $aiService->getAnalysisById($analysis_id);
        if (!$result) {
            echo json_encode(['success' => false, 'error' => 'Analysis not found']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
    } else {
        // Get recent analyses for application
        $results = $aiService->getRecentAnalysis($application_id, $limit);
        
        echo json_encode([
            'success' => true,
            'data' => $results,
            'count' => count($results)
        ]);
    }
    
} catch (Exception $e) {
    error_log('Get AI Analysis Error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
