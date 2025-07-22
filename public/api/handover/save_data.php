<?php
// public/api/handover/save_data.php
require_once __DIR__ . '/../../../src/db/db.php';
session_start();

header('Content-Type: application/json');

// Log incoming request for debugging
file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " - Save request received\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug_log.txt', "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug_log.txt', "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n", FILE_APPEND);

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get document ID from form data
    $handover_document_id = $_POST['document_id'] ?? null;
    $section_name = $_POST['section_name'] ?? null;
    if (!$handover_document_id) {
        throw new Exception('Missing document ID');
    }
    if (!$section_name) {
        throw new Exception('Missing section name');
    }
    // Verify user has access to this document
    $stmt = $conn->prepare('SELECT id, created_by FROM handover_documents WHERE id = ?');
    $stmt->execute([$handover_document_id]);
    $doc = $stmt->fetch();
    
    if (!$doc) {
        throw new Exception('Document not found');
    }
    
    // Allow access if user created the document OR if user is admin
    $user_role = $_SESSION['user_role'] ?? 'viewer';
    if ($doc['created_by'] != $_SESSION['user_id'] && $user_role !== 'admin') {
        throw new Exception('Access denied - document belongs to user ' . $doc['created_by'] . ', current user is ' . $_SESSION['user_id']);
    }
    $conn->beginTransaction();
    $saved_fields = 0;
    
    // Special handling for step 2 (participants) - save to dedicated table
    if ($section_name === 'participants' && isset($_POST['participants']) && is_array($_POST['participants'])) {
        // Remove old participants for this document
        $stmt = $conn->prepare('DELETE FROM handover_participants WHERE handover_document_id = ?');
        $stmt->execute([$handover_document_id]);
        
        // Insert new participants
        foreach ($_POST['participants'] as $participant) {
            // Skip empty participants
            if (empty($participant['role']) && empty($participant['name'])) {
                continue;
            }
            
            $role = $participant['role'] ?? '';
            $name = $participant['name'] ?? '';
            $organization = $participant['organization'] ?? '';
            $contact_info = $participant['contact_info'] ?? '';
            
            $stmt = $conn->prepare('INSERT INTO handover_participants (handover_document_id, role, name, organization, contact_info) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$handover_document_id, $role, $name, $organization, $contact_info]);
            $saved_fields++;
        }
        
        // Also save participants as JSON in handover_data for backward compatibility
        $participants_json = json_encode($_POST['participants'], JSON_UNESCAPED_UNICODE);
        $stmt = $conn->prepare('DELETE FROM handover_data WHERE handover_document_id = ? AND section_name = ? AND field_name = ?');
        $stmt->execute([$handover_document_id, $section_name, 'participants']);
        
        $stmt = $conn->prepare('INSERT INTO handover_data (handover_document_id, section_name, field_name, field_value) VALUES (?, ?, ?, ?)');
        $stmt->execute([$handover_document_id, $section_name, 'participants', $participants_json]);
    } else if ($section_name === 'participants' && isset($_POST['participants']) && !is_array($_POST['participants'])) {
        // Log error if participants is not array
        file_put_contents(__DIR__ . '/error_log.txt', date('Y-m-d H:i:s') . ' - participants is not array: ' . print_r($_POST['participants'], true) . "\n", FILE_APPEND);
        throw new Exception('Participants data is not an array. Received: ' . gettype($_POST['participants']));
    }
    
    // Generic handling for all array-based fields
    $array_fields = [
        'contacts', 'participants', 'functionality', 'contracts', 'service_desk_items', 
        'testing_documentation', 'release_items', 'mfa_items', 'risks', 'security_reviews', 
        'current_year_costs', 'next_year_costs', 'data_sources', 'data_classifications',
        'signatures', 'meetings', 'decisions', 'action_items', 'kb_articles', 'log_items',
        'documentation'
    ];
    
    foreach ($array_fields as $field_name) {
        if (isset($_POST[$field_name]) && is_array($_POST[$field_name])) {
            // Process the array field
            $processed_items = [];
            foreach ($_POST[$field_name] as $item) {
                // Skip completely empty items
                if (is_array($item) && !empty(array_filter($item, function($value) { return !empty(trim($value)); }))) {
                    // Special handling for contacts with custom roles
                    if ($field_name === 'contacts' && isset($item['role']) && $item['role'] === 'custom') {
                        $item['role'] = $item['custom_role'] ?? '';
                        unset($item['custom_role']);
                    }
                    $processed_items[] = $item;
                    $saved_fields++;
                }
            }
            
            // Save as JSON in handover_data
            if (!empty($processed_items)) {
                $items_json = json_encode($processed_items, JSON_UNESCAPED_UNICODE);
                $stmt = $conn->prepare('DELETE FROM handover_data WHERE handover_document_id = ? AND section_name = ? AND field_name = ?');
                $stmt->execute([$handover_document_id, $section_name, $field_name]);
                
                $stmt = $conn->prepare('INSERT INTO handover_data (handover_document_id, section_name, field_name, field_value) VALUES (?, ?, ?, ?)');
                $stmt->execute([$handover_document_id, $section_name, $field_name, $items_json]);
            }
        }
    }
    // Save all other form data to handover_data
    foreach ($_POST as $field_name => $field_value) {
        // Skip meta fields and array fields (already handled above)
        $skip_fields = array_merge(['document_id', 'section_name'], $array_fields);
        if (in_array($field_name, $skip_fields)) {
            continue;
        }
        
        // Handle arrays (for any remaining table data)
        if (is_array($field_value)) {
            $field_value = json_encode($field_value, JSON_UNESCAPED_UNICODE);
        }
        
        // Only save non-empty values
        if (!empty($field_value) || $field_value === '0') {
            // Delete existing entry for this field in this section
            $stmt = $conn->prepare('DELETE FROM handover_data WHERE handover_document_id = ? AND section_name = ? AND field_name = ?');
            $stmt->execute([$handover_document_id, $section_name, $field_name]);
            
            // Insert new value
            $stmt = $conn->prepare('INSERT INTO handover_data (handover_document_id, section_name, field_name, field_value) VALUES (?, ?, ?, ?)');
            $stmt->execute([$handover_document_id, $section_name, $field_name, $field_value]);
            $saved_fields++;
        }
    }
    
    // Update document timestamp and calculate progress
    $stmt = $conn->prepare('UPDATE handover_documents SET updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->execute([$handover_document_id]);
    // Calculate completion percentage by counting unique sections with data
    $stmt = $conn->prepare('SELECT DISTINCT section_name FROM handover_data WHERE handover_document_id = ?');
    $stmt->execute([$handover_document_id]);
    $sections_completed = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $completion_percentage = (count($sections_completed) / 15) * 100;
    $stmt = $conn->prepare('UPDATE handover_documents SET completion_percentage = ? WHERE id = ?');
    $stmt->execute([round($completion_percentage, 1), $handover_document_id]);
    $conn->commit();
    echo json_encode([
        'success' => true,
        'saved_fields' => $saved_fields,
        'completion_percentage' => round($completion_percentage, 1),
        'completed_steps' => count($sections_completed)
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    http_response_code(500);
    // Skriv feilmelding til fil for debugging
    file_put_contents(__DIR__ . '/error_log.txt', date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
