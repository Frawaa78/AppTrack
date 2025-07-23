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
    </style>
</head>
<body class="bg-light">
    <?php include 'shared/topbar.php'; ?>
    
    <div class="container">
        <div class="header-with-buttons">
            <div class="d-flex align-items-center">
                <?php if (isset($_GET['application_id'])): ?>
                    <a href="app_view.php?id=<?php echo htmlspecialchars($_GET['application_id']); ?>" 
                       class="header-action-btn me-3" 
                       title="Back to Application">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                <?php endif; ?>
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

        <!-- Statistics Cards -->
        <div class="row mb-4" id="statisticsCards">
            <div class="col-md-2">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted">Total Stories</h5>
                        <h3 class="text-primary" id="totalStories">-</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted">Backlog</h5>
                        <h3 class="text-secondary" id="backlogCount">-</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted">In Progress</h5>
                        <h3 class="text-warning" id="inProgressCount">-</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted">Review</h5>
                        <h3 class="text-info" id="reviewCount">-</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted">Done</h5>
                        <h3 class="text-success" id="doneCount">-</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted">Critical</h5>
                        <h3 class="text-danger" id="criticalCount">-</h3>
                    </div>
                </div>
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
                        <th>Created By</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="storiesTableBody">
                    <tr>
                        <td colspan="8" class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
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
    <script src="../assets/js/pages/user-stories.js"></script>
</body>
</html>
