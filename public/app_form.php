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
        if ($id > 0) {
            // Update
            $set = '';
            foreach ($fields as $f) { $set .= "$f = :$f, "; }
            $set .= "relationship_yggdrasil = :relationship_yggdrasil";
            $stmt = $db->prepare("UPDATE applications SET $set WHERE id = :id");
            $data['id'] = $id;
            $stmt->execute($data);
            // Redirect to view page for existing application
            header('Location: app_view.php?id=' . $id);
        } else {
            // Insert
            $cols = implode(',', array_merge($fields, ['relationship_yggdrasil']));
            $vals = ':' . implode(',:', array_merge($fields, ['relationship_yggdrasil']));
            $stmt = $db->prepare("INSERT INTO applications ($cols) VALUES ($vals)");
            $stmt->execute($data);
            // Get the ID of the newly created application
            $newId = $db->lastInsertId();
            // Redirect to view page for new application
            header('Location: app_view.php?id=' . $newId);
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
    .tooltip-follow { position: absolute; top: -10px; transform: translateX(-50%); background-color: #D4E6FF; color: #000; padding: 6px 10px; border-radius: 4px; font-size: 0.75rem; white-space: nowrap; display: none; }
    .form-floating > .input-group > .form-control, .form-floating > .input-group > .form-select { height: calc(3.5rem + 2px); line-height: 1.25; }
    .form-floating > .input-group > .form-control:focus, .form-floating > .input-group > .form-control:not(:placeholder-shown) { border-color: #86b7fe; box-shadow: 0 0 0 .2rem rgba(13,110,253,.25); }
    .form-floating > .input-group > label { left: 0.75rem; z-index: 2; pointer-events: none; transition: all .1s ease-in-out; opacity: .65; background: white; padding: 0 .25em; }
    .form-floating > .input-group > .form-control:focus ~ label, .form-floating > .input-group > .form-control:not(:placeholder-shown) ~ label { opacity: 1; transform: scale(.85) translateY(-0.85rem) translateX(0.15rem); background: white; padding: 0 .25em; z-index: 3; }
    .form-floating .input-group-text, .form-floating .btn { z-index: 4; }
    .input-group .btn.info-btn { border-top-left-radius: 0; border-bottom-left-radius: 0; }
    .input-group .form-control { border-right: 0; }
    .input-group .btn { border-left: 0; }
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
      background: #f1f3f5;
      border-radius: 0.25rem;
    }
    .form-range::-moz-range-track {
      height: 0.5rem;
      background: #f1f3f5;
      border-radius: 0.25rem;
    }
    .form-range::-ms-fill-lower, .form-range::-ms-fill-upper {
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
    @media (max-width: 767px) { .row { gap: 0 !important; } }
  </style>
</head>
<body class="bg-light">
<!-- Topbar -->
<?php include __DIR__ . '/shared/topbar.php'; ?>

<div class="container">
  <h2><?php echo $id > 0 ? 'Edit Application' : 'Application Form'; ?></h2>
  <form method="post" autocomplete="off">
    <div class="row g-3">
      <!-- Left column -->
      <div class="col-md-6">
        <div class="form-floating mb-3">
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
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="applicationService" name="application_service" placeholder="Application service" value="<?php echo htmlspecialchars($app['application_service'] ?? ''); ?>">
          <label for="applicationService">Application service</label>
        </div>
        <div class="form-floating mb-3">
          <select class="form-select" id="relevantFor" name="relevant_for">
            <option<?php if(($app['relevant_for'] ?? '')==='To be decided') echo ' selected'; ?>>To be decided</option>
            <option<?php if(($app['relevant_for'] ?? '')==='Yggdrasil') echo ' selected'; ?>>Yggdrasil</option>
            <option<?php if(($app['relevant_for'] ?? '')==='Not relevant') echo ' selected'; ?>>Not relevant</option>
          </select>
          <label for="relevantFor">Relevant for</label>
        </div>
        <div class="mb-3">
          <label class="form-label d-block">Phase</label>
          <input type="hidden" name="phase" id="phase_input" value="<?php echo htmlspecialchars($app['phase'] ?? ''); ?>">
          <div class="btn-group w-100" role="group" aria-label="Phase">
            <?php foreach ($phases as $phase): ?>
              <button type="button" class="btn btn-outline-primary<?php if(($app['phase'] ?? '')===$phase) echo ' active'; ?>" onclick="setPhase('<?php echo $phase; ?>', this)"><?php echo $phase; ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label d-block">Status</label>
          <input type="hidden" name="status" id="status_input" value="<?php echo htmlspecialchars($app['status'] ?? ''); ?>">
          <div class="btn-group w-100" role="group" aria-label="Status">
            <?php foreach ($statuses as $status): ?>
              <button type="button" class="btn btn-outline-secondary<?php if(($app['status'] ?? '')===$status) echo ' active'; ?>" onclick="setStatus('<?php echo $status; ?>', this)"><?php echo $status; ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="mb-3 position-relative">
          <label class="form-label d-block">Handover status</label>
          <input type="range" class="form-range" min="0" max="100" step="10" name="handover_status" value="<?php echo htmlspecialchars($app['handover_status'] ?? '0'); ?>" oninput="updateHandoverTooltip(this)">
          <div id="handoverTooltip" class="tooltip-follow">Tooltip</div>
        </div>
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="contractNumber" name="contract_number" placeholder="Contract number" value="<?php echo htmlspecialchars($app['contract_number'] ?? ''); ?>">
          <label for="contractNumber">Contract number</label>
        </div>
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="contractResponsible" name="contract_responsible" placeholder="Contract responsible" value="<?php echo htmlspecialchars($app['contract_responsible'] ?? ''); ?>">
          <label for="contractResponsible">Contract responsible</label>
        </div>
        <div class="form-floating mb-3">
          <input type="url" class="form-control" id="informationSpace" name="information_space" placeholder="Information Space" value="<?php echo htmlspecialchars($app['information_space'] ?? ''); ?>">
          <label for="informationSpace">Information Space</label>
        </div>
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="baSharepoint" name="ba_sharepoint_list" placeholder="BA Sharepoint list" value="<?php echo htmlspecialchars($app['ba_sharepoint_list'] ?? ''); ?>">
          <label for="baSharepoint">BA Sharepoint list</label>
        </div>
        <div class="mb-5">
          <label for="relationshipYggdrasil" class="form-label">Related applications</label>
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
          <div class="form-text">Search and select related applications from the database</div>
        </div>
      </div>
      <!-- Right column -->
      <div class="col-md-6">
        <div class="form-floating mb-3">
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
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="preOpsPortfolio" name="preops_portfolio" placeholder="Pre-ops portfolio" value="<?php echo htmlspecialchars($app['preops_portfolio'] ?? ''); ?>">
          <label for="preOpsPortfolio">Pre-ops portfolio</label>
        </div>
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="applicationPortfolio" name="application_portfolio" placeholder="Application Portfolio" value="<?php echo htmlspecialchars($app['application_portfolio'] ?? ''); ?>">
          <label for="applicationPortfolio">Application Portfolio</label>
        </div>
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="deliveryResponsible" name="delivery_responsible" placeholder="Delivery responsible" value="<?php echo htmlspecialchars($app['delivery_responsible'] ?? ''); ?>">
          <label for="deliveryResponsible">Delivery responsible</label>
        </div>
        <div class="form-floating mb-3">
          <input type="url" class="form-control" id="corporatorLink" name="corporator_link" placeholder="Link to Corporator" value="<?php echo htmlspecialchars($app['corporator_link'] ?? ''); ?>">
          <label for="corporatorLink">Link to Corporator</label>
        </div>
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="projectManager" name="project_manager" placeholder="Project manager" value="<?php echo htmlspecialchars($app['project_manager'] ?? ''); ?>">
          <label for="projectManager">Project manager</label>
        </div>
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="productOwner" name="product_owner" placeholder="Product owner" value="<?php echo htmlspecialchars($app['product_owner'] ?? ''); ?>">
          <label for="productOwner">Product owner</label>
        </div>
        <div class="form-floating mb-3">
          <input type="date" class="form-control" id="dueDate" name="due_date" placeholder="Due date" value="<?php echo htmlspecialchars($app['due_date'] ?? ''); ?>">
          <label for="dueDate">Due date</label>
        </div>
        <div class="form-floating mb-3">
          <select class="form-select" id="deploymentModel" name="deployment_model">
            <?php foreach (["Client Application","On-premise","SaaS","Externally hosted"] as $model): ?>
              <option<?php if(($app['deployment_model'] ?? '')===$model) echo ' selected'; ?>><?php echo $model; ?></option>
            <?php endforeach; ?>
          </select>
          <label for="deploymentModel">Deployment model</label>
        </div>
        <div class="form-floating mb-3">
          <select class="form-select" id="integrations" name="integrations" onchange="toggleSADocument(this)">
            <?php foreach (["Not defined","Yes","No"] as $opt): ?>
              <option<?php if(($app['integrations'] ?? '')===$opt) echo ' selected'; ?>><?php echo $opt; ?></option>
            <?php endforeach; ?>
          </select>
          <label for="integrations">Integrations</label>
        </div>
        <div class="form-floating mb-3" id="sa_document_group" style="display: <?php echo (($app['integrations'] ?? '')==='Yes') ? 'block' : 'none'; ?>;">
          <input type="url" class="form-control" id="saDocument" name="sa_document" placeholder="S.A. Document" value="<?php echo htmlspecialchars($app['sa_document'] ?? ''); ?>">
          <label for="saDocument">S.A. Document</label>
        </div>
      </div>
    </div>
    <div class="form-floating mb-3">
      <textarea class="form-control" id="businessNeed" name="business_need" style="height: 100px" placeholder="Business need"><?php echo htmlspecialchars($app['business_need'] ?? ''); ?></textarea>
      <label for="businessNeed">Business need</label>
    </div>
    <div class="d-flex gap-2 mt-3">
      <button type="submit" class="btn btn-primary">Save</button>
      <a href="<?php echo $id > 0 ? 'app_view.php?id=' . $id : 'dashboard.php'; ?>" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
const tooltipMap = {
  0: '', 10: '10% - Early planning started', 20: '20% - Stakeholders identified', 30: '30% - Key data collected', 40: '40% - Requirements being defined', 50: '50% - Documentation in progress', 60: '60% - Infra/support needs mapped', 70: '70% - Ops model drafted', 80: '80% - Final review ongoing', 90: '90% - Ready for transition', 100: 'Completed'
};
function updateHandoverTooltip(slider) {
  const tooltip = document.getElementById('handoverTooltip');
  const value = parseInt(slider.value);
  const sliderWidth = slider.offsetWidth;
  const offset = sliderWidth * (value / 100);
  tooltip.style.left = `${offset}px`;
  tooltip.innerText = tooltipMap[value];
  tooltip.style.display = tooltipMap[value] ? 'block' : 'none';
}
function setPhase(value, button) {
  document.getElementById('phase_input').value = value;
  button.parentElement.querySelectorAll('button').forEach(btn => btn.classList.remove('active'));
  button.classList.add('active');
}
function setStatus(value, button) {
  document.getElementById('status_input').value = value;
  button.parentElement.querySelectorAll('button').forEach(btn => btn.classList.remove('active'));
  button.classList.add('active');
}
function toggleSADocument(select) {
  const saDoc = document.getElementById('sa_document_group');
  saDoc.style.display = select.value === 'Yes' ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', function () {
  // Info popovers
  const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
  popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
  });
  // Choices.js for multiple select
  const relationshipChoices = new Choices('#relationshipYggdrasil', {
    removeItemButton: true,
    placeholder: true,
    placeholderValue: 'Search for applications...',
    searchEnabled: true,
    searchChoices: false,
    searchFloor: 2,
    searchResultLimit: 4, // Limit to 4 visible results
    renderChoiceLimit: 4, // Limit rendered choices to 4
    shouldSort: false,
    callbackOnCreateTemplates: function(template) {
      return {
        choice: (classNames, data) => {
          return template(`
            <div class="${classNames.item} ${classNames.itemChoice} ${data.highlighted ? classNames.highlightedState : classNames.itemSelectable} custom-choice-item" data-select-text="${this.config.itemSelectText}" data-choice data-id="${data.id}" data-value="${data.value}" ${data.disabled ? 'data-choice-disabled aria-disabled="true"' : 'data-choice-selectable'} role="option">
              <div class="choice-content">
                <strong class="choice-title">${data.customProperties?.description || data.label}</strong>
                ${data.customProperties?.service ? `<small class="choice-subtitle">${data.customProperties.service}</small>` : ''}
              </div>
            </div>
          `);
        }
      };
    }
  });

  // Search functionality
  let searchTimeout;
  document.getElementById('relationshipYggdrasil').addEventListener('search', function(e) {
    const query = e.detail.value;
    
    if (query.length < 2) {
      relationshipChoices.clearChoices();
      return;
    }

    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      const currentAppId = <?php echo $id; ?>;
      const url = `api/search_applications.php?q=${encodeURIComponent(query)}&exclude=${currentAppId}`;
      
      console.log('Making API request to:', url); // Debug output
      
      fetch(url)
        .then(response => {
          console.log('Response status:', response.status); // Debug output
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          console.log('Search results:', data); // Debug output
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
});
</script>
</body>
</html>
