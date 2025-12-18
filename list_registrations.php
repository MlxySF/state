<?php
// List All Registrations API
// Returns all registration records from database

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once __DIR__ . '/config.php';

try {
    // Fetch all registrations ordered by most recent first
    $sql = "SELECT 
        id, registration_number, name_en, name_cn, ic, age, school, status,
        phone, email, level, events, schedule, class_count,
        parent_name, parent_ic,
        payment_amount, payment_date, payment_status,
        form_date, created_at, updated_at
    FROM registrations 
    ORDER BY created_at DESC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }
    
    $registrations = [];
    while ($row = $result->fetch_assoc()) {
        $registrations[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'registrations' => $registrations,
        'total' => count($registrations)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>