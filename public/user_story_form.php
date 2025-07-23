<?php
// public/user_story_form.php
require_once __DIR__ . '/../src/db/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Check if editing existing story
$editMode = false;
$story = null;
$storyId = null;
$preselectedApplicationId = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $storyId = (int)$_GET['id'];
    $stmt = $db->prepare('SELECT * FROM user_stories WHERE id = :id');
    $stmt->execute([':id' => $storyId]);
    $story = $stmt->fetch();
    
    if ($story) {
        $editMode = true;
    } else {
        header('Location: user_stories.php');
        exit;
    }
}

// Check for preselected application from URL
if (isset($_GET['application_id']) && !empty($_GET['application_id'])) {
    $preselectedApplicationId = (int)$_GET['application_id'];
}

// Get applications for dropdown
$stmt = $db->prepare('SELECT id, short_description FROM applications ORDER BY short_description');
$stmt->execute();
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editMode ? 'Edit' : 'New'; ?> User Story - AppTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- FontAwesome Pro -->
    <script src="https://kit.fontawesome.com/d67c79608d.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/components/forms.css">
    <link rel="stylesheet" href="../assets/css/components/buttons.css">
    <style>
        /* Match app_view.php button styling */
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

        /* Form card styling to match app_view.php */
        .form-card {
            background-color: #FCFCFC;
            border: 1px solid #F0F1F2;
            border-radius: 6px;
            padding: 2rem;
        }

        /* Form container styling */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'shared/topbar.php'; ?>
    
    <div class="container">
        <div class="header-with-buttons">
            <div class="d-flex align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-journal-plus"></i>
                    <?php echo $editMode ? 'Edit User Story' : 'New User Story'; ?>
                </h5>
            </div>
            <div class="header-buttons">
                <?php if (isset($_GET['application_id'])): ?>
                    <a href="user_stories.php?application_id=<?php echo htmlspecialchars($_GET['application_id']); ?>" 
                       class="header-action-btn me-2" 
                       title="Back to User Stories">
                        <i class="bi bi-arrow-left"></i> Back to Stories
                    </a>
                    <a href="app_view.php?id=<?php echo htmlspecialchars($_GET['application_id']); ?>" 
                       class="header-action-btn" 
                       title="Back to Application">
                        <i class="bi bi-house"></i> Back to App
                    </a>
                <?php else: ?>
                    <a href="user_stories.php" 
                       class="header-action-btn" 
                       title="Back to User Stories">
                        <i class="bi bi-arrow-left"></i> Back to Stories
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-container">
            <div class="form-card">
                <form id="userStoryForm">
                    <?php if ($editMode): ?>
                        <input type="hidden" id="storyId" value="<?php echo $story['id']; ?>">
                    <?php endif; ?>
                            
                            <!-- Title -->
                            <div class="form-group-horizontal">
                                <label class="form-label" for="title">Title *</label>
                                <input type="text" class="form-control" id="title" name="title" required
                                       value="<?php echo $editMode ? htmlspecialchars($story['title']) : ''; ?>"
                                       placeholder="Enter a descriptive title for this user story">
                            </div>

                            <!-- Jira Integration -->
                            <div class="form-group-horizontal">
                                <label class="form-label" for="jiraId">Jira ID</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="jiraId" name="jira_id"
                                           value="<?php echo $editMode ? htmlspecialchars($story['jira_id'] ?? '') : ''; ?>"
                                           placeholder="e.g., OD-2660">
                                    <button type="button" class="btn btn-outline-secondary" id="jiraImportBtn">
                                        <i class="bi bi-download"></i> Import from Jira
                                    </button>
                                </div>
                                <small class="form-text text-muted">Optional: Enter Jira issue key to link this story</small>
                            </div>

                            <!-- User Story Structure -->
                            <div class="form-card mb-3" style="background-color: #F8F9FA; border: 1px solid #F0F1F2;">
                                <h6 class="mb-3" style="color: #6c757d;">User Story Structure</h6>
                                <div class="form-group-horizontal">
                                    <label class="form-label" for="role">As a... *</label>
                                    <input type="text" class="form-control" id="role" name="role" required
                                           value="<?php echo $editMode ? htmlspecialchars($story['role']) : ''; ?>"
                                           placeholder="e.g., Operations Engineer, Field Technician, System Administrator">
                                </div>

                                <div class="form-group-horizontal">
                                    <label class="form-label" for="wantTo">I want to... *</label>
                                    <textarea class="form-control" id="wantTo" name="want_to" rows="3" required
                                              placeholder="Describe the capability or feature needed"><?php echo $editMode ? htmlspecialchars($story['want_to']) : ''; ?></textarea>
                                </div>

                                <div class="form-group-horizontal">
                                    <label class="form-label" for="soThat">So that... *</label>
                                    <textarea class="form-control" id="soThat" name="so_that" rows="3" required
                                              placeholder="Explain the business value or benefit"><?php echo $editMode ? htmlspecialchars($story['so_that']) : ''; ?></textarea>
                                </div>
                            </div>

                            <!-- Metadata -->
                            <div class="form-group-horizontal">
                                <label class="form-label" for="priority">Priority</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="Low" <?php echo ($editMode && $story['priority'] === 'Low') ? 'selected' : ''; ?>>Low</option>
                                    <option value="Medium" <?php echo ($editMode && $story['priority'] === 'Medium') ? 'selected' : 'selected'; ?>>Medium</option>
                                    <option value="High" <?php echo ($editMode && $story['priority'] === 'High') ? 'selected' : ''; ?>>High</option>
                                    <option value="Critical" <?php echo ($editMode && $story['priority'] === 'Critical') ? 'selected' : ''; ?>>Critical</option>
                                </select>
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label" for="status">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="backlog" <?php echo ($editMode && $story['status'] === 'backlog') ? 'selected' : 'selected'; ?>>Backlog</option>
                                    <option value="in_progress" <?php echo ($editMode && $story['status'] === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="review" <?php echo ($editMode && $story['status'] === 'review') ? 'selected' : ''; ?>>Review</option>
                                    <option value="done" <?php echo ($editMode && $story['status'] === 'done') ? 'selected' : ''; ?>>Done</option>
                                    <option value="cancelled" <?php echo ($editMode && $story['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label" for="storyPoints">Story Points</label>
                                <input type="number" class="form-control" id="storyPoints" name="story_points" min="1" max="100"
                                       value="<?php echo $editMode ? htmlspecialchars($story['story_points'] ?? '') : ''; ?>"
                                       placeholder="Estimation (1-100)">
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label" for="applicationId">Related Application</label>
                                <select class="form-select" id="applicationId" name="application_id">
                                    <option value="">Select Application (Optional)</option>
                                    <?php foreach ($applications as $app): 
                                        $isSelected = false;
                                        if ($editMode && $story['application_id'] == $app['id']) {
                                            $isSelected = true;
                                        } elseif (!$editMode && $preselectedApplicationId == $app['id']) {
                                            $isSelected = true;
                                        }
                                    ?>
                                        <option value="<?php echo $app['id']; ?>" 
                                                <?php echo $isSelected ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($app['short_description']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Additional Information -->
                            <div class="form-group-horizontal">
                                <label class="form-label" for="category">Category</label>
                                <input type="text" class="form-control" id="category" name="category"
                                       value="<?php echo $editMode ? htmlspecialchars($story['category'] ?? '') : ''; ?>"
                                       placeholder="e.g., Operations, Maintenance, Monitoring">
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label" for="tags">Tags</label>
                                <input type="text" class="form-control" id="tags" name="tags"
                                       value="<?php echo $editMode ? htmlspecialchars($story['tags'] ?? '') : ''; ?>"
                                       placeholder="Comma-separated tags (e.g., dashboard, monitoring, mobile)">
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label" for="epic">Epic</label>
                                <input type="text" class="form-control" id="epic" name="epic"
                                       value="<?php echo $editMode ? htmlspecialchars($story['epic'] ?? '') : ''; ?>"
                                       placeholder="Epic or theme this story belongs to">
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label" for="sprint">Sprint</label>
                                <input type="text" class="form-control" id="sprint" name="sprint"
                                       value="<?php echo $editMode ? htmlspecialchars($story['sprint'] ?? '') : ''; ?>"
                                       placeholder="Sprint assignment">
                            </div>

                            <!-- External Links -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group-horizontal">
                                        <label class="form-label" for="jiraUrl">Jira URL</label>
                                        <input type="url" class="form-control" id="jiraUrl" name="jira_url"
                                               value="<?php echo $editMode ? htmlspecialchars($story['jira_url'] ?? '') : ''; ?>"
                                               placeholder="https://jira.company.com/browse/OD-2660">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-horizontal">
                                        <label class="form-label" for="sharepointUrl">SharePoint URL</label>
                                        <input type="url" class="form-control" id="sharepointUrl" name="sharepoint_url"
                                               value="<?php echo $editMode ? htmlspecialchars($story['sharepoint_url'] ?? '') : ''; ?>"
                                               placeholder="https://sharepoint.company.com/...">
                                    </div>
                                </div>
                            </div>

                            <!-- Detailed Information -->
                            <div class="form-group-horizontal">
                                <label class="form-label" for="acceptanceCriteria">Acceptance Criteria</label>
                                <textarea class="form-control" id="acceptanceCriteria" name="acceptance_criteria" rows="4"
                                          placeholder="Define the conditions that must be met for this story to be considered complete"><?php echo $editMode ? htmlspecialchars($story['acceptance_criteria'] ?? '') : ''; ?></textarea>
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label" for="businessValue">Business Value</label>
                                <textarea class="form-control" id="businessValue" name="business_value" rows="3"
                                          placeholder="Describe the business impact and value of implementing this story"><?php echo $editMode ? htmlspecialchars($story['business_value'] ?? '') : ''; ?></textarea>
                            </div>

                            <div class="form-group-horizontal">
                                <label class="form-label" for="technicalNotes">Technical Notes</label>
                                <textarea class="form-control" id="technicalNotes" name="technical_notes" rows="3"
                                          placeholder="Any technical considerations, constraints, or implementation notes"><?php echo $editMode ? htmlspecialchars($story['technical_notes'] ?? '') : ''; ?></textarea>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-group-horizontal">
                                <label class="form-label"></label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="bi bi-check2"></i>
                                        <?php echo $editMode ? 'Update Story' : 'Create Story'; ?>
                                    </button>
                                    <a href="user_stories.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="successMessage"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        <div id="errorToast" class="toast align-items-center text-white bg-danger border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="errorMessage"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/pages/user-story-form.js"></script>
</body>
</html>
