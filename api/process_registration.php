<?php
// Registration Processing with Database Storage
// Saves registration data to MySQL database and sends confirmation email

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

// Include email functions
require_once __DIR__ . '/send_confirmation_email.php';

// Set charset to UTF-8 to handle Chinese characters
$conn->set_charset('utf8mb4');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit;
}

// DETAILED LOGGING
error_log('========== REGISTRATION SUBMISSION DEBUG ==========');
error_log('All received fields: ' . implode(', ', array_keys($data)));

if (isset($data['signature_base64'])) {
    error_log("signature_base64: EXISTS, Length=" . strlen($data['signature_base64']));
} else {
    error_log('signature_base64: MISSING!');
}

if (isset($data['schedule'])) {
    error_log("schedule: EXISTS, Value='{$data['schedule']}'");
} else {
    error_log('schedule: MISSING!');
}

if (isset($data['events'])) {
    error_log("events: EXISTS, Value='{$data['events']}'");
} else {
    error_log('events: MISSING!');
}

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
    }
}

if (!empty($missing_fields)) {
    echo json_encode([
        'success' => false, 
        'error' => 'Missing required fields: ' . implode(', ', $missing_fields),
        'debug' => ['missing' => $missing_fields]
    ]);
    exit;
}

// Validate signature format
if (!preg_match('/^data:image\/[a-z]+;base64,/', $data['signature_base64'])) {
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid signature format.'
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
    
    // Process data
    $events = is_array($data['events']) ? implode(', ', $data['events']) : $data['events'];
    $schedule = $data['schedule'];
    $signature_base64 = $data['signature_base64'];
    $age = (int)$data['age'];
    $payment_amount = (float)$data['payment_amount'];
    
    error_log('SAVING TO DATABASE:');
    error_log("  Events: '{$events}' (len: " . strlen($events) . ")");
    error_log("  Schedule: '{$schedule}' (len: " . strlen($schedule) . ")");
    error_log("  Signature: len=" . strlen($signature_base64));
    
    // Use prepared statement with proper escaping
    // Escape all string values to prevent SQL injection and handle special characters
    $registration_number_escaped = $conn->real_escape_string($registration_number);
    $name_en = $conn->real_escape_string($data['name_en']);
    $name_cn = $conn->real_escape_string($name_cn);
    $ic = $conn->real_escape_string($data['ic']);
    $school = $conn->real_escape_string($data['school']);
    $status = $conn->real_escape_string($data['status']);
    $phone = $conn->real_escape_string($data['phone']);
    $email = $conn->real_escape_string($data['email']);
    $level = $conn->real_escape_string($level);
    $events_escaped = $conn->real_escape_string($events);
    $schedule_escaped = $conn->real_escape_string($schedule);
    $parent_name = $conn->real_escape_string($data['parent_name']);
    $parent_ic = $conn->real_escape_string($data['parent_ic']);
    $signature_base64_escaped = $conn->real_escape_string($signature_base64);
    $payment_date = $conn->real_escape_string($data['payment_date']);
    $payment_receipt_base64 = $conn->real_escape_string($data['payment_receipt_base64']);
    $signed_pdf_base64 = $conn->real_escape_string($data['signed_pdf_base64']);
    $form_date = $conn->real_escape_string($form_date);
    $payment_status_escaped = $conn->real_escape_string($payment_status);
    
    // Build SQL with escaped values
    $sql = "INSERT INTO registrations (
        registration_number, name_en, name_cn, ic, age, school, status,
        phone, email, level, events, schedule, class_count,
        parent_name, parent_ic, signature_base64,
        payment_amount, payment_date, payment_receipt_base64, payment_status,
        signed_pdf_base64, form_date
    ) VALUES (
        '{$registration_number_escaped}', '{$name_en}', '{$name_cn}', '{$ic}', {$age}, '{$school}', '{$status}',
        '{$phone}', '{$email}', '{$level}', '{$events_escaped}', '{$schedule_escaped}', {$class_count},
        '{$parent_name}', '{$parent_ic}', '{$signature_base64_escaped}',
        {$payment_amount}, '{$payment_date}', '{$payment_receipt_base64}', '{$payment_status_escaped}',
        '{$signed_pdf_base64}', '{$form_date}'
    )";
    
    error_log('Executing SQL insert...');
    
    if ($conn->query($sql)) {
        $insert_id = $conn->insert_id;
        
        error_log("✓ Registration saved! ID: {$insert_id}");
        
        // Verify data was saved
        $verify_sql = "SELECT events, schedule, signature_base64 FROM registrations WHERE id = {$insert_id}";
        $result = $conn->query($verify_sql);
        $saved_data = $result->fetch_assoc();
        
        error_log('VERIFICATION - Data read back:');
        error_log("  Events: '" . ($saved_data['events'] ?? 'NULL') . "'");
        error_log("  Schedule: '" . ($saved_data['schedule'] ?? 'NULL') . "'");
        error_log("  Signature length: " . (isset($saved_data['signature_base64']) ? strlen($saved_data['signature_base64']) : 0));
        
        // Send confirmation email to user
        $emailSent = false;
        try {
            $emailSent = sendRegistrationConfirmationEmail(
                $data['email'],
                $data['name_en'],
                $registration_number,
                $events,
                $schedule,
                $payment_amount
            );
            
            if ($emailSent) {
                error_log("✓ Confirmation email sent to {$data['email']}");
            } else {
                error_log("⚠ Failed to send confirmation email to {$data['email']}");
            }
        } catch (Exception $emailError) {
            error_log("⚠ Email error: " . $emailError->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'registration_number' => $registration_number,
            'id' => $insert_id,
            'message' => 'Registration submitted successfully! Please check your email for confirmation. Admin will review your payment within 1-2 business days.',
            'email_sent' => $emailSent,
            'debug' => [
                'events_saved' => $saved_data['events'],
                'schedule_saved' => $saved_data['schedule'],
                'signature_length' => isset($saved_data['signature_base64']) ? strlen($saved_data['signature_base64']) : 0
            ]
        ]);
    } else {
        throw new Exception('Failed to save: ' . $conn->error);
    }
    
} catch (Exception $e) {
    error_log('ERROR: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>