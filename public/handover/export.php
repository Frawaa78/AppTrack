<?php
// public/handover/export.php - Document export functionality
require_once '../../src/config/config.php';
require_once '../../src/db/db.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$document_id = $_GET['document_id'] ?? 0;
$formats = explode(',', $_GET['formats'] ?? 'pdf');

if (!$document_id) {
    exit('Document ID required');
}

// Get document data
$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM handover_documents WHERE id = ? AND created_by = ?");
$stmt->execute([$document_id, $_SESSION['user_id']]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    exit('Document not found');
}

// Get all document data
$stmt = $conn->prepare("SELECT field_name, field_value FROM handover_data WHERE document_id = ?");
$stmt->execute([$document_id]);
$data_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = [];
foreach ($data_rows as $row) {
    $data[$row['field_name']] = $row['field_value'];
}

// Create export directory if it doesn't exist
$exportDir = '../../exports/handover/';
if (!is_dir($exportDir)) {
    mkdir($exportDir, 0755, true);
}

$filename = 'handover_' . $document['application_name'] . '_' . date('Y-m-d_H-i-s');
$files_created = [];

foreach ($formats as $format) {
    switch ($format) {
        case 'json':
            $json_file = $exportDir . $filename . '.json';
            $export_data = [
                'document' => $document,
                'data' => $data,
                'exported_at' => date('c'),
                'exported_by' => $_SESSION['user_id']
            ];
            file_put_contents($json_file, json_encode($export_data, JSON_PRETTY_PRINT));
            $files_created[] = $json_file;
            break;
            
        case 'html':
            $html_file = $exportDir . $filename . '.html';
            $html_content = generateHtmlDocument($document, $data);
            file_put_contents($html_file, $html_content);
            $files_created[] = $html_file;
            break;
            
        case 'csv':
            // Export tables as CSV
            $csv_file = $exportDir . $filename . '_tables.csv';
            $csv_content = generateCsvData($data);
            file_put_contents($csv_file, $csv_content);
            $files_created[] = $csv_file;
            break;
    }
}

// If only one file, download directly
if (count($files_created) == 1) {
    $file = $files_created[0];
    $download_name = basename($file);
    
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $download_name . '"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    
    // Clean up
    unlink($file);
    exit;
}

// Multiple files - create ZIP
if (count($files_created) > 1) {
    $zip_file = $exportDir . $filename . '.zip';
    $zip = new ZipArchive();
    
    if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
        foreach ($files_created as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($zip_file) . '"');
        header('Content-Length: ' . filesize($zip_file));
        readfile($zip_file);
        
        // Clean up
        unlink($zip_file);
        foreach ($files_created as $file) {
            unlink($file);
        }
        exit;
    }
}

function generateHtmlDocument($document, $data) {
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handover Document - ' . htmlspecialchars($document['title']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: #f4f4f4; padding: 20px; margin-bottom: 30px; border-left: 5px solid #007bff; }
        .section { margin-bottom: 30px; }
        .section h2 { color: #007bff; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .metadata { background: #f9f9f9; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Application Handover Documentation</h1>
        <h2>' . htmlspecialchars($document['title']) . '</h2>
        <div class="metadata">
            <strong>Application:</strong> ' . htmlspecialchars($document['application_name'] ?? 'N/A') . '<br>
            <strong>Created:</strong> ' . date('F j, Y g:i A', strtotime($document['created_at'])) . '<br>
            <strong>Status:</strong> ' . htmlspecialchars($document['status']) . '<br>
            <strong>Progress:</strong> ' . $document['progress'] . '%<br>
            <strong>Exported:</strong> ' . date('F j, Y g:i A') . '
        </div>
    </div>';
    
    $sections = [
        1 => 'Definitions and Terminology',
        2 => 'Participants and Roles',
        3 => 'Contact Points and Information',
        4 => 'Support Models and SLAs',
        5 => 'Deliverables and Documentation',
        6 => 'Testing Procedures and Results',
        7 => 'Release Management',
        8 => 'Technical Architecture',
        9 => 'Risk Assessment and Mitigation',
        10 => 'Security Requirements',
        11 => 'Economics and Cost Management',
        12 => 'Data Storage and Management',
        13 => 'Digital Signatures and Approvals',
        14 => 'Meeting Minutes and Decisions',
        15 => 'Final Review and Export'
    ];
    
    foreach ($sections as $step => $title) {
        $html .= '<div class="section">';
        $html .= '<h2>' . $step . '. ' . $title . '</h2>';
        
        // Find data for this section
        $section_data = [];
        foreach ($data as $key => $value) {
            if (strpos($key, "step_{$step}_") === 0 || isRelatedToSection($key, $step)) {
                $section_data[$key] = $value;
            }
        }
        
        if (!empty($section_data)) {
            foreach ($section_data as $key => $value) {
                if (empty($value)) continue;
                
                $displayName = ucwords(str_replace('_', ' ', $key));
                $decoded = json_decode($value, true);
                
                if (is_array($decoded) && !empty($decoded)) {
                    $html .= '<h3>' . $displayName . '</h3>';
                    $html .= '<table>';
                    
                    if (!empty($decoded[0])) {
                        $html .= '<tr>';
                        foreach (array_keys($decoded[0]) as $header) {
                            $html .= '<th>' . ucwords(str_replace('_', ' ', $header)) . '</th>';
                        }
                        $html .= '</tr>';
                    }
                    
                    foreach ($decoded as $row) {
                        $html .= '<tr>';
                        foreach ($row as $cell) {
                            $html .= '<td>' . htmlspecialchars($cell ?: '-') . '</td>';
                        }
                        $html .= '</tr>';
                    }
                    $html .= '</table>';
                } else {
                    $html .= '<h3>' . $displayName . '</h3>';
                    $html .= '<p>' . nl2br(htmlspecialchars($value)) . '</p>';
                }
            }
        } else {
            $html .= '<p><em>No data available for this section.</em></p>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</body></html>';
    return $html;
}

function generateCsvData($data) {
    $csv = "Field Name,Field Value\n";
    
    foreach ($data as $key => $value) {
        // For JSON data, flatten it
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $csv .= '"' . $key . '","' . str_replace('"', '""', json_encode($decoded)) . '"' . "\n";
        } else {
            $csv .= '"' . $key . '","' . str_replace('"', '""', $value) . '"' . "\n";
        }
    }
    
    return $csv;
}

function isRelatedToSection($fieldName, $step) {
    $fieldMappings = [
        1 => ['terminology', 'definitions'],
        2 => ['participants', 'roles'],
        3 => ['contacts', 'contact_'],
        4 => ['support_', 'sla_'],
        5 => ['deliverables', 'documentation'],
        6 => ['testing_', 'test_'],
        7 => ['release_', 'deployment'],
        8 => ['technical_', 'architecture'],
        9 => ['risk_', 'mitigation'],
        10 => ['security_', 'mfa_', 'logs_'],
        11 => ['current_year_costs', 'next_year_costs', 'budget'],
        12 => ['data_', 'backup_', 'archiving'],
        13 => ['signatures', 'handover_completion'],
        14 => ['meetings', 'decisions', 'action_items'],
        15 => ['review_', 'export_', 'final_']
    ];
    
    if (isset($fieldMappings[$step])) {
        foreach ($fieldMappings[$step] as $pattern) {
            if (strpos($fieldName, $pattern) !== false) {
                return true;
            }
        }
    }
    return false;
}

// If we get here, no files were created
echo "Error: No files could be generated for the selected formats.";
?>
