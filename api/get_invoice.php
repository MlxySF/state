<?php
// Get Invoice API
// Generates and returns invoice for a specific registration

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Registration ID is required']);
    exit;
}

// Include database configuration
require_once __DIR__ . '/../config.php';

// Include invoice generator
require_once __DIR__ . '/generate_invoice_pdf.php';

$id = (int)$_GET['id'];

try {
    // Get registration details
    $sql = "SELECT * FROM registrations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Registration not found']);
        exit;
    }
    
    $registration = $result->fetch_assoc();
    $stmt->close();
    
    // Generate invoice HTML
    $invoiceHTML = getInvoiceHTML($registration);
    
    // Return invoice HTML
    echo json_encode([
        'success' => true,
        'invoice_html' => $invoiceHTML,
        'registration_number' => $registration['registration_number']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>