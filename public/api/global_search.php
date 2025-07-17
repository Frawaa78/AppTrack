<?php
// public/api/global_search.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../src/db/db.php';

try {
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (strlen($query) < 3) {
        echo json_encode(['applications' => [], 'users' => []]);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    $searchTerm = '%' . $query . '%';
    
    // Search Applications - Extended search across multiple fields
    $appSql = "SELECT DISTINCT a.id, a.short_description, a.status, a.updated_at
               FROM applications a
               LEFT JOIN work_notes wn ON a.id = wn.application_id
               WHERE a.short_description LIKE ? 
                  OR a.application_service LIKE ?
                  OR a.business_need LIKE ?
                  OR a.delivery_responsible LIKE ?
                  OR a.project_manager LIKE ?
                  OR a.product_owner LIKE ?
                  OR a.assigned_to LIKE ?
                  OR a.preops_portfolio LIKE ?
                  OR a.application_portfolio LIKE ?
                  OR a.contract_responsible LIKE ?
                  OR a.phase LIKE ?
                  OR a.relevant_for LIKE ?
                  OR wn.note LIKE ?
               ORDER BY a.short_description 
               LIMIT 10";
    
    $appStmt = $db->prepare($appSql);
    // Execute with search term for all fields
    $searchParams = array_fill(0, 13, $searchTerm);
    $appStmt->execute($searchParams);
    $applications = $appStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format applications with proper date formatting
    $formattedApps = [];
    foreach ($applications as $app) {
        $formattedApps[] = [
            'id' => $app['id'],
            'short_description' => $app['short_description'],
            'status' => $app['status'] ?? 'Unknown',
            'updated_at' => $app['updated_at'] ? date('M j, Y', strtotime($app['updated_at'])) : 'Never'
        ];
    }
    
    // Search Users
    $userSql = "SELECT 
                    id,
                    first_name,
                    last_name,
                    display_name,
                    email
                FROM users 
                WHERE is_active = 1 
                AND (
                    first_name LIKE ? 
                    OR last_name LIKE ? 
                    OR display_name LIKE ? 
                    OR email LIKE ?
                    OR CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?
                )
                ORDER BY display_name ASC
                LIMIT 10";
    
    $userStmt = $db->prepare($userSql);
    $userStmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format users with proper display names
    $formattedUsers = [];
    foreach ($users as $user) {
        $displayName = $user['display_name'];
        if (empty($displayName)) {
            $displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        }
        if (empty($displayName)) {
            $displayName = $user['email'];
        }
        
        $formattedUsers[] = [
            'id' => $user['id'],
            'name' => $displayName,
            'email' => $user['email']
        ];
    }
    
    echo json_encode([
        'applications' => $formattedApps,
        'users' => $formattedUsers
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Search failed: ' . $e->getMessage()]);
}
?>
