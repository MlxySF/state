<?php
// Get Monthly Statistics API
// Returns smart analytics for monthly registration trends

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once __DIR__ . '/config.php';

try {
    // Get year parameter (default to current year)
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    // Fetch monthly stats for the specified year
    $sql = "SELECT 
        year, month, 
        total_registrations, total_revenue,
        state_team_count, backup_team_count, student_count,
        created_at, updated_at
    FROM monthly_stats 
    WHERE year = ?
    ORDER BY month ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $monthly_data = [];
    while ($row = $result->fetch_assoc()) {
        $monthly_data[] = $row;
    }
    
    // Calculate year totals
    $year_total_registrations = 0;
    $year_total_revenue = 0;
    $year_state_team = 0;
    $year_backup_team = 0;
    $year_students = 0;
    
    foreach ($monthly_data as $month) {
        $year_total_registrations += $month['total_registrations'];
        $year_total_revenue += $month['total_revenue'];
        $year_state_team += $month['state_team_count'];
        $year_backup_team += $month['backup_team_count'];
        $year_students += $month['student_count'];
    }
    
    echo json_encode([
        'success' => true,
        'year' => $year,
        'monthly_data' => $monthly_data,
        'year_summary' => [
            'total_registrations' => $year_total_registrations,
            'total_revenue' => $year_total_revenue,
            'state_team_count' => $year_state_team,
            'backup_team_count' => $year_backup_team,
            'student_count' => $year_students
        ]
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>