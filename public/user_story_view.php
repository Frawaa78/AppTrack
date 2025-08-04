<?php
// public/user_story_view.php
require_once __DIR__ . '/../src/db/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: user_stories.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Get user story with related information
$stmt = $db->prepare('
    SELECT us.*, u.display_name as created_by_name, u.email as created_by_email
    FROM user_stories us 
    LEFT JOIN users u ON us.created_by = u.id
    WHERE us.id = :id
');
$stmt->execute([':id' => $id]);
$story = $stmt->fetch();

if (!$story) {
    header('Location: user_stories.php');
    exit;
}

// Handle multiple application IDs
$relatedApplications = [];
if (!empty($story['application_id'])) {
    $applicationIds = [];
    if (strpos($story['application_id'], ',') !== false) {
        $applicationIds = array_map('intval', array_map('trim', explode(',', $story['application_id'])));
    } else {
        $applicationIds = [(int)$story['application_id']];
    }
    
    if (!empty($applicationIds)) {
        $placeholders = implode(',', array_fill(0, count($applicationIds), '?'));
        $stmt = $db->prepare("SELECT id, short_description, application_service FROM applications WHERE id IN ($placeholders)");
        $stmt->execute($applicationIds);
        $relatedApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Format tags
$tags = !empty($story['tags']) ? explode(',', $story['tags']) : [];
$tags = array_map('trim', $tags);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($story['title']); ?> - User Story - AppTrack</title>
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
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/pages/user-stories.css">
    <link rel="stylesheet" href="../assets/css/components/buttons.css">
    <link rel="stylesheet" href="../assets/css/components/forms.css">
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
        
        .header-action-btn i {
            font-size: 16px;
            min-width: 16px;
            text-align: center;
        }

        /* Content cards styling */
        .content-card {
            background-color: #FCFCFC;
            border: 1px solid #F0F1F2;
            border-radius: 6px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        /* Badge styling to match app_view.php */
        .jira-badge {
            background-color: #6C757D;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .priority-badge, .status-badge {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'shared/topbar.php'; ?>
    
    <div class="container">
        <div class="header-with-buttons">
            <div class="d-flex align-items-center">
                <?php if (isset($_GET['from_app']) && $_GET['from_app']): ?>
                    <a href="user_stories.php?application_id=<?php echo $story['application_id']; ?>" 
                       class="header-action-btn me-3" 
                       title="Back to User Stories">
                        <i class="bi bi-arrow-left"></i> Stories
                    </a>
                <?php else: ?>
                    <a href="user_stories.php" 
                       class="header-action-btn me-3" 
                       title="Back to User Stories">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                <?php endif; ?>
                <h5 class="mb-0">User Story Details</h5>
            </div>
            <div class="header-buttons">
                <?php if (isset($_GET['from_app']) && $_GET['from_app']): ?>
                    <a href="app_view.php?id=<?php echo $story['application_id']; ?>" 
                       class="header-action-btn me-2" 
                       title="Back to Application">
                        <i class="bi bi-house"></i> App
                    </a>
                <?php endif; ?>
                <a href="user_story_form.php?id=<?php echo $story['id']; ?><?php echo isset($_GET['from_app']) && $_GET['from_app'] ? '&application_id=' . $story['application_id'] : ''; ?>" 
                   class="header-action-btn me-2">
                    <i class="bi bi-pencil"></i> Edit Story
                </a>
                <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Story Header -->
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 class="mb-2">
                                <?php echo htmlspecialchars($story['title']); ?>
                                <?php if ($story['jira_id']): ?>
                                    <span class="jira-badge ms-2"><?php echo htmlspecialchars($story['jira_id']); ?></span>
                                <?php endif; ?>
                            </h4>
                            <div class="d-flex gap-2 align-items-center">
                                <span class="badge priority-badge priority-<?php echo strtolower($story['priority']); ?>">
                                    <?php echo $story['priority']; ?> Priority
                                </span>
                                <span class="badge status-badge status-<?php echo $story['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $story['status'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- User Story Structure -->
                    <div class="user-story-preview mb-4">
                        <h6 class="mb-3" style="color: #6c757d;">User Story</h6>
                        <p><strong>As a</strong> <?php echo htmlspecialchars($story['role']); ?></p>
                        <p><strong>I want to</strong> <?php echo htmlspecialchars($story['want_to']); ?></p>
                        <p><strong>So that</strong> <?php echo htmlspecialchars($story['so_that']); ?></p>
                    </div>

                    <!-- Tags -->
                    <?php if (!empty($tags)): ?>
                        <div class="mb-4">
                            <h6 style="color: #6c757d;"><i class="bi bi-tags"></i> Tags</h6>
                            <?php foreach ($tags as $tag): ?>
                                <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Metadata -->
                <div class="content-card">
                    <h6 class="mb-3" style="color: #6c757d;"><i class="bi bi-info-circle"></i> Details</h6>
                    <div class="form-group-horizontal">
                        <label class="form-label">Created:</label>
                        <span><?php echo date('M j, Y', strtotime($story['created_at'])); ?></span>
                    </div>
                    <div class="form-group-horizontal">
                        <label class="form-label">Updated:</label>
                        <span><?php echo date('M j, Y', strtotime($story['updated_at'])); ?></span>
                    </div>
                    <div class="form-group-horizontal">
                        <label class="form-label">Created by:</label>
                        <span><?php echo htmlspecialchars($story['created_by_name'] ?: 'Unknown'); ?></span>
                    </div>
                    <?php if ($story['category']): ?>
                        <div class="form-group-horizontal">
                            <label class="form-label">Category:</label>
                            <span><?php echo htmlspecialchars($story['category']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="form-group-horizontal">
                        <label class="form-label">Source:</label>
                        <span class="badge source-<?php echo $story['source']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $story['source'])); ?>
                        </span>
                    </div>
                </div>

                <!-- Related Applications -->
                <?php if (!empty($relatedApplications)): ?>
                    <div class="content-card">
                        <h6 class="mb-3" style="color: #6c757d;"><i class="bi bi-link-45deg"></i> Related Applications</h6>
                        <?php foreach ($relatedApplications as $app): ?>
                            <div class="mb-2">
                                <a href="app_view.php?id=<?php echo $app['id']; ?>" class="text-decoration-none d-block">
                                    <i class="bi bi-grid-3x3-gap me-2"></i>
                                    <?php echo htmlspecialchars($app['short_description']); ?>
                                    <?php if ($app['application_service']): ?>
                                        <small class="text-muted"> - <?php echo htmlspecialchars($app['application_service']); ?></small>
                                    <?php endif; ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- External Links -->
                <?php if ($story['jira_url'] || $story['sharepoint_url']): ?>
                    <div class="content-card">
                        <h6 class="mb-3" style="color: #6c757d;"><i class="bi bi-box-arrow-up-right"></i> External Links</h6>
                        <?php if ($story['jira_url']): ?>
                            <div class="mb-2">
                                <a href="<?php echo htmlspecialchars($story['jira_url']); ?>" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-bug me-2"></i>View in Jira
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if ($story['sharepoint_url']): ?>
                            <div class="mb-2">
                                <a href="<?php echo htmlspecialchars($story['sharepoint_url']); ?>" target="_blank" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="bi bi-cloud me-2"></i>View in SharePoint
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this user story? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete() {
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
            try {
                const response = await fetch(`api/user_stories/delete_story.php?id=<?php echo $story['id']; ?>`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = 'user_stories.php';
                } else {
                    alert('Failed to delete user story: ' + result.error);
                }
            } catch (error) {
                console.error('Error deleting story:', error);
                alert('Failed to delete user story');
            }
        });
    </script>
</body>
</html>
