<?php
// Download Invoice as PDF
// Generates and downloads invoice as a PDF file using mPDF

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
        throw new Exception('mPDF library not found. Please install: composer require mpdf/mpdf');
    }
    
    require_once $mpdfPath;
    
    try {
        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'tempDir' => __DIR__ . '/../tmp',
        ]);
        
        $mpdf->WriteHTML($invoiceHTML);
        
        $filename = 'Invoice_' . str_replace('/', '-', $registration['registration_number']) . '_' . date('YmdHis') . '.pdf';
        $mpdf->Output($filename, 'D');
    } catch (Exception $e) {
        throw new Exception('PDF generation failed: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
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
    
    // Use a local embedded SVG logo instead of external URL
    $logoSvg = '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" width="80" height="80">
        <rect x="20" y="20" width="60" height="60" fill="#DC143C" stroke="#DC143C" stroke-width="2" rx="4"/>
        <text x="50" y="65" font-size="48" font-weight="bold" fill="white" text-anchor="middle" font-family="Arial">武术</text>
    </svg>';
    
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice ' . $safe_invoice_num . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: Arial, sans-serif; 
            background: white;
            color: #333;
        }
        
        .invoice-container { 
            width: 210mm;
            height: 297mm;
            background: white; 
            padding: 20mm;
            color: #333;
            line-height: 1.6;
        }
        
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 3px solid #0f3460; padding-bottom: 15px; }
        .company-info { flex: 1; display: flex; align-items: center; gap: 15px; }
        .logo { width: 60px; height: 60px; }
        .company-text h1 { font-size: 24px; color: #DC143C; margin: 0; font-weight: bold; }
        .company-text p { font-size: 12px; color: #0f3460; margin: 0; }
        .invoice-title-box { text-align: right; display: flex; flex-direction: column; align-items: flex-end; justify-content: center; }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 4px; font-weight: bold; font-size: 14px; margin-bottom: 10px; color: white; background: ' . $status_color . '; }
        .invoice-number { font-size: 12px; color: #666; }
        
        .details-section { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .section-title { font-size: 11px; font-weight: bold; color: #0f3460; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 1px; }
        .detail-row { margin-bottom: 6px; display: flex; justify-content: space-between; }
        .detail-label { font-size: 11px; color: #666; font-weight: normal; }
        .detail-value { font-size: 11px; color: #000; font-weight: bold; }
        .student-name { font-size: 13px; font-weight: bold; color: #000; margin-bottom: 8px; }
        .student-info { font-size: 10px; color: #666; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table thead { background: #0f3460; color: white; }
        .items-table th { padding: 10px; text-align: left; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .items-table th:last-child { text-align: right; }
        .items-table td { padding: 10px; border-bottom: 1px solid #e0e0e0; font-size: 11px; }
        .items-table tbody tr:last-child td { border-bottom: none; }
        .items-table td:last-child { text-align: right; font-weight: bold; }
        
        .totals-section { display: flex; justify-content: flex-end; margin-bottom: 20px; }
        .totals-box { width: 250px; }
        .total-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 11px; border-bottom: 1px solid #e0e0e0; }
        .total-row.separator { border-top: 2px solid #0f3460; border-bottom: 2px solid #0f3460; padding: 8px 0; font-weight: bold; font-size: 12px; }
        .total-row.separator span:last-child { color: #0f3460; }
        
        .payment-status-box { background: #dcfce7; border: 1px solid #22c55e; border-radius: 4px; padding: 12px; margin-bottom: 20px; display: none; }
        .payment-status-box.paid { display: block; }
        .payment-status-title { font-weight: bold; color: #22c55e; font-size: 11px; text-transform: uppercase; margin-bottom: 4px; }
        .payment-status-detail { font-size: 10px; color: #16a34a; }
        
        .notes-section { margin-top: 20px; padding-top: 15px; border-top: 1px solid #e0e0e0; }
        .notes-title { font-size: 10px; font-weight: bold; color: #0f3460; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px; }
        .notes-content { font-size: 9px; color: #666; line-height: 1.5; margin-bottom: 10px; }
        
        .footer { margin-top: 15px; padding-top: 10px; border-top: 1px solid #e0e0e0; text-align: center; font-size: 9px; color: #999; line-height: 1.6; }
        
        @media print { 
            body { background: white; } 
            .invoice-container { box-shadow: none; width: 210mm; height: 297mm; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="company-info">
                <div class="logo">' . $logoSvg . '</div>
                <div class="company-text">
                    <h1>武术体育</h1>
                    <p>WUSHU SPORT ACADEMY</p>
                </div>
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
                <div class="total-row"><span>Subtotal:</span><span>RM ' . $payment_amount . '</span></div>
                <div class="total-row"><span>Tax (0%):</span><span>RM 0.00</span></div>
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
