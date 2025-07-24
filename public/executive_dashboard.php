<?php
// public/executive_dashboard.php
require_once __DIR__ . '/../src/db/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$current_user_role = $_SESSION['user_role'] ?? 'viewer';

// Get key metrics
function getKeyMetrics($db) {
    $metrics = [];
    
    // Total applications
    $stmt = $db->query("SELECT COUNT(*) as total FROM applications");
    $metrics['total_applications'] = $stmt->fetch()['total'];
    
    // Active projects (not completed or retired)
    $stmt = $db->query("SELECT COUNT(*) as active FROM applications WHERE status NOT IN ('completed', 'retired', 'cancelled')");
    $metrics['active_applications'] = $stmt->fetch()['active'];
    
    // Completion rate
    $stmt = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status IN ('completed', 'operate') THEN 1 ELSE 0 END) as completed
        FROM applications");
    $result = $stmt->fetch();
    $metrics['completion_rate'] = $result['total'] > 0 ? round(($result['completed'] / $result['total']) * 100, 1) : 0;
    
    // AI insights generated (last 30 days)
    try {
        $stmt = $db->query("SELECT COUNT(*) as ai_count FROM ai_analysis WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $metrics['ai_insights'] = $stmt->fetch()['ai_count'];
    } catch (Exception $e) {
        $metrics['ai_insights'] = 0;
    }
    
    // User stories completed (if table exists)
    try {
        $stmt = $db->query("SELECT COUNT(*) as story_count FROM user_stories WHERE status = 'done'");
        $metrics['user_stories_completed'] = $stmt->fetch()['story_count'];
    } catch (Exception $e) {
        $metrics['user_stories_completed'] = 0;
    }
    
    return $metrics;
}

// Get phase distribution
function getPhaseDistribution($db) {
    $stmt = $db->query("SELECT 
        phase, 
        COUNT(*) as count,
        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM applications)), 1) as percentage
        FROM applications 
        WHERE phase IS NOT NULL 
        GROUP BY phase 
        ORDER BY FIELD(phase, 'need', 'solution', 'build', 'implement', 'operate')");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get status distribution
function getStatusDistribution($db) {
    $stmt = $db->query("SELECT 
        status, 
        COUNT(*) as count,
        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM applications)), 1) as percentage
        FROM applications 
        WHERE status IS NOT NULL 
        GROUP BY status");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get product owner distribution
function getProductOwnerDistribution($db) {
    $stmt = $db->query("SELECT 
        COALESCE(product_owner, 'Unassigned') as owner, 
        COUNT(*) as count
        FROM applications 
        GROUP BY product_owner 
        ORDER BY count DESC 
        LIMIT 15");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get timeline data (last 12 months)
function getTimelineData($db) {
    $stmt = $db->query("SELECT 
        DATE_FORMAT(updated_at, '%Y-%m') as month,
        COUNT(*) as activity_count
        FROM applications 
        WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(updated_at, '%Y-%m')
        ORDER BY month");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get due date distribution for horizontal timeline
function getDueDateDistribution($db) {
    // Get current date and future dates up to a reasonable limit
    $stmt = $db->query("SELECT 
        DATE(due_date) as due_date,
        COUNT(*) as count
        FROM applications 
        WHERE due_date IS NOT NULL 
        AND due_date >= CURDATE()
        AND due_date <= '2027-12-31'
        GROUP BY DATE(due_date)
        ORDER BY due_date");
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create a comprehensive date range from today to end of 2027
    $start_date = new DateTime();
    $end_date = new DateTime('2027-12-31');
    $date_range = [];
    
    // Build complete date range
    $current_date = clone $start_date;
    while ($current_date <= $end_date) {
        $date_range[$current_date->format('Y-m-d')] = 0;
        $current_date->add(new DateInterval('P1D'));
    }
    
    // Fill in actual data
    foreach ($results as $row) {
        if (isset($date_range[$row['due_date']])) {
            $date_range[$row['due_date']] = (int)$row['count'];
        }
    }
    
    return $date_range;
}

// Get recent activity
function getRecentActivity($db) {
    try {
        $stmt = $db->query("SELECT 
            wn.created_at,
            wn.type,
            wn.note,
            wn.priority,
            a.short_description as app_name,
            u.display_name as user_name
            FROM work_notes wn
            LEFT JOIN applications a ON wn.application_id = a.id
            LEFT JOIN users u ON wn.user_id = u.id
            WHERE wn.is_visible = 1
            ORDER BY wn.created_at DESC 
            LIMIT 10");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// Get User Stories metrics
function getUserStoriesMetrics($db) {
    try {
        $stmt = $db->query("SELECT 
            status,
            priority,
            COUNT(*) as count
            FROM user_stories 
            GROUP BY status, priority");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $metrics = [
            'by_status' => [],
            'by_priority' => [],
            'total' => 0
        ];
        
        foreach ($results as $row) {
            $metrics['by_status'][$row['status']] = ($metrics['by_status'][$row['status']] ?? 0) + $row['count'];
            $metrics['by_priority'][$row['priority']] = ($metrics['by_priority'][$row['priority']] ?? 0) + $row['count'];
            $metrics['total'] += $row['count'];
        }
        
        return $metrics;
    } catch (Exception $e) {
        return ['by_status' => [], 'by_priority' => [], 'total' => 0];
    }
}

// Fetch all data
$key_metrics = getKeyMetrics($db);
$phase_distribution = getPhaseDistribution($db);
$status_distribution = getStatusDistribution($db);
$owner_distribution = getProductOwnerDistribution($db);
$timeline_data = getTimelineData($db);
$due_date_distribution = getDueDateDistribution($db);
$recent_activity = getRecentActivity($db);
$user_stories_metrics = getUserStoriesMetrics($db);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Dashboard - AppTrack</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- FontAwesome -->
    <script src="https://kit.fontawesome.com/d67c79608d.js" crossorigin="anonymous"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon/favicon-16x16.png">
    <link rel="manifest" href="../assets/favicon/site.webmanifest">
    <link rel="shortcut icon" href="../assets/favicon/favicon.ico">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .executive-dashboard {
            background: #f8f9fa;
            min-height: calc(100vh - 80px);
            padding: 1.5rem 0;
        }
        
        .metric-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .metric-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .metric-label {
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            font-weight: 500;
        }
        
        .metric-icon {
            font-size: 1.5rem;
            opacity: 0.7;
            margin-bottom: 1rem;
        }
        
        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            margin-bottom: 1.5rem;
        }
        
        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .activity-item {
            padding: 1rem;
            border-left: 3px solid #0d6efd;
            margin-bottom: 0.75rem;
            background: #f8f9fa;
            border-radius: 0 6px 6px 0;
            border: 1px solid #e9ecef;
            border-left: 3px solid #0d6efd;
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .priority-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .priority-high { background: #dc3545; color: white; }
        .priority-medium { background: #ffc107; color: #000; }
        .priority-low { background: #6c757d; color: white; }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .grid-full-width {
            grid-column: 1 / -1;
        }
        
        .grid-two-thirds {
            grid-column: span 2;
        }
        
        .refresh-indicator {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr 1fr;
            }
            .grid-two-thirds {
                grid-column: span 2;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .grid-two-thirds {
                grid-column: span 1;
            }
            .metric-value {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/shared/topbar.php'; ?>
    
    <div class="executive-dashboard">
        <!-- Top controls -->
        <div class="container mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 style="color: #212529; font-size: 1.75rem; font-weight: 600; margin: 0;">
                        <i class="fas fa-chart-line me-3"></i>
                        Executive Dashboard
                    </h1>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex align-items-center justify-content-end">
                        <small class="me-3" style="color: #6c757d;">
                            <i class="fas fa-clock me-1"></i>
                            Last updated: <?php echo date('Y-m-d H:i'); ?>
                        </small>
                        <button class="btn btn-outline-primary btn-sm" onclick="refreshDashboard()">
                            <i class="fas fa-sync-alt" id="refreshIcon"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <!-- Key Metrics Row -->
            <div class="row mb-4">
                <div class="col-md-2 mb-3">
                    <div class="metric-card text-center">
                        <div class="metric-icon">
                            <i class="fas fa-cube text-primary"></i>
                        </div>
                        <div class="metric-value"><?php echo $key_metrics['total_applications']; ?></div>
                        <div class="metric-label">Total Applications</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="metric-card text-center">
                        <div class="metric-icon">
                            <i class="fas fa-play text-success"></i>
                        </div>
                        <div class="metric-value"><?php echo $key_metrics['active_applications']; ?></div>
                        <div class="metric-label">Active Projects</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="metric-card text-center">
                        <div class="metric-icon">
                            <i class="fas fa-check-circle text-info"></i>
                        </div>
                        <div class="metric-value"><?php echo $key_metrics['completion_rate']; ?>%</div>
                        <div class="metric-label">Completion Rate</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="metric-card text-center">
                        <div class="metric-icon">
                            <i class="fas fa-brain text-warning"></i>
                        </div>
                        <div class="metric-value"><?php echo $key_metrics['ai_insights']; ?></div>
                        <div class="metric-label">AI Insights (30d)</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="metric-card text-center">
                        <div class="metric-icon">
                            <i class="fas fa-user-edit text-purple"></i>
                        </div>
                        <div class="metric-value"><?php echo $key_metrics['user_stories_completed']; ?></div>
                        <div class="metric-label">Stories Completed</div>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="dashboard-grid">
                <!-- Due Date Timeline Chart -->
                <div class="chart-container grid-two-thirds">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-area me-2"></i>
                        Expected Workload Timeline
                        <small class="text-muted float-end">From today to end of 2027</small>
                    </h3>
                    <div style="height: 300px;">
                        <canvas id="dueDateChart"></canvas>
                    </div>
                </div>

                <!-- Status Distribution -->
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-pie me-2"></i>
                        Status Distribution
                    </h3>
                    <canvas id="statusChart"></canvas>
                </div>

                <!-- Phase Distribution -->
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-bar me-2"></i>
                        Phase Distribution
                    </h3>
                    <canvas id="phaseChart"></canvas>
                </div>

                <!-- Product Owner Distribution -->
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-users me-2"></i>
                        Product Owner Workload
                    </h3>
                    <canvas id="ownerChart"></canvas>
                </div>

                <!-- User Stories Metrics -->
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-user-edit me-2"></i>
                        User Stories Status
                    </h3>
                    <canvas id="storiesChart"></canvas>
                </div>

                <!-- Recent Activity -->
                <div class="chart-container grid-full-width">
                    <h3 class="chart-title">
                        <i class="fas fa-clock me-2"></i>
                        Recent Activity Feed
                        <small class="text-muted float-end">Last 10 work notes</small>
                    </h3>
                    <div class="activity-feed" style="max-height: 400px; overflow-y: auto;">
                        <?php if (empty($recent_activity)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-info-circle me-2"></i>
                                No recent activity data available
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_activity as $activity): ?>
                                <div class="activity-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($activity['app_name'] ?? 'Unknown App'); ?></strong>
                                            <?php if (!empty($activity['priority'])): ?>
                                                <span class="priority-badge priority-<?php echo strtolower($activity['priority']); ?>">
                                                    <?php echo strtoupper($activity['priority']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <div class="mt-1">
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars(substr($activity['note'] ?? '', 0, 100)); ?>
                                                    <?php echo strlen($activity['note'] ?? '') > 100 ? '...' : ''; ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="activity-time">
                                                <?php echo date('M j, H:i', strtotime($activity['created_at'])); ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($activity['user_name'] ?? 'Unknown User'); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Back -->
    <div class="container">
        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="dashboard.php" class="btn btn-outline-primary me-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to Main Dashboard
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-2"></i>Home
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Chart.js Configuration
        Chart.defaults.font.family = 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        Chart.defaults.color = '#6c757d';

        // Due Date Timeline Chart (Area Chart for Workload Visualization)
        const dueDateCtx = document.getElementById('dueDateChart').getContext('2d');
        
        // Prepare due date data
        const dueDateData = <?php echo json_encode($due_date_distribution); ?>;
        
        // Filter out dates with 0 applications and create a smooth timeline
        const dueDateEntries = Object.entries(dueDateData).filter(([date, count]) => count > 0);
        
        // Group by month and create a continuous timeline
        const monthlyData = {};
        dueDateEntries.forEach(([date, count]) => {
            const monthKey = date.substring(0, 7); // YYYY-MM
            monthlyData[monthKey] = (monthlyData[monthKey] || 0) + count;
        });
        
        // Create a continuous timeline from today to end of 2027
        const today = new Date();
        const endDate = new Date(2027, 11, 31); // Dec 31, 2027
        const timelineData = [];
        
        let currentDate = new Date(today.getFullYear(), today.getMonth(), 1); // Start of current month
        
        while (currentDate <= endDate) {
            const monthKey = currentDate.toISOString().substring(0, 7);
            const count = monthlyData[monthKey] || 0;
            
            timelineData.push({
                month: monthKey,
                count: count,
                label: currentDate.toLocaleDateString('no-NO', { 
                    year: '2-digit', 
                    month: 'short'
                })
            });
            
            // Move to next month
            currentDate.setMonth(currentDate.getMonth() + 1);
        }
        
        // Limit to reasonable display (30 months max)
        const displayData = timelineData.slice(0, 30);
        
        const dueDateChart = new Chart(dueDateCtx, {
            type: 'line',
            data: {
                labels: displayData.map(item => item.label),
                datasets: [{
                    label: 'Expected Workload',
                    data: displayData.map(item => item.count),
                    backgroundColor: 'rgba(102, 126, 234, 0.3)',
                    borderColor: '#667eea',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4, // This creates the smooth curve
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#667eea',
                        borderWidth: 1,
                        callbacks: {
                            title: function(context) {
                                const dataIndex = context[0].dataIndex;
                                const monthData = displayData[dataIndex];
                                const [year, month] = monthData.month.split('-');
                                const date = new Date(year, month - 1);
                                return date.toLocaleDateString('no-NO', { 
                                    year: 'numeric', 
                                    month: 'long'
                                });
                            },
                            label: function(context) {
                                const value = context.parsed.y;
                                if (value === 0) {
                                    return 'No applications due';
                                }
                                return `${value} application${value !== 1 ? 's' : ''} due`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Timeline',
                            font: {
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            maxTicksLimit: 8,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        title: {
                            display: true,
                            text: 'Expected Workload',
                            font: {
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($status_distribution, 'status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($status_distribution, 'count')); ?>,
                    backgroundColor: [
                        '#28a745', '#17a2b8', '#ffc107', '#dc3545', '#6c757d', '#007bff'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Phase Distribution Chart
        const phaseCtx = document.getElementById('phaseChart').getContext('2d');
        const phaseChart = new Chart(phaseCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($phase_distribution, 'phase')); ?>,
                datasets: [{
                    label: 'Applications',
                    data: <?php echo json_encode(array_column($phase_distribution, 'count')); ?>,
                    backgroundColor: [
                        '#007bff', '#28a745', '#ffc107', '#fd7e14', '#20c997'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Product Owner Chart
        const ownerCtx = document.getElementById('ownerChart').getContext('2d');
        const ownerChart = new Chart(ownerCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($owner_distribution, 'owner')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($owner_distribution, 'count')); ?>,
                    backgroundColor: [
                        '#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe',
                        '#43e97b', '#38f9d7', '#ffecd2', '#fcb69f', '#a8edea', '#fed6e3'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            maxBoxWidth: 12,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // User Stories Chart
        const storiesCtx = document.getElementById('storiesChart').getContext('2d');
        const storiesData = <?php echo json_encode($user_stories_metrics['by_status']); ?>;
        const storiesChart = new Chart(storiesCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(storiesData),
                datasets: [{
                    data: Object.values(storiesData),
                    backgroundColor: ['#28a745', '#ffc107', '#17a2b8', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Refresh Dashboard Function
        function refreshDashboard() {
            const refreshIcon = document.getElementById('refreshIcon');
            refreshIcon.classList.add('refresh-indicator');
            
            // Simulate refresh (in real implementation, you'd reload data via AJAX)
            setTimeout(() => {
                refreshIcon.classList.remove('refresh-indicator');
                location.reload();
            }, 1000);
        }

        // Auto-refresh every 5 minutes
        setInterval(refreshDashboard, 300000);

        console.log('Executive Dashboard loaded successfully');
    </script>
</body>
</html>
