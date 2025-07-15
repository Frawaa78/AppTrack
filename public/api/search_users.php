<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../src/db/db.php';

try {
    // Get search query
    $query = trim($_GET['q'] ?? '');
    
    // Validate query length
    if (strlen($query) < 2) {
        echo json_encode([]);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Search in first_name, last_name, display_name, and email
    // Return all active users if they exist
    $sql = "SELECT 
                id,
                first_name,
                last_name,
                display_name,
                email,
                phone,
                role
            FROM users 
            WHERE is_active = 1 
            AND (role = 'admin' OR role = 'editor' OR role IS NULL)
            AND (
                first_name LIKE ? 
                OR last_name LIKE ? 
                OR display_name LIKE ? 
                OR email LIKE ?
                OR CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?
            )
            ORDER BY display_name ASC
            LIMIT 10";
    
    $stmt = $db->prepare($sql);
    $searchTerm = '%' . $query . '%';
    
    $stmt->execute([
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm
    ]);
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format results for Choices.js
    $results = [];
    foreach ($users as $user) {
        // Use display_name if available, otherwise combine first and last name
        $displayName = !empty($user['display_name']) 
            ? $user['display_name'] 
            : trim($user['first_name'] . ' ' . $user['last_name']);
        
        // If display name is still empty, use email
        if (empty($displayName)) {
            $displayName = $user['email'];
        }
        
        // Create subtitle with role and email/phone info
        $subtitle = '';
        if (!empty($user['role'])) {
            $subtitle .= ucfirst($user['role']);
        }
        if (!empty($user['email'])) {
            $subtitle .= ($subtitle ? ' • ' : '') . $user['email'];
        }
        if (!empty($user['phone'])) {
            $subtitle .= ($subtitle ? ' • ' : '') . $user['phone'];
        }
        
        // IMPORTANT: Only display name goes in value (what gets saved)
        // Full info with email/role goes in label (what shows in dropdown)
        $results[] = [
            'value' => $displayName, // Only the name - this is what gets stored in the form
            'label' => $displayName . ($subtitle ? '<br><small style="color: #666; font-size: 11px;">' . htmlspecialchars($subtitle) . '</small>' : ''),
            'customProperties' => [
                'id' => $user['id'],
                'email' => $user['email'] ?? '',
                'phone' => $user['phone'] ?? '',
                'role' => $user['role'] ?? ''
            ]
        ];
    }
    
    // If no results found, add a "no results" message
    if (empty($results)) {
        $results = [
            [
                'value' => '',
                'label' => 'Ingen brukere funnet for "' . htmlspecialchars($query) . '"',
                'disabled' => true
            ]
        ];
    }
    
    echo json_encode($results);
    
} catch (PDOException $e) {
    error_log('Database error in search_users.php: ' . $e->getMessage());
    echo json_encode([
        [
            'value' => '',
            'label' => 'Database feil: ' . $e->getMessage(),
            'disabled' => true
        ]
    ]);
} catch (Exception $e) {
    error_log('General error in search_users.php: ' . $e->getMessage());
    echo json_encode([
        [
            'value' => '',
            'label' => 'Feil: ' . $e->getMessage(),
            'disabled' => true
        ]
    ]);
}
?>
