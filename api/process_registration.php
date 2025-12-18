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
    
    // DEBUG: Log what we're about to insert
    $debug_data = [
        'registration_number' => $registration_number,
        'name_en' => $data['name_en'] ?? 'NULL',
        'name_cn' => $data['name_cn'] ?? 'NULL',
        'ic' => $data['ic'] ?? 'NULL',
        'age' => $data['age'] ?? 'NULL',
        'school' => $data['school'] ?? 'NULL',
        'status' => $data['status'] ?? 'NULL',
        'phone' => $data['phone'] ?? 'NULL',
        'email' => $data['email'] ?? 'NULL',
        'level' => $data['level'] ?? 'NULL',
        'events' => $data['events'] ?? 'NULL',
        'schedule' => $data['schedule'] ?? 'NULL',
        'class_count' => $data['class_count'] ?? 'NULL',
        'parent_name' => $data['parent_name'] ?? 'NULL',
        'parent_ic' => $data['parent_ic'] ?? 'NULL',
        'payment_amount' => $data['payment_amount'] ?? 'NULL',
        'payment_date' => $data['payment_date'] ?? 'NULL',
        'form_date' => $data['form_date'] ?? 'NULL'
    ];
    
    error_log('DEBUG - Registration data: ' . json_encode($debug_data));
    
    // Set defaults for optional fields
    $name_cn = $data['name_cn'] ?? '';
    $level = $data['level'] ?? '';
    $class_count = isset($data['class_count']) ? (int)$data['class_count'] : 0;
    $form_date = $data['form_date'] ?? date('Y-m-d');
    $payment_status = 'pending';
    
    // Prepare SQL statement
    $sql = "INSERT INTO registrations (
        registration_number, name_en, name_cn, ic, age, school, status,
        phone, email, level, events, schedule, class_count,
        parent_name, parent_ic, signature_base64,
        payment_amount, payment_date, payment_receipt_base64, payment_status,
        signed_pdf_base64, form_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    error_log('DEBUG - SQL: ' . $sql);
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    // Count parameters
    $param_count = substr_count($sql, '?');
    error_log('DEBUG - SQL has ' . $param_count . ' parameters');
    
    // Bind parameters - Fixed type string with 22 characters for 22 parameters
    // s=string, i=integer, d=double
    $type_string = 'ssssississsisssdssssss';
    error_log('DEBUG - Type string: ' . $type_string . ' (length: ' . strlen($type_string) . ')');
    
    $bind_result = $stmt->bind_param(
        $type_string,
        $registration_number,      // 1: s
        $data['name_en'],          // 2: s
        $name_cn,                  // 3: s
        $data['ic'],               // 4: s
        $data['age'],              // 5: i
        $data['school'],           // 6: s
        $data['status'],           // 7: s
        $data['phone'],            // 8: s
        $data['email'],            // 9: s
        $level,                    // 10: s
        $data['events'],           // 11: s
        $data['schedule'],         // 12: s
        $class_count,              // 13: i
        $data['parent_name'],      // 14: s
        $data['parent_ic'],        // 15: s
        $data['signature_base64'], // 16: s
        $data['payment_amount'],   // 17: d
        $data['payment_date'],     // 18: s
        $data['payment_receipt_base64'], // 19: s
        $payment_status,           // 20: s
        $data['signed_pdf_base64'],// 21: s
        $form_date                 // 22: s
    );
    
    if (!$bind_result) {
        throw new Exception('Bind param failed: ' . $stmt->error);
    }
    
    error_log('DEBUG - Parameters bound successfully');
    
    // Execute query
    if ($stmt->execute()) {
        $insert_id = $conn->insert_id;
        
        error_log('DEBUG - Insert successful, ID: ' . $insert_id);
        
        echo json_encode([
            'success' => true,
            'registration_number' => $registration_number,
            'id' => $insert_id,
            'message' => 'Registration submitted successfully. Admin will review your payment.'
        ]);
    } else {
        error_log('DEBUG - Execute failed: ' . $stmt->error);
        throw new Exception('Failed to save registration: ' . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log('DEBUG - Exception: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => [
            'type_string_length' => isset($type_string) ? strlen($type_string) : 0,
            'sql_param_count' => isset($param_count) ? $param_count : 0
        ]
    ]);
}

$conn->close();
?>