<?php
session_start();
require_once __DIR__ . '/../src/db/db.php';

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
            $data[$f] = trim($_POST[$f] ?? '');
        }
        // relationship_yggdrasil as comma-separated application IDs
        $relationship_ids = isset($_POST['relationship_yggdrasil']) ? (array)$_POST['relationship_yggdrasil'] : [];
        // Validate that all IDs are numeric
        $relationship_ids = array_filter($relationship_ids, 'is_numeric');
        $data['relationship_yggdrasil'] = implode(',', $relationship_ids);
        
        $db = Database::getInstance()->getConnection();
        
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
        } else {
            // Insert new application
            $cols = implode(',', array_merge($fields, ['relationship_yggdrasil']));
            $vals = ':' . implode(',:', array_merge($fields, ['relationship_yggdrasil']));
            $stmt = $db->prepare("INSERT INTO applications ($cols) VALUES ($vals)");
            $stmt->execute($data);
            $currentAppId = $db->lastInsertId();
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
  <title>Application Registration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    .profile-img { width: 36px; height: 36px; object-fit: cover; border-radius: 50%; }
    .navbar-brand { font-weight: bold; letter-spacing: 1px; }
    .search-bar { min-width: 350px; max-width: 600px; width: 100%; }
    @media (max-width: 768px) { .search-bar { min-width: 150px; } }
    body { font-size: 0.9rem; }
    
    /* Form styling with consistent borders and no blue glow */
    .form-control, .form-select, textarea.form-control {
      border-color: #dee2e6 !important;
      background-color: white !important;
    }
    
    .form-control:focus, .form-select:focus, textarea.form-control:focus,
    .form-control:active, .form-select:active, textarea.form-control:active {
      border-color: #dee2e6 !important;
      box-shadow: none !important;
      outline: none !important;
      background-color: white !important;
    }
    
    /* Form labels positioned to the right */
    .form-label {
      font-weight: 400;
      color: #6c757d;
      margin-bottom: 0;
      display: inline-block;
      width: 160px;
      text-align: right;
      padding-right: 10px;
      vertical-align: top;
      padding-top: 0.375rem;
    }
    
    /* Horizontal form layout */
    .form-group-horizontal {
      display: flex;
      align-items: flex-start;
      margin-bottom: 1rem;
    }
    
    .form-group-horizontal .form-control,
    .form-group-horizontal .form-select,
    .form-group-horizontal .input-group {
      flex: 1;
    }
    
    .form-group-horizontal textarea.form-control {
      resize: vertical;
    }
    
    /* Button groups need special handling */
    .form-group-horizontal .btn-group {
      flex: 1;
    }
    
    /* Button group text styling */
    .btn-group .btn {
      font-size: 0.77rem;
      height: 38px;
      line-height: 1.5;
    }
    
    /* Range input special handling */
    .form-group-horizontal .form-range {
      flex: 1;
      margin-top: 0.375rem;
      position: relative;
    }
    
    /* Range input label styling */
    .form-group-horizontal.position-relative .form-label {
      font-size: inherit;
      padding-top: 0.375rem;
      margin-top: 0;
    }
    
    /* Range slider container with markers */
    .range-container {
      position: relative;
      margin-bottom: 20px;
    }
    
    /* Range markers */
    .range-markers {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .range-marker {
      width: 2px;
      height: 12px;
      background-color: #dee2e6;
      border-radius: 1px;
      opacity: 1;
    }
    
    .range-marker.active {
      opacity: 0;
    }
    
    /* Tooltip styling for handover status */
    .tooltip-follow {
      font-size: 0.69rem;
      position: absolute;
      top: -14px;
      background: #55595c;
      color: white;
      padding: 2px 6px;
      border-radius: 3px;
      white-space: nowrap;
      pointer-events: none;
      z-index: 1000;
      transform: translateX(-50%);
    }
    
    /* Input group styling for info buttons */
    .input-group .btn.info-btn { 
      border-color: #dee2e6 !important;
      background-color: #f8f9fa;
      color: #6c757d;
    }
    .input-group .form-control { 
      border-right: 0; 
      border-color: #dee2e6 !important;
    }
    .input-group .btn { 
      border-left: 0; 
      border-color: #dee2e6 !important;
    }
    
    /* Clear button styling for URL fields */
    .btn.clear-btn {
      border-color: #dee2e6 !important;
      background-color: #f8f9fa;
      color: #6c757d;
      padding: 0.375rem 0.5rem;
      font-size: 0.8rem;
    }
    .btn.clear-btn:hover {
      background-color: #e9ecef;
      color: #495057;
    }
    .input-group .btn.clear-btn {
      border-top-left-radius: 0;
      border-bottom-left-radius: 0;
      border-left: 0;
    }
    
    .tooltip-follow { position: absolute; top: -14px; transform: translateX(-50%); background: #55595c; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.69rem; white-space: nowrap; display: none; pointer-events: none; z-index: 1000; }
    .input-group .btn.info-btn { border-top-left-radius: 0; border-bottom-left-radius: 0; }
    .choices__inner { min-height: calc(3.5rem + 2px); padding-top: 1rem; }
    .form-range {
      width: 100%;
      background-color: transparent;
      margin-bottom: 0.5rem;
    }
    
    /* Enhanced styling for Choices.js dropdown */
    .choices__list--dropdown {
      border: 1px solid #dee2e6 !important;
      border-radius: 0.375rem !important;
      box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
      margin-top: 2px !important;
      max-height: 244px !important; /* Exactly 4 items: 4 × 60px + 4px for borders */
      overflow-y: scroll !important;
      overflow-x: hidden !important;
      background: white !important;
      z-index: 1050 !important;
      position: absolute !important;
      width: 100% !important;
      scrollbar-width: none !important; /* Firefox */
      -ms-overflow-style: none !important; /* IE and Edge */
    }
    
    /* Hide all scrollbars completely and use custom one */
    .choices__list--dropdown::-webkit-scrollbar {
      display: none !important;
    }
    
    /* Add a custom scrollbar indicator on hover */
    .choices__list--dropdown:hover {
      scrollbar-width: thin !important;
      scrollbar-color: #c1c1c1 transparent !important;
    }
    
    .choices__list--dropdown:hover::-webkit-scrollbar {
      display: block !important;
      width: 4px !important;
    }
    
    .choices__list--dropdown:hover::-webkit-scrollbar-track {
      background: transparent !important;
    }
    
    .choices__list--dropdown:hover::-webkit-scrollbar-thumb {
      background: #c1c1c1 !important;
      border-radius: 2px !important;
    }
    
    /* Remove all scrollbars from all choices elements */
    .choices,
    .choices *,
    .choices__inner,
    .choices__list,
    .choices__list--multiple,
    .choices__list--single,
    .choices[data-type*="select-multiple"],
    .choices[data-type*="select-multiple"] *,
    .choices__item,
    .choices__item--selectable {
      overflow: visible !important;
      max-height: none !important;
      scrollbar-width: none !important;
      -ms-overflow-style: none !important;
    }
    
    .choices::-webkit-scrollbar,
    .choices *::-webkit-scrollbar,
    .choices__inner::-webkit-scrollbar,
    .choices__list::-webkit-scrollbar,
    .choices__list--multiple::-webkit-scrollbar,
    .choices__list--single::-webkit-scrollbar,
    .choices__item::-webkit-scrollbar {
      display: none !important;
      width: 0 !important;
    }
    
    /* Ensure proper spacing for the Related applications field */
    .mb-5 {
      margin-bottom: 4rem !important;
    }
    
    /* Add extra spacing specifically for the choices container when open */
    .choices.is-open {
      margin-bottom: 2rem !important;
    }
    
    .choices__item--choice,
    .custom-choice-item {
      padding: 16px 20px !important;
      margin: 0 !important;
      border-bottom: 1px solid #f1f3f4 !important;
      transition: all 0.2s ease !important;
      cursor: pointer !important;
      background: white !important;
      min-height: 60px !important;
      display: flex !important;
      align-items: center !important;
    }
    
    .choices__item--choice:last-child,
    .custom-choice-item:last-child {
      border-bottom: none !important;
    }
    
    .choices__item--choice:hover,
    .choices__item--choice.is-highlighted,
    .custom-choice-item:hover,
    .choices__item--choice[aria-selected="true"] {
      background-color: #e3f2fd !important;
      color: #1976d2 !important;
      border-left: 4px solid #2196f3 !important;
      padding-left: 16px !important;
      box-shadow: 0 2px 8px rgba(33, 150, 243, 0.15) !important;
    }
    
    .choice-content {
      display: block !important;
      width: 100% !important;
    }
    
    .choice-title,
    .choices__item--choice strong {
      display: block !important;
      font-weight: 600 !important;
      color: #2c3e50 !important;
      margin-bottom: 4px !important;
      font-size: 14px !important;
      line-height: 1.3 !important;
    }
    
    .choice-subtitle,
    .choices__item--choice small {
      display: block !important;
      font-size: 12px !important;
      color: #7b8794 !important;
      font-style: italic !important;
      margin-top: 2px !important;
    }
    
    .choices__item--choice:hover .choice-title,
    .choices__item--choice:hover strong,
    .choices__item--choice.is-highlighted .choice-title,
    .choices__item--choice.is-highlighted strong {
      color: #1976d2 !important;
    }
    
    .choices__item--choice:hover .choice-subtitle,
    .choices__item--choice:hover small,
    .choices__item--choice.is-highlighted .choice-subtitle,
    .choices__item--choice.is-highlighted small {
      color: #1565c0 !important;
    }
    
    /* Input field styling */
    .choices__input {
      background-color: transparent !important;
      margin-bottom: 0 !important;
      font-size: 14px !important;
      padding: 8px 12px !important;
    }
    
    /* Loading state */
    .choices__placeholder {
      opacity: 0.7 !important;
      font-style: italic !important;
      color: #9e9e9e !important;
    }
    
    /* Remove default Choices.js styling that conflicts */
    .choices__item--choice[data-choice-selectable] {
      padding-right: 20px !important;
    }
    .form-range::-webkit-slider-runnable-track {
      height: 0.5rem;
      background: linear-gradient(to right, #B8D2F7 0%, #B8D2F7 var(--progress, 0%), #f1f3f5 var(--progress, 0%), #f1f3f5 100%);
      border-radius: 0.25rem;
    }
    .form-range::-moz-range-track {
      height: 0.5rem;
      background: #f1f3f5;
      border-radius: 0.25rem;
    }
    .form-range::-moz-range-progress {
      height: 0.5rem;
      background: #B8D2F7;
      border-radius: 0.25rem;
    }
    .form-range::-ms-fill-lower {
      height: 0.5rem;
      background: #B8D2F7;
      border-radius: 0.25rem;
    }
    .form-range::-ms-fill-upper {
      height: 0.5rem;
      background: #f1f3f5;
      border-radius: 0.25rem;
    }
    .form-range:focus {
      outline: none;
      box-shadow: none;
    }
    .form-range::-webkit-slider-thumb {
      background: #0d6efd;
      border: none;
      box-shadow: 0 0 2px rgba(0,0,0,0.2);
    }
    .form-range::-moz-range-thumb {
      background: #0d6efd;
      border: none;
      box-shadow: 0 0 2px rgba(0,0,0,0.2);
    }
    .form-range::-ms-thumb {
      background: #0d6efd;
      border: none;
      box-shadow: 0 0 2px rgba(0,0,0,0.2);
    }
    
    /* Enhanced active state for disabled buttons */
    .btn.active {
      background-color: #0d6efd !important;
      border-color: #0d6efd !important;
      color: white !important;
    }
    
    .btn-outline-secondary.active {
      background-color: #6c757d !important;
      border-color: #6c757d !important;
      color: white !important;
    }
    
    /* Header with buttons styling */
    .header-with-buttons {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }
    
    .header-with-buttons h2 {
      margin: 0;
    }
    
    .header-buttons {
      display: flex;
      gap: 0.5rem;
    }
    
    @media (max-width: 767px) { .row { gap: 0 !important; } }
  </style>
</head>
<body class="bg-light">
<!-- Topbar -->
<?php include __DIR__ . '/shared/topbar.php'; ?>

<div class="container">
  <form method="post" autocomplete="off" id="applicationForm">
    <div class="header-with-buttons">
      <div></div>
      <div class="header-buttons">
        <a href="<?php echo $id > 0 ? 'app_view.php?id=' . $id : 'dashboard.php'; ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" form="applicationForm" class="btn btn-primary">Save</button>
      </div>
    </div>
    <div class="row g-3">
      <!-- Left column -->
      <div class="col-md-6">
        <div class="form-group-horizontal">
          <label for="shortDescription" class="form-label">Short description</label>
          <div class="input-group">
            <input type="text" class="form-control" id="shortDescription" name="short_description" placeholder="Short description" value="<?php echo htmlspecialchars($app['short_description'] ?? ''); ?>">
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
          <div class="input-group">
            <input type="text" class="form-control" id="assignedTo" name="assigned_to" placeholder="Assigned to" value="<?php echo htmlspecialchars($app['assigned_to'] ?? ''); ?>">
            <button type="button" class="btn btn-outline-secondary info-btn" tabindex="0"
              data-bs-toggle="popover"
              data-bs-placement="bottom"
              title="Assigned to"
              data-bs-content="Specify the name of the person or team responsible for this application.">
              <i class="bi bi-info-circle"></i>
            </button>
          </div>
        </div>
        <div class="form-group-horizontal">
          <label for="preOpsPortfolio" class="form-label">Pre-ops portfolio</label>
          <input type="text" class="form-control" id="preOpsPortfolio" name="preops_portfolio" placeholder="Pre-ops portfolio" value="<?php echo htmlspecialchars($app['preops_portfolio'] ?? ''); ?>">
        </div>
        <div class="form-group-horizontal">
          <label for="applicationPortfolio" class="form-label">Application Portfolio</label>
          <input type="text" class="form-control" id="applicationPortfolio" name="application_portfolio" placeholder="Application Portfolio" value="<?php echo htmlspecialchars($app['application_portfolio'] ?? ''); ?>">
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
            <?php foreach (["Client Application","On-premise","SaaS","Externally hosted"] as $model): ?>
              <option<?php if(($app['deployment_model'] ?? '')===$model) echo ' selected'; ?>><?php echo $model; ?></option>
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
</div>
<script>
// Define all global functions BEFORE any library loading
window.clearField = function(fieldId) {
  const field = document.getElementById(fieldId);
  if (field) {
    field.value = '';
    field.focus();
  }
};

window.setPhase = function(value, button) {
  document.getElementById('phase_input').value = value;
  button.parentElement.querySelectorAll('button').forEach(btn => btn.classList.remove('active'));
  button.classList.add('active');
};

window.setStatus = function(value, button) {
  document.getElementById('status_input').value = value;
  button.parentElement.querySelectorAll('button').forEach(btn => btn.classList.remove('active'));
  button.classList.add('active');
};

window.toggleSADocument = function(select) {
  const saDoc = document.getElementById('sa_document_group');
  saDoc.style.display = select.value === 'Yes' ? 'flex' : 'none';
};

window.updateHandoverTooltip = function(slider) {
  const tooltip = document.getElementById('handoverTooltip');
  const container = slider.parentElement;
  const value = parseInt(slider.value);
  const tooltipMap = {
    0: '', 10: '10% - Early planning started', 20: '20% - Stakeholders identified', 
    30: '30% - Key data collected', 40: '40% - Requirements being defined', 
    50: '50% - Documentation in progress', 60: '60% - Infra/support needs mapped', 
    70: '70% - Ops model drafted', 80: '80% - Final review ongoing', 
    90: '90% - Ready for transition', 100: 'Completed'
  };
  
  // Update CSS custom property for progress
  const progress = ((value - slider.min) / (slider.max - slider.min)) * 100;
  slider.style.setProperty('--progress', `${progress}%`);
  
  // Calculate position based on slider thumb position
  const sliderRect = slider.getBoundingClientRect();
  const containerRect = container.getBoundingClientRect();
  const thumbPosition = ((value - slider.min) / (slider.max - slider.min)) * slider.offsetWidth;
  
  // Position tooltip relative to container
  tooltip.style.left = `${thumbPosition}px`;
  tooltip.innerText = tooltipMap[value];
  tooltip.style.display = tooltipMap[value] ? 'block' : 'none';
  
  // Update markers
  const markers = container.querySelectorAll('.range-marker');
  markers.forEach((marker, index) => {
    const markerValue = index * 10;
    if (markerValue <= value) {
      marker.classList.add('active');
    } else {
      marker.classList.remove('active');
    }
  });
};
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
// DOM Content Loaded event
document.addEventListener('DOMContentLoaded', function () {
  console.log('DOM loaded, initializing components...');
  
  // Initialize handover tooltip
  const slider = document.querySelector('input[type="range"][name="handover_status"]');
  if (slider) {
    // Initialize progress CSS property
    const value = parseInt(slider.value);
    const progress = ((value - slider.min) / (slider.max - slider.min)) * 100;
    slider.style.setProperty('--progress', `${progress}%`);
    
    updateHandoverTooltip(slider);
  }
  
  // Info popovers
  const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
  popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
  });
  
  // Check if Choices.js is loaded
  if (typeof Choices === 'undefined') {
    console.error('Choices.js is not loaded');
    return;
  }
  
  // Initialize Choices.js for multiple select
  const relationshipSelect = document.getElementById('relationshipYggdrasil');
  if (relationshipSelect) {
    console.log('Initializing Choices.js...');
    
    try {
      const relationshipChoices = new Choices(relationshipSelect, {
        removeItemButton: true,
        placeholder: true,
        placeholderValue: 'Search for applications...',
        searchEnabled: true,
        searchChoices: false,
        searchFloor: 2,
        searchResultLimit: 20,
        renderChoiceLimit: -1,
        shouldSort: false
      });

      // Clear search results after selection
      relationshipSelect.addEventListener('choice', function(e) {
        console.log('Choice selected:', e.detail);
        relationshipChoices.clearChoices();
      });

      // Search functionality
      let searchTimeout;
      relationshipSelect.addEventListener('search', function(e) {
        const query = e.detail.value;
        console.log('Search query:', query);
        
        if (query.length < 2) {
          relationshipChoices.clearChoices();
          return;
        }

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          const currentAppId = <?php echo $id; ?>;
          
          const selectedValues = relationshipChoices.getValue(true);
          const selectedIds = selectedValues.length > 0 ? selectedValues.join(',') : '';
          
          let url = `api/search_applications.php?q=${encodeURIComponent(query)}&exclude=${currentAppId}`;
          if (selectedIds) {
            url += `&selected=${encodeURIComponent(selectedIds)}`;
          }
          
          console.log('Fetching from:', url);
          
          fetch(url)
            .then(response => {
              console.log('Response status:', response.status);
              if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
              }
              return response.json();
            })
            .then(data => {
              console.log('Search results:', data);
              if (data.error) {
                console.error('API Error:', data);
                return;
              }
              relationshipChoices.clearChoices();
              relationshipChoices.setChoices(data, 'value', 'label', true);
            })
            .catch(error => {
              console.error('Search error:', error);
            });
        }, 300);
      });
      
      console.log('Choices.js initialized successfully');
    } catch (error) {
      console.error('Error initializing Choices.js:', error);
    }
  } else {
    console.error('relationshipYggdrasil element not found');
  }
});
</script>
</body>
</html>
