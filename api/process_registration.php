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

// DETAILED LOGGING - Check what we actually received
error_log('========== REGISTRATION SUBMISSION DEBUG ==========');
error_log('All received fields: ' . implode(', ', array_keys($data)));

// Check signature specifically
if (isset($data['signature_base64'])) {
    $sig_len = strlen($data['signature_base64']);
    $sig_start = substr($data['signature_base64'], 0, 50);
    error_log("signature_base64: EXISTS, Length={$sig_len}, Starts with: {$sig_start}");
} else {
    error_log('signature_base64: MISSING!');
}

// Check schedule
if (isset($data['schedule'])) {
    error_log("schedule: EXISTS, Value='{$data['schedule']}', Type=" . gettype($data['schedule']));
} else {
    error_log('schedule: MISSING!');
}

// Check events
if (isset($data['events'])) {
    error_log("events: EXISTS, Value='{$data['events']}', Type=" . gettype($data['events']));
} else {
    error_log('events: MISSING!');
}

error_log('===================================================');

// Validate required fields
$required = [
    'name_en', 'ic', 'age', 'school', 'status', 'phone', 'email', 
    'events', 'schedule', 'parent_name', 'parent_ic', 'signature_base64', 
    'signed_pdf_base64', 'payment_amount', 'payment_date', 'payment_receipt_base64'
];

$missing_fields = [];
foreach ($required as $field) {
    if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
        $missing_fields[] = $field;
        error_log("Missing or empty field: {$field}");
    }
}

if (!empty($missing_fields)) {
    $error_response = [
        'success' => false, 
        'error' => 'Missing required fields: ' . implode(', ', $missing_fields),
        'debug' => [
            'missing' => $missing_fields,
            'received_keys' => array_keys($data),
            'signature_check' => isset($data['signature_base64']) ? 'exists (len=' . strlen($data['signature_base64']) . ')' : 'missing',
            'schedule_check' => isset($data['schedule']) ? "exists ('{$data['schedule']}')": 'missing',
            'events_check' => isset($data['events']) ? "exists ('{$data['events']}')": 'missing'
        ]
    ];
    error_log('Validation failed: ' . json_encode($error_response['debug']));
    echo json_encode($error_response);
    exit;
}

// Validate signature is proper base64 data
if (!preg_match('/^data:image\/[a-z]+;base64,/', $data['signature_base64'])) {
    error_log('Invalid signature format - missing data URI prefix');
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid signature format. Must be a data URL.',
        'debug' => [
            'signature_start' => substr($data['signature_base64'], 0, 50)
        ]
    ]);
    exit;
}

try {
    // Generate unique registration number
    $year = date('Y');
    $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    $registration_number = "WSA{$year}-{$random}";
    
    // Set defaults for optional fields
    $name_cn = $data['name_cn'] ?? '';
    $level = $data['level'] ?? '';
    $class_count = isset($data['class_count']) ? (int)$data['class_count'] : 0;
    $form_date = $data['form_date'] ?? date('Y-m-d');
    $payment_status = 'pending';
    
    // Process events - handle both array and string
    $events = is_array($data['events']) ? implode(', ', $data['events']) : $data['events'];
    
    // Process schedule - ensure it's a string
    $schedule = (string)$data['schedule'];
    
    // Process signature - ensure it's a string
    $signature_base64 = (string)$data['signature_base64'];
    
    // Log what we're about to save
    error_log('SAVING TO DATABASE:');
    error_log("  Registration: {$registration_number}");
    error_log("  Events: '{$events}' (length: " . strlen($events) . ")");
    error_log("  Schedule: '{$schedule}' (length: " . strlen($schedule) . ")");
    error_log("  Signature: length=" . strlen($signature_base64));
    
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
    
    // Bind parameters - all as strings except age and class_count
    $stmt->bind_param(
        'ssssississsisssdssssss',  // 22 parameters
        $registration_number,      // 1
        $data['name_en'],          // 2
        $name_cn,                  // 3
        $data['ic'],               // 4
        $data['age'],              // 5 - integer
        $data['school'],           // 6
        $data['status'],           // 7
        $data['phone'],            // 8
        $data['email'],            // 9
        $level,                    // 10
        $events,                   // 11 - processed events
        $schedule,                 // 12 - processed schedule
        $class_count,              // 13 - integer
        $data['parent_name'],      // 14
        $data['parent_ic'],        // 15
        $signature_base64,         // 16 - processed signature
        $data['payment_amount'],   // 17 - double
        $data['payment_date'],     // 18
        $data['payment_receipt_base64'], // 19
        $payment_status,           // 20
        $data['signed_pdf_base64'],// 21
        $form_date                 // 22
    );
    
    // Execute query
    if ($stmt->execute()) {
        $insert_id = $conn->insert_id;
        
        error_log("✓ Registration saved successfully! ID: {$insert_id}");
        
        // Verify data was saved by reading it back
        $verify_sql = "SELECT events, schedule, signature_base64 FROM registrations WHERE id = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param('i', $insert_id);
        $verify_stmt->execute();
        $result = $verify_stmt->get_result();
        $saved_data = $result->fetch_assoc();
        
        error_log('VERIFICATION - Data read back from database:');
        error_log("  Events: '" . ($saved_data['events'] ?? 'NULL') . "'");
        error_log("  Schedule: '" . ($saved_data['schedule'] ?? 'NULL') . "'");
        error_log("  Signature: " . (isset($saved_data['signature_base64']) && $saved_data['signature_base64'] ? 'YES (' . strlen($saved_data['signature_base64']) . ' bytes)' : 'NO/NULL'));
        
        $verify_stmt->close();
        
        echo json_encode([
            'success' => true,
            'registration_number' => $registration_number,
            'id' => $insert_id,
            'message' => 'Registration submitted successfully. Admin will review your payment.',
            'debug' => [
                'events_saved' => $saved_data['events'],
                'schedule_saved' => $saved_data['schedule'],
                'signature_length' => isset($saved_data['signature_base64']) ? strlen($saved_data['signature_base64']) : 0
            ]
        ]);
    } else {
        throw new Exception('Failed to save registration: ' . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log('ERROR saving registration: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>