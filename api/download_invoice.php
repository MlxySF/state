<?php
/**
 * Professional Invoice PDF Generator
 * Using tFPDF for UTF-8/Chinese character support
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

logError('=== Invoice Generation Started ===');

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
    require_once __DIR__ . '/../tfpdf.php';
    logError('Config and tFPDF loaded');
    
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
    
    class InvoicePDF extends tFPDF {
        private $letterheadPath = '';
        private $invoice;
        
        public function setLetterhead($path) {
            $this->letterheadPath = $path;
        }
        
        public function setInvoiceData($data) {
            $this->invoice = $data;
        }
        
        function Header() {
            if (!empty($this->letterheadPath) && file_exists($this->letterheadPath)) {
                try {
                    $this->Image($this->letterheadPath, 10, 8, 140, 25, 'JPG');
                } catch (Exception $e) {
                    $this->createTextHeader();
                }
            } else {
                $this->createTextHeader();
            }
            
            $is_paid = ($this->invoice['payment_status'] === 'approved');
            $this->SetXY(165, 10);
            
            if ($is_paid) {
                $this->SetFillColor(34, 197, 94);
                $this->SetDrawColor(34, 197, 94);
                $badgeText = 'PAID';
            } else {
                $this->SetFillColor(245, 158, 11);
                $this->SetDrawColor(245, 158, 11);
                $badgeText = 'PENDING';
            }
            
            $this->SetLineWidth(0.5);
            $this->Rect(165, 10, 30, 12, 'FD');
            $this->SetFont('Arial', 'B', 14);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(30, 12, $badgeText, 0, 0, 'C');
            
            $invoice_number = !empty($this->invoice['invoice_number']) ? $this->invoice['invoice_number'] : 'INV-' . $this->invoice['registration_number'];
            $this->SetXY(155, 25);
            $this->SetFont('Arial', '', 8);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(40, 4, $invoice_number, 0, 0, 'R');
            
            $this->SetY(36);
            $this->SetDrawColor(15, 52, 96);
            $this->SetLineWidth(0.5);
            $this->Line(10, 36, 200, 36);
            $this->SetY(40);
        }
        
        function createTextHeader() {
            $this->SetFillColor(15, 52, 96);
            $this->Rect(0, 0, 210, 35, 'F');
            $this->SetXY(15, 12);
            $this->SetFont('Arial', 'B', 22);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(0, 10, 'WUSHU SPORT ACADEMY', 0, 1, 'L');
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', '', 8);
            $this->SetTextColor(120, 120, 120);
            $this->Cell(0, 4, 'This is a computer-generated invoice. No signature required.', 0, 1, 'C');
            $this->Cell(0, 4, 'Generated: ' . date('d M Y, g:i A'), 0, 1, 'C');
        }
    }
    
    $pdf = new InvoicePDF('P', 'mm', 'A4');
    $pdf->setInvoiceData($reg);
    if ($letterheadPath) {
        $pdf->setLetterhead($letterheadPath);
    }
    $pdf->SetMargins(15, 43, 15);
    $pdf->SetAutoPageBreak(true, 25);
    
    // Add Unicode font - check if DejaVu exists, otherwise use Arial
    if(file_exists(__DIR__ . '/../font/DejaVuSans.ttf')) {
        $pdf->AddFont('DejaVu','',__DIR__ . '/../font/DejaVuSans.ttf', true);
        $pdf->AddFont('DejaVu','B',__DIR__ . '/../font/DejaVuSans-Bold.ttf', true);
    }
    
    $pdf->AddPage();
    logError('PDF page added');
    
    $invoice_number = !empty($reg['invoice_number']) ? $reg['invoice_number'] : 'INV-' . $reg['registration_number'];
    $is_paid = ($reg['payment_status'] === 'approved');
    
    // Keep original Chinese events
    $chineseEvents = $reg['events'];
    $cleanSchedule = stripChinese($reg['schedule']);
    
    logError('Chinese events: ' . $chineseEvents);
    logError('Clean schedule: ' . $cleanSchedule);
    
    // Left Column - Invoice Details
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(15, 52, 96);
    $pdf->SetX(15);
    $pdf->Cell(90, 6, 'INVOICE DETAILS', 0, 1, 'L');
    $pdf->Ln(1);
    
    $pdf->SetFont('Arial', '', 10);
    $details = [
        ['Invoice Number:', $invoice_number],
        ['Date Issued:', date('d M Y', strtotime($reg['created_at']))],
        ['Registration ID:', $reg['registration_number']],
        ['Payment Date:', $reg['payment_date'] ?: 'Pending'],
    ];
    
    foreach ($details as $row) {
        $pdf->SetX(15);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(35, 5.5, $row[0], 0, 0, 'L');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 5.5, $row[1], 0, 1, 'L');
        $pdf->SetFont('Arial', '', 10);
    }
    
    // Right Column - Billed To
    $pdf->SetXY(115, 43);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(15, 52, 96);
    $pdf->Cell(80, 6, 'BILLED TO', 0, 1, 'L');
    $pdf->Ln(1);
    
    $pdf->SetX(115);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 5.5, $reg['name_en'], 0, 1, 'L');
    
    $billedDetails = [
        ['IC Number:', $reg['ic']],
        ['Email:', $reg['email']],
        ['Phone:', $reg['phone']],
    ];
    
    foreach ($billedDetails as $row) {
        $pdf->SetX(115);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(22, 5, $row[0], 0, 0, 'L');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 5, $row[1], 0, 1, 'L');
    }
    
    $pdf->Ln(7);
    
    // LINE ITEMS TABLE
    $pdf->SetX(15);
    $pdf->SetFillColor(15, 52, 96);
    $pdf->SetDrawColor(15, 52, 96);
    $pdf->SetLineWidth(0.3);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(255, 255, 255);
    
    $pdf->Cell(135, 8, 'DESCRIPTION', 1, 0, 'L', true);
    $pdf->Cell(20, 8, 'QTY', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'AMOUNT (RM)', 1, 1, 'R', true);
    
    // Item row
    $pdf->SetDrawColor(220, 220, 220);
    $pdf->SetLineWidth(0.2);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    
    // Split events by comma for display
    $eventsList = array_filter(array_map('trim', explode(',', $chineseEvents)));
    
    // Split schedule by comma
    $scheduleList = array_filter(array_map('trim', explode(',', $cleanSchedule)));
    
    // Combine Chinese events with English schedule
    $allLines = array_merge($eventsList, $scheduleList);
    $descriptionText = implode("\n", $allLines);
    
    // Calculate height
    $lineHeight = 4;
    $numLines = count($allLines);
    $cellHeight = max($numLines * $lineHeight, 8);
    
    $startX = 15;
    $startY = $pdf->GetY();
    
    // Description cell with Chinese characters
    $pdf->SetXY($startX, $startY);
    $pdf->MultiCell(135, $lineHeight, $descriptionText, 1, 'L');
    
    // QTY cell
    $pdf->SetXY($startX + 135, $startY);
    $pdf->Cell(20, $cellHeight, $reg['class_count'], 1, 0, 'C');
    
    // Amount cell
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetX($startX + 155);
    $pdf->Cell(30, $cellHeight, number_format((float)$reg['payment_amount'], 2), 1, 1, 'R');
    
    $pdf->Ln(5);
    
    // TOTALS
    $amount = (float)$reg['payment_amount'];
    
    $pdf->SetX(105);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->Cell(60, 6, 'Subtotal:', 0, 0, 'R');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 6, 'RM ' . number_format($amount, 2), 0, 1, 'R');
    
    $pdf->SetX(105);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->Cell(60, 6, 'Tax (0%):', 0, 0, 'R');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 6, 'RM 0.00', 0, 1, 'R');
    
    $pdf->SetX(105);
    $pdf->SetDrawColor(15, 52, 96);
    $pdf->SetLineWidth(0.4);
    $pdf->Line(105, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(2);
    
    $pdf->SetX(105);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(15, 52, 96);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(60, 9, 'TOTAL AMOUNT:', 0, 0, 'R', true);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 9, 'RM ' . number_format($amount, 2), 0, 1, 'R', true);
    
    $pdf->Ln(8);
    
    // PAYMENT STATUS BOX
    if ($is_paid) {
        $current_y = $pdf->GetY();
        $pdf->SetFillColor(220, 252, 231);
        $pdf->SetDrawColor(34, 197, 94);
        $pdf->SetLineWidth(0.4);
        $pdf->Rect(15, $current_y, 180, 14, 'FD');
        
        $pdf->SetXY(20, $current_y + 2);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(34, 197, 94);
        $pdf->Cell(0, 5, 'PAYMENT COMPLETED AND VERIFIED', 0, 1, 'L');
        
        $pdf->SetX(20);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(22, 163, 74);
        $pdf->Cell(0, 4, 'Payment received on ' . date('d M Y, g:i A', strtotime($reg['payment_date'])), 0, 1, 'L');
        
        $pdf->SetY($current_y + 16);
        $pdf->Ln(4);
    }
    
    // CLASS DETAILS
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(15, 52, 96);
    $pdf->SetX(15);
    $pdf->Cell(0, 6, 'CLASS DETAILS', 0, 1, 'L');
    
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->SetX(15);
    // Display Chinese events directly
    $pdf->MultiCell(180, 4.5, 'Registered Routines: ' . $chineseEvents, 0, 'L');
    
    $pdf->SetX(15);
    $pdf->MultiCell(180, 4.5, 'Schedule: ' . $cleanSchedule, 0, 'L');
    
    $pdf->Ln(2);
    
    // IMPORTANT NOTES
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(15, 52, 96);
    $pdf->SetX(15);
    $pdf->Cell(0, 6, 'IMPORTANT NOTES', 0, 1, 'L');
    
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->SetX(15);
    $pdf->MultiCell(180, 4.5, 'Thank you for your payment. This invoice confirms your class enrollment and payment. Please keep this document for your records.', 0, 'L');
    
    $pdf->Ln(2);
    
    // NOTES / TERMS
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(15, 52, 96);
    $pdf->SetX(15);
    $pdf->Cell(0, 6, 'NOTES / TERMS:', 0, 1, 'L');
    
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->SetX(15);
    $notesText = 'Fees are non-refundable and must be paid by the 10th of every month. Strict discipline and punctuality are required at all times. The Academy reserves the right to adjust training schedules and venues when necessary.';
    $pdf->MultiCell(180, 4, $notesText, 0, 'L');
    
    logError('PDF content built');
    
    $filename = 'Invoice_' . $reg['registration_number'] . '.pdf';
    
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    $pdf->Output('D', $filename);
    logError('=== Invoice Generated Successfully ===');
    exit;
    
} catch (Exception $e) {
    logError('ERROR: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
    exit;
}
?>