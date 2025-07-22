<?php
// public/api/kanban_data.php - Kanban Board Data API
require_once __DIR__ . '/../../src/db/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if "show mine only" filter is requested
    $showMineOnly = isset($_GET['show_mine_only']) && $_GET['show_mine_only'] === 'true';
    $currentUserEmail = $_SESSION['user_email'] ?? '';
    
    // Base SQL query to fetch applications with work notes count, grouped by phase
    $sql = "
        SELECT 
            a.id,
            a.short_description,
            a.phase,
            a.status,
            a.handover_status,
            a.project_manager,
            a.assigned_to,
            a.updated_at,
            COUNT(wn.id) as work_notes_count
        FROM applications a
        LEFT JOIN work_notes wn ON a.id = wn.application_id";
    
    // Add WHERE clause for filtering if requested
    if ($showMineOnly && !empty($currentUserEmail)) {
        // Join with users table to get current user's display name
        $sql .= " LEFT JOIN users u ON u.email = :user_email
                  WHERE (a.assigned_to = u.display_name 
                         OR a.assigned_to = CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))
                         OR a.assigned_to = u.email)";
    }
        
    $sql .= " GROUP BY a.id, a.short_description, a.phase, a.status, a.handover_status, a.project_manager, a.assigned_to, a.updated_at
        ORDER BY a.phase, a.updated_at DESC
    ";
    
    $stmt = $db->prepare($sql);
    
    // Bind parameters if filtering
    if ($showMineOnly && !empty($currentUserEmail)) {
        $stmt->bindParam(':user_email', $currentUserEmail, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Define phase order and initialize result structure
    $phases = ['Need', 'Solution', 'Build', 'Implement', 'Operate'];
    $kanbanData = [];
    
    // Initialize empty arrays for each phase
    foreach ($phases as $phase) {
        $kanbanData[$phase] = [];
    }
    
    // Group applications by phase
    foreach ($applications as $app) {
        $phase = $app['phase'] ?? 'Need'; // Default to Need if phase is null
        
        // Ensure phase exists in our structure
        if (!isset($kanbanData[$phase])) {
            $kanbanData[$phase] = [];
        }
        
        // Format the application data for frontend
        $kanbanData[$phase][] = [
            'id' => (int)$app['id'],
            'name' => $app['short_description'],
            'status' => $app['status'] ?? 'Unknown',
            'handover_status' => (int)($app['handover_status'] ?? 0),
            'project_manager' => $app['project_manager'] ?? 'Not assigned',
            'work_notes_count' => (int)$app['work_notes_count'],
            'updated_at' => $app['updated_at'],
            'formatted_date' => date('d.m.Y', strtotime($app['updated_at']))
        ];
    }
    
    // Sort each phase by updated_at DESC (newest first)
    foreach ($kanbanData as $phase => $apps) {
        usort($kanbanData[$phase], function($a, $b) {
            return strtotime($b['updated_at']) - strtotime($a['updated_at']);
        });
    }
    
    // Return success response with data
    echo json_encode([
        'success' => true,
        'data' => $kanbanData,
        'phase_counts' => [
            'Need' => count($kanbanData['Need']),
            'Solution' => count($kanbanData['Solution']),
            'Build' => count($kanbanData['Build']),
            'Implement' => count($kanbanData['Implement']),
            'Operate' => count($kanbanData['Operate'])
        ],
        'total_applications' => array_sum(array_map('count', $kanbanData))
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
