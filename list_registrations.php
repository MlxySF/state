<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Read registrations from JSON file
$registrations_file = __DIR__ . '/data/registrations.json';

if (!file_exists($registrations_file)) {
    echo json_encode([
        'success' => true,
        'registrations' => []
    ]);
    exit;
}

$content = file_get_contents($registrations_file);
$registrations = json_decode($content, true) ?? [];

// Add ID to each registration for easy reference
$registrations_with_ids = array_map(function($reg, $index) {
    $reg['id'] = $index;
    // Flatten structure for easier display
    return [
        'id' => $index,
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
        'level' => 'Various', // Derived from events
        'parent_name' => $reg['parent']['name'] ?? '',
        'parent_ic' => $reg['parent']['ic'] ?? '',
        'signature_base64' => $reg['parent']['signature_base64'] ?? '',
        'form_date' => $reg['form_date'] ?? '',
        'created_at' => $reg['timestamp'] ?? '',
        'payment_status' => $reg['payment']['status'] ?? 'pending',
        'payment_amount' => $reg['payment']['amount'] ?? 0,
        'admin_status' => $reg['admin_status'] ?? 'pending'
    ];
}, $registrations, array_keys($registrations));

echo json_encode([
    'success' => true,
    'registrations' => $registrations_with_ids
]);
?>