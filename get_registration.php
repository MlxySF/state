<?php
// Get Single Registration API
// Returns detailed registration data including base64 files

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once __DIR__ . '/config.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing registration ID']);
    exit;
}

$id = intval($_GET['id']);

try {
    // Fetch complete registration data including base64 files
    $sql = "SELECT * FROM registrations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Registration not found']);
        exit;
    }
    
    $registration = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'registration' => $registration
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>