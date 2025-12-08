<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$input = @file_get_contents('php://input');
$data = @json_decode($input, true);

if (!$data || !isset($data['id'])) {
    die(json_encode(['success' => false, 'error' => 'No ID provided', 'input' => $input]));
}

$id = intval($data['id']);

// Database connection - REPLACE THESE VALUES
$host = 'localhost';
$user = 'mlxysf_state';     // ← CHANGE THIS
$pass = 'BIrh57MoXE6dZ';     // ← CHANGE THIS
$db   = 'mlxysf_state';   // ← CHANGE THIS

$conn = @new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Connection failed: ' . $conn->connect_error]));
}

$stmt = $conn->prepare("DELETE FROM registrations WHERE id = ?");

if (!$stmt) {
    die(json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]));
}

$stmt->bind_param('i', $id);

if (!$stmt->execute()) {
    die(json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]));
}

$affected = $stmt->affected_rows;
$stmt->close();
$conn->close();

if ($affected > 0) {
    echo json_encode(['success' => true, 'message' => 'Deleted', 'id' => $id]);
} else {
    echo json_encode(['success' => false, 'error' => 'ID not found']);
}
?>
