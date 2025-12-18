<?php
// API endpoint to get a single registration by ID
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing registration ID']);
    exit;
}

$id = intval($_GET['id']);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid registration ID']);
    exit;
}

// Include database configuration
require_once __DIR__ . '/../config.php';

try {
    // Fetch registration including base64 fields for PDF generation
    $sql = "SELECT 
                id, registration_number, name_en, name_cn, ic, age, school, status,
                phone, email, level, events, schedule, class_count,
                parent_name, parent_ic, signature_base64,
                payment_amount, payment_date, payment_receipt_base64, payment_status,
                signed_pdf_base64, form_date, created_at, updated_at
            FROM registrations 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Registration not found'
        ]);
        exit;
    }
    
    $registration = $result->fetch_assoc();
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'registration' => $registration
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>