<?php
// download_invoice.php - Generate and download invoice PDF using FPDF
// Fixed: Use text header instead of remote image to avoid crashes

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error_invoice.log');

function logError($message) {
    $logFile = __DIR__ . '/../error_invoice.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

logError('=== Invoice Generation Started ===');
logError('Request Method: ' . $_SERVER['REQUEST_METHOD']);
logError('Request URI: ' . $_SERVER['REQUEST_URI']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    logError('ERROR: Invalid request method');
    http_response_code(405);
    die('Method not allowed');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    logError('ERROR: Invalid or missing ID parameter');
    http_response_code(400);
    die('Registration ID is required');
}

$registration_id = (int)$_GET['id'];
logError("Processing registration ID: $registration_id");

try {
    // Load database config
    $config_path = __DIR__ . '/../config.php';
    logError("Checking config.php at: $config_path");
    
    if (!file_exists($config_path)) {
        throw new Exception("config.php not found");
    }
    require_once $config_path;
    logError('config.php loaded successfully');
    
    if (!isset($conn)) {
        throw new Exception('Database connection not established');
    }
    logError('Database connection verified');
    
    // Load FPDF
    $fpdf_path = __DIR__ . '/../fpdf.php';
    logError("Checking fpdf.php at: $fpdf_path");
    
    if (!file_exists($fpdf_path)) {
        throw new Exception("fpdf.php not found");
    }
    require_once $fpdf_path;
    logError('fpdf.php loaded successfully');
    
    if (!class_exists('FPDF')) {
        throw new Exception('FPDF class not found');
    }
    logError('FPDF class verified');
    
    // Fetch registration
    logError('Preparing database query...');
    $stmt = $conn->prepare("SELECT * FROM registrations WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $registration_id);
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    logError('Result fetched, rows: ' . $result->num_rows);
    
    if ($result->num_rows === 0) {
        logError('ERROR: Registration not found');
        http_response_code(404);
        throw new Exception('Registration not found');
    }
    
    $reg = $result->fetch_assoc();
    $stmt->close();
    logError('Registration data loaded: ' . $reg['registration_number']);
    
    // Create PDF with simple text header (no remote images)
    logError('Creating PDF class...');
    
    class InvoicePDF extends FPDF {
        function Header() {
            // Simple text header - no remote images to avoid crashes
            $this->SetFont('Arial', 'B', 20);
            $this->SetTextColor(15, 52, 96);
            $this->Cell(0, 12, 'WUSHU SPORT ACADEMY', 0, 1, 'C');
            $this->SetFont('Arial', '', 11);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(0, 6, 'Registration & Payment Invoice', 0, 1, 'C');
            $this->SetTextColor(0, 0, 0);
            $this->Ln(8);
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(150, 150, 150);
            $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
            $this->SetTextColor(0, 0, 0);
        }
    }
    
    logError('Instantiating InvoicePDF...');
    $pdf = new InvoicePDF('P', 'mm', 'A4');
    logError('PDF object created');
    
    logError('Adding page...');
    $pdf->AddPage();
    logError('Page added successfully');
    
    $pdf->SetAutoPageBreak(true, 20);
    
    // Invoice details
    $invoice_number = !empty($reg['invoice_number']) ? $reg['invoice_number'] : 'INV-' . $reg['registration_number'];
    $is_paid = ($reg['payment_status'] === 'approved');
    $status_text = $is_paid ? 'PAID' : 'PENDING';
    
    logError('Building PDF content...');
    
    // Title and status
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(0, 10, 'INVOICE', 0, 1, 'R');
    $pdf->SetFont('Arial', 'B', 12);
    if ($is_paid) {
        $pdf->SetTextColor(34, 197, 94);
    } else {
        $pdf->SetTextColor(245, 158, 11);
    }
    $pdf->Cell(0, 8, $status_text, 0, 1, 'R');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(5);
    
    // Invoice info
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(50, 7, 'Invoice Number:', 0, 0);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 7, $invoice_number, 0, 1);
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(50, 7, 'Date Issued:', 0, 0);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 7, date('d M Y', strtotime($reg['created_at'])), 0, 1);
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(50, 7, 'Registration ID:', 0, 0);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 7, $reg['registration_number'], 0, 1);
    $pdf->Ln(5);
    
    // Billed to section
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(0, 10, 'BILLED TO', 0, 1, 'L', true);
    $pdf->SetFont('Arial', '', 11);
    
    $pdf->Cell(50, 7, 'Name:', 0, 0);
    $pdf->Cell(0, 7, $reg['name_en'], 0, 1);
    $pdf->Cell(50, 7, 'IC Number:', 0, 0);
    $pdf->Cell(0, 7, $reg['ic'], 0, 1);
    $pdf->Cell(50, 7, 'Email:', 0, 0);
    $pdf->Cell(0, 7, $reg['email'], 0, 1);
    $pdf->Cell(50, 7, 'Phone:', 0, 0);
    $pdf->Cell(0, 7, $reg['phone'], 0, 1);
    $pdf->Ln(5);
    
    // Class details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'CLASS DETAILS', 0, 1, 'L', true);
    $pdf->SetFont('Arial', '', 11);
    
    $pdf->Cell(50, 7, 'Level:', 0, 0);
    $pdf->Cell(0, 7, $reg['level'], 0, 1);
    $pdf->Cell(50, 7, 'Class Count:', 0, 0);
    $pdf->Cell(0, 7, $reg['class_count'] . ' classes', 0, 1);
    $pdf->Ln(5);
    
    // Payment details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'PAYMENT DETAILS', 0, 1, 'L', true);
    $pdf->SetFont('Arial', '', 11);
    
    $amount = (float)$reg['payment_amount'];
    $pdf->Cell(50, 7, 'Payment Date:', 0, 0);
    $pdf->Cell(0, 7, $reg['payment_date'] ?: 'Pending', 0, 1);
    $pdf->Ln(3);
    
    // Total amount
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(50, 10, 'Total Amount:', 0, 0);
    $pdf->SetTextColor(0, 128, 0);
    $pdf->Cell(0, 10, 'RM ' . number_format($amount, 2), 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(10);
    
    // Footer notes
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->MultiCell(0, 5, 'Thank you for your payment. This invoice confirms your class registration. Please keep this document for your records. For any inquiries, please contact Wushu Sport Academy.');
    
    logError('PDF content built successfully');
    
    // Generate filename and download
    $filename = 'Invoice_' . $reg['registration_number'] . '.pdf';
    logError("Outputting PDF: $filename");
    
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Output as download
    logError('Calling PDF Output...');
    $pdf->Output('D', $filename);
    logError('PDF output completed successfully');
    logError('=== Invoice Generation Completed Successfully ===');
    exit;
    
} catch (Exception $e) {
    logError('EXCEPTION CAUGHT: ' . $e->getMessage());
    logError('Stack trace: ' . $e->getTraceAsString());
    logError('=== Invoice Generation Failed ===');
    
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Error</title></head><body style="font-family:Arial;padding:40px;">';
    echo '<h1 style="color:#e74c3c;">❌ Error Generating Invoice</h1>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Check the error log at: <code>error_invoice.log</code></p>';
    echo '</body></html>';
    exit;
}
?>