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

<script>
// Set current app ID for AI analysis
window.currentAppId = <?php echo $id; ?>;

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

</body>
</html>
