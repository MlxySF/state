<?php
/**
 * Professional Invoice PDF Generator using TCPDF
 * Supports Chinese characters natively
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error_invoice.log');

function logError($message) {
    $logFile = __DIR__ . '/../error_invoice.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

logError('=== TCPDF Invoice Generation Started ===');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die('Method not allowed');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die('Registration ID is required');
}

$registration_id = (int)$_GET['id'];
logError("Processing registration ID: $registration_id");

try {
    require_once __DIR__ . '/../config.php';
    
    // Try to load TCPDF from different possible locations
    $tcpdf_paths = [
        __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php',
        __DIR__ . '/../tcpdf/tcpdf.php',
        __DIR__ . '/../vendor/tcpdf/tcpdf.php',
    ];
    
    $tcpdf_loaded = false;
    foreach ($tcpdf_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $tcpdf_loaded = true;
            logError('TCPDF loaded from: ' . $path);
            break;
        }
    }
    
    if (!$tcpdf_loaded) {
        throw new Exception('TCPDF library not found. Please install TCPDF first.');
    }
    
    $stmt = $conn->prepare("SELECT * FROM registrations WHERE id = ?");
    $stmt->bind_param('i', $registration_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Registration not found');
    }
    
    $reg = $result->fetch_assoc();
    $stmt->close();
    logError('Registration data loaded: ' . $reg['registration_number']);
    logError('Events field: ' . $reg['events']);
    
    function stripChinese($text) {
        return preg_replace('/[\x{4E00}-\x{9FFF}]/u', '', $text);
    }
    
    function getLetterheadImage() {
        $imageUrl = 'https://wushu-assets.s3.ap-southeast-1.amazonaws.com/WSP+Letter.png';
        $tempDir = sys_get_temp_dir();
        $cacheFile = $tempDir . '/wushu_letterhead.jpg';
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 86400)) {
            return $cacheFile;
        }
        
        $imageData = @file_get_contents($imageUrl);
        if ($imageData === false) {
            return false;
        }
        
        $image = @imagecreatefromstring($imageData);
        if ($image === false) {
            return false;
        }
        
        $jpgImage = imagecreatetruecolor(imagesx($image), imagesy($image));
        $white = imagecolorallocate($jpgImage, 255, 255, 255);
        imagefill($jpgImage, 0, 0, $white);
        imagecopy($jpgImage, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        
        imagejpeg($jpgImage, $cacheFile, 95);
        imagedestroy($image);
        imagedestroy($jpgImage);
        
        return $cacheFile;
    }
    
    $letterheadPath = getLetterheadImage();
    logError('Letterhead: ' . ($letterheadPath ? 'cached successfully' : 'using fallback'));
    
    // Create TCPDF instance
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $invoice_number = !empty($reg['invoice_number']) ? $reg['invoice_number'] : 'INV-' . $reg['registration_number'];
    $pdf->SetCreator('Wushu Sport Academy');
    $pdf->SetAuthor('Wushu Sport Academy');
    $pdf->SetTitle('Invoice ' . $invoice_number);
    $pdf->SetSubject('Invoice');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 25);
    
    // Add a page
    $pdf->AddPage();
    
    $is_paid = ($reg['payment_status'] === 'approved');
    
    // Keep original Chinese events - don't split by comma for display
    $chineseEvents = $reg['events'];
    $cleanSchedule = $reg['schedule'];
    
    logError('Chinese events: ' . $chineseEvents);
    logError('Schedule: ' . $cleanSchedule);
    
    // Build HTML content for invoice
    $html = '
    <style>
        body { font-family: helvetica; }
        .header-box { background-color: #0F3460; color: white; padding: 10px; margin-bottom: 10px; }
        .header-title { font-size: 22px; font-weight: bold; }
        .badge-paid { background-color: #22C55E; color: white; padding: 8px 15px; font-weight: bold; font-size: 14px; text-align: center; }
        .badge-pending { background-color: #F59E0B; color: white; padding: 8px 15px; font-weight: bold; font-size: 14px; text-align: center; }
        .section-title { color: #0F3460; font-weight: bold; font-size: 11px; margin-top: 10px; margin-bottom: 5px; }
        .detail-label { color: #666666; font-size: 10px; }
        .detail-value { color: #000000; font-weight: bold; font-size: 10px; }
        table { border-collapse: collapse; width: 100%; }
        .table-header { background-color: #0F3460; color: white; font-weight: bold; padding: 8px; }
        .table-cell { border: 1px solid #DCDCDC; padding: 5px; font-size: 9px; }
        .total-box { background-color: #0F3460; color: white; padding: 8px; font-weight: bold; text-align: right; }
        .payment-completed { background-color: #DCF8E7; border: 2px solid #22C55E; padding: 10px; margin: 10px 0; }
        .payment-text { color: #22C55E; font-weight: bold; }
        .notes { font-size: 9px; color: #3C3C3C; line-height: 1.4; }
        .footer { font-size: 8px; color: #787878; text-align: center; margin-top: 20px; }
    </style>
    ';
    
    // Header with letterhead or text
    if (!empty($letterheadPath) && file_exists($letterheadPath)) {
        $html .= '<img src="' . $letterheadPath . '" width="140">';
    } else {
        $html .= '<div class="header-box"><div class="header-title">WUSHU SPORT ACADEMY</div></div>';
    }
    
    // Status badge
    if ($is_paid) {
        $html .= '<div class="badge-paid" style="float: right; margin-top: -30px;">PAID</div>';
    } else {
        $html .= '<div class="badge-pending" style="float: right; margin-top: -30px;">PENDING</div>';
    }
    
    $html .= '<div style="text-align: right; font-size: 8px; color: #666666; margin-top: 5px;">' . $invoice_number . '</div>';
    $html .= '<hr style="border: 1px solid #0F3460; margin: 10px 0;">';
    
    // Two columns: Invoice Details and Billed To
    $html .= '<table style="border: none;"><tr><td style="width: 50%; border: none; vertical-align: top;">';
    
    // Invoice Details
    $html .= '<div class="section-title">INVOICE DETAILS</div>';
    $html .= '<div class="detail-label">Invoice Number:</div>';
    $html .= '<div class="detail-value">' . $invoice_number . '</div>';
    $html .= '<div class="detail-label">Date Issued:</div>';
    $html .= '<div class="detail-value">' . date('d M Y', strtotime($reg['created_at'])) . '</div>';
    $html .= '<div class="detail-label">Registration ID:</div>';
    $html .= '<div class="detail-value">' . $reg['registration_number'] . '</div>';
    $html .= '<div class="detail-label">Payment Date:</div>';
    $html .= '<div class="detail-value">' . ($reg['payment_date'] ?: 'Pending') . '</div>';
    
    $html .= '</td><td style="width: 50%; border: none; vertical-align: top;">';
    
    // Billed To
    $html .= '<div class="section-title">BILLED TO</div>';
    $html .= '<div style="font-weight: bold; font-size: 11px;">' . htmlspecialchars($reg['name_en']) . '</div>';
    $html .= '<div class="detail-label">IC Number:</div>';
    $html .= '<div class="detail-value">' . htmlspecialchars($reg['ic']) . '</div>';
    $html .= '<div class="detail-label">Email:</div>';
    $html .= '<div class="detail-value">' . htmlspecialchars($reg['email']) . '</div>';
    $html .= '<div class="detail-label">Phone:</div>';
    $html .= '<div class="detail-value">' . htmlspecialchars($reg['phone']) . '</div>';
    
    $html .= '</td></tr></table>';
    
    // Line Items Table
    $html .= '<br><table>';
    $html .= '<tr>';
    $html .= '<th class="table-header" style="width: 65%;">DESCRIPTION</th>';
    $html .= '<th class="table-header" style="width: 15%; text-align: center;">QTY</th>';
    $html .= '<th class="table-header" style="width: 20%; text-align: right;">AMOUNT (RM)</th>';
    $html .= '</tr>';
    
    // Description with Chinese characters - keep as single line per item
    $descriptionText = htmlspecialchars($chineseEvents) . '<br>' . htmlspecialchars($cleanSchedule);
    
    $html .= '<tr>';
    $html .= '<td class="table-cell">' . $descriptionText . '</td>';
    $html .= '<td class="table-cell" style="text-align: center;">' . $reg['class_count'] . '</td>';
    $html .= '<td class="table-cell" style="text-align: right; font-weight: bold;">' . number_format((float)$reg['payment_amount'], 2) . '</td>';
    $html .= '</tr>';
    $html .= '</table>';
    
    // Totals
    $amount = (float)$reg['payment_amount'];
    $html .= '<br><table style="border: none;">';
    $html .= '<tr><td style="border: none; width: 70%;"></td><td style="border: none; width: 30%; text-align: right;">';
    $html .= '<div style="font-size: 10px; color: #505050;">Subtotal: <b>RM ' . number_format($amount, 2) . '</b></div>';
    $html .= '<div style="font-size: 10px; color: #505050;">Tax (0%): <b>RM 0.00</b></div>';
    $html .= '<hr style="border: 1px solid #0F3460; margin: 5px 0;">';
    $html .= '<div class="total-box">TOTAL AMOUNT: RM ' . number_format($amount, 2) . '</div>';
    $html .= '</td></tr></table>';
    
    // Payment Status
    if ($is_paid) {
        $html .= '<br><div class="payment-completed">';
        $html .= '<div class="payment-text">PAYMENT COMPLETED AND VERIFIED</div>';
        $html .= '<div style="font-size: 9px; color: #16A34A;">Payment received on ' . date('d M Y, g:i A', strtotime($reg['payment_date'])) . '</div>';
        $html .= '</div>';
    }
    
    // Class Details
    $html .= '<br><div class="section-title">CLASS DETAILS</div>';
    $html .= '<div class="notes">Registered Routines: ' . htmlspecialchars($chineseEvents) . '</div>';
    $html .= '<div class="notes">Schedule: ' . htmlspecialchars($cleanSchedule) . '</div>';
    
    // Important Notes
    $html .= '<br><div class="section-title">IMPORTANT NOTES</div>';
    $html .= '<div class="notes">Thank you for your payment. This invoice confirms your class enrollment and payment. Please keep this document for your records.</div>';
    
    // Notes/Terms
    $html .= '<br><div class="section-title">NOTES / TERMS:</div>';
    $html .= '<div class="notes">Fees are non-refundable and must be paid by the 10th of every month. Strict discipline and punctuality are required at all times. The Academy reserves the right to adjust training schedules and venues when necessary.</div>';
    
    // Footer
    $html .= '<div class="footer">';
    $html .= '<div>This is a computer-generated invoice. No signature required.</div>';
    $html .= '<div>Generated: ' . date('d M Y, g:i A') . '</div>';
    $html .= '</div>';
    
    // Write HTML to PDF
    $pdf->writeHTML($html, true, false, true, false, '');
    
    logError('PDF content built');
    
    $filename = 'Invoice_' . $reg['registration_number'] . '.pdf';
    
    // Clean output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Output PDF for download
    $pdf->Output($filename, 'D');
    logError('=== TCPDF Invoice Generated Successfully ===');
    exit;
    
} catch (Exception $e) {
    logError('ERROR: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
    exit;
}
?>