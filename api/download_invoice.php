<?php
// download_invoice.php - Generate and download invoice PDF using FPDF
// Simple, straightforward version that works

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die('Method not allowed');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die('Registration ID is required');
}

$registration_id = (int)$_GET['id'];

try {
    // Load database config
    $config_path = __DIR__ . '/../config.php';
    if (!file_exists($config_path)) {
        throw new Exception('config.php not found. Please check file location.');
    }
    require_once $config_path;
    
    if (!isset($conn)) {
        throw new Exception('Database connection failed in config.php');
    }
    
    // Load FPDF
    $fpdf_path = __DIR__ . '/../fpdf.php';
    if (!file_exists($fpdf_path)) {
        throw new Exception('fpdf.php not found. Please copy it from the student project.');
    }
    require_once $fpdf_path;
    
    // Fetch registration
    $stmt = $conn->prepare("SELECT * FROM registrations WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $registration_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        throw new Exception('Registration not found');
    }
    
    $reg = $result->fetch_assoc();
    $stmt->close();
    
    // Create PDF
    class InvoicePDF extends FPDF {
        function Header() {
            // Try to load letterhead from S3
            $letterhead = 'https://wushu-assets.s3.ap-southeast-1.amazonaws.com/WSP+Letter.png';
            try {
                @$this->Image($letterhead, 10, 10, 190);
                $this->Ln(40);
            } catch (Exception $e) {
                // If letterhead fails, show text header
                $this->SetFont('Arial', 'B', 18);
                $this->Cell(0, 10, 'WUSHU SPORT ACADEMY', 0, 1, 'C');
                $this->SetFont('Arial', '', 10);
                $this->Cell(0, 5, 'Registration Invoice', 0, 1, 'C');
                $this->Ln(10);
            }
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
        }
    }
    
    $pdf = new InvoicePDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 20);
    
    // Invoice details
    $invoice_number = !empty($reg['invoice_number']) ? $reg['invoice_number'] : 'INV-' . $reg['registration_number'];
    $is_paid = ($reg['payment_status'] === 'approved');
    $status_text = $is_paid ? 'PAID' : 'PENDING';
    
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
    
    // Generate filename and download
    $filename = 'Invoice_' . $reg['registration_number'] . '.pdf';
    
    // Output as download
    $pdf->Output('D', $filename);
    exit;
    
} catch (Exception $e) {
    // Show error in plain HTML
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Error</title></head><body style="font-family:Arial;padding:40px;">';
    echo '<h1 style="color:#e74c3c;">❌ Error Generating Invoice</h1>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<hr><p><strong>Troubleshooting:</strong></p><ul>';
    echo '<li>Make sure <code>fpdf.php</code> exists in: ' . __DIR__ . '/../</li>';
    echo '<li>Make sure <code>config.php</code> exists in: ' . __DIR__ . '/../</li>';
    echo '<li>Run: <code>git pull origin main</code> on your server</li>';
    echo '</ul></body></html>';
    exit;
}
?>