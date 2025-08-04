<?php
session_start();
require_once __DIR__ . '/../src/db/db.php';
require_once __DIR__ . '/../src/managers/ActivityManager.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get existing data if id is set (edit mode)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$app = [
    'short_description' => '', 'application_type_id' => '', 'relevant_for' => '', 'phase' => '', 'status' => '',
    'handover_status' => 0, 'contract_number' => '', 'contract_responsible' => '', 'information_space' => '',
    'ba_sharepoint_list' => '', 'relationship_yggdrasil' => [], 'assigned_to' => '', 'preops_portfolio' => '',
    'application_portfolio' => '', 'delivery_responsible' => '', 'corporator_link' => '', 'project_manager' => '',
    'product_owner' => '', 'due_date' => '', 'deployment_model' => '', 'integrations' => '', 'sa_document' => '',
    'business_need' => ''
];
if ($id > 0) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare('SELECT * FROM applications WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if ($row) {
        $app = $row;
        // relationship_yggdrasil can be stored as comma-separated application IDs
        if (isset($app['relationship_yggdrasil']) && !empty($app['relationship_yggdrasil'])) {
            $app['relationship_yggdrasil'] = array_map('trim', explode(',', $app['relationship_yggdrasil']));
        } else {
            $app['relationship_yggdrasil'] = [];
        }
        
        // Fetch related application details for display
        $app['related_apps'] = [];
        if (!empty($app['relationship_yggdrasil'])) {
            $placeholders = implode(',', array_fill(0, count($app['relationship_yggdrasil']), '?'));
            $stmt = $db->prepare("SELECT id, short_description, application_service FROM applications WHERE id IN ($placeholders)");
            $stmt->execute($app['relationship_yggdrasil']);
            $app['related_apps'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debug output removed - form working correctly
        
        $fields = [
            'short_description', 'application_type_id', 'relevant_for', 'phase', 'status', 'handover_status',
            'contract_number', 'contract_responsible', 'information_space', 'ba_sharepoint_list', 'assigned_to',
            'preops_portfolio', 'application_portfolio', 'delivery_responsible', 'corporator_link',
            'project_manager', 'product_owner', 'due_date', 'deployment_model', 'integrations', 'sa_document',
            'business_need'
        ];
        $data = [];
        foreach ($fields as $f) {
            $value = trim($_POST[$f] ?? '');
            
            // Handle date fields - convert empty strings to NULL
            if ($f === 'due_date' && empty($value)) {
                $data[$f] = null;
            } else {
                $data[$f] = $value;
            }
        }
        // relationship_yggdrasil as comma-separated application IDs
        $relationship_ids = isset($_POST['relationship_yggdrasil']) ? (array)$_POST['relationship_yggdrasil'] : [];
        // Validate that all IDs are numeric
        $relationship_ids = array_filter($relationship_ids, 'is_numeric');
        $data['relationship_yggdrasil'] = implode(',', $relationship_ids);
        
        $db = Database::getInstance()->getConnection();
        $activityManager = new ActivityManager();
        
        // Store old values for change logging (only for updates)
        $oldValues = [];
        if ($id > 0) {
            $stmt = $db->prepare('SELECT * FROM applications WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $oldValues = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }
        
        // Handle bidirectional relationships
        $currentAppId = $id > 0 ? $id : null;
        $newRelationshipIds = $relationship_ids;
        
        if ($id > 0) {
            // Update existing application
            $set = '';
            foreach ($fields as $f) { $set .= "$f = :$f, "; }
            $set .= "relationship_yggdrasil = :relationship_yggdrasil";
            $stmt = $db->prepare("UPDATE applications SET $set WHERE id = :id");
            $data['id'] = $id;
            $stmt->execute($data);
            $currentAppId = $id;
            
            // Log field changes
            foreach ($fields as $field) {
                if (isset($oldValues[$field])) {
                    $oldVal = $oldValues[$field];
                    $newVal = $data[$field];
                    
                    // Special handling for numeric fields to avoid false positives
                    $numericFields = ['handover_status'];
                    if (in_array($field, $numericFields)) {
                        $oldVal = (string)$oldVal; // Convert to string for comparison
                        $newVal = (string)$newVal;
                    }
                    
                    if ($oldVal !== $newVal) {
                        $activityManager->logFieldChange(
                            $currentAppId,
                            $field,
                            $oldValues[$field],
                            $data[$field],
                            $_SESSION['user_id']
                        );
                    }
                }
            }
            
            // Log relationship changes
            if (isset($oldValues['relationship_yggdrasil']) && $oldValues['relationship_yggdrasil'] !== $data['relationship_yggdrasil']) {
                $activityManager->logFieldChange(
                    $currentAppId,
                    'relationship_yggdrasil',
                    $oldValues['relationship_yggdrasil'],
                    $data['relationship_yggdrasil'],
                    $_SESSION['user_id']
                );
            }
        } else {
            // Insert new application
            $cols = implode(',', array_merge($fields, ['relationship_yggdrasil']));
            $vals = ':' . implode(',:', array_merge($fields, ['relationship_yggdrasil']));
            $stmt = $db->prepare("INSERT INTO applications ($cols) VALUES ($vals)");
            $stmt->execute($data);
            $currentAppId = $db->lastInsertId();
            
            // Log creation
            $activityManager->logFieldChange(
                $currentAppId,
                'application_created',
                '',
                'New application created',
                $_SESSION['user_id'],
                'INSERT'
            );
        }
        
        // Update bidirectional relationships
        if (!empty($newRelationshipIds) && $currentAppId) {
            foreach ($newRelationshipIds as $relatedAppId) {
                // Get current relationships of the related application
                $stmt = $db->prepare("SELECT relationship_yggdrasil FROM applications WHERE id = :id");
                $stmt->execute([':id' => $relatedAppId]);
                $relatedApp = $stmt->fetch();
                
                if ($relatedApp) {
                    // Parse existing relationships
                    $existingRels = [];
                    if (!empty($relatedApp['relationship_yggdrasil'])) {
                        $existingRels = array_map('trim', explode(',', $relatedApp['relationship_yggdrasil']));
                    }
                    
                    // Add current app to related app's relationships if not already present
                    if (!in_array($currentAppId, $existingRels)) {
                        $existingRels[] = $currentAppId;
                        $updatedRels = implode(',', array_filter($existingRels));
                        
                        // Update the related application
                        $stmt = $db->prepare("UPDATE applications SET relationship_yggdrasil = :relationships WHERE id = :id");
                        $stmt->execute([
                            ':relationships' => $updatedRels,
                            ':id' => $relatedAppId
                        ]);
                    }
                }
            }
        }
        
        // Redirect to view page
        if ($id > 0) {
            header('Location: app_view.php?id=' . $id);
        } else {
            header('Location: app_view.php?id=' . $currentAppId);
        }
        exit;
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 20px; border-radius: 5px;'>";
        echo "<strong>Error saving data:</strong><br>";
        echo htmlspecialchars($e->getMessage());
        echo "</div>";
        // Continue to show the form
    }
}

// Fetch phases from database
$db = Database::getInstance()->getConnection();
$phases = $db->query('SELECT name FROM phases ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
// Fetch statuses from database
$statuses = $db->query('SELECT name FROM statuses ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);

// Fetch pre-ops portfolios from database
$preopsPortfolios = $db->query("SELECT id, name FROM portfolios WHERE type = 'preops' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch application portfolios from database
$applicationPortfolios = $db->query("SELECT id, name FROM portfolios WHERE type = 'application' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch deployment models from database
$deploymentModels = $db->query("SELECT id, name FROM deployment_models ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch application types from database
try {
    $applicationTypes = $db->query("SELECT id, type_name, description, complexity_level, typical_duration_weeks, requires_infrastructure, vendor_support_available FROM application_types ORDER BY type_name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback if application_types table doesn't exist
    error_log("Application types table error: " . $e->getMessage());
    $applicationTypes = [];
}

// Fallback to hardcoded values if database is empty
if (empty($phases)) {
    $phases = ['Need', 'Solution', 'Build', 'Implement', 'Operate'];
}
if (empty($statuses)) {
    $statuses = ['Unknown', 'Not started', 'Ongoing Work', 'On Hold', 'Completed'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $id > 0 ? 'Edit Application' : 'New Application'; ?> | AppTrack</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Favicon -->
  <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon/favicon-16x16.png">
  <link rel="manifest" href="../assets/favicon/site.webmanifest">
  <link rel="shortcut icon" href="../assets/favicon/favicon.ico">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <!-- FontAwesome Pro -->
  <script src="https://kit.fontawesome.com/d67c79608d.js" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/components/user-dropdown.css">
  <link rel="stylesheet" href="../assets/css/components/activity-tracker.css">
  <style>

    
    /* Shared styling for header action buttons */
    .header-action-btn {
        background-color: #FCFCFC;
        border: 1px solid #F0F1F2;
        color: #212529;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        cursor: pointer;
    }
    
    .header-action-btn:hover {
        background-color: #F8F9FA;
        border-color: #DEE2E6;
        color: #212529;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .header-action-btn:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
        background-color: #F8F9FA;
        border-color: #86B7FE;
        color: #212529;
    }
    
    .header-action-btn i {
        font-size: 16px;
        min-width: 16px;
        text-align: center;
    }
    
    /* Fallback for when FontAwesome Pro is not loaded */
    .integration-architecture-btn i:before {
        content: "🗂️" !important;
        font-family: "Apple Color Emoji", "Segoe UI Emoji", "Noto Color Emoji", sans-serif !important;
        font-size: 14px;
    }
    
    /* Override fallback when FontAwesome Pro is loaded */
    .fa-light.fa-sitemap:before,
    .fa-solid.fa-sitemap:before,
    .fas.fa-sitemap:before,
    .fa-sitemap:before {
        content: "\f0e8" !important;
        font-family: "Font Awesome 6 Pro", "Font Awesome 5 Pro", "Font Awesome 6 Free", "Font Awesome 5 Free" !important;
    }
    
    .fa-light.fa-sitemap:before {
        font-weight: 300;
    }
    
    .fa-solid.fa-sitemap:before,
    .fas.fa-sitemap:before {
        font-weight: 900;
    }
    
    /* FontAwesome Pro icons for new header buttons */
    .fa-light.fa-lightbulb:before {
        content: "\f0eb" !important;
        font-family: "Font Awesome 6 Pro" !important;
        font-weight: 300;
    }
    
    .fa-light.fa-microchip:before {
        content: "\f2db" !important;
        font-family: "Font Awesome 6 Pro" !important;
        font-weight: 300;
    }
    
    .fa-light.fa-list-check:before {
        content: "\f0ae" !important;
        font-family: "Font Awesome 6 Pro" !important;
        font-weight: 300;
    }

    /* Toolbar Styles */
    .editor-toolbar {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 12px 16px;
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
        flex-shrink: 0;
    }
    
    .editor-toolbar .btn {
        font-size: 0.875rem;
        padding: 6px 12px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    
    .editor-toolbar .btn-group .btn {
        font-size: 0.75rem;
        padding: 4px 8px;
    }
    
    /* Editor Container */
    .editor-container {
        flex: 1;
        position: relative;
        overflow: hidden;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }


    
    /* Property Panel */
    .property-panel {
      position: absolute;
      top: 120px;
      right: 20px;
      width: 250px;
      background: white;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 0;
      box-shadow: 0 10px 25px rgba(0,0,0,0.15);
      z-index: 1000;
    }

    .property-panel-header {
      background: #f8fafc;
      border-bottom: 1px solid #e2e8f0;
      border-radius: 8px 8px 0 0;
      padding: 12px 16px;
      cursor: move;
      user-select: none;
    }

    .property-panel-content {
      padding: 16px;
    }
    
    .property-group {
      margin-bottom: 12px;
    }
    
    .property-group label {
      font-size: 0.875rem;
      font-weight: 500;
      color: #374151;
      margin-bottom: 4px;
      display: block;
    }
    
    /* Element Types */
    .diagram-element {
      position: absolute;
      cursor: move;
      user-select: none;
      transition: all 0.2s ease;
    }
    
    .diagram-element.selected {
      box-shadow: 0 0 0 2px #3b82f6;
    }
    
    .diagram-element.hover {
      transform: scale(1.05);
    }
    
    .diagram-element.element-process {
      border: 2px solid #6b7280 !important;
      border-radius: 4px !important;
      background: #e2e8f0 !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      text-align: center !important;
      font-size: 0.875rem !important;
      font-weight: 500 !important;
    }
    
    .element-decision {
      border: 2px solid #dc2626;
      background: #fecaca;
      transform: rotate(45deg);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .element-decision .element-text {
      transform: rotate(-45deg);
      text-align: center;
      font-size: 0.75rem;
    }
    
    .diagram-element.element-start {
      border: 2px solid #059669 !important;
      border-radius: 50% !important;
      background: #dcfce7 !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      text-align: center !important;
      font-size: 0.875rem !important;
      font-weight: 500 !important;
    }
    
    .element-database {
      border: 2px solid #7c3aed;
      border-radius: 8px 8px 0 0;
      background: #e9d5ff;
      position: relative;
    }
    
    .element-database::after {
      content: '';
      position: absolute;
      bottom: -6px;
      left: -2px;
      right: -2px;
      height: 6px;
      background: #e9d5ff;
      border: 2px solid #7c3aed;
      border-top: none;
      border-radius: 0 0 8px 8px;
    }
    
    .element-api {
      border: 2px solid #0891b2;
      border-radius: 20px;
      background: #cffafe;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      font-size: 0.875rem;
    }
    
    .element-user {
      border: 2px solid #ea580c;
      background: #fed7aa;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      font-size: 0.875rem;
      position: relative;
    }
    
    .element-user::before {
      content: '👤';
      position: absolute;
      top: -10px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 1.2rem;
    }

    .diagram-element.element-data {
      border: 2px solid #dc3545 !important;
      background: #f8d7da !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      text-align: center !important;
      font-size: 0.875rem !important;
      border-radius: 2px !important; /* Sharp corners for data elements */
    }

    /* Free Lines */
    .free-line {
      cursor: pointer;
      transition: stroke-width 0.2s ease;
    }

    .free-line:hover {
      stroke-width: 3px;
      stroke: #007bff;
    }

    .free-line.selected {
      stroke: #007bff;
      stroke-width: 3px;
    }
    
    /* Text Notes */
    .text-note {
      position: absolute;
      background: #fef3c7;
      border: 1px solid #f59e0b;
      border-radius: 4px;
      padding: 8px;
      font-size: 0.875rem;
      max-width: 200px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      cursor: move;
    }
    
    .text-note.selected {
      border-color: #3b82f6;
      box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
    }
    
    /* Connection Lines */
    .connection-line {
      stroke: #6b7280;
      stroke-width: 2;
      fill: none;
      pointer-events: stroke;
      cursor: pointer;
    }
    
    .connection-line.selected {
      stroke: #3b82f6;
      stroke-width: 3;
    }
    
    .connection-line.hover {
      stroke: #9ca3af;
      stroke-width: 3;
    }
    
    /* Resize Handles */
    .resize-handle {
      position: absolute;
      width: 8px;
      height: 8px;
      background: #3b82f6;
      border: 1px solid white;
      border-radius: 50%;
      cursor: nw-resize;
    }
    
    .resize-handle.se {
      bottom: -4px;
      right: -4px;
      cursor: se-resize;
    }
    
    .resize-handle.ne {
      top: -4px;
      right: -4px;
      cursor: ne-resize;
    }
    
    .resize-handle.sw {
      bottom: -4px;
      left: -4px;
      cursor: sw-resize;
    }
    
    .resize-handle.nw {
      top: -4px;
      left: -4px;
      cursor: nw-resize;
    }
    
    /* Tool States */
    .btn.tool-active {
      background-color: #3b82f6 !important;
      border-color: #3b82f6 !important;
      color: white !important;
    }
    
    /* Canvas States */
    .canvas-connecting {
      cursor: crosshair !important;
    }
    
    .canvas-text-mode {
      cursor: text !important;
    }
        gap: 8px;
    }
    
    .integration-architecture-btn:hover {
        background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        box-shadow: 0 4px 8px rgba(102, 126, 234, 0.35);
        transform: translateY(-1px);
        color: white;
    }
    
    .integration-architecture-btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(102, 126, 234, 0.25);
    }

    .modal-header-controls {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .editor-toolbar {
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: 0.5rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
        flex-shrink: 0;
    }

    .toolbar-section {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .toolbar-section .btn.active {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
    }

    .editor-container {
        flex: 1;
        position: relative;
        overflow: hidden;
        background: #ffffff;
        min-height: 0;
        box-sizing: border-box;
    }
    
    /* Application Type Info Popover Styling */
    .application-type-info {
        font-size: 0.875rem;
        max-width: 300px;
    }
    
    .application-type-info .row {
        margin: 0;
    }
    
    .application-type-info .col-6 {
        padding: 0.25rem 0.5rem;
    }
    
    .application-type-info strong {
        font-size: 0.8rem;
        color: #6c757d;
        display: block;
    }
    
    .application-type-info .text-primary {
        font-weight: 500;
        font-size: 0.9rem;
    }
  </style>
</head>
<body class="bg-light">
<!-- Topbar -->
<?php include __DIR__ . '/shared/topbar.php'; ?>

<div class="container">
  <form method="post" autocomplete="off" id="applicationForm">
    <div class="header-with-buttons">
      <div class="d-flex align-items-center">
        <?php if ($id > 0): ?>
        <a href="app_view.php?id=<?php echo $id; ?>" 
           class="header-action-btn me-3" 
           title="Go back to application view">
          <i class="bi bi-arrow-left"></i> Back
        </a>
        <?php endif; ?>
        <h5 class="mb-0"><?php echo $id > 0 ? 'Edit Application' : 'Create New Application'; ?></h5>
      </div>
      <div class="header-buttons">
        <?php if ($id > 0): ?>
        <button type="button" 
                class="header-action-btn" 
                onclick="openUserStories()" 
                title="View and manage user stories">
          <i class="fa-light fa-lightbulb" data-fallback="fa-solid fa-lightbulb,fas fa-lightbulb,bi bi-lightbulb"></i>
          User Stories
        </button>
        <button type="button" 
                class="header-action-btn" 
                onclick="openAIInsight()" 
                title="Get AI-powered insights and analysis">
          <i class="fa-light fa-microchip" data-fallback="fa-solid fa-microchip,fas fa-microchip,bi bi-cpu"></i>
          AI Insight
        </button>
        <button type="button" 
                class="header-action-btn" 
                onclick="openDataMap()" 
                title="Open DataMap Editor - Create visual data flow diagrams">
          <i class="fa-light fa-project-diagram" data-fallback="fa-solid fa-project-diagram,fas fa-project-diagram,bi bi-diagram-3"></i>
          DataMap
        </button>

        <?php endif; ?>
        <a href="<?php echo $id > 0 ? 'app_view.php?id=' . $id : 'dashboard.php'; ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" form="applicationForm" class="btn btn-primary"><?php echo $id > 0 ? 'Update' : 'Create'; ?></button>
      </div>
    </div>
    
    <div class="row g-3">
      <!-- Left column -->
      <div class="col-md-6">
        <div class="form-group-horizontal">
          <label for="shortDescription" class="form-label">App. Name *</label>
          <div class="input-group">
            <input type="text" class="form-control" id="shortDescription" name="short_description" placeholder="App. Name" value="<?php echo htmlspecialchars($app['short_description'] ?? ''); ?>" required>
                        <button type="button" class="btn btn-outline-secondary info-btn" tabindex="-1"
              data-bs-toggle="popover"
              data-bs-placement="bottom"
              title="App. Name"
              data-bs-content="Provide a short and meaningful description of the application.">
              <i class="bi bi-info-circle"></i>
            </button>
          </div>
        </div>
        <div class="form-group-horizontal">
          <label for="applicationType" class="form-label">Application Type <span class="text-danger">*</span></label>
          <div class="input-group">
            <select class="form-select" id="applicationType" name="application_type_id" required>
              <option value="">Select Application Type...</option>
              <?php foreach ($applicationTypes as $type): ?>
                <option value="<?php echo $type['id']; ?>" 
                        data-complexity="<?php echo $type['complexity_level']; ?>"
                        data-duration="<?php echo $type['typical_duration_weeks']; ?>"
                        data-infrastructure="<?php echo $type['requires_infrastructure'] ? 'Yes' : 'No'; ?>"
                        data-vendor="<?php echo $type['vendor_support_available'] ? 'Yes' : 'No'; ?>"
                        <?php if(($app['application_type_id'] ?? '') == $type['id']) echo 'selected'; ?>>
                  <?php echo htmlspecialchars($type['type_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-outline-secondary info-btn" tabindex="-1"
              data-bs-toggle="popover"
              data-bs-placement="bottom"
              data-bs-trigger="click"
              data-bs-html="true"
              title="Application Type Information"
              id="applicationTypeInfoBtn"
              style="display: none;">
              <i class="bi bi-info-circle"></i>
            </button>
          </div>
        </div>
        <div class="form-group-horizontal">
          <label for="relevantFor" class="form-label">Relevant for</label>
          <select class="form-select" id="relevantFor" name="relevant_for">
            <option<?php if(($app['relevant_for'] ?? '')==='To be decided') echo ' selected'; ?>>To be decided</option>
            <option<?php if(($app['relevant_for'] ?? '')==='Yggdrasil') echo ' selected'; ?>>Yggdrasil</option>
            <option<?php if(($app['relevant_for'] ?? '')==='Not relevant') echo ' selected'; ?>>Not relevant</option>
          </select>
        </div>
        <div class="form-group-horizontal">
          <label class="form-label">Phase</label>
          <input type="hidden" name="phase" id="phase_input" value="<?php echo htmlspecialchars($app['phase'] ?? ''); ?>">
          <div class="btn-group w-100" role="group" aria-label="Phase">
            <?php foreach ($phases as $phase): ?>
              <button type="button" class="btn btn-outline-primary<?php if(($app['phase'] ?? '')===$phase) echo ' active'; ?>" onclick="event.preventDefault(); setPhase('<?php echo $phase; ?>', this)"><?php echo $phase; ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="form-group-horizontal">
          <label class="form-label">Status</label>
          <input type="hidden" name="status" id="status_input" value="<?php echo htmlspecialchars($app['status'] ?? ''); ?>">
          <div class="btn-group w-100" role="group" aria-label="Status">
            <?php foreach ($statuses as $status): ?>
              <button type="button" class="btn btn-outline-secondary<?php if(($app['status'] ?? '')===$status) echo ' active'; ?>" onclick="event.preventDefault(); setStatus('<?php echo $status; ?>', this)"><?php echo $status; ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="form-group-horizontal position-relative">
          <label class="form-label">Handover status</label>
          <div class="range-container" style="flex: 1;">
            <input type="range" class="form-range" min="0" max="100" step="10" name="handover_status" value="<?php echo htmlspecialchars($app['handover_status'] ?? '0'); ?>" oninput="updateHandoverTooltip(this)">
            <div class="range-markers">
              <div class="range-marker"></div>
              <div class="range-marker"></div>
              <div class="range-marker"></div>
              <div class="range-marker"></div>
              <div class="range-marker"></div>
              <div class="range-marker"></div>
              <div class="range-marker"></div>
              <div class="range-marker"></div>
              <div class="range-marker"></div>
              <div class="range-marker"></div>
              <div class="range-marker"></div>
            </div>
            <div id="handoverTooltip" class="tooltip-follow">Tooltip</div>
          </div>
        </div>
        <div class="form-group-horizontal">
          <label for="contractNumber" class="form-label">Contract number</label>
          <input type="text" class="form-control" id="contractNumber" name="contract_number" placeholder="Contract number" value="<?php echo htmlspecialchars($app['contract_number'] ?? ''); ?>">
        </div>
        <div class="form-group-horizontal">
          <label for="contractResponsible" class="form-label">Contract responsible</label>
          <input type="text" class="form-control" id="contractResponsible" name="contract_responsible" placeholder="Contract responsible" value="<?php echo htmlspecialchars($app['contract_responsible'] ?? ''); ?>">
        </div>
        <div class="form-group-horizontal">
          <label for="informationSpace" class="form-label">Information Space</label>
          <div class="input-group">
            <input type="url" class="form-control" id="informationSpace" name="information_space" placeholder="Information Space" value="<?php echo htmlspecialchars($app['information_space'] ?? ''); ?>">
            <button type="button" class="btn btn-outline-secondary clear-btn" onclick="event.preventDefault(); clearField('informationSpace')" title="Clear field">
              <i class="bi bi-x"></i>
            </button>
          </div>
        </div>
        <div class="form-group-horizontal">
          <label for="baSharepoint" class="form-label">BA Sharepoint list</label>
          <div class="input-group">
            <input type="text" class="form-control" id="baSharepoint" name="ba_sharepoint_list" placeholder="BA Sharepoint list" value="<?php echo htmlspecialchars($app['ba_sharepoint_list'] ?? ''); ?>">
            <button type="button" class="btn btn-outline-secondary clear-btn" onclick="event.preventDefault(); clearField('baSharepoint')" title="Clear field">
              <i class="bi bi-x"></i>
            </button>
          </div>
        </div>
        <div class="form-group-horizontal">
          <label for="relationshipYggdrasil" class="form-label">Related applications</label>
          <div style="flex: 1;">
            <select class="form-select" id="relationshipYggdrasil" name="relationship_yggdrasil[]" multiple>
              <?php if (!empty($app['related_apps'])): ?>
                <?php foreach ($app['related_apps'] as $relApp): ?>
                  <option value="<?php echo $relApp['id']; ?>" selected>
                    <?php echo htmlspecialchars($relApp['short_description']); ?>
                    <?php if (!empty($relApp['application_service'])): ?>
                      (<?php echo htmlspecialchars($relApp['application_service']); ?>)
                    <?php endif; ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>
        </div>
      </div>
      <!-- Right column -->
      <div class="col-md-6">
        <div class="form-group-horizontal">
          <label for="assignedTo" class="form-label">Assigned to</label>
          <div style="flex: 1;">
            <!-- Bootstrap Dropdown for Assigned To -->
            <div class="dropdown w-100">
              <input type="hidden" id="assignedToValue" name="assigned_to" 
                     value="<?php echo htmlspecialchars($app['assigned_to'] ?? ''); ?>">
              
              <button class="btn btn-outline-light dropdown-toggle w-100 text-start" 
                      type="button" 
                      id="assignedToDropdown" 
                      data-bs-toggle="dropdown" 
                      aria-expanded="false"
                      style="height: 38px; display: flex; align-items: center; justify-content: space-between; background-color: white !important; border: 1px solid #dee2e6 !important; color: #495057 !important;">
                <span id="assignedToDisplay">
                  <?php echo !empty($app['assigned_to']) ? htmlspecialchars($app['assigned_to']) : 'Select user...'; ?>
                </span>
              </button>
              
              <div class="dropdown-menu w-100 p-0" aria-labelledby="assignedToDropdown" style="max-width: 100%;">
                <div class="p-2 border-bottom">
                  <input type="text" 
                         class="form-control form-control-sm" 
                         id="userSearchInput" 
                         placeholder="Search for users..." 
                         autocomplete="off">
                </div>
                <div id="userDropdownResults" class="dropdown-results" style="max-height: 200px; overflow-y: auto;">
                  <div class="p-3 text-muted text-center">
                    <small>Type at least 2 letters to search</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="form-group-horizontal">
          <label for="preOpsPortfolio" class="form-label">Pre-ops portfolio</label>
          <select class="form-select" id="preOpsPortfolio" name="preops_portfolio">
            <option value="">Select portfolio...</option>
            <?php foreach ($preopsPortfolios as $portfolio): ?>
              <option value="<?php echo htmlspecialchars($portfolio['name']); ?>"<?php if(($app['preops_portfolio'] ?? '') === $portfolio['name']) echo ' selected'; ?>>
                <?php echo htmlspecialchars($portfolio['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group-horizontal">
          <label for="applicationPortfolio" class="form-label">Application Portfolio</label>
          <select class="form-select" id="applicationPortfolio" name="application_portfolio">
            <option value="">Select portfolio...</option>
            <?php foreach ($applicationPortfolios as $portfolio): ?>
              <option value="<?php echo htmlspecialchars($portfolio['name']); ?>"<?php if(($app['application_portfolio'] ?? '') === $portfolio['name']) echo ' selected'; ?>>
                <?php echo htmlspecialchars($portfolio['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group-horizontal">
          <label for="deliveryResponsible" class="form-label">Delivery responsible</label>
          <input type="text" class="form-control" id="deliveryResponsible" name="delivery_responsible" placeholder="Delivery responsible" value="<?php echo htmlspecialchars($app['delivery_responsible'] ?? ''); ?>">
        </div>
        <div class="form-group-horizontal">
          <label for="corporatorLink" class="form-label">Link to Corporator</label>
          <div class="input-group">
            <input type="url" class="form-control" id="corporatorLink" name="corporator_link" placeholder="Link to Corporator" value="<?php echo htmlspecialchars($app['corporator_link'] ?? ''); ?>">
            <button type="button" class="btn btn-outline-secondary clear-btn" onclick="event.preventDefault(); clearField('corporatorLink')" title="Clear field">
              <i class="bi bi-x"></i>
            </button>
          </div>
        </div>
        <div class="form-group-horizontal">
          <label for="projectManager" class="form-label">Project manager</label>
          <input type="text" class="form-control" id="projectManager" name="project_manager" placeholder="Project manager" value="<?php echo htmlspecialchars($app['project_manager'] ?? ''); ?>">
        </div>
        <div class="form-group-horizontal">
          <label for="productOwner" class="form-label">Product owner</label>
          <input type="text" class="form-control" id="productOwner" name="product_owner" placeholder="Product owner" value="<?php echo htmlspecialchars($app['product_owner'] ?? ''); ?>">
        </div>
        <div class="form-group-horizontal">
          <label for="dueDate" class="form-label">Due date</label>
          <input type="date" class="form-control" id="dueDate" name="due_date" placeholder="Due date" value="<?php echo htmlspecialchars($app['due_date'] ?? ''); ?>">
        </div>
        <div class="form-group-horizontal">
          <label for="deploymentModel" class="form-label">Deployment model</label>
          <select class="form-select" id="deploymentModel" name="deployment_model">
            <option value="">Select deployment model...</option>
            <?php foreach ($deploymentModels as $model): ?>
              <option value="<?php echo htmlspecialchars($model['name']); ?>"<?php if(($app['deployment_model'] ?? '') === $model['name']) echo ' selected'; ?>>
                <?php echo htmlspecialchars($model['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group-horizontal">
          <label for="integrations" class="form-label">Integrations</label>
          <select class="form-select" id="integrations" name="integrations" onchange="toggleSADocument(this)">
            <?php foreach (["Not defined","Yes","No"] as $opt): ?>
              <option<?php if(($app['integrations'] ?? '')===$opt) echo ' selected'; ?>><?php echo $opt; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group-horizontal" id="sa_document_group" style="display: <?php echo (($app['integrations'] ?? '')==='Yes') ? 'flex' : 'none'; ?>;">
          <label for="saDocument" class="form-label">S.A. Document</label>
          <div class="input-group">
            <input type="url" class="form-control" id="saDocument" name="sa_document" placeholder="S.A. Document" value="<?php echo htmlspecialchars($app['sa_document'] ?? ''); ?>">
            <button type="button" class="btn btn-outline-secondary clear-btn" onclick="event.preventDefault(); clearField('saDocument')" title="Clear field">
              <i class="bi bi-x"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="form-group-horizontal">
      <label for="businessNeed" class="form-label">Business need</label>
      <textarea class="form-control" id="businessNeed" name="business_need" style="height: 100px" placeholder="Business need"><?php echo htmlspecialchars($app['business_need'] ?? ''); ?></textarea>
    </div>
  </form>
  
  <?php if ($id > 0): ?>
    <!-- Work Notes and Activity Tracker Container - Only show when editing existing applications -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="work-activity-container">
          <!-- Work Notes Form -->
          <?php 
          $user_role = $_SESSION['user_role'] ?? 'viewer';
          if ($user_role === 'editor' || $user_role === 'admin'): 
          ?>
            <div class="work-notes-form">
              <h5>Add Work Note</h5>
              <form id="work-notes-form" enctype="multipart/form-data">
                <div class="form-group-horizontal">
                  <label for="work-note-text" class="form-label">Work Notes</label>
                  <textarea 
                    class="form-control" 
                    id="work-note-text" 
                    name="note" 
                    rows="3" 
                    placeholder="Add a comment, update, or note about this application..."
                    required></textarea>
                </div>
                
                <div class="row g-2">
                  <div class="col-md-6">
                    <div class="form-group-horizontal">
                      <label for="work-note-file" class="form-label">Attachment</label>
                      <input type="file" class="form-control" id="work-note-file" name="attachment">
                      <div id="file-info" class="file-info"></div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group-horizontal">
                      <label for="work-note-type" class="form-label">Type of note</label>
                      <select class="form-select" id="work-note-type" name="type">
                        <option value="comment">Comment</option>
                        <option value="change">Change</option>
                        <option value="problem">Problem</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group-horizontal">
                      <label class="form-label" style="visibility: hidden;">Button</label>
                      <button type="submit" class="btn btn-primary w-100">
                        Post
                      </button>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          <?php endif; ?>
          
          <!-- Activity Tracker Section -->
          <?php 
          $application_id = $id; 
          include __DIR__ . '/shared/activity_tracker.php'; 
          ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>




<script>
// Set current app ID for integration diagram
window.currentAppId = <?php echo $id; ?>;

// Simple form change detection for new applications
<?php if ($id === 0): ?>
let formChanged = false;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('applicationForm');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('change', () => {
            formChanged = true;
        });
    });
    
    // Warn before leaving if form has changes
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
    
    // Don't warn when submitting the form
    form.addEventListener('submit', function() {
        formChanged = false;
    });
});
<?php endif; ?>

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
// Header action button functions
function openUserStories() {
    console.log('User Stories button clicked');
    window.location.href = 'user_stories.php?application_id=<?php echo $id; ?>';
}

function openAIInsight() {  
    console.log('AI Insight button clicked');
    window.location.href = 'ai_insights.php?application_id=<?php echo $id; ?>';
}

function openDataMap() {
    const appId = <?php echo $id; ?>;
    if (appId > 0) {
        const datamapUrl = `datamap.php?app_id=${appId}`;
        window.location.href = datamapUrl;
    } else {
        alert('Please save the application first before creating data maps.');
    }
}
</script>
<script src="../assets/js/components/activity-tracker.js"></script>
<script src="../assets/js/components/form-handlers.js"></script>
<script src="../assets/js/components/choices-init.js"></script>
<script src="../assets/js/pages/app-form.js"></script>

<script>
// Application Type Information Display - Enhanced with popup info button
document.addEventListener('DOMContentLoaded', function() {
    // Add a small delay to ensure all other scripts have loaded
    setTimeout(function() {
        console.log('Application Type script loading...');
        
        const applicationTypeSelect = document.getElementById('applicationType');
        const infoBtn = document.getElementById('applicationTypeInfoBtn');

        console.log('Elements found:', {
            select: applicationTypeSelect ? 'Found' : 'NOT FOUND',
            infoBtn: infoBtn ? 'Found' : 'NOT FOUND'
        });

        if (applicationTypeSelect && infoBtn) {
            console.log('All elements found, setting up enhanced event listeners');
            
            let currentPopover = null;
            
            // Function to update info button and popover
            function updateTypeInfo(eventType = 'unknown') {
                console.log(`Application type update triggered by: ${eventType}, value: ${applicationTypeSelect.value}`);
                const selectedOption = applicationTypeSelect.options[applicationTypeSelect.selectedIndex];
                
                if (selectedOption && selectedOption.value) {
                    const complexity = selectedOption.getAttribute('data-complexity');
                    const duration = selectedOption.getAttribute('data-duration');
                    const infrastructure = selectedOption.getAttribute('data-infrastructure');
                    const vendor = selectedOption.getAttribute('data-vendor');
                    
                    console.log('Data attributes:', { complexity, duration, infrastructure, vendor });
                    
                    if (complexity && duration && infrastructure && vendor) {
                        // Show the info button
                        infoBtn.style.display = 'block';
                        
                        // Destroy existing popover if it exists
                        if (currentPopover) {
                            currentPopover.dispose();
                        }
                        
                        // Create popover content
                        const popoverContent = `
                            <div class="application-type-info">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <strong>Complexity:</strong><br>
                                        <span class="text-primary">${complexity}</span>
                                    </div>
                                    <div class="col-6">
                                        <strong>Duration:</strong><br>
                                        <span class="text-primary">${duration} weeks</span>
                                    </div>
                                    <div class="col-6">
                                        <strong>Infrastructure:</strong><br>
                                        <span class="text-primary">${infrastructure}</span>
                                    </div>
                                    <div class="col-6">
                                        <strong>Vendor Support:</strong><br>
                                        <span class="text-primary">${vendor}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Update popover content
                        infoBtn.setAttribute('data-bs-content', popoverContent);
                        
                        // Initialize new popover
                        currentPopover = new bootstrap.Popover(infoBtn, {
                            html: true,
                            trigger: 'click',
                            placement: 'bottom',
                            container: 'body'
                        });
                        
                        console.log(`Info button shown and popover updated via ${eventType}`);
                    } else {
                        console.log('Some data attributes missing');
                        infoBtn.style.display = 'none';
                        if (currentPopover) {
                            currentPopover.dispose();
                            currentPopover = null;
                        }
                    }
                } else {
                    infoBtn.style.display = 'none';
                    if (currentPopover) {
                        currentPopover.dispose();
                        currentPopover = null;
                    }
                    console.log(`Info button hidden via ${eventType} - no selection`);
                }
            }
            
            // Event listeners for dropdown changes
            applicationTypeSelect.addEventListener('change', () => updateTypeInfo('change'));
            applicationTypeSelect.addEventListener('input', () => updateTypeInfo('input'));
            
            // Show info for pre-selected option on page load
            if (applicationTypeSelect.value) {
                console.log('Triggering change for pre-selected value:', applicationTypeSelect.value);
                updateTypeInfo('page-load');
            }
            
            // Close popover when clicking elsewhere
            document.addEventListener('click', function(e) {
                if (currentPopover && !infoBtn.contains(e.target)) {
                    currentPopover.hide();
                }
            });
            
            console.log('Application Type functionality initialized with popup info button');
        } else {
            console.error('Some elements not found for Application Type functionality');
            console.log('applicationTypeSelect:', applicationTypeSelect);
            console.log('infoBtn:', infoBtn);
        }
    }, 500); // 500ms delay to ensure everything is loaded
});
</script>
</body>
</html>
