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
if (isset($app['relationship_yggdrasil'])) {
    $app['relationship_yggdrasil'] = array_map('trim', explode(',', $app['relationship_yggdrasil']));
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
    .form-floating > .input-group > .form-control, .form-floating > .input-group > .form-select { height: calc(3.5rem + 2px); line-height: 1.25; }
    .form-floating > .input-group > .form-control:focus, .form-floating > .input-group > .form-control:not(:placeholder-shown) { border-color: #86b7fe; box-shadow: 0 0 0 .2rem rgba(13,110,253,.25); }
    .form-floating > .input-group > label { left: 0.75rem; z-index: 2; pointer-events: none; transition: all .1s ease-in-out; opacity: .65; background: white; padding: 0 .25em; }
    .form-floating > .input-group > .form-control:focus ~ label, .form-floating > .input-group > .form-control:not(:placeholder-shown) ~ label { opacity: 1; transform: scale(.85) translateY(-0.85rem) translateX(0.15rem); background: white; padding: 0 .25em; z-index: 3; }
    .form-floating .input-group-text, .form-floating .btn { z-index: 4; }
    .input-group .btn.info-btn { border-top-left-radius: 0; border-bottom-left-radius: 0; }
    .input-group .form-control { border-right: 0; }
    .input-group .btn { border-left: 0; }
    .choices__inner { min-height: calc(3.5rem + 2px); padding-top: 1rem; }
    @media (max-width: 767px) { .row { gap: 0 !important; } }
    .form-range {
      width: 100%;
      background-color: transparent;
      margin-bottom: 0.5rem;
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
  </style>
</head>
<body class="bg-light">
<!-- Topbar -->
<?php $topbar_search_disabled = true; include __DIR__ . '/shared/topbar.php'; ?>
<div class="container">
  <h2>View Application</h2>
  <form autocomplete="off">
    <div class="row g-3">
      <!-- Venstre kolonne -->
      <div class="col-md-6">
        <div class="form-floating mb-3">
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
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="applicationService" name="application_service" placeholder="Application service" value="<?php echo htmlspecialchars($app['application_service']); ?>" readonly>
          <label for="applicationService">Application service</label>
        </div>
        <div class="form-floating mb-3">
          <select class="form-select" id="relevantFor" name="relevant_for" disabled>
            <option<?php if($app['relevant_for']==='To be decided') echo ' selected'; ?>>To be decided</option>
            <option<?php if($app['relevant_for']==='Yggdrasil') echo ' selected'; ?>>Yggdrasil</option>
            <option<?php if($app['relevant_for']==='Not relevant') echo ' selected'; ?>>Not relevant</option>
          </select>
          <label for="relevantFor">Relevant for</label>
        </div>
        <div class="mb-3">
          <label class="form-label d-block">Phase</label>
          <input type="hidden" name="phase" id="phase_input" value="<?php echo htmlspecialchars($app['phase']); ?>">
          <div class="btn-group w-100" role="group" aria-label="Phase">
            <?php foreach (["Need","Solution","Build","Implement","Operate"] as $phase): ?>
              <button type="button" class="btn btn-outline-primary<?php if($app['phase']===$phase) echo ' active'; ?>" disabled><?php echo $phase; ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label d-block">Status</label>
          <input type="hidden" name="status" id="status_input" value="<?php echo htmlspecialchars($app['status']); ?>">
          <div class="btn-group w-100" role="group" aria-label="Status">
            <?php foreach (["Unknown","Not started","Ongoing Work","On Hold","Completed"] as $status): ?>
              <button type="button" class="btn btn-outline-secondary<?php if($app['status']===$status) echo ' active'; ?>" disabled><?php echo $status; ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="mb-3 position-relative">
          <label class="form-label d-block">Handover status</label>
          <input type="range" class="form-range" min="0" max="100" step="10" name="handover_status" value="<?php echo htmlspecialchars($app['handover_status']); ?>" disabled>
          <div id="handoverTooltip" class="tooltip-follow">Tooltip</div>
        </div>
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="contractNumber" name="contract_number" placeholder="Contract number" value="<?php echo htmlspecialchars($app['contract_number']); ?>" readonly>
          <label for="contractNumber">Contract number</label>
        </div>
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="contractResponsible" name="contract_responsible" placeholder="Contract responsible" value="<?php echo htmlspecialchars($app['contract_responsible']); ?>" readonly>
          <label for="contractResponsible">Contract responsible</label>
        </div>
        <div class="form-floating mb-3">
          <input type="url" class="form-control" id="informationSpace" name="information_space" placeholder="Information Space" value="<?php echo htmlspecialchars($app['information_space']); ?>" readonly>
          <label for="informationSpace">Information Space</label>
        </div>
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="baSharepoint" name="ba_sharepoint" placeholder="BA Sharepoint list" value="<?php echo htmlspecialchars($app['ba_sharepoint']); ?>" readonly>
          <label for="baSharepoint">BA Sharepoint list</label>
        </div>
        <div class="mb-3">
          <select class="form-select" id="relationshipYggdrasil" name="relationship_yggdrasil[]" multiple disabled>
            <?php foreach (["Engineering Base","AIM Tool","SolutionSeeker","Energy Components","Infield"] as $rel): ?>
              <option<?php if(in_array($rel, (array)$app['relationship_yggdrasil'])) echo ' selected'; ?>><?php echo $rel; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <!-- Høyre kolonne -->
      <div class="col-md-6">
        <div class="form-floating mb-3">
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
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="preOpsPortfolio" name="preops_portfolio" placeholder="Pre-ops portfolio" value="<?php echo htmlspecialchars($app['preops_portfolio']); ?>" readonly>
          <label for="preOpsPortfolio">Pre-ops portfolio</label>
        </div>
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="applicationPortfolio" name="application_portfolio" placeholder="Application Portfolio" value="<?php echo htmlspecialchars($app['application_portfolio']); ?>" readonly>
          <label for="applicationPortfolio">Application Portfolio</label>
        </div>
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="deliveryResponsible" name="delivery_responsible" placeholder="Delivery responsible" value="<?php echo htmlspecialchars($app['delivery_responsible']); ?>" readonly>
          <label for="deliveryResponsible">Delivery responsible</label>
        </div>
        <div class="form-floating mb-3">
          <input type="url" class="form-control" id="corporatorLink" name="corporator_link" placeholder="Link to Corporator" value="<?php echo htmlspecialchars($app['corporator_link']); ?>" readonly>
          <label for="corporatorLink">Link to Corporator</label>
        </div>
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="projectManager" name="project_manager" placeholder="Project manager" value="<?php echo htmlspecialchars($app['project_manager']); ?>" readonly>
          <label for="projectManager">Project manager</label>
        </div>
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="productOwner" name="product_owner" placeholder="Product owner" value="<?php echo htmlspecialchars($app['product_owner']); ?>" readonly>
          <label for="productOwner">Product owner</label>
        </div>
        <div class="form-floating mb-3">
          <input type="date" class="form-control" id="dueDate" name="due_date" placeholder="Due date" value="<?php echo htmlspecialchars($app['due_date']); ?>" readonly>
          <label for="dueDate">Due date</label>
        </div>
        <div class="form-floating mb-3">
          <select class="form-select" id="deploymentModel" name="deployment_model" disabled>
            <?php foreach (["Client Application","On-premise","SaaS","Externally hosted"] as $model): ?>
              <option<?php if($app['deployment_model']===$model) echo ' selected'; ?>><?php echo $model; ?></option>
            <?php endforeach; ?>
          </select>
          <label for="deploymentModel">Deployment model</label>
        </div>
        <div class="form-floating mb-3">
          <select class="form-select" id="integrations" name="integrations" disabled>
            <?php foreach (["Not defined","Yes","No"] as $opt): ?>
              <option<?php if($app['integrations']===$opt) echo ' selected'; ?>><?php echo $opt; ?></option>
            <?php endforeach; ?>
          </select>
          <label for="integrations">Integrations</label>
        </div>
        <div class="form-floating mb-3" id="sa_document_group" style="display: <?php echo ($app['integrations']==='Yes') ? 'block' : 'none'; ?>;">
          <input type="url" class="form-control" id="saDocument" name="sa_document" placeholder="S.A. Document" value="<?php echo htmlspecialchars($app['sa_document']); ?>" readonly>
          <label for="saDocument">S.A. Document</label>
        </div>
      </div>
    </div>
    <div class="form-floating mb-3">
      <textarea class="form-control" id="businessNeed" name="business_need" style="height: 100px" placeholder="Business need" readonly><?php echo htmlspecialchars($app['business_need']); ?></textarea>
      <label for="businessNeed">Business need</label>
    </div>
    <div class="d-flex gap-2 mt-3">
      <a href="dashboard.php" class="btn btn-secondary">Back</a>
      <?php if (isset($_SESSION['user_role'])) { $role = $_SESSION['user_role']; } else { $role = null; } ?>
      <?php if ($role === 'admin' || $role === 'editor') : ?>
        <a href="app_form.php?id=<?php echo $id; ?>" class="btn btn-primary">Edit</a>
      <?php endif; ?>
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
document.addEventListener('DOMContentLoaded', function () {
  // Show tooltip for handover status
  const slider = document.querySelector('input[type="range"][name="handover_status"]');
  if (slider) updateHandoverTooltip(slider);
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
