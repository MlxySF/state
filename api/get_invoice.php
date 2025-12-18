<?php
// Get Invoice API
// Generates and returns invoice HTML for a specific registration

// Start output buffering to capture any output
ob_start();

error_reporting(0);
ini_set('display_errors', 0);

// Clear any previous output
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Registration ID is required']);
    exit;
}

try {
    // Include database configuration
    $db_path = __DIR__ . '/../config.php';
    if (!file_exists($db_path)) {
        throw new Exception('Database configuration file not found at: ' . $db_path);
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
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Registration not found']);
        exit;
    }
    
    $registration = $result->fetch_assoc();
    $stmt->close();
    
    // Generate invoice HTML
    $invoiceHTML = generateInvoiceHTML($registration);
    
    // Clear output buffer and send clean JSON
    ob_end_clean();
    
    echo json_encode([
        'success' => true,
        'invoice_html' => $invoiceHTML,
        'registration_number' => $registration['registration_number']
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
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
    
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice ' . $safe_invoice_num . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; color: #333; line-height: 1.6; padding: 20px 10px; background: #f5f5f5; }
        .invoice-container { max-width: 900px; margin: 0 auto; background: white; padding: 30px 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 8px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 2px solid #0f3460; padding-bottom: 15px; flex-wrap: wrap; gap: 15px; }
        .company-info { flex: 1; min-width: 250px; }
        .letterhead-img { max-width: 100%; width: 100%; max-width: 400px; height: auto; display: block; }
        .invoice-title-box { text-align: right; display: flex; flex-direction: column; align-items: flex-end; justify-content: center; min-width: 180px; }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 4px; font-weight: bold; font-size: 13px; margin-bottom: 8px; color: white; background: ' . $status_color . '; white-space: nowrap; }
        .invoice-number { font-size: 11px; color: #666; white-space: nowrap; }
        .details-section { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .section-title { font-size: 11px; font-weight: bold; color: #0f3460; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 0.5px; }
        .detail-row { margin-bottom: 6px; display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .detail-label { font-size: 11px; color: #666; font-weight: 500; }
        .detail-value { font-size: 11px; color: #000; font-weight: bold; text-align: right; word-break: break-word; }
        .student-name { font-size: 13px; font-weight: bold; color: #000; margin-bottom: 8px; word-break: break-word; }
        .student-info { font-size: 10px; color: #666; line-height: 1.8; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 11px; }
        .items-table thead { background: #0f3460; color: white; }
        .items-table th { padding: 10px 8px; text-align: left; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; }
        .items-table th:last-child { text-align: right; }
        .items-table td { padding: 10px 8px; border-bottom: 1px solid #e0e0e0; font-size: 11px; }
        .items-table tbody tr:last-child td { border-bottom: none; }
        .items-table td:last-child { text-align: right; font-weight: bold; white-space: nowrap; }
        .totals-section { display: flex; justify-content: flex-end; margin-bottom: 25px; }
        .totals-box { width: 100%; max-width: 300px; }
        .total-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 11px; border-bottom: 1px solid #e0e0e0; }
        .total-row.amount { color: #666; }
        .total-row.amount span:last-child { font-weight: bold; color: #000; }
        .total-row.separator { border-top: 2px solid #0f3460; border-bottom: 2px solid #0f3460; padding: 8px 0; font-weight: bold; font-size: 13px; }
        .total-row.separator span:last-child { color: #0f3460; }
        .payment-status-box { background: #dcfce7; border: 1px solid #22c55e; border-radius: 4px; padding: 12px; margin-bottom: 25px; display: none; }
        .payment-status-box.paid { display: block; }
        .payment-status-title { font-weight: bold; color: #22c55e; font-size: 11px; text-transform: uppercase; margin-bottom: 4px; }
        .payment-status-detail { font-size: 10px; color: #16a34a; }
        .notes-section { margin-top: 25px; padding-top: 15px; border-top: 1px solid #e0e0e0; }
        .notes-title { font-size: 10px; font-weight: bold; color: #0f3460; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
        .notes-content { font-size: 10px; color: #666; line-height: 1.6; margin-bottom: 12px; }
        .footer { margin-top: 25px; padding-top: 15px; border-top: 1px solid #e0e0e0; text-align: center; font-size: 9px; color: #999; line-height: 1.6; }
        
        /* Mobile Responsive Design */
        @media screen and (max-width: 768px) {
            body { padding: 10px 5px; }
            .invoice-container { padding: 20px 15px; border-radius: 4px; }
            .header { flex-direction: column; gap: 15px; }
            .company-info { min-width: 100%; }
            .letterhead-img { max-width: 100%; }
            .invoice-title-box { width: 100%; text-align: left; align-items: flex-start; }
            .status-badge { font-size: 12px; padding: 6px 12px; }
            .invoice-number { font-size: 10px; }
            .details-section { grid-template-columns: 1fr; gap: 20px; }
            .section-title { font-size: 10px; }
            .detail-row { font-size: 10px; }
            .detail-label { font-size: 10px; }
            .detail-value { font-size: 10px; }
            .student-name { font-size: 12px; }
            .items-table { font-size: 9px; }
            .items-table th { padding: 8px 4px; font-size: 8px; }
            .items-table td { padding: 8px 4px; font-size: 9px; }
            .totals-box { max-width: 100%; }
            .total-row { font-size: 10px; }
            .total-row.separator { font-size: 11px; }
            .notes-title { font-size: 9px; }
            .notes-content { font-size: 9px; }
            .footer { font-size: 8px; }
        }
        
        @media screen and (max-width: 480px) {
            .invoice-container { padding: 15px 10px; }
            .items-table th:nth-child(2),
            .items-table td:nth-child(2) { display: none; }
        }
        
        @media print { 
            body { background: white; padding: 0; } 
            .invoice-container { box-shadow: none; border-radius: 0; padding: 20mm; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="company-info">
                <img src="https://wushu-assets.s3.ap-southeast-1.amazonaws.com/WSP+Letter.png" alt="Wushu Sport Academy" class="letterhead-img">
            </div>
            <div class="invoice-title-box">
                <div class="status-badge">' . $payment_status_text . '</div>
                <div class="invoice-number">Invoice: ' . $safe_invoice_num . '</div>
            </div>
        </div>
        <div class="details-section">
            <div>
                <div class="section-title">Invoice Details</div>
                <div class="detail-row"><span class="detail-label">Invoice Number:</span><span class="detail-value">' . $safe_invoice_num . '</span></div>
                <div class="detail-row"><span class="detail-label">Date Issued:</span><span class="detail-value">' . $created_date->format('d M Y') . '</span></div>
                <div class="detail-row"><span class="detail-label">Due Date:</span><span class="detail-value">' . $due_date->format('d M Y') . '</span></div>
                <div class="detail-row"><span class="detail-label">Payment Date:</span><span class="detail-value">' . $created_date->format('d M Y, g:i A') . '</span></div>
            </div>
            <div>
                <div class="section-title">Billed To</div>
                <div class="student-name">' . $safe_name . '</div>
                <div class="student-info">
                    <div class="detail-row"><span class="detail-label">Registration ID:</span><span class="detail-value">' . $safe_reg_num . '</span></div>
                    <div class="detail-row"><span class="detail-label">IC Number:</span><span class="detail-value">' . $safe_ic . '</span></div>
                    <div class="detail-row"><span class="detail-label">Email:</span><span class="detail-value">' . $safe_email . '</span></div>
                    <div class="detail-row"><span class="detail-label">Phone:</span><span class="detail-value">' . $safe_phone . '</span></div>
                </div>
            </div>
        </div>
        <table class="items-table">
            <thead><tr><th>Description</th><th style="text-align:center">Class Level</th><th style="text-align:center">Qty</th><th>Amount (RM)</th></tr></thead>
            <tbody><tr><td>Class Registration &amp; Training Enrollment</td><td style="text-align:center">' . $safe_level . '</td><td style="text-align:center">' . $safe_class_count . '</td><td>RM ' . $payment_amount . '</td></tr></tbody>
        </table>
        <div class="totals-section">
            <div class="totals-box">
                <div class="total-row amount"><span>Subtotal:</span><span>RM ' . $payment_amount . '</span></div>
                <div class="total-row amount"><span>Tax (0%):</span><span>RM 0.00</span></div>
                <div class="total-row separator"><span>Total Amount:</span><span>RM ' . $payment_amount . '</span></div>
            </div>
        </div>';
    
    if ($is_paid) {
        try {
            $payment_date = new DateTime($reg['payment_date'] ?? date('Y-m-d H:i:s'));
        } catch (Exception $e) {
            $payment_date = new DateTime();
        }
        $html .= '<div class="payment-status-box paid"><div class="payment-status-title">✓ Payment Completed and Verified</div><div class="payment-status-detail">Payment received and verified on ' . $payment_date->format('d M Y, g:i A') . '</div></div>';
    }
    
    $html .= '<div class="notes-section">
            <div class="notes-title">Important Notes</div>
            <div class="notes-content">Thank you for your payment. This invoice confirms your class enrollment and payment has been received and verified. Please keep this document for your records. If you have any questions, please contact our administration office.</div>
            <div class="notes-title">Terms and Conditions</div>
            <div class="notes-content">This invoice is a valid proof of payment and enrollment. Please retain this document for your records. Classes are non-transferable unless prior approval is obtained. Wushu Sport Academy reserves the right to modify class schedules with 7 days notice. Payment is final and non-refundable except in case of class cancellation.</div>
        </div>
        <div class="footer">This is a computer-generated invoice. No signature required.<br>Generated: ' . $created_date->format('d M Y, g:i A') . '<br><br>&copy; 2025 Wushu Sport Academy. All rights reserved.</div>
    </div>
</body>
</html>';
    
    return $html;
}
?>