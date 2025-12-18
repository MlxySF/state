<?php
// Admin-Only Registration Processing
// This saves registrations to a JSON file for admin viewing

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
$required = ['name_en', 'ic', 'age', 'school', 'status', 'phone', 'email', 'events', 'schedule', 'parent_name', 'parent_ic', 'signature_base64', 'signed_pdf_base64', 'payment_amount', 'payment_date', 'payment_receipt_base64'];

foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
        exit;
    }
}

// Generate unique registration number
$year = date('Y');
$timestamp = time();
$random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
$registration_number = "WSA{$year}-{$random}";

// Prepare registration record
$registration = [
    'registration_number' => $registration_number,
    'timestamp' => date('Y-m-d H:i:s'),
    'student_info' => [
        'name_cn' => $data['name_cn'] ?? '',
        'name_en' => $data['name_en'],
        'ic' => $data['ic'],
        'age' => $data['age'],
        'school' => $data['school'],
        'status' => $data['status']
    ],
    'contact' => [
        'phone' => $data['phone'],
        'email' => $data['email']
    ],
    'training' => [
        'events' => $data['events'],
        'schedule' => $data['schedule'],
        'class_count' => $data['class_count'] ?? 0
    ],
    'parent' => [
        'name' => $data['parent_name'],
        'ic' => $data['parent_ic'],
        'signature_base64' => $data['signature_base64']
    ],
    'payment' => [
        'amount' => $data['payment_amount'],
        'date' => $data['payment_date'],
        'receipt_base64' => $data['payment_receipt_base64'],
        'status' => 'pending' // Admin will approve/reject
    ],
    'documents' => [
        'signed_pdf_base64' => $data['signed_pdf_base64']
    ],
    'form_date' => $data['form_date'] ?? date('d/m/Y'),
    'admin_status' => 'pending', // pending, approved, rejected
    'admin_notes' => ''
];

// Save to JSON file (for simple admin view)
$registrations_file = __DIR__ . '/../data/registrations.json';

// Create data directory if it doesn't exist
if (!file_exists(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data', 0755, true);
}

// Load existing registrations
$registrations = [];
if (file_exists($registrations_file)) {
    $existing = file_get_contents($registrations_file);
    $registrations = json_decode($existing, true) ?? [];
}

// Add new registration
$registrations[] = $registration;

// Save back to file
if (file_put_contents($registrations_file, json_encode($registrations, JSON_PRETTY_PRINT))) {
    echo json_encode([
        'success' => true,
        'registration_number' => $registration_number,
        'message' => 'Registration submitted successfully. Admin will review your payment.'
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save registration']);
}
?>