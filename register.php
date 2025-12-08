<?php
// register.php - receives JSON from front‑end and stores it

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
$name_en  = $data['name_en']  ?? '';
$name_cn  = $data['name_cn']  ?? '';
$ic       = $data['ic']       ?? '';
$age      = $data['age']      ?? '';   // keep as string, easier for bind_param
$school   = $data['school']   ?? '';
$status   = $data['status']   ?? '';
$phone    = $data['phone']    ?? '';
$email    = $data['email']    ?? '';
$level    = $data['level']    ?? '';
$events   = $data['events']   ?? '';
$schedule = $data['schedule'] ?? '';
$parent_name = $data['parent_name'] ?? '';
$parent_ic   = $data['parent_ic']   ?? '';
$form_date   = $data['form_date']   ?? '';
$signature_base64 = $data['signature_base64'] ?? '';
$raw_json = $input;

// Prepare statement – 16 columns, 16 placeholders
$sql = "INSERT INTO registrations
        (name_en, name_cn, ic, age, school, status, phone, email, level,
         events, schedule, parent_name, parent_ic, form_date,
         signature_base64, raw_json)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

// IMPORTANT: 16 type chars for 16 values
$stmt->bind_param(
    'ssssssssssssssss',   // 16 × 's' so count matches 16 placeholders
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
    $raw_json
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
