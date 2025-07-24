<?php
// public/api/dashboard_data.php
session_start();
require_once __DIR__ . '/../../src/db/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$db = Database::getInstance()->getConnection();

function getDashboardData($db) {
    $data = [];
    
    try {
        // Key Metrics
        $data['key_metrics'] = [
            'total_applications' => (int)$db->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
            'active_applications' => (int)$db->query("SELECT COUNT(*) FROM applications WHERE status NOT IN ('completed', 'retired', 'cancelled')")->fetchColumn(),
            'ai_insights' => 0,
            'user_stories_completed' => 0
        ];
        
        // Calculate completion rate
        $stmt = $db->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status IN ('completed', 'operate') THEN 1 ELSE 0 END) as completed
            FROM applications");
        $result = $stmt->fetch();
        $data['key_metrics']['completion_rate'] = $result['total'] > 0 ? 
            round(($result['completed'] / $result['total']) * 100, 1) : 0;
        
        // Try to get AI insights count
        try {
            $data['key_metrics']['ai_insights'] = (int)$db->query(
                "SELECT COUNT(*) FROM ai_analysis WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )->fetchColumn();
        } catch (Exception $e) {
            // Table might not exist
        }
        
        // Try to get user stories count
        try {
            $data['key_metrics']['user_stories_completed'] = (int)$db->query(
                "SELECT COUNT(*) FROM user_stories WHERE status = 'done'"
            )->fetchColumn();
        } catch (Exception $e) {
            // Table might not exist
        }
        
        // Phase Distribution
        $stmt = $db->query("SELECT 
            phase, 
            COUNT(*) as count,
            ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM applications)), 1) as percentage
            FROM applications 
            WHERE phase IS NOT NULL 
            GROUP BY phase 
            ORDER BY FIELD(phase, 'need', 'solution', 'build', 'implement', 'operate')");
        $data['phase_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Status Distribution
        $stmt = $db->query("SELECT 
            status, 
            COUNT(*) as count,
            ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM applications)), 1) as percentage
            FROM applications 
            WHERE status IS NOT NULL 
            GROUP BY status
            ORDER BY count DESC");
        $data['status_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Product Owner Distribution (Top 10)
        $stmt = $db->query("SELECT 
            COALESCE(product_owner, 'Unassigned') as owner, 
            COUNT(*) as count
            FROM applications 
            GROUP BY product_owner 
            ORDER BY count DESC 
            LIMIT 10");
        $data['owner_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Timeline Data (Last 12 months activity)
        $stmt = $db->query("SELECT 
            DATE_FORMAT(updated_at, '%Y-%m') as month,
            DATE_FORMAT(updated_at, '%M %Y') as month_label,
            COUNT(*) as activity_count
            FROM applications 
            WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(updated_at, '%Y-%m')
            ORDER BY month");
        $data['timeline_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Recent Activity
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
                LIMIT 15");
            $data['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $data['recent_activity'] = [];
        }
        
        // User Stories Metrics
        try {
            $stmt = $db->query("SELECT 
                status,
                priority,
                COUNT(*) as count
                FROM user_stories 
                GROUP BY status, priority");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stories_metrics = [
                'by_status' => [],
                'by_priority' => [],
                'total' => 0
            ];
            
            foreach ($results as $row) {
                $stories_metrics['by_status'][$row['status']] = 
                    ($stories_metrics['by_status'][$row['status']] ?? 0) + $row['count'];
                $stories_metrics['by_priority'][$row['priority']] = 
                    ($stories_metrics['by_priority'][$row['priority']] ?? 0) + $row['count'];
                $stories_metrics['total'] += $row['count'];
            }
            
            $data['user_stories_metrics'] = $stories_metrics;
        } catch (Exception $e) {
            $data['user_stories_metrics'] = ['by_status' => [], 'by_priority' => [], 'total' => 0];
        }
        
        // Application Health Score (custom metric)
        $stmt = $db->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN phase = 'operate' THEN 1 ELSE 0 END) as operating,
            SUM(CASE WHEN handover_status = 'completed' THEN 1 ELSE 0 END) as handed_over
            FROM applications");
        $health = $stmt->fetch();
        
        $health_score = 0;
        if ($health['total'] > 0) {
            $health_score = round((
                ($health['operating'] * 0.4) + 
                ($health['handed_over'] * 0.3) + 
                ($health['active'] * 0.3)
            ) / $health['total'] * 100, 1);
        }
        
        $data['health_score'] = $health_score;
        
        // Risk Indicators
        $data['risk_indicators'] = [
            'overdue_applications' => 0,
            'missing_owners' => 0,
            'stalled_projects' => 0
        ];
        
        // Overdue applications (due_date passed)
        try {
            $data['risk_indicators']['overdue_applications'] = (int)$db->query(
                "SELECT COUNT(*) FROM applications 
                 WHERE due_date < NOW() AND status NOT IN ('completed', 'cancelled', 'operate')"
            )->fetchColumn();
        } catch (Exception $e) {
            // due_date column might not exist
        }
        
        // Applications without product owners
        $data['risk_indicators']['missing_owners'] = (int)$db->query(
            "SELECT COUNT(*) FROM applications WHERE product_owner IS NULL OR product_owner = ''"
        )->fetchColumn();
        
        // Stalled projects (no activity in 30 days)
        $data['risk_indicators']['stalled_projects'] = (int)$db->query(
            "SELECT COUNT(*) FROM applications 
             WHERE updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY) 
             AND status NOT IN ('completed', 'cancelled', 'operate')"
        )->fetchColumn();
        
        $data['success'] = true;
        $data['timestamp'] = date('Y-m-d H:i:s');
        
    } catch (Exception $e) {
        $data = [
            'success' => false,
            'error' => 'Failed to fetch dashboard data: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    return $data;
}

// Handle different request types
$request_type = $_GET['type'] ?? 'all';

switch ($request_type) {
    case 'metrics':
        // Return only key metrics for quick updates
        $metrics = [
            'total_applications' => (int)$db->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
            'active_applications' => (int)$db->query("SELECT COUNT(*) FROM applications WHERE status NOT IN ('completed', 'retired', 'cancelled')")->fetchColumn(),
        ];
        
        $stmt = $db->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status IN ('completed', 'operate') THEN 1 ELSE 0 END) as completed
            FROM applications");
        $result = $stmt->fetch();
        $metrics['completion_rate'] = $result['total'] > 0 ? 
            round(($result['completed'] / $result['total']) * 100, 1) : 0;
        
        echo json_encode(['success' => true, 'data' => $metrics]);
        break;
        
    case 'activity':
        // Return only recent activity
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
            $activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $activity]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'all':
    default:
        // Return all dashboard data
        echo json_encode(getDashboardData($db));
        break;
}
?>
