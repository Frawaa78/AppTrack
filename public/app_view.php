<?php
// public/app_view.php
require_once __DIR__ . '/../src/db/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT * FROM applications WHERE id = :id');
$stmt->execute([':id' => $id]);
$app = $stmt->fetch();
if (!$app) {
    header('Location: dashboard.php');
    exit;
}
if (isset($app['relationship_yggdrasil']) && !empty($app['relationship_yggdrasil'])) {
    $app['relationship_yggdrasil'] = array_map('trim', explode(',', $app['relationship_yggdrasil']));
    
    // Fetch related application details for display
    if (!empty($app['relationship_yggdrasil'])) {
        $placeholders = implode(',', array_fill(0, count($app['relationship_yggdrasil']), '?'));
        $stmt = $db->prepare("SELECT id, short_description, application_service FROM applications WHERE id IN ($placeholders)");
        $stmt->execute($app['relationship_yggdrasil']);
        $app['related_apps'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $app['related_apps'] = [];
    }
} else {
    $app['relationship_yggdrasil'] = [];
    $app['related_apps'] = [];
}

// Fetch phases from database
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
  <title>View Application</title>
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
  <link rel="stylesheet" href="../assets/css/pages/app-view.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../assets/css/components/activity-tracker.css">
  <link rel="stylesheet" href="../assets/css/components/ai-analysis.css">
  <style>
    /* Related Applications readonly styling */
    .choices--disabled {
      pointer-events: none !important;
      opacity: 1 !important;
    }
    .choices--disabled .choices__inner {
      background-color: #f8f9fa !important;
      cursor: default !important;
      min-height: auto !important;
      padding: 0.375rem 0.75rem !important;
    }
    .choices[data-type*="select-multiple"] .choices__inner {
      background-color: #f8f9fa !important;
      border-color: #dee2e6 !important;
      min-height: auto !important;
      padding: 0.375rem 0.75rem !important;
    }
    .choices__list--multiple .choices__item {
      background-color: #9DA3A8 !important;
      border: 1px solid #73787D !important;
      color: white !important;
      border-radius: 0.25rem !important;
      margin: 0.125rem 0.25rem 0.125rem 0 !important;
      padding: 0.25rem 0.5rem !important;
      font-size: 0.875rem !important;
      line-height: 1.2 !important;
      display: inline-block !important;
      font-weight: 500 !important;
    }
    .choices__list--multiple .choices__item .choices__button {
      display: none !important;
    }
    .choices--disabled .choices__button {
      display: none !important;
    }

    /* Header Action Button Styling */
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

    /* FontAwesome Pro icons */
    .fa-light.fa-lightbulb:before {
        content: "\f0eb" !important;
        font-family: "Font Awesome 6 Pro" !important;
        font-weight: 300;
    }
    
    .fa-light.fa-monitor-waveform:before {
        content: "\f611" !important;
        font-family: "Font Awesome 6 Pro" !important;
        font-weight: 300;
    }
    
    .fa-light.fa-microchip:before {
        content: "\f2db" !important;
        font-family: "Font Awesome 6 Pro" !important;
        font-weight: 300;
    }
    
    .fa-light.fa-wand-magic-sparkles:before {
        content: "\e2ca" !important;
        font-family: "Font Awesome 6 Pro" !important;
        font-weight: 300;
    }
    
    .fa-light.fa-sitemap:before {
        content: "\f0e8" !important;
        font-family: "Font Awesome 6 Pro" !important;
        font-weight: 300;
    }
    
    .fa-light.fa-project-diagram:before {
        content: "\f542" !important;
        font-family: "Font Awesome 6 Pro" !important;
        font-weight: 300;
    }

    /* Fallback for when FontAwesome Pro is not loaded */
    .fa-light:before {
        font-family: "Font Awesome 6 Free", "Font Awesome 5 Free", "Bootstrap Icons" !important;
        font-weight: 900;
    }

    /* Range Slider Styling for Handover Status */
    .form-range {
      width: 100% !important;
      background-color: transparent !important;
      margin-bottom: 0.5rem !important;
    }
    
    .range-container {
      position: relative !important;
      margin-bottom: 20px !important;
    }
    
    .range-markers {
      position: absolute !important;
      top: 0 !important;
      left: 0 !important;
      width: 100% !important;
      height: 100% !important;
      pointer-events: none !important;
      display: flex !important;
      justify-content: space-between !important;
      align-items: center !important;
    }
    
    .range-marker {
      width: 2px !important;
      height: 12px !important;
      background-color: #dee2e6 !important;
      border-radius: 1px !important;
      opacity: 1 !important;
    }
    
    .range-marker.active {
      opacity: 0 !important;
    }
    
    .tooltip-follow {
      font-size: 0.69rem !important;
      position: absolute !important;
      top: 27px !important;
      background: #6C757D !important;
      color: white !important;
      padding: 4px 8px !important;
      border-radius: 4px !important;
      white-space: nowrap !important;
      pointer-events: none !important;
      z-index: 1000 !important;
      transform: translateX(-50%) !important;
      display: block !important;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
    }
    
    /* Range slider track styling - gray for view mode */
    .form-range::-webkit-slider-runnable-track {
      height: 0.5rem !important;
      background: linear-gradient(to right, #A9ADAF 0%, #A9ADAF var(--progress, 0%), #f1f3f5 var(--progress, 0%), #f1f3f5 100%) !important;
      border-radius: 0.25rem !important;
    }
    .form-range::-moz-range-track {
      height: 0.5rem !important;
      background: #f1f3f5 !important;
      border-radius: 0.25rem !important;
    }
    .form-range::-moz-range-progress {
      height: 0.5rem !important;
      background: #A9ADAF !important;
      border-radius: 0.25rem !important;
    }
    .form-range::-ms-fill-lower {
      height: 0.5rem !important;
      background: #A9ADAF !important;
      border-radius: 0.25rem !important;
    }
    .form-range::-ms-fill-upper {
      height: 0.5rem !important;
      background: #f1f3f5 !important;
      border-radius: 0.25rem !important;
    }
    
    /* Range slider thumb styling */
    .form-range:focus {
      outline: none !important;
      box-shadow: none !important;
    }
    .form-range::-webkit-slider-thumb {
      background: #6c757d !important;
      border: none !important;
      box-shadow: 0 0 2px rgba(0,0,0,0.2) !important;
      width: 1rem !important;
      height: 1rem !important;
      border-radius: 50% !important;
      cursor: pointer !important;
    }
    .form-range::-moz-range-thumb {
      background: #6c757d !important;
      border: none !important;
      box-shadow: 0 0 2px rgba(0,0,0,0.2) !important;
      width: 1rem !important;
      height: 1rem !important;
      border-radius: 50% !important;
      cursor: pointer !important;
    }
    .form-range::-ms-thumb {
      background: #6c757d !important;
      border: none !important;
    }
    .choices[data-type*="select-multiple"] {
      min-height: 38px !important;
    }
    
    /* Force text ellipsis on long URLs in form controls */
    .form-control[href] {
      overflow: hidden !important;
      text-overflow: ellipsis !important;
      white-space: nowrap !important;
      display: block !important;
      max-width: 100% !important;
    }
    

    

    #integrationDiagramModal .modal-dialog {
      max-width: 90vw;
      width: 90vw;
      height: 85vh;
      margin: 2.5vh auto;
    }
    
    #integrationDiagramModal .modal-content {
      height: 100%;
      border: none;
      border-radius: 12px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }
    
    #integrationDiagramModal .modal-body {
      height: calc(100% - 60px);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      padding: 0 !important;
    }
    
    .modal-header-controls {
      display: flex;
      align-items: center;
    }
    
    #integrationDiagramModal .modal-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-bottom: none;
      border-radius: 12px 12px 0 0;
    }
    
    #integrationDiagramModal .modal-title {
      color: white;
      font-weight: 600;
    }
    
    #integrationDiagramModal .btn-close-white {
      filter: none;
      background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3e%3cpath d='m.146 14.146-.354-.354a.5.5 0 0 1 .708-.708L8 10.293l6.5-6.5a.5.5 0 0 1 .708.708L8.707 11l6.147 6.146a.5.5 0 0 1-.708.708L8 11.707l-6.146 6.147a.5.5 0 0 1-.708-.708L7.293 11 .146 4.854a.5.5 0 1 1 .708-.708L8 10.293l6.5-6.5z'/%3e%3c/svg%3e") center/1em auto no-repeat;
    }
    
    /* Editor Toolbar */
    .editor-toolbar {
      background: #f8fafc;
      border-bottom: 1px solid #e2e8f0;
      padding: 8px 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-shrink: 0;
    }
    
    .toolbar-section {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .toolbar-section .btn {
      padding: 4px 8px;
      font-size: 0.875rem;
    }
    
    .zoom-level {
      font-size: 0.875rem;
      color: #6b7280;
      min-width: 50px;
      text-align: center;
    }
    
    /* Editor Container */
    .editor-container {
      flex: 1;
      position: relative;
      overflow: hidden;
      background: #ffffff;
      min-height: 0; /* Critical for flex child */
    }
    
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
      top: 60px;
      right: 20px;
      width: 250px;
      background: white;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 16px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.15);
      z-index: 1000;
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
  </style>
</head>
<body class="bg-light">
<!-- Topbar -->
<?php include __DIR__ . '/shared/topbar.php'; ?>
<div class="container">
  <div class="header-with-buttons">
    <div class="d-flex align-items-center">
      <a href="dashboard.php" 
         class="header-action-btn me-3" 
         title="Go back to dashboard">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <h5 class="mb-0">Status & Details</h5>
    </div>
    <div class="header-buttons">
      <button type="button" 
              class="header-action-btn" 
              onclick="openUserStories()" 
              title="View and manage user stories">
        <i class="fa-light fa-lightbulb" data-fallback="fa-solid fa-lightbulb,fas fa-lightbulb,bi bi-lightbulb"></i>
        User Stories
      </button>
      <button type="button" 
              class="header-action-btn" 
              onclick="openAIAnalysis()" 
              title="Get AI-powered insights and analysis">
        <i class="fa-light fa-microchip" data-fallback="fa-solid fa-microchip,fas fa-microchip,bi bi-cpu"></i>
        AI Insights
      </button>
      <button type="button" 
              class="header-action-btn" 
              onclick="openDataMap()" 
              title="Open DataMap Editor - Create visual data flow diagrams">
        <i class="fa-light fa-project-diagram" data-fallback="fa-solid fa-project-diagram,fas fa-project-diagram,bi bi-diagram-3"></i>
        DataMap
      </button>
      <button type="button" 
              class="header-action-btn" 
              onclick="window.open('handover/index.php?app_id=<?php echo $app['id']; ?>', '_blank')" 
              title="Open handover wizard">
        <i class="fa-light fa-wand-magic-sparkles" data-fallback="fa-solid fa-wand-magic-sparkles,fas fa-magic,bi bi-magic"></i>
        Handover Wizard
      </button>
      <?php if (isset($_SESSION['user_role'])) { $role = $_SESSION['user_role']; } else { $role = null; } ?>
      <?php if ($role === 'admin' || $role === 'editor') : ?>
        <a href="app_form.php?id=<?php echo $id; ?>" class="btn btn-primary">Edit</a>
      <?php endif; ?>
    </div>
  </div>
  <form autocomplete="off">
    <div class="row g-3">
      <!-- Venstre kolonne -->
      <div class="col-md-6">
        <div class="form-group-horizontal">
          <label for="shortDescription" class="form-label">Short description</label>
          <div class="input-group">
            <input type="text" class="form-control" id="shortDescription" name="short_description" placeholder="Short description" value="<?php echo htmlspecialchars($app['short_description']); ?>" readonly>
            <button type="button" class="btn btn-outline-secondary info-btn" tabindex="-1" disabled
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
          <input type="text" class="form-control" id="applicationService" name="application_service" placeholder="Application service" value="<?php echo htmlspecialchars($app['application_service']); ?>" readonly>
        </div>
        <div class="form-group-horizontal">
          <label for="relevantFor" class="form-label">Relevant for</label>
          <select class="form-select" id="relevantFor" name="relevant_for" disabled>
            <option<?php if($app['relevant_for']==='To be decided') echo ' selected'; ?>>To be decided</option>
            <option<?php if($app['relevant_for']==='Yggdrasil') echo ' selected'; ?>>Yggdrasil</option>
            <option<?php if($app['relevant_for']==='Not relevant') echo ' selected'; ?>>Not relevant</option>
          </select>
        </div>
        <div class="form-group-horizontal">
          <label class="form-label">Phase</label>
          <div class="btn-group w-100" role="group" aria-label="Phase">
            <?php foreach ($phases as $phase): 
                $isActive = (trim($app['phase'] ?? '') === trim($phase));
            ?>
              <button type="button" class="btn btn-outline-primary<?php if($isActive) echo ' active'; ?>" disabled><?php echo $phase; ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="form-group-horizontal">
          <label class="form-label">Status</label>
          <div class="btn-group w-100" role="group" aria-label="Status">
            <?php foreach ($statuses as $status): 
                $isActive = (trim($app['status'] ?? '') === trim($status));
            ?>
              <button type="button" class="btn btn-outline-secondary<?php if($isActive) echo ' active'; ?>" disabled><?php echo $status; ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="form-group-horizontal position-relative">
          <label class="form-label">Handover status</label>
          <div class="range-container" style="flex: 1;">
            <input type="range" class="form-range" min="0" max="100" step="10" name="handover_status" value="<?php echo htmlspecialchars($app['handover_status'] ?? 0); ?>" disabled>
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
          <input type="text" class="form-control" id="contractNumber" name="contract_number" placeholder="Contract number" value="<?php echo htmlspecialchars($app['contract_number']); ?>" readonly>
        </div>
        <div class="form-group-horizontal">
          <label for="contractResponsible" class="form-label">Contract responsible</label>
          <input type="text" class="form-control" id="contractResponsible" name="contract_responsible" placeholder="Contract responsible" value="<?php echo htmlspecialchars($app['contract_responsible']); ?>" readonly>
        </div>
        <div class="form-group-horizontal">
          <label for="informationSpace" class="form-label">Information Space</label>
          <?php if (!empty($app['information_space'])): ?>
            <a href="<?php echo htmlspecialchars($app['information_space']); ?>" target="_blank" rel="noopener noreferrer" class="form-control" style="position: relative; text-decoration: none; color: #0d6efd; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; display: block !important; max-width: 100% !important; padding-right: 2.5rem !important;">
              <?php echo htmlspecialchars($app['information_space']); ?>
              <i class="bi bi-box-arrow-up-right" style="position: absolute !important; right: 0.75rem !important; top: 50% !important; transform: translateY(-50%) !important; pointer-events: none !important;"></i>
            </a>
          <?php else: ?>
            <input type="url" class="form-control" id="informationSpace" name="information_space" placeholder="Information Space" value="" readonly>
          <?php endif; ?>
        </div>
        <div class="form-group-horizontal">
          <label for="baSharepoint" class="form-label">BA Sharepoint list</label>
          <?php if (!empty($app['ba_sharepoint_list'])): ?>
            <a href="<?php echo htmlspecialchars($app['ba_sharepoint_list']); ?>" target="_blank" rel="noopener noreferrer" class="form-control" style="position: relative; text-decoration: none; color: #0d6efd; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; display: block !important; max-width: 100% !important; padding-right: 2.5rem !important;">
              <?php echo htmlspecialchars($app['ba_sharepoint_list']); ?>
              <i class="bi bi-box-arrow-up-right" style="position: absolute !important; right: 0.75rem !important; top: 50% !important; transform: translateY(-50%) !important; pointer-events: none !important;"></i>
            </a>
          <?php else: ?>
            <input type="text" class="form-control" id="baSharepoint" name="ba_sharepoint_list" placeholder="BA Sharepoint list" value="" readonly>
          <?php endif; ?>
        </div>
        <div class="form-group-horizontal">
          <label for="relationshipYggdrasil" class="form-label">Related applications</label>
          <div style="flex: 1;">
            <select class="form-select" id="relationshipYggdrasil" name="relationship_yggdrasil[]" multiple disabled>
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
      <!-- Høyre kolonne -->
      <div class="col-md-6">
        <div class="form-group-horizontal">
          <label for="assignedTo" class="form-label">Assigned to</label>
          <div class="input-group">
            <input type="text" class="form-control" id="assignedTo" name="assigned_to" placeholder="Assigned to" value="<?php echo htmlspecialchars($app['assigned_to']); ?>" readonly>
            <button type="button" class="btn btn-outline-secondary info-btn" tabindex="-1" disabled
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
          <input type="text" class="form-control" id="preOpsPortfolio" name="preops_portfolio" placeholder="Pre-ops portfolio" value="<?php echo htmlspecialchars($app['preops_portfolio']); ?>" readonly>
        </div>
        <div class="form-group-horizontal">
          <label for="applicationPortfolio" class="form-label">Application Portfolio</label>
          <input type="text" class="form-control" id="applicationPortfolio" name="application_portfolio" placeholder="Application Portfolio" value="<?php echo htmlspecialchars($app['application_portfolio']); ?>" readonly>
        </div>
        <div class="form-group-horizontal">
          <label for="deliveryResponsible" class="form-label">Delivery responsible</label>
          <input type="text" class="form-control" id="deliveryResponsible" name="delivery_responsible" placeholder="Delivery responsible" value="<?php echo htmlspecialchars($app['delivery_responsible']); ?>" readonly>
        </div>
        <div class="form-group-horizontal">
          <label for="corporatorLink" class="form-label">Link to Corporator</label>
          <?php if (!empty($app['corporator_link'])): ?>
            <a href="<?php echo htmlspecialchars($app['corporator_link']); ?>" target="_blank" rel="noopener noreferrer" class="form-control" style="position: relative; text-decoration: none; color: #0d6efd; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; display: block !important; max-width: 100% !important; padding-right: 2.5rem !important;">
              <?php echo htmlspecialchars($app['corporator_link']); ?>
              <i class="bi bi-box-arrow-up-right" style="position: absolute !important; right: 0.75rem !important; top: 50% !important; transform: translateY(-50%) !important; pointer-events: none !important;"></i>
            </a>
          <?php else: ?>
            <input type="url" class="form-control" id="corporatorLink" name="corporator_link" placeholder="Link to Corporator" value="" readonly>
          <?php endif; ?>
        </div>
        <div class="form-group-horizontal">
          <label for="projectManager" class="form-label">Project manager</label>
          <input type="text" class="form-control" id="projectManager" name="project_manager" placeholder="Project manager" value="<?php echo htmlspecialchars($app['project_manager']); ?>" readonly>
        </div>
        <div class="form-group-horizontal">
          <label for="productOwner" class="form-label">Product owner</label>
          <input type="text" class="form-control" id="productOwner" name="product_owner" placeholder="Product owner" value="<?php echo htmlspecialchars($app['product_owner']); ?>" readonly>
        </div>
        <div class="form-group-horizontal">
          <label for="dueDate" class="form-label">Due date</label>
          <input type="date" class="form-control" id="dueDate" name="due_date" placeholder="Due date" value="<?php echo htmlspecialchars($app['due_date']); ?>" readonly>
        </div>
        <div class="form-group-horizontal">
          <label for="deploymentModel" class="form-label">Deployment model</label>
          <select class="form-select" id="deploymentModel" name="deployment_model" disabled>
            <?php foreach (["Client Application","On-premise","SaaS","Externally hosted"] as $model): ?>
              <option<?php if($app['deployment_model']===$model) echo ' selected'; ?>><?php echo $model; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group-horizontal">
          <label for="integrations" class="form-label">Integrations</label>
          <select class="form-select" id="integrations" name="integrations" disabled>
            <?php foreach (["Not defined","Yes","No"] as $opt): ?>
              <option<?php if($app['integrations']===$opt) echo ' selected'; ?>><?php echo $opt; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group-horizontal" id="sa_document_group" style="display: <?php echo ($app['integrations']==='Yes') ? 'flex' : 'none'; ?>;">
          <label for="saDocument" class="form-label">S.A. Document</label>
          <?php if (!empty($app['sa_document'])): ?>
            <a href="<?php echo htmlspecialchars($app['sa_document']); ?>" target="_blank" rel="noopener noreferrer" class="form-control" style="position: relative; text-decoration: none; color: #0d6efd; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; display: block !important; width: 100% !important; padding-right: 2.5rem !important;">
              <?php echo htmlspecialchars($app['sa_document']); ?>
              <i class="bi bi-box-arrow-up-right" style="position: absolute !important; right: 0.75rem !important; top: 50% !important; transform: translateY(-50%) !important; pointer-events: none !important;"></i>
            </a>
          <?php else: ?>
            <input type="url" class="form-control" id="saDocument" name="sa_document" placeholder="S.A. Document" value="" readonly>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="form-group-horizontal">
      <label for="businessNeed" class="form-label">Business need</label>
      <textarea class="form-control" id="businessNeed" name="business_need" style="height: 100px" placeholder="Business need" readonly><?php echo htmlspecialchars($app['business_need']); ?></textarea>
    </div>
  </form>
  
  <!-- Activity Tracker Section -->
  <?php 
  $application_id = $id; 
  include __DIR__ . '/shared/activity_tracker.php'; 
  ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script src="../assets/js/components/activity-tracker.js"></script>
<script src="../assets/js/components/form-handlers.js"></script>
<script src="../assets/js/pages/app-view.js?v=<?php echo time(); ?>"></script>
<script>
// Set current app ID for JavaScript modules
window.currentAppId = <?php echo $id; ?>;

// Initialize read-only Activity Tracker
document.addEventListener('DOMContentLoaded', function() {
  // Initialize activity tracker in read-only mode with switches
  const activityTracker = new ActivityTracker(<?php echo $id; ?>, '<?php echo $_SESSION['user_role'] ?? 'viewer'; ?>', true); // true = read-only mode
});

// Backup inline script for Related applications and Handover slider if external files fail
document.addEventListener('DOMContentLoaded', function() {
  console.log('Inline script: Initializing components...');
  
  // Backup handover tooltip function
  function updateHandoverTooltip(slider) {
    const tooltip = document.getElementById('handoverTooltip');
    if (!tooltip) {
      console.error('Tooltip element not found!');
      return;
    }
    
    const container = slider.parentElement;
    const value = parseInt(slider.value) || 0; // Use same default as initialization
    console.log('updateHandoverTooltip called with value:', value);
    
    const tooltipMap = {
      0: '', 
      10: '10% - Early planning started', 
      20: '20% - Stakeholders identified', 
      30: '30% - Key data collected', 
      40: '40% - Requirements being defined', 
      50: '50% - Documentation in progress', 
      60: '60% - Infra/support needs mapped', 
      70: '70% - Ops model drafted', 
      80: '80% - Final review ongoing', 
      90: '90% - Ready for transition', 
      100: 'Completed'
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
    tooltip.innerText = tooltipMap[value] || `${value}% - Custom value`;
    console.log('Setting tooltip text to:', tooltip.innerText);
    
    // Always show tooltip
    tooltip.style.display = 'block';
    console.log('Tooltip shown');
    
    // Update markers
    const markers = container.querySelectorAll('.range-marker');
    console.log('Found markers:', markers.length);
    markers.forEach((marker, index) => {
      const markerValue = index * 10;
      if (markerValue <= value) {
        marker.classList.add('active');
      } else {
        marker.classList.remove('active');
      }
    });
  }
  
  // Initialize handover slider immediately
  const slider = document.querySelector('input[type="range"][name="handover_status"]');
  if (slider) {
    console.log('Initializing handover slider...');
    console.log('Slider element found:', slider);
    console.log('Slider value attribute:', slider.getAttribute('value'));
    console.log('Slider value property:', slider.value);
    
    // Wait a bit to ensure elements are fully rendered
    setTimeout(function() {
      // Initialize progress CSS property
      const value = parseInt(slider.value) || 0; // Default to 0 if no value
      console.log('Using value:', value);
      
      const progress = ((value - slider.min) / (slider.max - slider.min)) * 100;
      slider.style.setProperty('--progress', `${progress}%`);
      
      console.log('Slider value:', value);
      updateHandoverTooltip(slider);
      
      // Force tooltip to be visible for testing
      const tooltip = document.getElementById('handoverTooltip');
      if (tooltip) {
        tooltip.style.display = 'block';
        console.log('Tooltip forced visible');
      }
    }, 100);
  } else {
    console.error('Slider element not found!');
  }
  
  // Wait a bit for external scripts for Related applications
  setTimeout(function() {
    const relationshipSelect = document.getElementById('relationshipYggdrasil');
    if (relationshipSelect && !relationshipSelect.classList.contains('choices__input')) {
      console.log('External script failed, using inline initialization...');
      
      try {
        const choices = new Choices(relationshipSelect, {
          removeItemButton: false,
          placeholder: false,
          placeholderValue: '',
          shouldSort: false,
          searchEnabled: false,
          itemSelectText: '',
          renderChoiceLimit: -1,
          allowHTML: true,
          duplicateItemsAllowed: false,
          addItemFilter: null,
          editItems: false,
          maxItemCount: -1,
          silent: false
        });
        
        // Disable the choices instance completely
        choices.disable();
        
        console.log('Inline Choices.js initialized successfully');
      } catch (error) {
        console.error('Inline Choices.js initialization failed:', error);
      }
    } else {
      console.log('External script worked, skipping inline initialization');
    }
  }, 500);
});

// Function to open User Stories page
function openUserStories() {
  console.log('User Stories button clicked');
  window.location.href = 'user_stories.php?application_id=<?php echo $id; ?>';
}

// Function to go back
function goBack() {
  // Try to go back in history, fallback to dashboard
  if (document.referrer && document.referrer !== window.location.href) {
    window.history.back();
  } else {
    window.location.href = 'dashboard.php';
  }
}
</script>


  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="integrationDiagramModalLabel">
          <i class="bi bi-diagram-3"></i> Integration Architecture - <?php echo htmlspecialchars($app['short_description']); ?>
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
          <h6><i class="bi bi-gear"></i> Properties</h6>
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
          <div class="property-group">
            <button type="button" class="btn btn-sm btn-outline-info w-100" onclick="if(window.visualEditor) { window.visualEditor.debugSVGMarkers(); } else if(window.currentEditor) { window.currentEditor.debugSVGMarkers(); } else { console.log('❌ No visual editor found - checking alternatives...'); console.log('Available objects:', Object.keys(window).filter(k => k.includes('editor') || k.includes('visual'))); }">
              🔍 Debug SVG Markers
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Set current app ID for AI analysis
window.currentAppId = <?php echo $id; ?>;

// Test that openIntegrationDiagram function will be available
console.log('🔍 Script loading - preparing openIntegrationDiagram function...');

// Open AI Analysis - redirects to dedicated page
function openAIAnalysis() {
    console.log('AI Analysis button clicked, redirecting to ai_insights.php');
    const appId = <?php echo json_encode($id); ?>;
    window.location.href = 'ai_insights.php?application_id=' + appId;
}

// Open DataMap Editor
function openDataMap() {
    console.log('DataMap button clicked, redirecting to datamap.php');
    const appId = <?php echo json_encode($id); ?>;
    window.location.href = 'datamap.php?app_id=' + appId;
}

// Legacy AI Analysis functions removed - functionality moved to ai_insights.php

console.log('🔍 Script loading complete');

// Open Integration Diagram function
function openIntegrationDiagram() {
    console.log('🚀 openIntegrationDiagram called!');
    console.log('Opening Integration Architecture Modal...');
    
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
                console.log('Initializing VisualDiagramEditor...');
                visualEditor = new VisualDiagramEditor('visual-diagram-editor');
                window.visualEditor = visualEditor; // Make it globally accessible
                window.currentEditor = visualEditor; // Alternative reference
                console.log('VisualDiagramEditor initialized successfully:', visualEditor);
            } else {
                console.log('Visual editor already exists, performing NUCLEAR CLEANUP before reload...');
                window.visualEditor = visualEditor; // Ensure global reference
                window.currentEditor = visualEditor; // Ensure alternative reference
                
                // CRITICAL: Always clear everything when modal reopens to prevent duplicates
                console.log('🧹 FORCING COMPLETE CLEANUP to prevent duplicates');
                if (typeof visualEditor.clearAll === 'function') {
                    visualEditor.clearAll();
                }
                
                // ULTRA-NUCLEAR: Also clear DOM elements manually to ensure no leftovers
                let cleanupEditorContainer = document.getElementById('visual-diagram-editor');
                if (cleanupEditorContainer) {
                    // Remove all child elements that might be leftover
                    const existingElements = cleanupEditorContainer.querySelectorAll('.diagram-element, .text-note, .connection-line, path');
                    console.log(`🧹 MANUAL DOM CLEANUP: Removing ${existingElements.length} leftover DOM elements`);
                    existingElements.forEach(element => {
                        console.log('🗑️ Removing leftover DOM element:', element.id || element.className);
                        element.remove();
                    });
                    
                    // Reset SVG paths completely
                    const svgElement = cleanupEditorContainer.querySelector('svg');
                    if (svgElement) {
                        const paths = svgElement.querySelectorAll('path');
                        paths.forEach(path => path.remove());
                        console.log(`🧹 Removed ${paths.length} SVG paths`);
                    }
                }
                
                // Reset all internal state
                visualEditor.nextNodeId = 1;
                visualEditor.nextNoteId = 1;
                visualEditor.selectedElement = null;
                
                // CRITICAL: Ensure canvas dimensions are correct after cleanup
                // Instead of manually setting styles, recreate canvas like first time
                if (typeof visualEditor.createCanvas === 'function') {
                    console.log('🎯 Recreating canvas completely to match first-time behavior...');
                    visualEditor.createCanvas();
                } else if (typeof visualEditor.setupCanvas === 'function') {
                    console.log('🎯 Setting up canvas...');
                    visualEditor.setupCanvas();
                }
                
                console.log('🔄 Editor state after NUCLEAR cleanup:');
                console.log('  - Nodes:', visualEditor.nodes.size);
                console.log('  - Connections:', visualEditor.connections.size);
                console.log('  - Text Notes:', visualEditor.textNotes.size);
                console.log('  - Next Node ID:', visualEditor.nextNodeId);
                console.log('  - Next Note ID:', visualEditor.nextNoteId);
                
                // NUCLEAR OPTION: Reset modal container CSS context completely
                const modalBody = document.querySelector('#integrationDiagramModal .modal-body');
                if (modalBody) {
                    console.log('🎯 Resetting modal body styles completely...');
                    
                    // Force modal body to maintain correct flex layout
                    modalBody.style.cssText = `
                        height: calc(100% - 60px) !important;
                        overflow: hidden !important;
                        display: flex !important;
                        flex-direction: column !important;
                        padding: 0 !important;
                        position: relative !important;
                        box-sizing: border-box !important;
                    `;
                }
                
                // Force editor container to fill available space
                let editorContainerElement = document.querySelector('.editor-container');
                if (editorContainerElement) {
                    console.log('🎯 Resetting editor container styles...');
                    editorContainerElement.style.cssText = `
                        flex: 1 !important;
                        position: relative !important;
                        overflow: hidden !important;
                        background: #ffffff !important;
                        min-height: 0 !important;
                        box-sizing: border-box !important;
                    `;
                }
                
                console.log('🎯 Modal body reset complete, createCanvas() will handle editor container');
            }
            
            // Load existing diagram data
            initializeIntegrationDiagram();
            
            // CRITICAL: Additional dimension check after modal is fully rendered
            setTimeout(() => {
                let finalEditorContainer = document.getElementById('visual-diagram-editor');
                const modalBody = document.querySelector('#integrationDiagramModal .modal-body');
                
                if (finalEditorContainer && modalBody) {
                    console.log('🔧 Final dimension check and correction...');
                    
                    const modalRect = modalBody.getBoundingClientRect();
                    const editorRect = finalEditorContainer.getBoundingClientRect();
                    
                    console.log('📐 Modal body dimensions:', {
                        width: modalRect.width,
                        height: modalRect.height
                    });
                    
                    console.log('📐 Editor container dimensions:', {
                        width: editorRect.width,
                        height: editorRect.height
                    });
                    
                    // Force editor to match modal body constraints
                    const toolbar = document.querySelector('.editor-toolbar');
                    const toolbarHeight = toolbar ? toolbar.getBoundingClientRect().height : 0;
                    const availableHeight = modalRect.height - toolbarHeight;
                    
                    console.log('📐 Available height for editor:', availableHeight);
                    
                    // If editor is overstepping or too small, fix it
                    if (editorRect.height > availableHeight + 20 || editorRect.height < availableHeight - 20) {
                        console.log('⚠️ Editor height mismatch detected, forcing correction...');
                        
                        // Reset editor container parent first
                        const editorContainerDiv = document.querySelector('.editor-container');
                        if (editorContainerDiv) {
                            editorContainerDiv.style.cssText = `
                                flex: 1 !important;
                                position: relative !important;
                                overflow: hidden !important;
                                background: #ffffff !important;
                                min-height: 0 !important;
                                max-height: ${availableHeight}px !important;
                                box-sizing: border-box !important;
                            `;
                        }
                        
                        // Then reset the actual editor
                        finalEditorContainer.style.cssText = `
                            width: 100% !important;
                            height: 100% !important;
                            position: relative !important;
                            overflow: auto !important;
                            background-image: linear-gradient(to right, #f1f5f9 1px, transparent 1px), linear-gradient(to bottom, #f1f5f9 1px, transparent 1px) !important;
                            background-size: 20px 20px !important;
                            cursor: crosshair !important;
                            box-sizing: border-box !important;
                        `;
                        
                        console.log('✅ Editor dimensions corrected');
                    }
                }
            }, 250); // Wait for modal animation to complete
        } catch (error) {
            console.error('Error initializing visual editor:', error);
            alert('Error initializing visual editor: ' + error.message);
        }
    });
    
    modalElement.addEventListener('hidden.bs.modal', function() {
        console.log('Integration modal closed');
    });
}

// Initialize the integration diagram
async function initializeIntegrationDiagram() {
    console.log('🚀 INIT DIAGRAM DEBUG: Starting initializeIntegrationDiagram');
    console.log('🚀 INIT DIAGRAM DEBUG: window.currentAppId =', window.currentAppId);
    console.log('🚀 INIT DIAGRAM DEBUG: visualEditor exists =', !!visualEditor);
    
    if (visualEditor) {
        console.log('🚀 INIT DIAGRAM DEBUG: Current editor state:');
        console.log('  - Nodes:', visualEditor.nodes.size);
        console.log('  - Connections:', visualEditor.connections.size);
        console.log('  - Text Notes:', visualEditor.textNotes.size);
        console.log('  - Next Node ID:', visualEditor.nextNodeId);
        console.log('  - Next Note ID:', visualEditor.nextNoteId);
    }
    
    try {
        // Check if VisualDiagramEditor class exists
        if (typeof VisualDiagramEditor === 'undefined') {
            console.error('VisualDiagramEditor class not found! Make sure visual-diagram-editor.js is loaded.');
            alert('Error: Visual editor not loaded. Please refresh the page.');
            return;
        }
        
        console.log('🌐 INIT DIAGRAM DEBUG: Fetching diagram data from server...');
        const response = await fetch(`api/get_application_data.php?id=${window.currentAppId}`);
        const data = await response.json();
        
        console.log('📡 INIT DIAGRAM DEBUG: Server response:', data);
        
        if (data.success) {
            const diagramCode = data.diagram_code && data.diagram_code.trim() ? data.diagram_code : null;
            console.log('📊 INIT DIAGRAM DEBUG: Diagram code received:', {
                hasCode: !!diagramCode,
                codeLength: diagramCode ? diagramCode.length : 0,
                codePreview: diagramCode ? diagramCode.substring(0, 200) + '...' : 'NULL'
            });
            
            if (diagramCode) {
                // Try to load existing Mermaid code into visual editor
                if (visualEditor && typeof visualEditor.loadFromMermaidCode === 'function') {
                    console.log('📥 INIT DIAGRAM DEBUG: Loading existing diagram into visual editor');
                    console.log('🔄 INIT DIAGRAM DEBUG: Editor state BEFORE load:');
                    console.log('  - Nodes:', visualEditor.nodes.size);
                    console.log('  - Connections:', visualEditor.connections.size);
                    console.log('  - Text Notes:', visualEditor.textNotes.size);
                    
                    // CRITICAL: Force a complete clear before loading to prevent duplicates
                    console.log('🧹 FORCING ADDITIONAL CLEAR before loading data...');
                    if (typeof visualEditor.clearAll === 'function') {
                        visualEditor.clearAll();
                        console.log('🔄 State after additional clear:');
                        console.log('  - Nodes:', visualEditor.nodes.size);
                        console.log('  - Connections:', visualEditor.connections.size);
                        console.log('  - Text Notes:', visualEditor.textNotes.size);
                    }
                    
                    visualEditor.loadFromMermaidCode(diagramCode);
                    
                    console.log('🔄 INIT DIAGRAM DEBUG: Editor state AFTER load:');
                    console.log('  - Nodes:', visualEditor.nodes.size);
                    console.log('  - Connections:', visualEditor.connections.size);
                    console.log('  - Text Notes:', visualEditor.textNotes.size);
                    
                    // CRITICAL: Ensure fingerprint is created after loading
                    setTimeout(() => {
                        if (typeof visualEditor.createPositionFingerprint === 'function') {
                            console.log('🔐 Creating position fingerprint after load');
                            visualEditor.createPositionFingerprint();
                        }
                        
                        // Also verify positions are correct
                        if (typeof visualEditor.comprehensivePositionAudit === 'function') {
                            console.log('🔍 Running position audit after load');
                            visualEditor.comprehensivePositionAudit();
                        }
                        
                        // CRITICAL FIX: Force recreation of arrows after modal reopen and data load
                        if (typeof visualEditor.forceRecreateArrows === 'function') {
                            console.log('🔧 MODAL REOPEN FIX: Force recreating arrows after data load');
                            visualEditor.forceRecreateArrows();
                        }
                    }, 1500); // Wait for load to complete
                } else {
                    console.warn('Visual editor not ready or loadFromMermaidCode method missing');
                }
            } else {
                // Load a default template
                console.log('📝 INIT DIAGRAM DEBUG: No existing diagram, loading default template');
                loadVisualTemplate('basic');
            }
        } else {
            console.error('❌ INIT DIAGRAM DEBUG: Error loading diagram:', data.error);
            loadVisualTemplate('basic');
        }
    } catch (error) {
        console.error('❌ INIT DIAGRAM DEBUG: Error fetching diagram data:', error);
        loadVisualTemplate('basic');
    }
}

// Tool Management Functions
function setActiveTool(tool) {
    if (visualEditor) {
        visualEditor.setActiveTool(tool);
    }
}

function setTool(tool) {
    setActiveTool(tool);
}

// Element Creation Functions
function addElement(type) {
    console.log('addElement called with type:', type);
    console.log('visualEditor exists:', !!visualEditor);
    
    if (visualEditor) {
        try {
            // Add element at center of visible area
            const centerX = visualEditor.container.scrollLeft + visualEditor.container.clientWidth / 2;
            const centerY = visualEditor.container.scrollTop + visualEditor.container.clientHeight / 2;
            
            console.log(`Adding ${type} element at position:`, centerX, centerY);
            
            const element = visualEditor.addElement(type, centerX, centerY);
            console.log('Element created:', element);
            
            if (!element) {
                console.error('Failed to create element');
                alert('Failed to create element. Check console for details.');
            }
        } catch (error) {
            console.error('Error adding element:', error);
            alert('Error adding element: ' + error.message);
        }
    } else {
        console.error('Visual editor not initialized');
        alert('Visual editor not ready. Please try again.');
    }
}

// Template Functions
function loadVisualTemplate(templateType) {
    if (!visualEditor) return;
    
    const appName = '<?php echo addslashes($app['short_description']); ?>';
    const templates = {
        'basic': {
            elements: [
                { type: 'process', text: appName, x: 300, y: 150 },
                { type: 'database', text: 'Database', x: 150, y: 300 },
                { type: 'api', text: 'External API', x: 450, y: 300 },
                { type: 'user', text: 'Users', x: 300, y: 50 },
                { type: 'start', text: 'Start', x: 100, y: 150 }
            ],
            connections: [
                { from: 4, to: 0 }, // Start to App
                { from: 3, to: 0 }, // Users to App
                { from: 0, to: 1 }, // App to Database
                { from: 0, to: 2 }  // App to API
            ],
            notes: [
                { text: 'Core Application\nProcesses user requests', x: 350, y: 120 },
                { text: 'External data source', x: 500, y: 270 }
            ]
        },
        'pipeline': {
            elements: [
                { type: 'start', text: 'Data Source', x: 100, y: 200 },
                { type: 'process', text: 'Extract', x: 250, y: 200 },
                { type: 'process', text: appName, x: 400, y: 200 },
                { type: 'process', text: 'Transform', x: 550, y: 200 },
                { type: 'database', text: 'Data Warehouse', x: 700, y: 200 }
            ],
            connections: [
                { from: 0, to: 1 }, { from: 1, to: 2 }, { from: 2, to: 3 }, { from: 3, to: 4 }
            ],
            notes: [
                { text: 'ETL Pipeline\nProcesses data in stages', x: 400, y: 120 }
            ]
        },
        'api': {
            elements: [
                { type: 'user', text: 'Client Apps', x: 100, y: 100 },
                { type: 'api', text: 'API Gateway', x: 300, y: 100 },
                { type: 'process', text: appName, x: 500, y: 100 },
                { type: 'process', text: 'Auth Service', x: 400, y: 250 },
                { type: 'process', text: 'Business Logic', x: 600, y: 250 },
                { type: 'database', text: 'Database', x: 600, y: 400 }
            ],
            connections: [
                { from: 0, to: 1 }, { from: 1, to: 2 }, { from: 2, to: 3 }, { from: 2, to: 4 }, { from: 4, to: 5 }
            ],
            notes: [
                { text: 'API Architecture\nSecure and scalable', x: 300, y: 50 }
            ]
        },
        'microservices': {
            elements: [
                { type: 'api', text: 'Load Balancer', x: 350, y: 50 },
                { type: 'process', text: appName, x: 350, y: 150 },
                { type: 'process', text: 'Service A', x: 150, y: 250 },
                { type: 'process', text: 'Service B', x: 350, y: 250 },
                { type: 'process', text: 'Service C', x: 550, y: 250 },
                { type: 'database', text: 'DB A', x: 150, y: 350 },
                { type: 'database', text: 'DB B', x: 350, y: 350 },
                { type: 'database', text: 'Cache', x: 550, y: 350 }
            ],
            connections: [
                { from: 0, to: 1 }, { from: 1, to: 2 }, { from: 1, to: 3 }, { from: 1, to: 4 },
                { from: 2, to: 5 }, { from: 3, to: 6 }, { from: 4, to: 7 }
            ],
            notes: [
                { text: 'Microservices\nDecoupled architecture', x: 200, y: 50 }
            ]
        }
    };
    
    const template = templates[templateType];
    if (!template) return;
    
    // Clear existing diagram
    visualEditor.clearAll();
    
    // Add elements
    const nodeMap = {};
    template.elements.forEach((elementData, index) => {
        const element = visualEditor.addElement(elementData.type, elementData.x, elementData.y, elementData.text);
        nodeMap[index] = element;
    });
    
    // Add connections
    template.connections.forEach(conn => {
        const fromNode = nodeMap[conn.from];
        const toNode = nodeMap[conn.to];
        if (fromNode && toNode) {
            visualEditor.createConnection(fromNode, toNode);
        }
    });
    
    // Add text notes
    if (template.notes) {
        template.notes.forEach(note => {
            visualEditor.addTextNote(note.x, note.y, note.text);
        });
    }
}

// Clear canvas
function clearCanvas() {
    if (visualEditor && confirm('Are you sure you want to clear the entire diagram?')) {
        visualEditor.clearAll();
    }
}

// Auto layout
function autoLayout() {
    if (visualEditor) {
        visualEditor.autoLayout();
    }
}

// Zoom functions
function zoomIn() {
    if (visualEditor) {
        visualEditor.zoomIn();
    }
}

function zoomOut() {
    if (visualEditor) {
        visualEditor.zoomOut();
    }
}

// Property panel functions
function updateSelectedElement() {
    if (!visualEditor || !visualEditor.selectedElement) return;
    
    const element = visualEditor.selectedElement;
    const textInput = document.getElementById('elementText');
    const widthSlider = document.getElementById('elementWidth');
    const heightSlider = document.getElementById('elementHeight');
    const colorSelect = document.getElementById('elementColor');
    const directionSelect = document.getElementById('connectionDirection');
    
    // Handle connection direction changes
    if (element.type === 'connection' && directionSelect && directionSelect.value !== element.direction) {
        element.direction = directionSelect.value;
        if (visualEditor.updateConnectionDirection) {
            visualEditor.updateConnectionDirection(element);
        }
        return; // Don't process other properties for connections
    }
    
    if (textInput && textInput.value !== element.text) {
        element.text = textInput.value;
        if (element.type === 'text') {
            element.domElement.textContent = element.text;
        } else {
            const textSpan = element.domElement.querySelector('.element-text');
            if (textSpan) {
                textSpan.textContent = element.text;
            }
        }
    }
    
    if (widthSlider && parseInt(widthSlider.value) !== element.width) {
        element.width = parseInt(widthSlider.value);
        element.domElement.style.width = element.width + 'px';
        visualEditor.redrawConnections();
    }
    
    if (heightSlider && parseInt(heightSlider.value) !== element.height) {
        element.height = parseInt(heightSlider.value);
        element.domElement.style.height = element.height + 'px';
        visualEditor.redrawConnections();
    }
    
    if (colorSelect && colorSelect.value !== element.color) {
        element.color = colorSelect.value;
        element.domElement.style.backgroundColor = element.color;
    }
}

function deleteSelectedElement() {
    if (visualEditor && visualEditor.selectedElement) {
        visualEditor.deleteElement(visualEditor.selectedElement);
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
    
    // CRITICAL: Use the new saveToMermaidCode() method that creates position fingerprint
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
        
        const response = await fetch('api/save_application_data.php', {
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
        
        console.log('📡 Response status:', response.status);
        console.log('📡 Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        console.log('📡 Raw response text:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ Failed to parse JSON response:', parseError);
            console.error('📡 Response was:', responseText);
            throw new Error('Invalid JSON response from server');
        }
        
        console.log('📡 Parsed response data:', data);
        
        if (data.success) {
            // Show success feedback
            const saveBtn = document.querySelector('button[onclick*="saveIntegrationData"]');
            if (saveBtn) {
                const originalText = saveBtn.innerHTML;
                saveBtn.innerHTML = '<i class="bi bi-check"></i> Saved!';
                saveBtn.classList.remove('btn-success');
                saveBtn.classList.add('btn-outline-success');
                
                setTimeout(() => {
                    saveBtn.innerHTML = originalText;
                    saveBtn.classList.remove('btn-outline-success');
                    saveBtn.classList.add('btn-success');
                }, 2000);
            }
            
            console.log('✅ Integration diagram saved successfully');
        } else {
            console.error('❌ Server returned error:', data.error);
            alert('Error saving data: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('❌ Error saving integration data:', error);
        console.error('📊 Error details:', {
            message: error.message,
            stack: error.stack,
            currentAppId: window.currentAppId,
            diagramCodeLength: diagramCode ? diagramCode.length : 0
        });
        alert('Error saving data. Please check console for details and try again.');
    }
}
</script>

<!-- Test script to verify function availability -->
<script>
// Test script ready
</script>

<!-- FontAwesome Icon Fallback System -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit for FontAwesome to load
    setTimeout(function() {
        // Check if FontAwesome Pro icons are loaded, if not use Bootstrap Icons as fallback
        const iconElements = document.querySelectorAll('.fa-regular.fa-grid-2, .fa-light.fa-monitor-waveform, .fa-light.fa-lightbulb, .fa-light.fa-project-diagram');
        
        iconElements.forEach(function(iconElement) {
            const computedStyle = window.getComputedStyle(iconElement, ':before');
            const content = computedStyle.getPropertyValue('content');
            
            // If content is empty or 'none', the FontAwesome icon didn't load
            if (!content || content === 'none' || content === '""') {
                console.log('FontAwesome icon not loading, trying fallbacks for:', iconElement.className);
                
                // Replace with Bootstrap Icons
                if (iconElement.classList.contains('fa-grid-2')) {
                    iconElement.className = 'bi bi-grid-3x3-gap';
                } else if (iconElement.classList.contains('fa-monitor-waveform')) {
                    iconElement.className = 'bi bi-speedometer2';
                } else if (iconElement.classList.contains('fa-lightbulb')) {
                    iconElement.className = 'bi bi-lightbulb';
                } else if (iconElement.classList.contains('fa-project-diagram')) {
                    iconElement.className = 'bi bi-diagram-3';
                }
            }
        });
    }, 1000); // Wait 1 second for FontAwesome to load
});
</script>
</script>

</body>
</html>
