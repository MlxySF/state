<?php
// Download Invoice as PDF
// Generates and downloads invoice as a PDF file

error_reporting(0);
ini_set('display_errors', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    exit('Registration ID is required');
}

try {
    // Include database configuration
    $db_path = __DIR__ . '/../config.php';
    if (!file_exists($db_path)) {
        throw new Exception('Database configuration file not found');
    }
    
    require_once $db_path;
    
    if (!isset($conn)) {
        throw new Exception('Database connection not established');
    }
    
    $registration_id = (int)$_GET['id'];
    
    // Get registration details
    $sql = "SELECT * FROM registrations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database query preparation failed: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $registration_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        http_response_code(404);
        exit('Registration not found');
    }
    
    $registration = $result->fetch_assoc();
    $stmt->close();
    
    // Generate invoice HTML
    $invoiceHTML = generateInvoiceHTML($registration);
    
    // Check if mPDF is installed
    $mpdfPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($mpdfPath)) {
        // Fallback: use browser's print-to-PDF
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="Invoice_' . htmlspecialchars($registration['registration_number']) . '.html"');
        echo $invoiceHTML;
        exit;
    }
    
    require_once $mpdfPath;
    
    $mpdf = new \Mpdf\Mpdf([
        'format' => 'A4',
        'margin_left' => 0,
        'margin_right' => 0,
        'margin_top' => 0,
        'margin_bottom' => 0,
    ]);
    
    $mpdf->WriteHTML($invoiceHTML);
    
    $filename = 'Invoice_' . htmlspecialchars($registration['registration_number']) . '_' . date('YmdHis') . '.pdf';
    $mpdf->Output($filename, 'D');
    
} catch (Exception $e) {
    http_response_code(500);
    exit('Error: ' . $e->getMessage());
}

exit;

function generateInvoiceHTML($reg) {
    // Generate invoice number if not exists
    $invoice_number = !empty($reg['invoice_number']) ? $reg['invoice_number'] : 'INV-' . $reg['registration_number'];
    
    // Safe date handling
    try {
        $created_date = new DateTime($reg['created_at']);
        $due_date = clone $created_date;
        $due_date->modify('+30 days');
    } catch (Exception $e) {
        $created_date = new DateTime();
        $due_date = new DateTime('+30 days');
    }
    
    // Calculate payment status
    $is_paid = ($reg['payment_status'] === 'approved') ? true : false;
    $payment_status_text = $is_paid ? 'PAID' : 'PENDING';
    $status_color = $is_paid ? '#22c55e' : '#f59e0b';
    
    // Format payment amount
    $payment_amount = number_format((float)$reg['payment_amount'], 2);
    
    // Sanitize data
    $safe_invoice_num = htmlspecialchars($invoice_number, ENT_QUOTES, 'UTF-8');
    $safe_name = htmlspecialchars($reg['name_en'], ENT_QUOTES, 'UTF-8');
    $safe_reg_num = htmlspecialchars($reg['registration_number'], ENT_QUOTES, 'UTF-8');
    $safe_ic = htmlspecialchars($reg['ic'], ENT_QUOTES, 'UTF-8');
    $safe_email = htmlspecialchars($reg['email'], ENT_QUOTES, 'UTF-8');
    $safe_phone = htmlspecialchars($reg['phone'], ENT_QUOTES, 'UTF-8');
    $safe_level = htmlspecialchars($reg['level'], ENT_QUOTES, 'UTF-8');
    $safe_class_count = htmlspecialchars($reg['class_count'], ENT_QUOTES, 'UTF-8');
    
    // Get letterhead image as base64 (embedded, no external URL)
    $letterheadBase64 = getLetterheadImage();
    
    $html = '<!DOCTYPE html>
<html lang="en": 