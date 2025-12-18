<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing ID parameter']);
    exit;
}

$id = intval($data['id']);
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

// Remove the registration
array_splice($registrations, $id, 1);

// Save back to file
if (file_put_contents($registrations_file, json_encode($registrations, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true, 'message' => 'Registration deleted successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save changes']);
}
?>