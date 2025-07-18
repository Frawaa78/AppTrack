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
$applications = $db->query('SELECT * FROM applications ORDER BY updated_at DESC')->fetchAll();

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
    <link rel="stylesheet" href="../assets/css/main.css">
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
    <div class="table-container">
        <div class="table-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Applications</h2>
                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'editor')): ?>
                    <a href="app_form.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> New Application
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table dashboard-table mb-0" id="applications-table">
                <thead>
                    <tr>
                        <th class="sortable" data-column="short_description">Short description</th>
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
