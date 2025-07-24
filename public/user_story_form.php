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
$stmt = $db->prepare('SELECT id, short_description, application_service FROM applications ORDER BY short_description');
$stmt->execute();
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle multiple application IDs for editing
$selectedApplicationIds = [];
if ($editMode && !empty($story['application_id'])) {
    // Handle comma-separated application IDs
    if (strpos($story['application_id'], ',') !== false) {
        $selectedApplicationIds = array_map('intval', array_map('trim', explode(',', $story['application_id'])));
    } else {
        $selectedApplicationIds = [(int)$story['application_id']];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editMode ? 'Edit' : 'New'; ?> User Story - AppTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon/favicon-16x16.png">
    <link rel="manifest" href="../assets/favicon/site.webmanifest">
    <link rel="shortcut icon" href="../assets/favicon/favicon.ico">
    <!-- FontAwesome Pro -->
    <script src="https://kit.fontawesome.com/d67c79608d.js" crossorigin="anonymous"></script>
    <!-- Choices.js for multi-select -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
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

        /* Override any custom form-group-horizontal styles to use default from forms.css */
        /* This ensures consistent alignment with app_form.php */

        /* Form container styling to match app_form.php */
        .form-container {
            max-width: none; /* Remove width restriction to match app_form.php */
        }

        /* Header buttons styling - match app_form.php exactly */
        .header-with-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem; /* Match app_form.php spacing */
        }

        .header-with-buttons h5 {
            margin: 0; /* Ensure consistent header margin */
        }

        .header-buttons {
            display: flex;
            gap: 0.5rem;
        }

        /* Multiple select styling */
        select[multiple] {
            min-height: 120px;
        }
        
        select[multiple] option {
            padding: 8px 12px;
            margin: 2px 0;
        }
        
        select[multiple] option:checked {
            background-color: #0d6efd;
            color: white;
        }

        /* Override any custom form-group-horizontal styles to use default from forms.css */
        /* This ensures consistent alignment with app_form.php */
    </style>
</head>
<body class="bg-light">
    <?php include 'shared/topbar.php'; ?>
    
    <div class="container">
        <form method="post" autocomplete="off" id="userStoryForm">
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
                           class="header-action-btn me-2" 
                           title="Back to Application">
                            <i class="bi bi-house"></i> Back to App
                        </a>
                    <?php else: ?>
                        <a href="user_stories.php" 
                           class="header-action-btn me-2" 
                           title="Back to User Stories">
                            <i class="bi bi-arrow-left"></i> Back to Stories
                        </a>
                    <?php endif; ?>
                    
                    <!-- Action buttons -->
                    <a href="<?php echo isset($_GET['application_id']) ? 'user_stories.php?application_id=' . htmlspecialchars($_GET['application_id']) : 'user_stories.php'; ?>" 
                       class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" form="userStoryForm" class="btn btn-primary" id="submitBtn">
                        <?php echo $editMode ? 'Update Story' : 'Save Story'; ?>
                    </button>
                </div>
            </div>

            <div class="form-container">
                <div class="row g-3">
                    <!-- Left column equivalent -->
                    <div class="col-12">
                        <?php if ($editMode): ?>
                            <input type="hidden" id="storyId" value="<?php echo $story['id']; ?>">
                        <?php endif; ?>

                        <!-- Title and Jira ID on same row -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <div class="form-group-horizontal">
                                    <label class="form-label" for="title">Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required
                                           value="<?php echo $editMode ? htmlspecialchars($story['title']) : ''; ?>"
                                           placeholder="Enter a descriptive title for this user story">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group-horizontal">
                                    <label class="form-label" for="jiraId">Jira ID</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="jiraId" name="jira_id"
                                               value="<?php echo $editMode ? htmlspecialchars($story['jira_id'] ?? '') : ''; ?>"
                                               placeholder="e.g., OD-2660">
                                        <button type="button" class="btn btn-outline-secondary" id="jiraImportBtn" title="Import from Jira">
                                            <i class="bi bi-download"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Story Structure - aligned left like app_form.php -->
                        <div class="mb-4">
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

                        <!-- Related Application and Status row -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <div class="form-group-horizontal">
                                    <label class="form-label" for="applicationIds">Related Applications</label>
                                    <div style="flex: 1;">
                                        <select class="form-select" id="applicationIds" name="application_ids[]" multiple>
                                            <?php if ($editMode && !empty($selectedApplicationIds)): ?>
                                                <?php
                                                // Get application details for selected apps
                                                if (!empty($selectedApplicationIds)) {
                                                    $placeholders = implode(',', array_fill(0, count($selectedApplicationIds), '?'));
                                                    $stmt = $db->prepare("SELECT id, short_description, application_service FROM applications WHERE id IN ($placeholders)");
                                                    $stmt->execute($selectedApplicationIds);
                                                    $selectedApps = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    
                                                    foreach ($selectedApps as $app):
                                                ?>
                                                    <option value="<?php echo $app['id']; ?>" selected>
                                                        <?php echo htmlspecialchars($app['short_description']); ?>
                                                        <?php if (!empty($app['application_service'])): ?>
                                                            (<?php echo htmlspecialchars($app['application_service']); ?>)
                                                        <?php endif; ?>
                                                    </option>
                                                <?php 
                                                    endforeach;
                                                }
                                                ?>
                                            <?php elseif (!$editMode && $preselectedApplicationId): ?>
                                                <?php
                                                // Pre-select single application for new story
                                                $stmt = $db->prepare("SELECT id, short_description, application_service FROM applications WHERE id = ?");
                                                $stmt->execute([$preselectedApplicationId]);
                                                $preselectedApp = $stmt->fetch();
                                                if ($preselectedApp):
                                                ?>
                                                    <option value="<?php echo $preselectedApp['id']; ?>" selected>
                                                        <?php echo htmlspecialchars($preselectedApp['short_description']); ?>
                                                        <?php if (!empty($preselectedApp['application_service'])): ?>
                                                            (<?php echo htmlspecialchars($preselectedApp['application_service']); ?>)
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </select>
                                        <small class="form-text text-muted mt-1">
                                            <i class="bi bi-search me-1"></i>
                                            Search and select multiple applications. Type at least 2 characters to search.
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
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
                            </div>
                        </div>

                        <!-- Priority, Category, and Tags row -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="form-group-horizontal">
                                    <label class="form-label" for="priority">Priority</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="Low" <?php echo ($editMode && $story['priority'] === 'Low') ? 'selected' : ''; ?>>Low</option>
                                        <option value="Medium" <?php echo ($editMode && $story['priority'] === 'Medium') ? 'selected' : 'selected'; ?>>Medium</option>
                                        <option value="High" <?php echo ($editMode && $story['priority'] === 'High') ? 'selected' : ''; ?>>High</option>
                                        <option value="Critical" <?php echo ($editMode && $story['priority'] === 'Critical') ? 'selected' : ''; ?>>Critical</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-horizontal">
                                    <label class="form-label" for="category">Category</label>
                                    <input type="text" class="form-control" id="category" name="category"
                                           value="<?php echo $editMode ? htmlspecialchars($story['category'] ?? '') : ''; ?>"
                                           placeholder="e.g., Operations, Maintenance, Monitoring">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-horizontal">
                                    <label class="form-label" for="tags">Tags</label>
                                    <input type="text" class="form-control" id="tags" name="tags"
                                           value="<?php echo $editMode ? htmlspecialchars($story['tags'] ?? '') : ''; ?>"
                                           placeholder="Comma-separated tags">
                                </div>
                            </div>
                        </div>

                        <!-- Jira URL and SharePoint URL row -->
                        <div class="row g-3 mb-3">
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
                    </div>
                </div>
            </div>
        </form>
    </div>    <!-- Success/Error Messages -->
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
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script src="../assets/js/pages/user-story-form.js"></script>
    
    <script>
        // Initialize Choices.js for Related Applications
        document.addEventListener('DOMContentLoaded', function() {
            const applicationSelect = document.getElementById('applicationIds');
            if (applicationSelect) {
                const applicationChoices = new Choices(applicationSelect, {
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
                applicationSelect.addEventListener('choice', function(e) {
                    applicationChoices.clearChoices();
                });

                // Search functionality
                let searchTimeout;
                applicationSelect.addEventListener('search', function(e) {
                    const query = e.detail.value;
                    
                    if (query.length < 2) {
                        applicationChoices.clearChoices();
                        return;
                    }

                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        const selectedValues = applicationChoices.getValue(true);
                        const selectedIds = selectedValues.length > 0 ? selectedValues.join(',') : '';
                        
                        let url = `api/search_applications.php?q=${encodeURIComponent(query)}`;
                        if (selectedIds) {
                            url += `&selected=${encodeURIComponent(selectedIds)}`;
                        }
                        
                        fetch(url)
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(`HTTP error! status: ${response.status}`);
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.error) {
                                    console.error('API Error:', data);
                                    return;
                                }
                                applicationChoices.clearChoices();
                                applicationChoices.setChoices(data, 'value', 'label', true);
                            })
                            .catch(error => {
                                console.error('Search error:', error);
                            });
                    }, 300);
                });
            }
        });
    </script>
</body>
</html>
