<?php
// public/user_stories.php
require_once __DIR__ . '/../src/db/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Get current user info
$userEmail = $_SESSION['user_email'] ?? '';
$currentUserId = $_SESSION['user_id'] ?? null;

// Check if filtering for current user
$showMineOnly = isset($_GET['show_mine_only']) && $_GET['show_mine_only'] === 'true';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Stories - AppTrack</title>
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

        /* Statistics cards styling to match app_view.php */
        .stat-card {
            border: 1px solid #F0F1F2;
            border-radius: 6px;
            background-color: #FCFCFC;
            transition: all 0.2s ease;
        }
        
        .stat-card:hover {
            border-color: #DEE2E6;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Filters panel styling */
        .filters-panel {
            background-color: #FCFCFC;
            border: 1px solid #F0F1F2;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        /* Form labels positioned to the right like app_view.php */
        .filters-panel .form-label {
            font-weight: 400;
            color: #6c757d;
            margin-bottom: 0;
            display: inline-block;
            width: 100px;
            text-align: right;
            padding-right: 10px;
            vertical-align: top;
            padding-top: 0.375rem;
        }

        /* Filters panel form group styling */
        .filters-panel .form-group-horizontal {
            display: flex;
            align-items: flex-start;
            margin-bottom: 0;
        }

        .filters-panel .form-group-horizontal .form-control,
        .filters-panel .form-group-horizontal .form-select {
            flex: 1;
        }

        /* Table styling to match app_view.php forms */
        .table {
            background-color: #FCFCFC;
            border: 1px solid #F0F1F2;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .table th {
            background-color: #F8F9FA;
            border-color: #F0F1F2;
            font-weight: 500;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .table td {
            border-color: #F0F1F2;
            font-size: 0.9rem;
        }

        /* Custom styling for Title and Story columns */
        .table td.title-column {
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .table td.story-column {
            font-size: 0.85rem;
            font-weight: 400;
        }
        
        /* Style story content specifically */
        .table td.story-column .story-summary {
            font-size: 0.85rem;
            font-weight: 400;
        }
        
        .table td.story-column .story-summary .story-role {
            font-size: 0.85rem;
            font-weight: 400;
        }
        
        .table td.story-column .story-summary .text-muted {
            font-size: 0.85rem !important;
            font-weight: 400 !important;
        }
        
        /* Keep "As a" and "I want to" bold and colored - override inline styles */
        .table td.story-column .story-role span[style],
        .table td.story-column .text-muted span[style] {
            color: #0D8ABC !important;
            font-weight: bold !important;
            font-size: 0.85rem !important;
        }

        /* Inline editing styles */
        .editable-field {
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: background-color 0.2s ease;
            min-height: 24px;
            display: inline-block;
            min-width: 60px;
        }

        .editable-field:hover {
            background-color: #f8f9fa;
        }

        .editable-field.editing {
            background-color: #fff3cd;
            cursor: default;
        }

        .inline-edit-select {
            width: 100%;
            min-width: 100px;
            font-size: 0.85rem;
            padding: 0.25rem 0.5rem;
            height: auto;
        }

        .inline-edit-choices {
            min-width: 180px;
        }

        .inline-edit-choices .choices__list--dropdown {
            font-size: 0.85rem;
        }

        .inline-edit-choices .choices__inner {
            padding: 0.25rem 0.5rem;
            font-size: 0.85rem;
            min-height: 32px;
        }

        .inline-edit-choices .choices__item {
            font-size: 0.8rem;
            padding: 0.15rem 0.4rem;
        }

        .saving-indicator {
            color: #6c757d;
            font-size: 0.8rem;
            font-style: italic;
        }

        /* Empty state styling */
        .empty-state {
            padding: 2rem 1rem !important;
        }
        
        .empty-state i {
            font-size: 2rem !important;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            font-size: 1rem !important;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        
        /* Empty state button styling */
        .empty-state-btn {
            background-color: transparent !important;
            color: #212529 !important;
            border: 1px solid #0d6efd !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            padding: 8px 16px !important;
            border-radius: 6px !important;
            transition: all 0.2s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            text-decoration: none !important;
        }
        
        .empty-state-btn i {
            font-size: 14px !important;
            margin: 0 !important;
        }
        
        .empty-state-btn:hover,
        .empty-state-btn:focus {
            background-color: #0d6efd !important;
            color: white !important;
            border-color: #0d6efd !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-decoration: none !important;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'shared/topbar.php'; ?>
    
    <div class="container">
        <div class="header-with-buttons">
            <div class="d-flex align-items-center">
                <button class="header-action-btn me-3" onclick="goBack()" title="Go Back">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
                <h5 class="mb-0"><i class="bi bi-journals"></i> User Stories</h5>
            </div>
            <div class="header-buttons">
                <button type="button" class="header-action-btn" id="filterToggle">
                    <i class="bi bi-funnel"></i> Filters
                </button>
                <a href="user_story_form.php<?php echo isset($_GET['application_id']) ? '?application_id=' . htmlspecialchars($_GET['application_id']) : ''; ?>" class="btn btn-primary">
                    <i class="bi bi-plus"></i> New Story
                </a>
            </div>
        </div>

        <!-- Filters Panel -->
        <div class="filters-panel" id="filtersPanel" style="display: none;">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="form-group-horizontal">
                        <label for="applicationFilter" class="form-label">Application</label>
                        <select class="form-select" id="applicationFilter">
                            <option value="">All Applications</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group-horizontal">
                        <label for="priorityFilter" class="form-label">Priority</label>
                        <select class="form-select" id="priorityFilter">
                            <option value="">All Priorities</option>
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group-horizontal">
                        <label for="statusFilter" class="form-label">Status</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">All Statuses</option>
                            <option value="backlog">Backlog</option>
                            <option value="in_progress">In Progress</option>
                            <option value="review">Review</option>
                            <option value="done">Done</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group-horizontal">
                        <label for="searchFilter" class="form-label">Search</label>
                        <input type="text" class="form-control" id="searchFilter" placeholder="Search stories...">
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="showMineOnly" 
                               <?php echo $showMineOnly ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="showMineOnly">
                            Show only my stories
                        </label>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                        <i class="bi bi-x-circle"></i> Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- User Stories Table -->
        <div class="table-responsive">
            <table class="table table-hover" id="storiesTable">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Story</th>
                        <th>Application</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="storiesTableBody">
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
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
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script src="../assets/js/pages/user-stories.js"></script>
    
    <script>
        function goBack() {
            window.history.back();
        }
    </script>
</body>
</html>
