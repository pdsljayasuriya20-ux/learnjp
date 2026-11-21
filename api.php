<?php
require_once 'config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = getDBConnection();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'dashboard_stats':
            // Get today's stats
            $today = date('Y-m-d');
            
            // Total visits today
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM visitors WHERE DATE(visit_timestamp) = :today");
            $stmt->execute([':today' => $today]);
            $totalVisits = $stmt->fetch()['count'];
            
            // Unique visitors today
            $stmt = $db->prepare("SELECT COUNT(DISTINCT ip_address) as count FROM visitors WHERE DATE(visit_timestamp) = :today");
            $stmt->execute([':today' => $today]);
            $uniqueVisitors = $stmt->fetch()['count'];
            
            // Total countries
            $stmt = $db->query("SELECT COUNT(DISTINCT country) as count FROM visitors WHERE country != 'Unknown'");
            $countries = $stmt->fetch()['count'];
            
            // Average session duration
            $stmt = $db->query("SELECT AVG(total_time_spent) as avg_time FROM user_sessions WHERE total_time_spent > 0");
            $avgTime = round($stmt->fetch()['avg_time'] ?? 0);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'totalVisits' => $totalVisits,
                    'uniqueVisitors' => $uniqueVisitors,
                    'countries' => $countries,
                    'avgTime' => $avgTime
                ]
            ]);
            break;
            
        case 'visitors_chart':
            // Last 7 days visitors
            $stmt = $db->query("
                SELECT 
                    DATE_FORMAT(visit_timestamp, '%a') as day,
                    COUNT(*) as count
                FROM visitors
                WHERE visit_timestamp >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
                GROUP BY DATE(visit_timestamp)
                ORDER BY DATE(visit_timestamp)
            ");
            $data = $stmt->fetchAll();
            
            $labels = array_column($data, 'day');
            $counts = array_column($data, 'count');
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'counts' => $counts
                ]
            ]);
            break;
            
        case 'browser_stats':
            $stmt = $db->query("
                SELECT browser, COUNT(*) as count 
                FROM visitors 
                WHERE browser != 'Unknown' 
                GROUP BY browser 
                ORDER BY count DESC 
                LIMIT 5
            ");
            $data = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;
            
        case 'device_stats':
            $stmt = $db->query("
                SELECT device_type, COUNT(*) as count 
                FROM visitors 
                WHERE device_type IS NOT NULL 
                GROUP BY device_type 
                ORDER BY count DESC
            ");
            $data = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;
            
        case 'top_countries':
            $stmt = $db->query("
                SELECT country, COUNT(*) as count 
                FROM visitors 
                WHERE country != 'Unknown' 
                GROUP BY country 
                ORDER BY count DESC 
                LIMIT 10
            ");
            $data = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;
            
        case 'recent_visitors':
            $page = $_GET['page'] ?? 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            // Apply filters
            $where = [];
            $params = [];
            
            if (!empty($_GET['date_from'])) {
                $where[] = "visit_timestamp >= :date_from";
                $params[':date_from'] = $_GET['date_from'] . ' 00:00:00';
            }
            
            if (!empty($_GET['date_to'])) {
                $where[] = "visit_timestamp <= :date_to";
                $params[':date_to'] = $_GET['date_to'] . ' 23:59:59';
            }
            
            if (!empty($_GET['country'])) {
                $where[] = "country = :country";
                $params[':country'] = $_GET['country'];
            }
            
            if (!empty($_GET['device'])) {
                $where[] = "device_type = :device";
                $params[':device'] = $_GET['device'];
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Get total count
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM visitors {$whereClause}");
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];
            
            // Get visitors
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            $stmt = $db->prepare("
                SELECT 
                    visit_timestamp,
                    ip_address,
                    country,
                    city,
                    page_title,
                    page_url,
                    browser,
                    device_type,
                    operating_system,
                    referrer
                FROM visitors 
                {$whereClause}
                ORDER BY visit_timestamp DESC 
                LIMIT :limit OFFSET :offset
            ");
            $stmt->execute($params);
            $visitors = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $visitors,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_records' => $total
                ]
            ]);
            break;
            
        case 'popular_pages':
            $stmt = $db->query("
                SELECT 
                    page_title,
                    page_url,
                    COUNT(*) as views,
                    COUNT(DISTINCT ip_address) as unique_views
                FROM visitors
                WHERE page_url IS NOT NULL
                GROUP BY page_url, page_title
                ORDER BY views DESC
                LIMIT 20
            ");
            $data = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;
            
        case 'realtime_visitors':
            // Visitors in last 5 minutes
            $stmt = $db->query("
                SELECT 
                    visit_timestamp,
                    country,
                    city,
                    page_title,
                    browser,
                    device_type
                FROM visitors
                WHERE visit_timestamp >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ORDER BY visit_timestamp DESC
                LIMIT 50
            ");
            $data = $stmt->fetchAll();
            
            // Active sessions (last 30 minutes)
            $stmt = $db->query("
                SELECT COUNT(DISTINCT session_id) as count
                FROM visitors
                WHERE visit_timestamp >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
            ");
            $activeSessions = $stmt->fetch()['count'];
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'recent_visitors' => $data,
                    'active_sessions' => $activeSessions
                ]
            ]);
            break;
            
        case 'export_csv':
            // Check permission
            if (!hasRole('admin')) {
                echo json_encode(['error' => 'Insufficient permissions']);
                exit;
            }
            
            // Get all visitors data
            $stmt = $db->query("
                SELECT 
                    visit_timestamp,
                    ip_address,
                    country,
                    city,
                    page_title,
                    page_url,
                    browser,
                    device_type,
                    operating_system,
                    referrer
                FROM visitors
                ORDER BY visit_timestamp DESC
            ");
            $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generate CSV
            $filename = 'visitors_export_' . date('Y-m-d_H-i-s') . '.csv';
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // Add headers
            if (!empty($visitors)) {
                fputcsv($output, array_keys($visitors[0]));
            }
            
            // Add data
            foreach ($visitors as $visitor) {
                fputcsv($output, $visitor);
            }
            
            fclose($output);
            exit;
            
        case 'analytics_summary':
            // Comprehensive analytics
            $period = $_GET['period'] ?? '7'; // days
            
            $stmt = $db->prepare("
                SELECT 
                    DATE(visit_timestamp) as date,
                    COUNT(*) as total_visits,
                    COUNT(DISTINCT ip_address) as unique_visitors,
                    COUNT(DISTINCT session_id) as sessions
                FROM visitors
                WHERE visit_timestamp >= DATE_SUB(CURRENT_DATE, INTERVAL :period DAY)
                GROUP BY DATE(visit_timestamp)
                ORDER BY date
            ");
            $stmt->execute([':period' => $period]);
            $dailyStats = $stmt->fetchAll();
            
            // Bounce rate (sessions with only 1 page view)
            $stmt = $db->prepare("
                SELECT 
                    COUNT(CASE WHEN total_pages_viewed = 1 THEN 1 END) as bounced,
                    COUNT(*) as total
                FROM user_sessions
                WHERE start_time >= DATE_SUB(NOW(), INTERVAL :period DAY)
            ");
            $stmt->execute([':period' => $period]);
            $bounceData = $stmt->fetch();
            $bounceRate = $bounceData['total'] > 0 
                ? round(($bounceData['bounced'] / $bounceData['total']) * 100, 2) 
                : 0;
            
            // Average pages per session
            $stmt = $db->prepare("
                SELECT AVG(total_pages_viewed) as avg_pages
                FROM user_sessions
                WHERE start_time >= DATE_SUB(NOW(), INTERVAL :period DAY)
            ");
            $stmt->execute([':period' => $period]);
            $avgPages = round($stmt->fetch()['avg_pages'] ?? 0, 2);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'daily_stats' => $dailyStats,
                    'bounce_rate' => $bounceRate,
                    'avg_pages_per_session' => $avgPages,
                    'period' => $period
                ]
            ]);
            break;
            
        case 'get_countries_list':
            // Get unique countries for filter dropdown
            $stmt = $db->query("
                SELECT DISTINCT country 
                FROM visitors 
                WHERE country != 'Unknown' 
                ORDER BY country
            ");
            $countries = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo json_encode([
                'success' => true,
                'data' => $countries
            ]);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
    
} catch (PDOException $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
?>