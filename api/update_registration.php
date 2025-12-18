<?php
// Update Registration Status API
// Allows admin to approve/reject registrations with email notifications and PDF attachments

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

// Include email functions
require_once __DIR__ . '/send_email.php';

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
    // Get FULL registration details including PDFs for email attachments
    $selectSql = "SELECT * FROM registrations WHERE id = ?";
    $selectStmt = $conn->prepare($selectSql);
    
    if (!$selectStmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $selectStmt->bind_param('i', $id);
    $selectStmt->execute();
    $result = $selectStmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'No registration found with the provided ID'
        ]);
        exit;
    }
    
    $registration = $result->fetch_assoc();
    $oldStatus = $registration['payment_status'];
    $selectStmt->close();
    
    // Only send email if status is actually changing and it's approved or rejected
    $shouldSendEmail = ($oldStatus !== $payment_status) && ($payment_status === 'approved' || $payment_status === 'rejected');
    
    // Update the registration
    $updateSql = "UPDATE registrations SET payment_status = ?, updated_at = NOW() WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    
    if (!$updateStmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $updateStmt->bind_param('si', $payment_status, $id);
    
    if ($updateStmt->execute()) {
        if ($updateStmt->affected_rows > 0) {
            $response = [
                'success' => true,
                'message' => 'Registration status updated successfully',
                'id' => $id,
                'new_status' => $payment_status,
                'email_sent' => false
            ];
            
            // Send email notification if status changed to approved or rejected
            if ($shouldSendEmail) {
                try {
                    error_log("Sending {$payment_status} email with attachments to {$registration['email']}");
                    
                    // Pass full registration data for PDF attachments
                    $emailSent = sendPaymentEmail(
                        $registration['email'],
                        $registration['name_en'],
                        $registration['registration_number'],
                        $payment_status,
                        $registration['payment_amount'],
                        $payment_status === 'approved' ? $registration : null // Only attach PDFs for approved
                    );
                    
                    $response['email_sent'] = $emailSent;
                    
                    if ($emailSent) {
                        $attachmentInfo = $payment_status === 'approved' ? ' with attachments' : '';
                        $response['message'] .= " and email notification sent{$attachmentInfo} to " . $registration['email'];
                        error_log("✓ Email sent successfully to {$registration['email']} for registration {$registration['registration_number']}");
                    } else {
                        $response['message'] .= ' but email notification failed';
                        error_log("⚠ Failed to send email to {$registration['email']} for registration {$registration['registration_number']}");
                    }
                } catch (Exception $emailError) {
                    error_log("⚠ Email error: " . $emailError->getMessage());
                    $response['message'] .= ' but email notification encountered an error';
                }
            }
            
            echo json_encode($response);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'No changes made - status may already be set to this value'
            ]);
        }
    } else {
        throw new Exception('Failed to update registration: ' . $updateStmt->error);
    }
    
    $updateStmt->close();
    
} catch (Exception $e) {
    error_log('Error updating registration: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>