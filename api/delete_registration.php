<?php
// API endpoint to delete a registration
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing registration ID']);
    exit;
}

$id = intval($data['id']);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid registration ID']);
    exit;
}

// Include database configuration
require_once __DIR__ . '/../config.php';

try {
    // Check if registration exists
    $check_sql = "SELECT id FROM registrations WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if (!$check_stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $check_stmt->bind_param('i', $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Registration not found'
        ]);
        exit;
    }
    
    $check_stmt->close();
    
    // Delete registration
    $delete_sql = "DELETE FROM registrations WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    
    if (!$delete_stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $delete_stmt->bind_param('i', $id);
    
    if ($delete_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Registration deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete registration: ' . $delete_stmt->error);
    }
    
    $delete_stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>