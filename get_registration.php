<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing ID parameter']);
    exit;
}

$id = intval($_GET['id']);
$registrations_file = __DIR__ . '/data/registrations.json';

if (!file_exists($registrations_file)) {
    echo json_encode(['success' => false, 'error' => 'No registrations found']);
    exit;
}

$content = file_get_contents($registrations_file);
$registrations = json_decode($content, true) ?? [];

if (!isset($registrations[$id])) {
    echo json_encode(['success' => false, 'error' => 'Registration not found']);
    exit;
}

$reg = $registrations[$id];

// Flatten structure
$registration = [
    'id' => $id,
    'registration_number' => $reg['registration_number'] ?? 'N/A',
    'name_en' => $reg['student_info']['name_en'] ?? '',
    'name_cn' => $reg['student_info']['name_cn'] ?? '',
    'ic' => $reg['student_info']['ic'] ?? '',
    'age' => $reg['student_info']['age'] ?? '',
    'school' => $reg['student_info']['school'] ?? '',
    'status' => $reg['student_info']['status'] ?? '',
    'phone' => $reg['contact']['phone'] ?? '',
    'email' => $reg['contact']['email'] ?? '',
    'events' => $reg['training']['events'] ?? '',
    'schedule' => $reg['training']['schedule'] ?? '',
    'level' => 'Various',
    'parent_name' => $reg['parent']['name'] ?? '',
    'parent_ic' => $reg['parent']['ic'] ?? '',
    'signature_base64' => $reg['parent']['signature_base64'] ?? '',
    'form_date' => $reg['form_date'] ?? '',
    'created_at' => $reg['timestamp'] ?? ''
];

echo json_encode([
    'success' => true,
    'registration' => $registration
]);
?>