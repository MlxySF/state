<?php
// register.php - receives JSON from front-end and stores it

require 'config.php';  // keeps DB connection + sets JSON header

// Read raw JSON
$input = file_get_contents('php://input');
$data  = json_decode($input, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

// Extract fields (use ?? '' so everything is defined)
$name_en  = $data['nameEn']  ?? ($data['name_en'] ?? '');
$name_cn  = $data['nameCn']  ?? ($data['name_cn'] ?? '');
$ic       = $data['ic']       ?? '';
$age      = $data['age']      ?? '';   // keep as string, easier for bind_param
$school   = $data['school']   ?? '';
$status   = $data['status']   ?? '';
$phone    = $data['phone']    ?? '';
$email    = $data['email']    ?? '';
$level    = $data['level']    ?? '';
$events   = $data['events']   ?? '';
$schedule = $data['schedule'] ?? '';
$parent_name = $data['parent'] ?? ($data['parent_name'] ?? '');
$parent_ic   = $data['parentIC'] ?? ($data['parent_ic']   ?? '');
$form_date   = $data['date'] ?? ($data['form_date']   ?? '');
$signature_base64 = $data['signature'] ?? ($data['signature_base64'] ?? '');
$pdf_base64 = $data['pdfBase64'] ?? '';
$raw_json = $input;

// Prepare statement
$sql = "INSERT INTO registrations
        (name_en, name_cn, ic, age, school, status, phone, email, level,
         events, schedule, parent_name, parent_ic, form_date,
         signature_base64, pdf_base64, raw_json, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

// Bind parameters
$stmt->bind_param(
    'sssssssssssssssss',
    $name_en,
    $name_cn,
    $ic,
    $age,
    $school,
    $status,
    $phone,
    $email,
    $level,
    $events,
    $schedule,
    $parent_name,
    $parent_ic,
    $form_date,
    $signature_base64,
    $pdf_base64,
    $raw_json
);

if ($stmt->execute()) {
    $insert_id = $stmt->insert_id;
    $registration_id = 'STATE-2026-' . str_pad($insert_id, 4, '0', STR_PAD_LEFT);
    
    echo json_encode([
        'success' => true, 
        'id' => $insert_id,
        'registration_id' => $registration_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>