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
            $data[$f] = trim($_POST[$f] ?? '');
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
                if (isset($oldValues[$field]) && $oldValues[$field] !== $data[$field]) {
                    $activityManager->logFieldChange(
                        $currentAppId,
                        $field,
                        $oldValues[$field],
                        $data[$field],
                        $_SESSION['user_id']
                    );
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
  <title>Application Registration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/components/user-dropdown.css">
  <link rel="stylesheet" href="../assets/css/components/activity-tracker.css">
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
    <!-- Work Notes Form - Only show when editing existing applications -->
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
          
          <div class="row g-3">
            <div class="col-md-6">
              <div class="form-group-horizontal">
                <label for="work-note-file" class="form-label">Attachment (Optional)</label>
                <input type="file" class="form-control" id="work-note-file" name="attachment">
                <div id="file-info" class="file-info"></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group-horizontal">
                <label for="work-note-type" class="form-label">Type of note</label>
                <select class="form-select" id="work-note-type" name="type">
                  <option value="comment">Comment</option>
                  <option value="change">Change</option>
                  <option value="problem">Problem</option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="d-flex justify-content-end mt-3">
            <button type="submit" class="btn btn-primary">
              Post
            </button>
          </div>
        </form>
      </div>
    <?php endif; ?>
    
    <!-- Activity Tracker Section - Only show when editing existing applications -->
    <div class="row mt-4">
      <div class="col-12">
        <?php 
        $application_id = $id; 
        include __DIR__ . '/shared/activity_tracker.php'; 
        ?>
      </div>
    </div>
  <?php endif; ?>
</div>
<script>
// Set current app ID for JavaScript modules
window.currentAppId = <?php echo $id; ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script src="../assets/js/components/activity-tracker.js"></script>
<script src="../assets/js/components/form-handlers.js"></script>
<script src="../assets/js/components/choices-init.js"></script>
<script src="../assets/js/pages/app-form.js"></script>
</body>
</html>
