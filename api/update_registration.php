<?php
// Update Registration Status API
// Allows admin to approve/reject registrations

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Include database configuration
require_once __DIR__ . '/../config.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['id']) || !isset($data['payment_status'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters: id and payment_status']);
    exit;
}

$id = (int)$data['id'];
$payment_status = $data['payment_status'];

// Validate payment status
$valid_statuses = ['pending', 'approved', 'rejected'];
if (!in_array($payment_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid payment status. Must be: pending, approved, or rejected']);
    exit;
}

try {
    // Update the registration
    $sql = "UPDATE registrations SET payment_status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('si', $payment_status, $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Registration status updated successfully',
                'id' => $id,
                'new_status' => $payment_status
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'No registration found with the provided ID or status unchanged'
            ]);
        }
    } else {
        throw new Exception('Failed to update registration: ' . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log('Error updating registration: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>