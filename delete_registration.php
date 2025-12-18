<?php
// Delete Registration API
// Removes registration record from database

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Include database configuration
require_once __DIR__ . '/config.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing registration ID']);
    exit;
}

$id = intval($data['id']);

try {
    // Delete registration
    $sql = "DELETE FROM registrations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Registration deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Registration not found'
            ]);
        }
    } else {
        throw new Exception('Failed to delete registration: ' . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>