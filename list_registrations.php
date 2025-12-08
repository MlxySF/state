<?php
require 'config.php';

try {
    $sql = "SELECT id, name_en, name_cn, ic, age, school, status, phone, email, 
                   level, events, schedule, parent_name, parent_ic, form_date, 
                   created_at 
            FROM registrations 
            ORDER BY created_at DESC";
    
    $result = $conn->query($sql);
    
    $registrations = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $registrations[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'registrations' => $registrations,
        'count' => count($registrations)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
