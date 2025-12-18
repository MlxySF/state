<?php
// Registration Processing with Database Storage
// Saves registration data to MySQL database

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Include database configuration
require_once __DIR__ . '/../config.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
$required = [
    'name_en', 'ic', 'age', 'school', 'status', 'phone', 'email', 
    'events', 'schedule', 'parent_name', 'parent_ic', 'signature_base64', 
    'signed_pdf_base64', 'payment_amount', 'payment_date', 'payment_receipt_base64'
];

foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Generate unique registration number
    $year = date('Y');
    $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    $registration_number = "WSA{$year}-{$random}";
    
    // Prepare SQL statement
    $sql = "INSERT INTO registrations (
        registration_number, name_en, name_cn, ic, age, school, status,
        phone, email, level, events, schedule, class_count,
        parent_name, parent_ic, signature_base64,
        payment_amount, payment_date, payment_receipt_base64, payment_status,
        signed_pdf_base64, form_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    // Bind parameters
    $payment_status = 'pending';
    $stmt->bind_param(
        'ssssississsisssdsssss',
        $registration_number,
        $data['name_en'],
        $data['name_cn'],
        $data['ic'],
        $data['age'],
        $data['school'],
        $data['status'],
        $data['phone'],
        $data['email'],
        $data['level'],
        $data['events'],
        $data['schedule'],
        $data['class_count'],
        $data['parent_name'],
        $data['parent_ic'],
        $data['signature_base64'],
        $data['payment_amount'],
        $data['payment_date'],
        $data['payment_receipt_base64'],
        $payment_status,
        $data['signed_pdf_base64'],
        $data['form_date']
    );
    
    // Execute query
    if ($stmt->execute()) {
        $insert_id = $conn->insert_id;
        
        echo json_encode([
            'success' => true,
            'registration_number' => $registration_number,
            'id' => $insert_id,
            'message' => 'Registration submitted successfully. Admin will review your payment.'
        ]);
    } else {
        throw new Exception('Failed to save registration: ' . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>