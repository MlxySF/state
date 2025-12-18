<?php
// Download Invoice as PDF using mPDF

error_reporting(0);
ini_set('display_errors', 0);

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

    // Use mPDF to generate PDF
    $mpdfPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($mpdfPath)) {
        throw new Exception('mPDF library not found. Please install it with: composer require mpdf/mpdf');
    }

    require_once $mpdfPath;

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

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo $mpdf->Output($filename, 'S');

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    exit('Error: ' . $e->getMessage());
}

exit;

function generateInvoiceHTML($reg) {
    $invoice_number = !empty($reg['invoice_number']) ? $reg['invoice_number'] : 'INV-' . $reg['registration_number'];

    try {
        $created_date = new DateTime($reg['created_at']);
        $due_date = clone $created_date;
        $due_date->modify('+30 days');
    } catch (Exception $e) {
        $created_date = new DateTime();
        $due_date = new DateTime('+30 days');
    }

    $is_paid = ($reg['payment_status'] === 'approved');
    $payment_status_text = $is_paid ? 'PAID' : 'PENDING';
    $status_color = $is_paid ? '#22c55e' : '#f59e0b';

    $payment_amount = number_format((float)$reg['payment_amount'], 2);

    $safe_invoice_num = htmlspecialchars($invoice_number, ENT_QUOTES, 'UTF-8');
    $safe_name = htmlspecialchars($reg['name_en'], ENT_QUOTES, 'UTF-8');
    $safe_reg_num = htmlspecialchars($reg['registration_number'], ENT_QUOTES, 'UTF-8');
    $safe_ic = htmlspecialchars($reg['ic'], ENT_QUOTES, 'UTF-8');
    $safe_email = htmlspecialchars($reg['email'], ENT_QUOTES, 'UTF-8');
    $safe_phone = htmlspecialchars($reg['phone'], ENT_QUOTES, 'UTF-8');
    $safe_level = htmlspecialchars($reg['level'], ENT_QUOTES, 'UTF-8');
    $safe_class_count = htmlspecialchars($reg['class_count'], ENT_QUOTES, 'UTF-8');

    $letterheadUrl = 'https://wushu-assets.s3.ap-southeast-1.amazonaws.com/WSP+Letter.png';

    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice ' . $safe_invoice_num . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: white; color: #333; }
        .invoice-page { width: 210mm; height: 297mm; padding: 20mm; }
        .letterhead { width: 100%; margin-bottom: 20px; }
        .letterhead img { width: 100%; height: auto; display: block; }
        .status-badge { float: right; padding: 8px 16px; border-radius: 4px; font-weight: bold; font-size: 14px; color: #fff; background: ' . $status_color . '; }
        .section-title { font-size: 11px; font-weight: bold; color: #0f3460; text-transform: uppercase; margin: 20px 0 10px; letter-spacing: 1px; }
        .detail-row { font-size: 11px; margin-bottom: 4px; display: flex; justify-content: space-between; }
        .detail-label { color: #666; }
        .detail-value { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px 10px; font-size: 11px; border-bottom: 1px solid #e0e0e0; }
        thead { background: #0f3460; color: #fff; }
        th:last-child, td:last-child { text-align: right; }
        .totals { margin-top: 20px; width: 250px; float: right; }
        .totals-row { display: flex; justify-content: space-between; font-size: 11px; padding: 4px 0; }
        .totals-row.total { border-top: 2px solid #0f3460; border-bottom: 2px solid #0f3460; font-weight: bold; margin-top: 5px; padding-top: 6px; }
    </style>
</head>
<body>
<div class="invoice-page">
    <div class="letterhead">
        <img src="' . $letterheadUrl . '" alt="Wushu Sport Academy Letterhead">
    </div>
    <div class="status-badge">' . $payment_status_text . '</div>
    <div style="clear: both;"></div>

    <div class="section-title">Invoice Details</div>
    <div class="detail-row"><span class="detail-label">Invoice Number:</span><span class="detail-value">' . $safe_invoice_num . '</span></div>
    <div class="detail-row"><span class="detail-label">Date Issued:</span><span class="detail-value">' . $created_date->format('d M Y') . '</span></div>
    <div class="detail-row"><span class="detail-label">Due Date:</span><span class="detail-value">' . $due_date->format('d M Y') . '</span></div>

    <div class="section-title">Billed To</div>
    <div class="detail-row"><span class="detail-label">Name:</span><span class="detail-value">' . $safe_name . '</span></div>
    <div class="detail-row"><span class="detail-label">Registration ID:</span><span class="detail-value">' . $safe_reg_num . '</span></div>
    <div class="detail-row"><span class="detail-label">IC Number:</span><span class="detail-value">' . $safe_ic . '</span></div>
    <div class="detail-row"><span class="detail-label">Email:</span><span class="detail-value">' . $safe_email . '</span></div>
    <div class="detail-row"><span class="detail-label">Phone:</span><span class="detail-value">' . $safe_phone . '</span></div>

    <div class="section-title">Items</div>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align:center">Class Level</th>
                <th style="text-align:center">Qty</th>
                <th>Amount (RM)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Class Registration &amp; Training Enrollment</td>
                <td style="text-align:center">' . $safe_level . '</td>
                <td style="text-align:center">' . $safe_class_count . '</td>
                <td>RM ' . $payment_amount . '</td>
            </tr>
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row"><span>Subtotal:</span><span>RM ' . $payment_amount . '</span></div>
        <div class="totals-row"><span>Tax (0%):</span><span>RM 0.00</span></div>
        <div class="totals-row total"><span>Total Amount:</span><span>RM ' . $payment_amount . '</span></div>
    </div>
</div>
</body>
</html>';

    return $html;
}
