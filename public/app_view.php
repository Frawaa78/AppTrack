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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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
  </style>
</head>
<body class="bg-light">
<!-- Topbar -->
<?php include __DIR__ . '/shared/topbar.php'; ?>
<div class="container">
  <div class="header-with-buttons">
    <div>
      <h5 class="mb-0">Status & Details</h5>
    </div>
    <div class="header-buttons">
      <button type="button" class="btn btn-info me-2" onclick="openAIAnalysis()" title="AI Analysis">
        <i class="bi bi-robot"></i> AI Insights
      </button>
      <a href="dashboard.php" class="btn btn-secondary">Back</a>
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
            <option<?php if($app['relevant_for']==='Project relevant') echo ' selected'; ?>>Project relevant</option>
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
            <a href="<?php echo htmlspecialchars($app['sa_document']); ?>" target="_blank" rel="noopener noreferrer" class="form-control" style="position: relative; text-decoration: none; color: #0d6efd; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; display: block !important; max-width: 100% !important; padding-right: 2.5rem !important;">
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
<script src="../assets/js/pages/app-view.js"></script>
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
</script>

<!-- AI Analysis Modal -->
<div class="modal fade" id="aiAnalysisModal" tabindex="-1" aria-labelledby="aiAnalysisModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="aiAnalysisModalLabel">
          <i class="bi bi-robot"></i> AI Analysis & Insights
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Analysis Type Selection -->
        <div class="analysis-controls mb-4">
          <div class="row">
            <div class="col-md-8">
              <label for="analysisType" class="form-label">Analysis Type</label>
              <select class="form-select" id="analysisType">
                <option value="summary">📋 Application Summary</option>
                <option value="timeline">⏱️ Timeline Analysis</option>
                <option value="risk_assessment">⚠️ Risk Assessment</option>
                <option value="relationship_analysis">🔗 Relationship Analysis</option>
                <option value="trend_analysis">📈 Trend Analysis</option>
              </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <button class="btn btn-primary me-2" onclick="generateAnalysis()">
                <i class="bi bi-magic"></i> Generate
              </button>
              <button class="btn btn-outline-primary me-2" onclick="generateAnalysis(true)" title="Force new analysis even if no changes detected">
                <i class="bi bi-arrow-clockwise"></i>
              </button>
              <button class="btn btn-outline-secondary" onclick="loadRecentAnalyses()" title="Load previous analysis">
                <i class="bi bi-clock-history"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Analysis Content -->
        <div id="analysisContent">
          <div class="text-center text-muted p-5">
            <i class="bi bi-robot" style="font-size: 3rem;"></i>
            <p class="mt-3">Select an analysis type and click "Generate" to get AI insights about this application.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Set current app ID for AI analysis
window.currentAppId = <?php echo $id; ?>;

// Open AI Analysis Modal
function openAIAnalysis() {
    const modal = new bootstrap.Modal(document.getElementById('aiAnalysisModal'));
    modal.show();
    
    // Load recent analyses when modal opens and check if Generate should be disabled
    loadRecentAnalyses();
    checkGenerateButtonState();
}

// Generate new AI analysis
async function generateAnalysis(forceRefresh = false) {
    const analysisType = document.getElementById('analysisType').value;
    const contentDiv = document.getElementById('analysisContent');
    const generateBtn = document.querySelector('button[onclick="generateAnalysis()"]');
    
    console.log('=== generateAnalysis called ===');
    console.log('Parameters:', { analysisType, forceRefresh, appId: window.currentAppId });
    
    // Check if button is disabled and not forcing refresh
    if (!forceRefresh && generateBtn && generateBtn.disabled) {
        console.log('Generate button is disabled, showing tooltip info');
        // Show a gentle message instead of generating
        const contentDiv = document.getElementById('analysisContent');
        contentDiv.innerHTML = `
            <div class="alert alert-info">
                <h6><i class="bi bi-info-circle"></i> Analysis Already Available</h6>
                <p>A recent analysis already exists for this application. The analysis is current and up-to-date.</p>
                <p><strong>Options:</strong></p>
                <ul>
                    <li>View the existing analysis above</li>
                    <li>Use <strong>"Force Refresh"</strong> button to generate a new analysis anyway</li>
                    <li>Wait for the analysis to become outdated (10+ minutes old)</li>
                </ul>
            </div>
        `;
        return;
    }
    
    // Show loading state
    contentDiv.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Generating analysis...</span>
            </div>
            <p class="mt-3">🤖 AI is analyzing the application data...</p>
            <small class="text-muted">This may take 10-30 seconds</small>
        </div>
    `;
    
    try {
        const requestBody = {
            application_id: window.currentAppId,
            analysis_type: analysisType,
            force_refresh: forceRefresh
        };
        
        console.log('Sending request to API...');
        
        const response = await fetch('api/ai_analysis.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestBody)
        });
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('API Response:', data);
        
        if (data.success && data.data) {
            console.log('✅ Analysis successful, displaying result...');
            displayAnalysisResult(data.data);
            
            // Update the latest analysis data and recheck button state
            window.latestAnalysisData = data.data;
            checkGenerateButtonState();
        } else {
            throw new Error(data.error || 'Analysis failed - no data returned');
        }
        
    } catch (error) {
        console.error('❌ AI Analysis Error:', error);
        contentDiv.innerHTML = `
            <div class="alert alert-danger">
                <h6>❌ Analysis Failed</h6>
                <p>Error: ${error.message}</p>
                <div class="mt-3">
                    <button class="btn btn-outline-danger btn-sm me-2" onclick="generateAnalysis(true)">
                        🔄 Try Again (Force Refresh)
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="loadRecentAnalyses()">
                        📋 Load Previous Analysis
                    </button>
                </div>
            </div>
        `;
    }
}

// Display analysis result
function displayAnalysisResult(analysisData) {
    const contentDiv = document.getElementById('analysisContent');
    
    console.log('=== displayAnalysisResult called ===');
    console.log('Input analysisData:', analysisData);
    
    try {
        // Determine which data source to use
        let result = null;
        let analysisContent = '';
        
        // Priority order for finding the actual analysis content
        if (analysisData.result && analysisData.result.type === 'text' && analysisData.result.data && analysisData.result.data.analysis) {
            console.log('✅ Using analysisData.result.data.analysis');
            analysisContent = analysisData.result.data.analysis;
        } else if (analysisData.analysis_result && analysisData.analysis_result.type === 'text' && analysisData.analysis_result.data && analysisData.analysis_result.data.analysis) {
            console.log('✅ Using analysisData.analysis_result.data.analysis');
            analysisContent = analysisData.analysis_result.data.analysis;
        } else if (analysisData.result && analysisData.result.raw_content) {
            console.log('✅ Using analysisData.result.raw_content');
            analysisContent = analysisData.result.raw_content;
        } else if (analysisData.analysis_result && analysisData.analysis_result.raw_content) {
            console.log('✅ Using analysisData.analysis_result.raw_content');
            analysisContent = analysisData.analysis_result.raw_content;
        } else if (analysisData.analysis_result && typeof analysisData.analysis_result === 'string') {
            console.log('✅ Using analysisData.analysis_result as string');
            analysisContent = analysisData.analysis_result;
        } else if (analysisData.analysis_result && analysisData.analysis_result.data && typeof analysisData.analysis_result.data === 'string') {
            console.log('✅ Using analysisData.analysis_result.data as string');
            analysisContent = analysisData.analysis_result.data;
        } else if (typeof analysisData === 'string') {
            console.log('✅ Using analysisData as string');
            analysisContent = analysisData;
        } else {
            console.log('❌ No recognized analysis content found');
            console.log('Available keys in analysisData:', Object.keys(analysisData));
            if (analysisData.result) console.log('Available keys in result:', Object.keys(analysisData.result));
            if (analysisData.analysis_result) console.log('Available keys in analysis_result:', Object.keys(analysisData.analysis_result));
            
            // Try to parse JSON if analysis_result looks like JSON string
            if (analysisData.analysis_result && typeof analysisData.analysis_result === 'string' && analysisData.analysis_result.startsWith('{')) {
                try {
                    const parsed = JSON.parse(analysisData.analysis_result);
                    if (parsed.data && parsed.data.analysis) {
                        console.log('✅ Parsed JSON and found analysis content');
                        analysisContent = parsed.data.analysis;
                    } else if (parsed.analysis) {
                        console.log('✅ Parsed JSON and found direct analysis');
                        analysisContent = parsed.analysis;
                    } else if (parsed.raw_content) {
                        console.log('✅ Parsed JSON and found raw_content');
                        analysisContent = parsed.raw_content;
                    } else if (typeof parsed === 'string') {
                        console.log('✅ Parsed JSON but result is string');
                        analysisContent = parsed;
                    }
                } catch (e) {
                    console.log('Failed to parse JSON:', e);
                    // If JSON parsing fails, try using the string directly
                    analysisContent = analysisData.analysis_result;
                }
            }
            
            // If still no content, try some more fallback options
            if (!analysisContent && analysisData.analysis_result && typeof analysisData.analysis_result === 'string') {
                console.log('✅ Using analysis_result string directly as fallback');
                analysisContent = analysisData.analysis_result;
            }
        }
        
        console.log('Final analysisContent:', analysisContent ? 'Found content' : 'No content');
        
        let htmlContent = `
            <div class="analysis-result">
                <div class="analysis-header mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6>📊 ${(analysisData.analysis_type || 'UNKNOWN').replace('_', ' ').toUpperCase()} Analysis</h6>
                        <div class="analysis-meta">
                            <small class="text-muted">
                                ${analysisData.cached ? '💾 Cached' : '✨ Fresh'} • 
                                ${analysisData.processing_time_ms || 0}ms • 
                                ${analysisData.created_at ? new Date(analysisData.created_at).toLocaleString() : 'Unknown time'}
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="analysis-content">
        `;
        
        if (analysisContent) {
            htmlContent += `<div class="analysis-text">${formatTextContent(analysisContent)}</div>`;
        } else {
            // Fallback: show raw data for debugging
            htmlContent += `
                <div class="alert alert-warning">
                    <h6>⚠️ Analysis Content Not Found</h6>
                    <p>The analysis was generated but the content format is not recognized.</p>
                    <details>
                        <summary>Debug Information</summary>
                        <pre>${JSON.stringify(analysisData, null, 2)}</pre>
                    </details>
                </div>
            `;
        }
        
        htmlContent += `
                </div>
                
                <div class="analysis-actions mt-4">
                    <button class="btn btn-outline-primary btn-sm" onclick="generateAnalysis(true)">
                        🔄 Refresh Analysis
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="exportAnalysis()">
                        📄 Export
                    </button>
                </div>
            </div>
        `;
        
        contentDiv.innerHTML = htmlContent;
        console.log('✅ Analysis displayed successfully');
        
    } catch (error) {
        console.error('❌ Error displaying analysis result:', error);
        contentDiv.innerHTML = `
            <div class="alert alert-danger">
                <h6>❌ Display Error</h6>
                <p>Failed to display analysis result: ${error.message}</p>
                <details>
                    <summary>Raw data</summary>
                    <pre>${JSON.stringify(analysisData, null, 2)}</pre>
                </details>
            </div>
        `;
    }
}

// Format structured data for display
function formatStructuredData(data) {
    let html = '';
    
    // Ensure data is an object
    if (!data || typeof data !== 'object') {
        return `<p class="text-muted">No structured data available</p>`;
    }
    
    for (const [key, value] of Object.entries(data)) {
        html += `<div class="data-section mb-3">`;
        html += `<h6 class="section-title">${String(key).replace(/_/g, ' ').toUpperCase()}</h6>`;
        
        if (Array.isArray(value)) {
            html += '<ul class="list-unstyled">';
            value.forEach(item => {
                if (typeof item === 'object' && item !== null) {
                    html += `<li class="mb-2"><pre>${JSON.stringify(item, null, 2)}</pre></li>`;
                } else {
                    html += `<li class="mb-1">• ${formatTextContent(item)}</li>`;
                }
            });
            html += '</ul>';
        } else if (typeof value === 'object' && value !== null) {
            html += `<div class="nested-data">${formatStructuredData(value)}</div>`;
        } else {
            html += `<p class="section-text">${formatTextContent(value)}</p>`;
        }
        
        html += '</div>';
    }
    
    return html;
}

// Format text content with line breaks and markdown
function formatTextContent(text) {
    if (!text) return '';
    
    // Ensure text is a string
    if (typeof text !== 'string') {
        text = String(text);
    }
    
    // Convert markdown-style formatting to HTML
    return text
        .replace(/\n\n/g, '<br><br>')                        // Double line breaks become paragraph breaks
        .replace(/\n/g, '<br>')                              // Single line breaks
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')    // Bold text
        .replace(/### (.*?)(?=\n|<br>|$)/g, '<h5 class="mt-4 mb-3">$1</h5>')   // H3 headers with spacing
        .replace(/## (.*?)(?=\n|<br>|$)/g, '<h4 class="mt-4 mb-3">$1</h4>')    // H2 headers with spacing  
        .replace(/# (.*?)(?=\n|<br>|$)/g, '<h3 class="mt-4 mb-3">$1</h3>')     // H1 headers with spacing
        .replace(/^\- (.*?)(?=\n|<br>|$)/gm, '<li>$1</li>')  // List items
        .replace(/(<li>.*?<\/li>)/gs, '<ul class="mb-3">$1</ul>')         // Wrap lists with spacing
        .replace(/<\/ul><br><ul>/g, '')                       // Merge adjacent lists
        .replace(/<br><h/g, '<h')                            // Clean up headers
        .replace(/<\/h([3-5])><br>/g, '</h$1>')              // Clean up after headers
        .replace(/(<\/p>|<\/ul>|<\/h[3-5]>)(<h[3-5]>|<p>)/g, '$1<div class="my-3"></div>$2'); // Add spacing between sections
}

// Load recent analyses
async function loadRecentAnalyses() {
    console.log('=== loadRecentAnalyses called ===');
    
    try {
        const response = await fetch(`api/get_ai_analysis.php?application_id=${window.currentAppId}&limit=5`);
        const data = await response.json();
        
        console.log('API Response:', data);
        
        if (data.success && data.data && data.data.length > 0) {
            console.log(`Found ${data.data.length} recent analyses`);
            
            // Display the most recent analysis automatically
            const mostRecent = data.data[0];
            console.log('Most recent analysis:', mostRecent);
            
            if (mostRecent) {
                // Format the data correctly for display
                const formattedData = {
                    result: mostRecent.analysis_result,  // This should now be parsed JSON
                    analysis_type: mostRecent.analysis_type || 'summary',
                    cached: true,
                    processing_time_ms: mostRecent.processing_time_ms || 0,
                    created_at: mostRecent.created_at
                };
                
                console.log('Formatted data for display:', formattedData);
                displayAnalysisResult(formattedData);
                
                // Store the latest analysis data for comparison
                window.latestAnalysisData = mostRecent;
            } else {
                console.log('No mostRecent found');
                showNoAnalysisMessage();
                window.latestAnalysisData = null;
            }
        } else {
            console.log('No analyses found or API failed');
            showNoAnalysisMessage();
            window.latestAnalysisData = null;
        }
    } catch (error) {
        console.error('Failed to load recent analyses:', error);
        showNoAnalysisMessage();
        window.latestAnalysisData = null;
    }
}

// Helper function to show "no analysis" message
function showNoAnalysisMessage() {
    const contentDiv = document.getElementById('analysisContent');
    contentDiv.innerHTML = `
        <div class="text-center text-muted p-5">
            <i class="bi bi-robot" style="font-size: 3rem;"></i>
            <p class="mt-3">No previous analyses found. Select an analysis type and click "Generate" to get AI insights.</p>
            <small class="text-muted">The system will automatically check for changes before generating new analyses to save OpenAI tokens.</small>
        </div>
    `;
}

// Export analysis (placeholder)
function exportAnalysis() {
    alert('Export functionality coming soon!');
}

// Check if Generate button should be enabled/disabled
async function checkGenerateButtonState() {
    console.log('=== checkGenerateButtonState called ===');
    
    const generateBtn = document.querySelector('button[onclick="generateAnalysis()"]');
    if (!generateBtn) {
        console.log('❌ Generate button not found');
        return;
    }
    
    // If no previous analysis exists, enable the button
    if (!window.latestAnalysisData) {
        console.log('✅ No previous analysis found, enabling Generate button');
        enableGenerateButton(generateBtn, 'No previous analysis available');
        return;
    }
    
    try {
        const lastAnalysisDate = new Date(window.latestAnalysisData.created_at);
        console.log('📅 Last analysis date:', lastAnalysisDate);
        console.log('📋 Latest analysis data:', window.latestAnalysisData);
        
        // Check application update date
        console.log('🔍 Checking application update date...');
        const response = await fetch(`api/get_application_info.php?id=${window.currentAppId}`);
        console.log('📡 Application info response status:', response.status);
        
        if (!response.ok) {
            console.log('❌ Failed to fetch application info, enabling button as fallback');
            enableGenerateButton(generateBtn, 'Unable to verify application updates');
            return;
        }
        
        const appData = await response.json();
        console.log('📦 Application data received:', appData);
        
        if (!appData.success) {
            console.log('❌ Application data request failed:', appData.error);
            enableGenerateButton(generateBtn, 'Unable to verify application updates');
            return;
        }
        
        // Check if application was updated after last analysis
        const appUpdatedAt = new Date(appData.data.updated_at || appData.data.created_at);
        console.log('📅 Application updated at:', appUpdatedAt);
        console.log('⏰ Time comparison: App updated', appUpdatedAt, 'vs Analysis', lastAnalysisDate);
        
        if (appUpdatedAt > lastAnalysisDate) {
            console.log('✅ Application updated since last analysis, enabling Generate button');
            enableGenerateButton(generateBtn, 'Application has been updated');
            return;
        }
        
        // Check for new work notes
        console.log('🔍 Checking for new work notes...');
        const workNotesResponse = await fetch(`api/get_latest_work_note.php?application_id=${window.currentAppId}`);
        console.log('📡 Work notes response status:', workNotesResponse.status);
        
        if (!workNotesResponse.ok) {
            console.log('❌ Failed to fetch work notes, enabling button as fallback');
            enableGenerateButton(generateBtn, 'Unable to verify work notes');
            return;
        }
        
        const workNotesData = await workNotesResponse.json();
        console.log('📝 Work notes data received:', workNotesData);
        
        if (workNotesData.success && workNotesData.data && workNotesData.data.created_at) {
            const latestWorkNoteDate = new Date(workNotesData.data.created_at);
            console.log('📅 Latest work note date:', latestWorkNoteDate);
            console.log('⏰ Time comparison: Work note', latestWorkNoteDate, 'vs Analysis', lastAnalysisDate);
            
            if (latestWorkNoteDate > lastAnalysisDate) {
                console.log('✅ New work notes found since last analysis, enabling Generate button');
                enableGenerateButton(generateBtn, 'New work notes available');
                return;
            }
        } else {
            console.log('ℹ️ No work notes found or no date available');
        }
        
        // No changes detected, disable the button
        console.log('🔒 No changes detected since last analysis, disabling Generate button');
        disableGenerateButton(generateBtn, 'Analysis is up-to-date');
        
    } catch (error) {
        console.error('❌ Error checking Generate button state:', error);
        // On error, enable the button as a fallback
        enableGenerateButton(generateBtn, 'Error checking changes - manual override available');
    }
}

// Enable Generate button with reason
function enableGenerateButton(button, reason) {
    button.disabled = false;
    button.classList.remove('btn-secondary');
    button.classList.add('btn-primary');
    button.title = `Generate Analysis - ${reason}`;
    button.style.opacity = '1'; // Reset opacity
    console.log(`Generate button enabled: ${reason}`);
}

// Disable Generate button with reason
function disableGenerateButton(button, reason) {
    button.disabled = true;
    button.classList.remove('btn-primary');
    button.classList.add('btn-secondary');
    button.title = `Analysis is current - ${reason}. No application or work note changes detected. Use "Force Refresh" to generate anyway.`;
    
    // Keep original text but make it visually disabled
    button.style.opacity = '0.6';
    console.log(`Generate button disabled: ${reason}`);
}
</script>

</body>
</html>
