<?php
// public/dashboard.php
require_once __DIR__ . '/../src/db/db.php';
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Check if filtering for current user
$showMineOnly = isset($_GET['show_mine_only']) && $_GET['show_mine_only'] === 'true';
$userEmail = $_SESSION['user_email'] ?? '';

if ($showMineOnly && !empty($userEmail)) {
    // Join with users table to filter applications assigned to current user
    $stmt = $db->prepare('
        SELECT DISTINCT a.* 
        FROM applications a
        LEFT JOIN users u ON u.email = ?
        WHERE (
            a.project_manager = u.display_name OR 
            a.project_manager = CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, "")) OR
            a.project_manager = u.email OR
            a.product_owner = u.display_name OR 
            a.product_owner = CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, "")) OR
            a.product_owner = u.email OR
            a.assigned_to = u.display_name OR 
            a.assigned_to = CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, "")) OR
            a.assigned_to = u.email
        )
        ORDER BY a.updated_at DESC
    ');
    $stmt->execute([$userEmail]);
    $applications = $stmt->fetchAll();
} else {
    // Show all applications
    $applications = $db->query('SELECT * FROM applications ORDER BY updated_at DESC')->fetchAll();
}

// Function to calculate time-based badge
function getTimeBadge($updatedAt) {
    if (!$updatedAt) {
        return ['class' => 'badge-orange', 'text' => 'Never updated'];
    }
    
    $updatedTime = strtotime($updatedAt);
    $currentTime = time();
    $hoursDiff = ($currentTime - $updatedTime) / 3600;
    
    if ($hoursDiff <= 48) {
        return ['class' => 'badge-green', 'text' => floor($hoursDiff) . ' hours ago'];
    } elseif ($hoursDiff <= 168) { // 7 days
        $days = floor($hoursDiff / 24);
        return ['class' => 'badge-blue', 'text' => $days . ' day' . ($days > 1 ? 's' : '') . ' ago'];
    } elseif ($hoursDiff <= 336) { // 14 days
        $days = floor($hoursDiff / 24);
        return ['class' => 'badge-gray', 'text' => $days . ' days ago'];
    } else {
        $days = floor($hoursDiff / 24);
        return ['class' => 'badge-orange', 'text' => $days . ' days ago'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications Dashboard | AppTrack</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- FontAwesome Pro -->
    <script src="https://kit.fontawesome.com/d67c79608d.js" crossorigin="anonymous"></script>
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon/favicon-16x16.png">
    <link rel="manifest" href="../assets/favicon/site.webmanifest">
    <link rel="shortcut icon" href="../assets/favicon/favicon.ico">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/pages/dashboard.css">
    <style>
        /* Dashboard Table Styles */
        .dashboard-table {
            border-radius: 8px;
            overflow: hidden;
            background: transparent !important;
        }
        
        .dashboard-table th {
            background-color: #f8f9fa;
            border: none;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
            color: #495057;
            padding: 0.56rem 0.75rem;
            font-size: 0.875rem;
            cursor: pointer;
            user-select: none;
            transition: background-color 0.15s ease;
            position: relative;
        }
        
        .dashboard-table th:hover {
            background-color: #e9ecef;
        }
        
        .dashboard-table th.sortable::after {
            content: '\f0dc';
            font-family: 'bootstrap-icons';
            font-size: 0.75rem;
            color: #6c757d;
            margin-left: 0.5rem;
            opacity: 0.5;
        }
        
        .dashboard-table th.sort-asc::after {
            content: '\f0d7';
            opacity: 1;
            color: #0d6efd;
        }
        
        .dashboard-table th.sort-desc::after {
            content: '\f0d8';
            opacity: 1;
            color: #0d6efd;
        }
        
        .dashboard-table td {
            border: none;
            padding: 0.42rem 0.75rem;
            vertical-align: middle;
            font-size: 0.875rem;
            background: transparent !important;
        }
        
        .dashboard-table td:nth-child(2) {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .dashboard-table td:nth-child(3),
        .dashboard-table td:nth-child(4) {
            white-space: nowrap;
        }
        
        .dashboard-table td:nth-child(7) {
            text-align: right;
        }
        
        .dashboard-table tbody tr {
            cursor: pointer;
            transition: background-color 0.15s ease;
            background: transparent !important;
        }
        
        .dashboard-table tbody tr:hover {
            background-color: #E3F1FF !important;
        }
        
        /* Time-based badges */
        .time-badge {
            display: inline-block;
            padding: 0.25rem 0.625rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            color: white;
            white-space: nowrap;
            min-width: 85px;
            text-align: center;
        }
        
        .badge-green {
            background-color: #90EFC3;
            color: #155724;
        }
        
        .badge-blue {
            background-color: #73C9FF;
            color: #004085;
        }
        
        .badge-gray {
            background-color: #C9C9C9;
            color: #495057;
        }
        
        .badge-orange {
            background-color: #FFD24C;
            color: #856404;
        }
        
        /* Table container */
        .table-container {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.5rem;
            background: transparent;
        }
        
        .table-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: #212529;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-table {
                font-size: 0.8rem;
            }
            
            .dashboard-table th,
            .dashboard-table td {
                padding: 0.32rem 0.375rem;
            }
        }
    </style>
</head>
<body class="bg-light">
<!-- Topbar -->
<?php include __DIR__ . '/shared/topbar.php'; ?>

<div class="container-fluid mt-4">
    <?php 
    // Display error messages
    if (isset($_GET['error'])) {
        $error_message = '';
        switch ($_GET['error']) {
            case 'handover_not_linked':
                $error_message = 'The handover document you tried to access is not properly linked to an application. Please start handover from an application page.';
                break;
            case 'missing_app_id':
                $error_message = 'Handover module requires an application ID. Please select an application first and then click "Handover Wizard".';
                break;
            case 'app_not_found':
                $error_message = 'The application you tried to access was not found. It may have been deleted.';
                break;
            default:
                $error_message = 'An error occurred.';
        }
        
        if ($error_message) {
            echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">';
            echo '<i class="bi bi-exclamation-triangle"></i> ' . htmlspecialchars($error_message);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
    }
    ?>
    
    <div class="table-container">
        <div class="table-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Applications</h2>
                <div class="d-flex align-items-center gap-3">
                    <!-- Show Mine Only Toggle -->
                    <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'editor')): ?>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="showMineOnlyToggle" style="cursor: pointer;">
                            <label class="form-check-label" for="showMineOnlyToggle" style="cursor: pointer; font-size: 0.875rem; color: #6c757d;">
                                Show mine only
                            </label>
                        </div>
                    <?php endif; ?>
                    <!-- View Toggle -->
                    <div class="view-toggle-container">
                        <div class="btn-group view-toggle" role="group" aria-label="View toggle">
                            <button type="button" class="btn btn-outline-secondary active" id="tableViewBtn" aria-pressed="true">
                                <i class="fas fa-table"></i> Table
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="kanbanViewBtn" aria-pressed="false">
                                <i class="fas fa-columns"></i> Kanban
                            </button>
                        </div>
                    </div>
                    <!-- New Application Button -->
                    <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'editor')): ?>
                        <a href="app_form.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> New Application
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="table-responsive" id="tableContainer">
            <table class="table dashboard-table mb-0" id="applications-table">
                <thead>
                    <tr>
                        <th class="sortable" data-column="short_description">App. Name</th>
                        <th class="sortable" data-column="preops_portfolio">Pre-ops portfolio</th>
                        <th class="sortable" data-column="phase">Phase</th>
                        <th class="sortable" data-column="status">Status</th>
                        <th class="sortable" data-column="project_manager">Project Manager</th>
                        <th class="sortable" data-column="product_owner">Product Owner</th>
                        <th class="sortable" data-column="updated_at">Updated</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($applications): foreach ($applications as $app): 
                    $timeBadge = getTimeBadge($app['updated_at']);
                ?>
                    <tr onclick="window.location='app_view.php?id=<?php echo $app['id']; ?>'" data-id="<?php echo $app['id']; ?>">
                        <td><?php echo htmlspecialchars($app['short_description']); ?></td>
                        <td><?php echo htmlspecialchars($app['preops_portfolio'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($app['phase'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($app['status'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($app['project_manager'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($app['product_owner'] ?? '-'); ?></td>
                        <td>
                            <span class="time-badge <?php echo $timeBadge['class']; ?>">
                                <?php echo $timeBadge['text']; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-1 mb-3 d-block"></i>
                            No applications found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Kanban Board Container -->
        <div id="kanbanContainer" style="display: none;">
            <div class="kanban-loading">
                <i class="fas fa-spinner fa-spin"></i>
                Loading kanban board...
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// FontAwesome icon fallback system
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit for FontAwesome to load
    setTimeout(function() {
        // Check if FontAwesome Pro icons are loaded, if not use Bootstrap Icons as fallback
        const iconElements = document.querySelectorAll('.fa-regular.fa-grid-2, .fa-light.fa-monitor-waveform, .fa-light.fa-lightbulb');
        
        iconElements.forEach(function(iconElement) {
            const computedStyle = window.getComputedStyle(iconElement, ':before');
            const content = computedStyle.getPropertyValue('content');
            
            // If content is empty or 'none', the FontAwesome icon didn't load
            if (!content || content === 'none' || content === '""') {
                console.log('FontAwesome icon not loading, trying fallbacks for:', iconElement.className);
                
                // Replace with Bootstrap Icons
                if (iconElement.classList.contains('fa-grid-2')) {
                    iconElement.className = 'bi bi-grid-3x3-gap';
                } else if (iconElement.classList.contains('fa-monitor-waveform')) {
                    iconElement.className = 'bi bi-graph-up';
                } else if (iconElement.classList.contains('fa-lightbulb')) {
                    iconElement.className = 'bi bi-lightbulb';
                }
            }
        });
    }, 1000); // Wait 1 second for FontAwesome to load
});

// Set user role and email for kanban board
window.userRole = '<?php echo $_SESSION['user_role'] ?? 'viewer'; ?>';
window.userEmail = '<?php echo $_SESSION['user_email'] ?? ''; ?>';
</script>
<script src="../assets/js/components/kanban-board.js"></script>
<script src="../assets/js/pages/dashboard.js"></script>
<script>
// Table sorting functionality
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('applications-table');
    const headers = table.querySelectorAll('th.sortable');
    let currentSort = { column: null, direction: null };
    
    headers.forEach(header => {
        header.addEventListener('click', function() {
            const column = this.dataset.column;
            let direction = 'asc';
            
            // Toggle direction if clicking the same column
            if (currentSort.column === column && currentSort.direction === 'asc') {
                direction = 'desc';
            }
            
            // Update header styles
            headers.forEach(h => {
                h.classList.remove('sort-asc', 'sort-desc');
            });
            
            this.classList.add(direction === 'asc' ? 'sort-asc' : 'sort-desc');
            
            // Sort the table
            sortTable(column, direction);
            
            currentSort = { column, direction };
        });
    });
    
    function sortTable(column, direction) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        // Don't sort if there's only the "no data" row
        if (rows.length === 1 && rows[0].children.length === 1) {
            return;
        }
        
        const columnIndex = getColumnIndex(column);
        
        rows.sort((a, b) => {
            const aValue = getCellValue(a, columnIndex);
            const bValue = getCellValue(b, columnIndex);
            
            // Handle different data types
            if (column === 'updated_at') {
                return compareDates(aValue, bValue, direction);
            } else {
                return compareText(aValue, bValue, direction);
            }
        });
        
        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
    }
    
    function getColumnIndex(column) {
        const columnMap = {
            'short_description': 0,
            'preops_portfolio': 1,
            'phase': 2,
            'status': 3,
            'project_manager': 4,
            'product_owner': 5,
            'updated_at': 6
        };
        return columnMap[column];
    }
    
    function getCellValue(row, index) {
        const cell = row.children[index];
        if (index === 6) { // Updated column with badge
            return cell.querySelector('.time-badge').textContent.trim();
        }
        return cell.textContent.trim();
    }
    
    function compareText(a, b, direction) {
        const comparison = a.toLowerCase().localeCompare(b.toLowerCase());
        return direction === 'asc' ? comparison : -comparison;
    }
    
    function compareDates(a, b, direction) {
        // Extract time values for comparison
        const getTimeValue = (text) => {
            if (text.includes('Never')) return 0;
            if (text.includes('hours')) return parseInt(text);
            if (text.includes('day')) return parseInt(text) * 24;
            return parseInt(text) * 24; // fallback
        };
        
        const aTime = getTimeValue(a);
        const bTime = getTimeValue(b);
        
        const comparison = aTime - bTime;
        return direction === 'asc' ? comparison : -comparison;
    }
});
</script>
</body>
</html>
