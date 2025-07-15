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
    
    /* Form labels positioned to the left */
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
    
    .input-group .btn.info-btn { border-top-left-radius: 0; border-bottom-left-radius: 0; }
    @media (max-width: 767px) { .row { gap: 0 !important; } }
    .form-range {
      width: 100%;
      background-color: transparent;
      margin-bottom: 0.5rem;
    }
    .form-range::-webkit-slider-runnable-track {
      height: 0.5rem;
      background: linear-gradient(to right, #A9ADAF 0%, #A9ADAF var(--progress, 0%), #f1f3f5 var(--progress, 0%), #f1f3f5 100%);
      border-radius: 0.25rem;
    }
    .form-range::-moz-range-track {
      height: 0.5rem;
      background: #f1f3f5;
      border-radius: 0.25rem;
    }
    .form-range::-moz-range-progress {
      height: 0.5rem;
      background: #A9ADAF;
      border-radius: 0.25rem;
    }
    .form-range::-ms-fill-lower {
      height: 0.5rem;
      background: #A9ADAF;
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
    .btn.active:disabled {
      background-color: #0d6efd !important;
      border-color: #0d6efd !important;
      color: white !important;
      opacity: 1 !important;
    }
    
    .btn-outline-secondary.active:disabled {
      background-color: #6c757d !important;
      border-color: #6c757d !important;
      color: white !important;
      opacity: 1 !important;
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
    
    /* Clickable URL links styling */
    a.form-control {
      background-color: #f8f9fa !important;
      border: 1px solid #dee2e6 !important;
      transition: all 0.2s ease;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    
    a.form-control:hover {
      background-color: #e3f2fd !important;
      border-color: #2196f3 !important;
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    a.form-control:focus {
      box-shadow: 0 0 0 0.2rem rgba(33, 150, 243, 0.25) !important;
      border-color: #2196f3 !important;
    }
  </style>
</head>
<body class="bg-light">
<!-- Topbar -->
<?php $topbar_search_disabled = true; include __DIR__ . '/shared/topbar.php'; ?>
<div class="container">
  <div class="header-with-buttons">
    <div></div>
    <div class="header-buttons">
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
            <input type="range" class="form-range" min="0" max="100" step="10" name="handover_status" value="<?php echo htmlspecialchars($app['handover_status']); ?>" disabled>
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
            <a href="<?php echo htmlspecialchars($app['information_space']); ?>" target="_blank" rel="noopener noreferrer" class="form-control" style="display: flex; align-items: center; text-decoration: none; color: #0d6efd;">
              <?php echo htmlspecialchars($app['information_space']); ?>
              <i class="bi bi-box-arrow-up-right ms-2"></i>
            </a>
          <?php else: ?>
            <input type="url" class="form-control" id="informationSpace" name="information_space" placeholder="Information Space" value="" readonly>
          <?php endif; ?>
        </div>
        <div class="form-group-horizontal">
          <label for="baSharepoint" class="form-label">BA Sharepoint list</label>
          <?php if (!empty($app['ba_sharepoint_list'])): ?>
            <a href="<?php echo htmlspecialchars($app['ba_sharepoint_list']); ?>" target="_blank" rel="noopener noreferrer" class="form-control" style="display: flex; align-items: center; text-decoration: none; color: #0d6efd;">
              <?php echo htmlspecialchars($app['ba_sharepoint_list']); ?>
              <i class="bi bi-box-arrow-up-right ms-2"></i>
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
            <a href="<?php echo htmlspecialchars($app['corporator_link']); ?>" target="_blank" rel="noopener noreferrer" class="form-control" style="display: flex; align-items: center; text-decoration: none; color: #0d6efd;">
              <?php echo htmlspecialchars($app['corporator_link']); ?>
              <i class="bi bi-box-arrow-up-right ms-2"></i>
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
            <a href="<?php echo htmlspecialchars($app['sa_document']); ?>" target="_blank" rel="noopener noreferrer" class="form-control" style="display: flex; align-items: center; text-decoration: none; color: #0d6efd;">
              <?php echo htmlspecialchars($app['sa_document']); ?>
              <i class="bi bi-box-arrow-up-right ms-2"></i>
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
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
const tooltipMap = {
  0: '', 10: '10% - Early planning started', 20: '20% - Stakeholders identified', 30: '30% - Key data collected', 40: '40% - Requirements being defined', 50: '50% - Documentation in progress', 60: '60% - Infra/support needs mapped', 70: '70% - Ops model drafted', 80: '80% - Final review ongoing', 90: '90% - Ready for transition', 100: 'Completed'
};
function updateHandoverTooltip(slider) {
  const tooltip = document.getElementById('handoverTooltip');
  const container = slider.parentElement;
  const value = parseInt(slider.value);
  
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
}
document.addEventListener('DOMContentLoaded', function () {
  // Show tooltip for handover status
  const slider = document.querySelector('input[type="range"][name="handover_status"]');
  if (slider) {
    // Initialize progress CSS property
    const value = parseInt(slider.value);
    const progress = ((value - slider.min) / (slider.max - slider.min)) * 100;
    slider.style.setProperty('--progress', `${progress}%`);
    
    updateHandoverTooltip(slider);
  }
  // Choices.js for multiple select (readonly)
  new Choices('#relationshipYggdrasil', {
    removeItemButton: false,
    placeholder: true,
    placeholderValue: 'Select relationship(s)...',
    shouldSort: false,
    searchEnabled: false,
    itemSelectText: '',
    renderChoiceLimit: -1
  });
});
</script>
</body>
</html>
