<?php
// API endpoint to get monthly statistics for admin analytics
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Include database configuration
require_once __DIR__ . '/../config.php';

try {
    // Get year parameter (default to current year)
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    // Fetch monthly stats for the specified year
    $sql = "SELECT 
                year, month, total_registrations, total_revenue,
                state_team_count, backup_team_count, student_count,
                created_at, updated_at
            FROM monthly_stats 
            WHERE year = ?
            ORDER BY month ASC";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $monthly_stats = [];
    while ($row = $result->fetch_assoc()) {
        $monthly_stats[] = $row;
    }
    
    $stmt->close();
    
    // Get year-to-date totals
    $ytd_sql = "SELECT 
                    SUM(total_registrations) as ytd_registrations,
                    SUM(total_revenue) as ytd_revenue,
                    SUM(state_team_count) as ytd_state_team,
                    SUM(backup_team_count) as ytd_backup_team,
                    SUM(student_count) as ytd_students
                FROM monthly_stats 
                WHERE year = ?";
    
    $ytd_stmt = $conn->prepare($ytd_sql);
    $ytd_stmt->bind_param('i', $year);
    $ytd_stmt->execute();
    $ytd_result = $ytd_stmt->get_result();
    $ytd_data = $ytd_result->fetch_assoc();
    $ytd_stmt->close();
    
    // Get comparison with previous year
    $prev_year = $year - 1;
    $prev_sql = "SELECT 
                    SUM(total_registrations) as prev_year_registrations,
                    SUM(total_revenue) as prev_year_revenue
                FROM monthly_stats 
                WHERE year = ?";
    
    $prev_stmt = $conn->prepare($prev_sql);
    $prev_stmt->bind_param('i', $prev_year);
    $prev_stmt->execute();
    $prev_result = $prev_stmt->get_result();
    $prev_data = $prev_result->fetch_assoc();
    $prev_stmt->close();
    
    // Calculate growth percentages
    $registration_growth = 0;
    $revenue_growth = 0;
    
    if ($prev_data['prev_year_registrations'] > 0) {
        $registration_growth = (($ytd_data['ytd_registrations'] - $prev_data['prev_year_registrations']) / $prev_data['prev_year_registrations']) * 100;
    }
    
    if ($prev_data['prev_year_revenue'] > 0) {
        $revenue_growth = (($ytd_data['ytd_revenue'] - $prev_data['prev_year_revenue']) / $prev_data['prev_year_revenue']) * 100;
    }
    
    echo json_encode([
        'success' => true,
        'year' => $year,
        'monthly_data' => $monthly_stats,
        'year_to_date' => [
            'total_registrations' => intval($ytd_data['ytd_registrations'] ?? 0),
            'total_revenue' => floatval($ytd_data['ytd_revenue'] ?? 0),
            'state_team_count' => intval($ytd_data['ytd_state_team'] ?? 0),
            'backup_team_count' => intval($ytd_data['ytd_backup_team'] ?? 0),
            'student_count' => intval($ytd_data['ytd_students'] ?? 0)
        ],
        'comparison' => [
            'previous_year' => $prev_year,
            'prev_year_registrations' => intval($prev_data['prev_year_registrations'] ?? 0),
            'prev_year_revenue' => floatval($prev_data['prev_year_revenue'] ?? 0),
            'registration_growth' => round($registration_growth, 2),
            'revenue_growth' => round($revenue_growth, 2)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>