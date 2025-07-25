<?php
// public/handover/preview.php - Document preview functionality
require_once __DIR__ . '/../../src/db/db.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$document_id = $_GET['document_id'] ?? 0;

if (!$document_id) {
    exit('Document ID required');
}

// Get document data
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT hd.*, a.short_description as application_name 
    FROM handover_documents hd 
    LEFT JOIN applications a ON hd.application_id = a.id 
    WHERE hd.id = ? AND hd.created_by = ?
");
$stmt->execute([$document_id, $_SESSION['user_id']]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    exit('Document not found');
}

// Get all document data
$stmt = $db->prepare("SELECT field_name, field_value FROM handover_data WHERE handover_document_id = ?");
$stmt->execute([$document_id]);
$data_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = [];
foreach ($data_rows as $row) {
    $data[$row['field_name']] = $row['field_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handover Document Preview - <?php echo htmlspecialchars($document['title']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- FontAwesome Pro -->
    <script src="https://kit.fontawesome.com/d67c79608d.js" crossorigin="anonymous"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/main.css" rel="stylesheet"> <!-- Go up two levels from handover to root -->
    <style>
        /* Ensure profile image has correct size and shape */
        .profile-img { 
            width: 36px !important; 
            height: 36px !important; 
            object-fit: cover !important; 
            border-radius: 50% !important; 
        }
        
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .section-header { background-color: #f8f9fa; padding: 1rem; margin: 2rem 0 1rem 0; border-left: 4px solid #007bff; }
        .data-table { margin-bottom: 2rem; }
        @media print {
            .no-print { display: none !important; }
            .section-header { break-after: avoid; }
        }
    </style>
</head>
<body class="bg-light">
    <?php include __DIR__ . '/../shared/topbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <!-- Header -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h3 mb-1"><i class="fas fa-exchange-alt"></i> Application Handover Documentation</h1>
                                <p class="text-muted mb-0">
                                    <strong><?php echo htmlspecialchars($document['title'] ?? 'Handover Document'); ?></strong>
                                    <?php if (isset($document['application_name'])): ?>
                                        - <?php echo htmlspecialchars($document['application_name']); ?>
                                    <?php endif; ?>
                                </p>
                                <small class="text-muted">
                                    Created: <?php echo date('d.m.Y H:i', strtotime($document['created_at'])); ?> | 
                                    Status: <span class="badge bg-<?php 
                                        echo $document['status'] === 'completed' ? 'success' : 
                                             ($document['status'] === 'review' ? 'warning' : 
                                             ($document['status'] === 'in_progress' ? 'info' : 'secondary')); 
                                    ?>"><?php echo htmlspecialchars($document['status']); ?></span>
                                </small>
                            </div>
                            <div>
                                <button class="btn btn-secondary me-2" onclick="window.history.back()">
                                    <i class="fas fa-arrow-left"></i> Back
                                </button>
                                <button class="btn btn-primary" onclick="window.print()">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
        <?php
        // Define sections for preview
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
            echo "<div class='section-header'>";
            echo "<h3>$step. $title</h3>";
            echo "</div>";
            
            // Display relevant data for this section
            $section_data = [];
            foreach ($data as $key => $value) {
                if (strpos($key, "step_{$step}_") === 0 || isRelatedField($key, $step)) {
                    $section_data[$key] = $value;
                }
            }
            
            if (!empty($section_data)) {
                displaySectionData($section_data);
            } else {
                echo "<p class='text-muted'>No data available for this section.</p>";
            }
        }
        
        function isRelatedField($fieldName, $step) {
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
        
        function displaySectionData($data) {
            foreach ($data as $key => $value) {
                if (empty($value)) continue;
                
                $displayName = ucwords(str_replace('_', ' ', $key));
                
                // Check if it's JSON data (tables)
                $decoded = json_decode($value, true);
                if (is_array($decoded) && !empty($decoded)) {
                    echo "<h6>$displayName</h6>";
                    echo "<div class='table-responsive data-table'>";
                    echo "<table class='table table-bordered table-sm'>";
                    
                    // Get headers from first row
                    if (!empty($decoded[0])) {
                        echo "<thead class='table-light'><tr>";
                        foreach (array_keys($decoded[0]) as $header) {
                            echo "<th>" . ucwords(str_replace('_', ' ', $header)) . "</th>";
                        }
                        echo "</tr></thead>";
                    }
                    
                    echo "<tbody>";
                    foreach ($decoded as $row) {
                        echo "<tr>";
                        foreach ($row as $cell) {
                            echo "<td>" . htmlspecialchars($cell ?: '-') . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</tbody></table></div>";
                } else {
                    // Regular text field
                    echo "<div class='mb-3'>";
                    echo "<strong>$displayName:</strong><br>";
                    echo "<p>" . nl2br(htmlspecialchars($value)) . "</p>";
                    echo "</div>";
                }
            }
        }
        ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
