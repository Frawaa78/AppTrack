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
    'short_description' => '', 'application_service' => '', 'relevant_for' => '', 'phase' => '', 'status' => '',
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
            'short_description', 'application_service', 'relevant_for', 'phase', 'status', 'handover_status',
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
    /* Integration Architecture Button Styling */
    .integration-architecture-btn {
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
    }
    
    .integration-architecture-btn:hover {
        background-color: #F8F9FA;
        border-color: #DEE2E6;
        color: #212529;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .integration-architecture-btn:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
        background-color: #F8F9FA;
        border-color: #86B7FE;
        color: #212529;
    }
    
    .integration-architecture-btn i {
        font-size: 16px;
        min-width: 16px;
        text-align: center;
    }
    
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

    /* Integration Architecture Modal Styles */
    #integrationDiagramModal .modal-dialog {
        max-width: 95vw;
        height: 90vh;
        margin: 2.5vh auto;
    }
    
    #integrationDiagramModal .modal-content {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    #integrationDiagramModal .modal-header {
        flex-shrink: 0;
        border-bottom: 2px solid #e2e8f0;
        padding: 1rem 1.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    #integrationDiagramModal .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    #integrationDiagramModal .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
    }
    
    #integrationDiagramModal .btn-close:hover {
        opacity: 1;
    }
    
    #integrationDiagramModal .modal-body {
        flex: 1;
        padding: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
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
    }

    /* Visual Diagram Editor Styles - COPIED FROM WORKING app_view.php */
    #visual-diagram-editor {
      width: 100%;
      height: 100%;
      position: relative;
      background-image: 
        linear-gradient(to right, #f1f5f9 1px, transparent 1px),
        linear-gradient(to bottom, #f1f5f9 1px, transparent 1px);
      background-size: 20px 20px;
      cursor: crosshair;
      overflow: auto; /* Allow scrolling within editor */
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
    
    .element-process {
      border: 2px solid #6b7280;
      border-radius: 4px;
      background: #e2e8f0;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      font-size: 0.875rem;
      font-weight: 500;
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
    
    .element-start {
      border: 2px solid #059669;
      border-radius: 50%;
      background: #dcfce7;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      font-size: 0.875rem;
      font-weight: 500;
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

    /* Integration Architecture Modal Styles */
    #integrationDiagramModal .modal-dialog {
        width: 95vw;
        max-width: 95vw;
        height: 90vh;
        max-height: 90vh;
        margin: 2.5vh auto;
    }

    #integrationDiagramModal .modal-content {
        height: 100%;
        display: flex;
        flex-direction: column;
        border-radius: 8px;
        overflow: hidden;
    }

    #integrationDiagramModal .modal-body {
        flex: 1;
        padding: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        position: relative;
        box-sizing: border-box;
        min-height: 0;
    }

    #integrationDiagramModal .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-bottom: none;
        padding: 1rem 1.5rem;
        flex-shrink: 0;
    }

    #integrationDiagramModal .modal-title {
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    #integrationDiagramModal .btn-close-white {
        filter: brightness(0) invert(1);
        opacity: 0.8;
    }

    #integrationDiagramModal .btn-close-white:hover {
        opacity: 1;
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

    .editor-container {
        flex: 1;
        position: relative;
        overflow: hidden;
        background: #ffffff;
        min-height: 0;
        box-sizing: border-box;
    }

    #visual-diagram-editor {
        width: 100%;
        height: 100%;
        position: relative;
        overflow: auto;
        background-image: 
            linear-gradient(to right, #f1f5f9 1px, transparent 1px),
            linear-gradient(to bottom, #f1f5f9 1px, transparent 1px);
        background-size: 20px 20px;
        cursor: crosshair;
        box-sizing: border-box;
    }

    .property-panel {
        position: absolute;
        top: 120px;
        right: 10px;
        width: 250px;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
    }

    .property-group {
        margin-bottom: 0.75rem;
    }

    .property-group label {
        font-weight: 500;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
        display: block;
    }

    .zoom-level {
        font-size: 0.875rem;
        font-weight: 500;
        padding: 0 0.5rem;
        color: #6c757d;
    }

    .diagram-element {
        position: absolute;
        cursor: move;
        border-radius: 4px;
        transition: border-color 0.2s ease;
    }

    .diagram-element.selected {
        border-color: #007bff !important;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
    }

    .diagram-element.hover {
        border-color: #6c757d !important;
    }
  </style>
</head>
<body class="bg-light">
<!-- Topbar -->
<?php include __DIR__ . '/shared/topbar.php'; ?>

<div class="container">
  <form method="post" autocomplete="off" id="applicationForm">
    <div class="header-with-buttons">
      <div>
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
                onclick="openTasks()" 
                title="View and manage tasks">
          <i class="fa-light fa-list-check" data-fallback="fa-solid fa-list-check,fas fa-tasks,bi bi-check2-square"></i>
          Tasks
        </button>
        <button type="button" 
                class="integration-architecture-btn" 
                onclick="openIntegrationDiagram()" 
                title="Open Integration Architecture Editor - Create visual diagrams">
          <i class="fa-light fa-sitemap" data-fallback="fa-solid fa-sitemap,fas fa-project-diagram,fas fa-network-wired,bi bi-diagram-3"></i>
          Integration Architecture
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
          <label for="shortDescription" class="form-label">Short description *</label>
          <div class="input-group">
            <input type="text" class="form-control" id="shortDescription" name="short_description" placeholder="Short description" value="<?php echo htmlspecialchars($app['short_description'] ?? ''); ?>" required>
            <button type="button" class="btn btn-outline-secondary info-btn" tabindex="0"
              data-bs-toggle="popover"
              data-bs-placement="bottom"
              title="Short description"
              data-bs-content="Provide a short and meaningful description of the application.">
              <i class="bi bi-info-circle"></i>
            </button>
          </div>
        </div>
        <div class="form-group-horizontal">
          <label for="applicationService" class="form-label">Application service</label>
          <input type="text" class="form-control" id="applicationService" name="application_service" placeholder="Application service" value="<?php echo htmlspecialchars($app['application_service'] ?? ''); ?>">
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

<!-- Integration Architecture Modal -->
<div class="modal fade" id="integrationDiagramModal" tabindex="-1" aria-labelledby="integrationDiagramModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="integrationDiagramModalLabel">
          <i class="fa-light fa-sitemap"></i> Integration Architecture Editor - <?php echo htmlspecialchars($app['short_description']); ?>
        </h5>
        <div class="modal-header-controls">
          <!-- Template Dropdown -->
          <?php if (in_array($_SESSION['user_role'] ?? 'viewer', ['admin', 'editor'])): ?>
          <div class="dropdown me-3">
            <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" id="templateDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-collection"></i> Templates
            </button>
            <ul class="dropdown-menu" aria-labelledby="templateDropdown">
              <li><a class="dropdown-item" href="#" onclick="loadVisualTemplate('basic')">
                <i class="bi bi-diagram-2"></i> Basic Integration
              </a></li>
              <li><a class="dropdown-item" href="#" onclick="loadVisualTemplate('pipeline')">
                <i class="bi bi-arrow-right-circle"></i> Data Pipeline
              </a></li>
              <li><a class="dropdown-item" href="#" onclick="loadVisualTemplate('api')">
                <i class="bi bi-cloud"></i> API Integration
              </a></li>
              <li><a class="dropdown-item" href="#" onclick="loadVisualTemplate('microservices')">
                <i class="bi bi-grid-3x3"></i> Microservices
              </a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="#" onclick="clearCanvas()">
                <i class="bi bi-trash"></i> Clear Canvas
              </a></li>
            </ul>
          </div>
          
          <!-- Tools Dropdown -->
          <div class="dropdown me-3">
            <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" id="toolsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-tools"></i> Tools
            </button>
            <ul class="dropdown-menu" aria-labelledby="toolsDropdown">
              <li><a class="dropdown-item" href="#" onclick="setTool('select')">
                <i class="bi bi-cursor"></i> Select Tool
              </a></li>
              <li><a class="dropdown-item" href="#" onclick="setTool('connect')">
                <i class="bi bi-arrow-left-right"></i> Connect Tool
              </a></li>
              <li><a class="dropdown-item" href="#" onclick="setTool('text')">
                <i class="bi bi-textarea-t"></i> Text Note Tool
              </a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="#" onclick="autoLayout()">
                <i class="bi bi-distribute-vertical"></i> Auto Layout
              </a></li>
            </ul>
          </div>
          
          <!-- Element Types Dropdown -->
          <div class="dropdown me-3">
            <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" id="elementsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-shapes"></i> Add Element
            </button>
            <ul class="dropdown-menu" aria-labelledby="elementsDropdown">
              <li><a class="dropdown-item" href="#" onclick="addElement('process')">
                <i class="bi bi-square"></i> Process Box
              </a></li>
              <li><a class="dropdown-item" href="#" onclick="addElement('decision')">
                <i class="bi bi-diamond"></i> Decision Diamond
              </a></li>
              <li><a class="dropdown-item" href="#" onclick="addElement('start')">
                <i class="bi bi-circle"></i> Start/End Circle
              </a></li>
              <li><a class="dropdown-item" href="#" onclick="addElement('database')">
                <i class="bi bi-server"></i> Database
              </a></li>
              <li><a class="dropdown-item" href="#" onclick="addElement('api')">
                <i class="bi bi-cloud"></i> API/Service
              </a></li>
              <li><a class="dropdown-item" href="#" onclick="addElement('user')">
                <i class="bi bi-person"></i> User/Actor
              </a></li>
            </ul>
          </div>
          <?php endif; ?>
          
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>
      <div class="modal-body p-0">
        <!-- Editor Toolbar -->
        <div class="editor-toolbar">
          <div class="toolbar-section">
            <button class="btn btn-sm btn-outline-secondary" id="selectTool" onclick="setActiveTool('select')" title="Select Tool">
              <i class="bi bi-cursor"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary" id="connectTool" onclick="setActiveTool('connect')" title="Connect Tool">
              <i class="bi bi-arrow-left-right"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary" id="textTool" onclick="setActiveTool('text')" title="Add Text Note">
              <i class="bi bi-textarea-t"></i>
            </button>
          </div>
          
          <div class="toolbar-section">
            <button class="btn btn-sm btn-outline-secondary" onclick="zoomIn()" title="Zoom In">
              <i class="bi bi-zoom-in"></i>
            </button>
            <span class="zoom-level" id="zoomLevel">100%</span>
            <button class="btn btn-sm btn-outline-secondary" onclick="zoomOut()" title="Zoom Out">
              <i class="bi bi-zoom-out"></i>
            </button>
          </div>
          
          <div class="toolbar-section">
            <button class="btn btn-sm btn-success" onclick="saveIntegrationData(event)" title="Save Diagram">
              <i class="bi bi-floppy"></i> Save
            </button>
          </div>
        </div>
        
        <!-- Main Editor Canvas -->
        <div class="editor-container">
          <div id="visual-diagram-editor" class="visual-editor-canvas"></div>
        </div>
        
        <!-- Property Panel (appears when element is selected) -->
        <div id="property-panel" class="property-panel" style="display: none;">
          <div class="property-panel-header">
            <h6 style="margin: 0;"><i class="bi bi-gear"></i> Properties</h6>
          </div>
          <div class="property-panel-content">
            <div class="property-group">
              <label>Text:</label>
              <input type="text" id="elementText" class="form-control form-control-sm" onchange="updateSelectedElement()">
            </div>
            <div class="property-group">
              <label>Width:</label>
              <input type="range" id="elementWidth" min="80" max="300" step="10" class="form-range" onchange="updateSelectedElement()">
            </div>
            <div class="property-group">
              <label>Height:</label>
              <input type="range" id="elementHeight" min="40" max="200" step="10" class="form-range" onchange="updateSelectedElement()">
            </div>
            <div class="property-group">
              <label>Background:</label>
              <select id="elementColor" class="form-select form-select-sm" onchange="updateSelectedElement()">
                <option value="#e2e8f0">Light Gray</option>
                <option value="#dbeafe">Light Blue</option>
                <option value="#dcfce7">Light Green</option>
                <option value="#fef3c7">Light Yellow</option>
                <option value="#fecaca">Light Red</option>
                <option value="#e9d5ff">Light Purple</option>
              </select>
            </div>
            <div class="property-group" id="connectionDirectionGroup" style="display: none;">
              <label>Arrow Direction:</label>
              <select id="connectionDirection" class="form-select form-select-sm" onchange="updateSelectedElement()">
                <option value="to">➡️ One Way (To)</option>
              <option value="from">⬅️ One Way (From)</option>
              <option value="both">↔️ Both Ways</option>
            </select>
          </div>
          <div class="property-group">
            <button class="btn btn-sm btn-danger w-100" onclick="deleteSelectedElement()">
              <i class="bi bi-trash"></i> Delete Element
            </button>
          </div>
          </div>
        </div>
      </div>
    </div>
  </div>
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

// Integration Diagram functionality
let mermaidLoaded = false;
let visualEditor = null;

// Open Integration Diagram Modal
window.openIntegrationDiagram = function() {
    console.log('🚀 openIntegrationDiagram called!');
    console.log('Opening Integration Architecture Modal...');
    
    // Debug: Check if visual-diagram-editor.js is loaded
    console.log('🔍 Checking if VisualDiagramEditor is available:', typeof VisualDiagramEditor);
    if (typeof VisualDiagramEditor === 'undefined') {
        console.error('❌ VisualDiagramEditor class not found! The visual-diagram-editor.js file may not have loaded properly.');
        alert('Error: Visual editor not loaded. Please check the browser console and refresh the page.');
        return;
    }
    
    // Create and show the modal
    const modalElement = document.getElementById('integrationDiagramModal');
    if (!modalElement) {
        console.error('Integration modal element not found!');
        alert('Error: Could not find Integration Architecture modal. Please refresh the page.');
        return;
    }
    
    const modal = new bootstrap.Modal(modalElement, {
        backdrop: 'static',
        keyboard: false,
        focus: true
    });
    
    modal.show();
    
    // Prevent modal from closing when clicking inside editor
    modalElement.addEventListener('click', function(e) {
        // Only allow closing via the X button
        if (e.target.matches('.btn-close, .btn-close *')) {
            return; // Allow closing
        }
        e.stopPropagation();
    });
    
    // Setup event listeners for modal events
    modalElement.addEventListener('shown.bs.modal', function() {
        console.log('Integration modal fully opened');
        
        // Check if VisualDiagramEditor class is available
        if (typeof VisualDiagramEditor === 'undefined') {
            console.error('VisualDiagramEditor class not found! Make sure visual-diagram-editor.js is loaded.');
            alert('Error: Visual editor class not found. Please refresh the page.');
            return;
        }
        
        // Initialize the visual editor
        try {
            if (!visualEditor) {
                console.log('🚀 Initializing VisualDiagramEditor...');
                visualEditor = new VisualDiagramEditor('visual-diagram-editor');
                window.visualEditor = visualEditor; // Make it globally accessible
                window.currentEditor = visualEditor; // Alternative reference
                
                // Initialize the editor
                console.log('🎯 Calling editor.init()...');
                visualEditor.init();
                
                console.log('✅ VisualDiagramEditor initialized successfully:', visualEditor);
            } else {
                console.log('🔄 Visual editor already exists, performing cleanup before reload...');
                window.visualEditor = visualEditor; // Ensure global reference
                window.currentEditor = visualEditor; // Ensure alternative reference
                
                // Clear everything when modal reopens to prevent duplicates
                console.log('🧹 Clearing editor to prevent duplicates');
                if (typeof visualEditor.clearAll === 'function') {
                    visualEditor.clearAll();
                }
                
                // Reset all internal state
                visualEditor.nextNodeId = 1;
                visualEditor.nextNoteId = 1;
                visualEditor.selectedElement = null;
                
                // Reinitialize canvas
                console.log('🎯 Reinitializing canvas...');
                if (typeof visualEditor.createCanvas === 'function') {
                    visualEditor.createCanvas();
                }
                if (typeof visualEditor.setupEventListeners === 'function') {
                    visualEditor.setupEventListeners();
                }
                if (typeof visualEditor.drawGrid === 'function') {
                    visualEditor.drawGrid();
                }
                if (typeof visualEditor.setActiveTool === 'function') {
                    visualEditor.setActiveTool('select');
                }
            }
            
            // Load existing diagram data
            initializeIntegrationDiagram();
            
            // Load existing diagram data completed
            
        } catch (error) {
            console.error('Error initializing visual editor:', error);
            alert('Error initializing visual editor: ' + error.message);
        }
    });
    
    modalElement.addEventListener('hidden.bs.modal', function() {
        console.log('Integration modal closed');
    });

    // Make property panel draggable
    initializePropertyPanelDragging();
}

// Make property panel draggable
function initializePropertyPanelDragging() {
    const propertyPanel = document.getElementById('property-panel');
    const header = propertyPanel.querySelector('.property-panel-header');
    
    if (!header) return;
    
    let isDragging = false;
    let offsetX, offsetY;
    
    header.addEventListener('mousedown', function(e) {
        isDragging = true;
        
        // Get the property panel's current computed style position
        const computedStyle = window.getComputedStyle(propertyPanel);
        const currentLeft = parseInt(computedStyle.left) || 0;
        const currentTop = parseInt(computedStyle.top) || 0;
        
        // Calculate offset from mouse to panel's current position
        offsetX = e.clientX - currentLeft;
        offsetY = e.clientY - currentTop;
        
        // Add visual feedback
        propertyPanel.style.cursor = 'grabbing';
        header.style.cursor = 'grabbing';
        
        // Prevent text selection
        e.preventDefault();
    });
    
    document.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        
        // Calculate new position directly from mouse position
        let newLeft = e.clientX - offsetX;
        let newTop = e.clientY - offsetY;
        
        // Get the modal content container for boundary checking
        const modalContent = document.querySelector('.modal-content');
        const modalRect = modalContent ? modalContent.getBoundingClientRect() : { left: 0, top: 0, width: window.innerWidth, height: window.innerHeight };
        
        // Convert to relative coordinates within the modal
        newLeft = newLeft - modalRect.left;
        newTop = newTop - modalRect.top;
        
        // Get panel dimensions
        const panelWidth = propertyPanel.offsetWidth;
        const panelHeight = propertyPanel.offsetHeight;
        
        // Keep panel within modal bounds with some padding
        const padding = 10;
        newLeft = Math.max(padding, Math.min(newLeft, modalRect.width - panelWidth - padding));
        newTop = Math.max(padding, Math.min(newTop, modalRect.height - panelHeight - padding));
        
        // Apply new position
        propertyPanel.style.left = newLeft + 'px';
        propertyPanel.style.top = newTop + 'px';
        propertyPanel.style.right = 'auto'; // Override right positioning
    });
    
    document.addEventListener('mouseup', function() {
        if (isDragging) {
            isDragging = false;
            propertyPanel.style.cursor = '';
            header.style.cursor = 'move';
        }
    });
}

// Initialize the integration diagram
async function initializeIntegrationDiagram() {
    console.log('🚀 INIT DIAGRAM: Starting initializeIntegrationDiagram');
    console.log('🚀 INIT DIAGRAM: window.currentAppId =', window.currentAppId);
    console.log('🚀 INIT DIAGRAM: visualEditor exists =', !!visualEditor);
    
    if (!window.currentAppId || window.currentAppId === 0) {
        console.log('📝 No app ID available (new application), loading default template');
        loadVisualTemplate('basic');
        return;
    }
    
    try {
        // Check if VisualDiagramEditor class exists
        if (typeof VisualDiagramEditor === 'undefined') {
            console.error('VisualDiagramEditor class not found! Make sure visual-diagram-editor.js is loaded.');
            alert('Error: Visual editor not loaded. Please refresh the page.');
            return;
        }
        
        console.log('🌐 INIT DIAGRAM: Fetching diagram data from server...');
        const response = await fetch(`api/get_integration_diagram.php?id=${window.currentAppId}`);
        const data = await response.json();
        
        console.log('📡 INIT DIAGRAM: Server response:', data);
        
        if (data.success) {
            const diagramCode = data.diagram_code && data.diagram_code.trim() ? data.diagram_code : null;
            console.log('📊 INIT DIAGRAM: Diagram code received:', {
                hasCode: !!diagramCode,
                codeLength: diagramCode ? diagramCode.length : 0,
                codePreview: diagramCode ? diagramCode.substring(0, 200) + '...' : 'NULL'
            });
            
            if (diagramCode) {
                // Try to load existing Mermaid code into visual editor
                if (visualEditor && typeof visualEditor.loadFromMermaidCode === 'function') {
                    console.log('📥 INIT DIAGRAM: Loading existing diagram into visual editor');
                    
                    // Force a complete clear before loading to prevent duplicates
                    console.log('🧹 Clearing before loading data...');
                    if (typeof visualEditor.clearAll === 'function') {
                        visualEditor.clearAll();
                    }
                    
                    visualEditor.loadFromMermaidCode(diagramCode);
                    
                    console.log('🔄 INIT DIAGRAM: Editor state AFTER load:');
                    console.log('  - Nodes:', visualEditor.nodes.size);
                    console.log('  - Connections:', visualEditor.connections.size);
                    console.log('  - Text Notes:', visualEditor.textNotes.size);
                    
                    // Ensure fingerprint is created after loading
                    setTimeout(() => {
                        if (typeof visualEditor.createPositionFingerprint === 'function') {
                            console.log('🔐 Creating position fingerprint after load');
                            visualEditor.createPositionFingerprint();
                        }
                        
                        // Force recreation of arrows after data load
                        if (typeof visualEditor.forceRecreateArrows === 'function') {
                            console.log('🔧 Force recreating arrows after data load');
                            visualEditor.forceRecreateArrows();
                        }
                    }, 1500); // Wait for load to complete
                } else {
                    console.warn('Visual editor not ready or loadFromMermaidCode method missing');
                }
            } else {
                // Load a default template
                console.log('📝 INIT DIAGRAM: No existing diagram, loading default template');
                loadVisualTemplate('basic');
            }
        } else {
            console.error('❌ INIT DIAGRAM: Error loading diagram:', data.error);
            loadVisualTemplate('basic');
        }
    } catch (error) {
        console.error('❌ INIT DIAGRAM: Exception loading diagram:', error);
        loadVisualTemplate('basic');
    }
}

// Template loading functions
function loadVisualTemplate(templateName) {
    if (!visualEditor) {
        console.error('Visual editor not initialized');
        return;
    }
    
    console.log('🎨 Loading template:', templateName);
    
    // Clear existing content
    if (typeof visualEditor.clearAll === 'function') {
        visualEditor.clearAll();
    }
    
    // Initialize the editor first if not done
    if (typeof visualEditor.init === 'function') {
        visualEditor.init();
    }
    
    switch (templateName) {
        case 'basic':
            // Create a basic integration template
            console.log('🏗️ Creating basic template elements');
            visualEditor.addElement('process', 150, 100, 'Application');
            visualEditor.addElement('database', 400, 100, 'Database');
            visualEditor.addElement('process', 150, 250, 'External API');
            
            // Add connections
            setTimeout(() => {
                const nodes = Array.from(visualEditor.nodes.values());
                console.log('🔗 Adding connections, nodes found:', nodes.length);
                if (nodes.length >= 3 && typeof visualEditor.addConnection === 'function') {
                    visualEditor.addConnection(nodes[0].id, nodes[1].id);
                    visualEditor.addConnection(nodes[0].id, nodes[2].id);
                }
            }, 200);
            break;
            
        case 'pipeline':
            // Create a data pipeline template
            console.log('🏗️ Creating pipeline template elements');
            visualEditor.addElement('start', 100, 150, 'Data Source');
            visualEditor.addElement('process', 250, 150, 'Transform');
            visualEditor.addElement('process', 400, 150, 'Validate');
            visualEditor.addElement('database', 550, 150, 'Store');
            
            setTimeout(() => {
                const nodes = Array.from(visualEditor.nodes.values());
                console.log('🔗 Adding pipeline connections, nodes found:', nodes.length);
                if (typeof visualEditor.addConnection === 'function') {
                    for (let i = 0; i < nodes.length - 1; i++) {
                        visualEditor.addConnection(nodes[i].id, nodes[i + 1].id);
                    }
                }
            }, 200);
            break;
            
        case 'api':
            // Create an API integration template
            console.log('🏗️ Creating API template elements');
            visualEditor.addElement('process', 100, 100, 'Client');
            visualEditor.addElement('process', 250, 100, 'API Gateway');
            visualEditor.addElement('process', 400, 50, 'Service A');
            visualEditor.addElement('process', 400, 150, 'Service B');
            visualEditor.addElement('database', 550, 100, 'Database');
            
            setTimeout(() => {
                const nodes = Array.from(visualEditor.nodes.values());
                console.log('🔗 Adding API connections, nodes found:', nodes.length);
                if (nodes.length >= 5 && typeof visualEditor.addConnection === 'function') {
                    visualEditor.addConnection(nodes[0].id, nodes[1].id);
                    visualEditor.addConnection(nodes[1].id, nodes[2].id);
                    visualEditor.addConnection(nodes[1].id, nodes[3].id);
                    visualEditor.addConnection(nodes[2].id, nodes[4].id);
                    visualEditor.addConnection(nodes[3].id, nodes[4].id);
                }
            }, 200);
            break;
            
        case 'microservices':
            // Create a microservices template
            console.log('🏗️ Creating microservices template elements');
            visualEditor.addElement('process', 100, 150, 'User');
            visualEditor.addElement('process', 250, 150, 'Load Balancer');
            visualEditor.addElement('process', 400, 80, 'Service 1');
            visualEditor.addElement('process', 400, 150, 'Service 2');
            visualEditor.addElement('process', 400, 220, 'Service 3');
            visualEditor.addElement('database', 550, 80, 'DB1');
            visualEditor.addElement('database', 550, 220, 'DB2');
            
            setTimeout(() => {
                const nodes = Array.from(visualEditor.nodes.values());
                console.log('🔗 Adding microservices connections, nodes found:', nodes.length);
                if (nodes.length >= 7 && typeof visualEditor.addConnection === 'function') {
                    visualEditor.addConnection(nodes[0].id, nodes[1].id);
                    visualEditor.addConnection(nodes[1].id, nodes[2].id);
                    visualEditor.addConnection(nodes[1].id, nodes[3].id);
                    visualEditor.addConnection(nodes[1].id, nodes[4].id);
                    visualEditor.addConnection(nodes[2].id, nodes[5].id);
                    visualEditor.addConnection(nodes[4].id, nodes[6].id);
                }
            }, 200);
            break;
    }
    
    console.log('✅ Template loaded successfully');
}

// Clear canvas
function clearCanvas() {
    if (visualEditor) {
        visualEditor.clearAll();
    }
}

// Tool functions
function setTool(tool) {
    setActiveTool(tool);
}

function setActiveTool(tool) {
    if (!visualEditor) return;
    
    console.log('🔧 Setting tool to:', tool);
    
    // Update button states
    document.querySelectorAll('.toolbar-section button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    const toolButton = document.getElementById(tool + 'Tool');
    if (toolButton) {
        toolButton.classList.add('active');
    }
    
    // Set tool in editor
    if (typeof visualEditor.setActiveTool === 'function') {
        visualEditor.setActiveTool(tool);
        console.log('✅ Tool set successfully to:', tool);
    } else {
        console.warn('⚠️ setActiveTool method not found on visualEditor');
    }
}

// Add element function
function addElement(type) {
    if (!visualEditor) return;
    
    console.log('🎯 addElement called with type:', type);
    
    // Add element at center of visible area
    const container = document.getElementById('visual-diagram-editor');
    const rect = container.getBoundingClientRect();
    const x = container.scrollLeft + rect.width / 2 - 50;
    const y = container.scrollTop + rect.height / 2 - 30;
    
    let text = type.charAt(0).toUpperCase() + type.slice(1);
    
    switch (type) {
        case 'database':
            text = 'Database';
            break;
        case 'decision':
            text = 'Decision';
            break;
        case 'start':
            text = 'Start';
            break;
        case 'api':
            text = 'API';
            break;
        case 'user':
            text = 'User';
            break;
        case 'process':
            text = 'Process';
            break;
    }
    
    console.log('🏗️ Adding element:', { type, x, y, text });
    
    // Use the correct method name: addElement (not addNode)
    if (typeof visualEditor.addElement === 'function') {
        const element = visualEditor.addElement(type, x, y, text);
        console.log('✅ Element added successfully:', element);
        return element;
    } else {
        console.error('❌ addElement method not found on visualEditor');
        console.log('Available methods:', Object.getOwnPropertyNames(Object.getPrototypeOf(visualEditor)));
    }
}

// Zoom functions
function zoomIn() {
    if (visualEditor && typeof visualEditor.zoomIn === 'function') {
        visualEditor.zoomIn();
        updateZoomLevel();
    }
}

function zoomOut() {
    if (visualEditor && typeof visualEditor.zoomOut === 'function') {
        visualEditor.zoomOut();
        updateZoomLevel();
    }
}

function updateZoomLevel() {
    const zoomElement = document.getElementById('zoomLevel');
    if (zoomElement && visualEditor && visualEditor.zoomLevel) {
        zoomElement.textContent = Math.round(visualEditor.zoomLevel * 100) + '%';
    }
}

// Property panel functions
function updateSelectedElement() {
    if (!visualEditor || !visualEditor.selectedElement) return;
    
    const text = document.getElementById('elementText').value;
    const width = document.getElementById('elementWidth').value;
    const height = document.getElementById('elementHeight').value;
    const color = document.getElementById('elementColor').value;
    const direction = document.getElementById('connectionDirection').value;
    
    if (typeof visualEditor.updateSelectedElement === 'function') {
        visualEditor.updateSelectedElement({
            text: text,
            width: parseInt(width),
            height: parseInt(height),
            backgroundColor: color,
            direction: direction
        });
    }
}

function deleteSelectedElement() {
    if (visualEditor && visualEditor.selectedElement) {
        if (typeof visualEditor.deleteElement === 'function') {
            visualEditor.deleteElement(visualEditor.selectedElement);
        }
    }
}

// Auto layout function
function autoLayout() {
    if (visualEditor && typeof visualEditor.autoLayout === 'function') {
        visualEditor.autoLayout();
    }
}

// Save integration data
async function saveIntegrationData(event) {
    // Prevent any default behavior that might close the modal
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    if (!visualEditor) {
        alert('No diagram to save');
        return;
    }
    
    if (!window.currentAppId || window.currentAppId === 0) {
        alert('Please save the application first before saving the integration diagram.');
        return;
    }
    
    // Use the saveToMermaidCode() method that creates position fingerprint
    const diagramCode = visualEditor.saveToMermaidCode();
    console.log('💾 Saving diagram with position fingerprint:', diagramCode);
    console.log('Number of nodes:', visualEditor.nodes.size);
    console.log('Number of connections:', visualEditor.connections.size);
    console.log('Number of text notes:', visualEditor.textNotes.size);
    
    // Verify fingerprint was created
    if (visualEditor.positionFingerprint) {
        console.log('✅ Position fingerprint created:', visualEditor.positionFingerprint);
    } else {
        console.warn('⚠️ No position fingerprint created during save!');
    }
    
    try {
        console.log('🌐 Sending save request to server...');
        console.log('📦 Request payload:', {
            application_id: window.currentAppId,
            diagram_code: diagramCode,
            notes: ''
        });
        
        const response = await fetch('api/save_integration_diagram.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                application_id: window.currentAppId,
                diagram_code: diagramCode,
                notes: '' // Notes are now embedded as text elements in the diagram
            })
        });
        
        const result = await response.json();
        console.log('💾 Save response:', result);
        
        if (result.success) {
            // Show success message
            const saveBtn = event.target.closest('button');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="bi bi-check"></i> Saved!';
            saveBtn.classList.remove('btn-success');
            saveBtn.classList.add('btn-outline-success');
            
            setTimeout(() => {
                saveBtn.innerHTML = originalText;
                saveBtn.classList.remove('btn-outline-success');
                saveBtn.classList.add('btn-success');
            }, 2000);
        } else {
            console.error('❌ Save failed:', result.error);
            alert('Failed to save integration diagram: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('❌ Error saving integration diagram:', error);
        alert('Error saving integration diagram: ' + error.message);
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    console.log('🔍 Loading visual-diagram-editor.js...');
</script>
<script src="../assets/js/components/visual-diagram-editor.js?v=<?php echo time() + 5; ?>"></script>
<script>
    console.log('🔍 After loading visual-diagram-editor.js, VisualDiagramEditor available:', typeof VisualDiagramEditor);
    
    // FontAwesome icon fallback system
    document.addEventListener('DOMContentLoaded', function() {
        // Function to test and apply fallback icons
        function setupIconFallback(iconElement) {
            if (!iconElement) return;
            
            const fallbackIcons = iconElement.dataset.fallback ? iconElement.dataset.fallback.split(',') : [];
            
            // Test if current icon is working
            setTimeout(() => {
                const styles = window.getComputedStyle(iconElement, ':before');
                const content = styles.content;
                
                // If no content is generated, try fallback icons
                if (!content || content === 'none' || content === '""') {
                    console.log('FontAwesome icon not loading, trying fallbacks for:', iconElement.className);
                    
                    // Try each fallback icon
                    for (let i = 0; i < fallbackIcons.length; i++) {
                        const iconClass = fallbackIcons[i].trim();
                        iconElement.className = iconClass;
                        
                        // Give a moment for the icon to load and check again
                        setTimeout(() => {
                            const newStyles = window.getComputedStyle(iconElement, ':before');
                            const newContent = newStyles.content;
                            
                            if (newContent && newContent !== 'none' && newContent !== '""') {
                                console.log('Working icon found:', iconClass);
                                return;
                            }
                        }, 50);
                    }
                }
            }, 200);
        }
        
        // Apply fallback to all header action button icons
        document.querySelectorAll('.header-action-btn i, .integration-architecture-btn i').forEach(setupIconFallback);
    });
</script>
<script>
// Header action button functions
function openUserStories() {
    console.log('User Stories button clicked');
    // TODO: Implement User Stories functionality
    alert('User Stories functionality will be implemented soon.');
}

function openAIInsight() {
    console.log('AI Insight button clicked');
    // TODO: Implement AI Insight functionality
    alert('AI Insight functionality will be implemented soon.');
}

function openTasks() {
    console.log('Tasks button clicked');
    // TODO: Implement Tasks functionality
    alert('Tasks functionality will be implemented soon.');
}
</script>
<script src="../assets/js/components/activity-tracker.js"></script>
<script src="../assets/js/components/form-handlers.js"></script>
<script src="../assets/js/components/choices-init.js"></script>
<script src="../assets/js/pages/app-form.js"></script>
</body>
</html>