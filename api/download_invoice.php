<?php
// download_invoice.php - generates and downloads invoice PDF without Composer
// Uses FPDF in the same way as MlxySF/student/generate_registration_invoice.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Check if config.php exists
    $config_path = __DIR__ . '/../config.php';
    if (!file_exists($config_path)) {
        throw new Exception('config.php not found at: ' . $config_path);
    }
    require_once $config_path;

    // Check if fpdf.php exists
    $fpdf_path = __DIR__ . '/../fpdf.php';
    if (!file_exists($fpdf_path)) {
        throw new Exception('fpdf.php not found at: ' . $fpdf_path . '. Please copy fpdf.php from MlxySF/student project to the state project root.');
    }
    require_once $fpdf_path;

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        throw new Exception('Method not allowed');
    }

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        http_response_code(400);
        throw new Exception('Registration ID is required');
    }

    $registration_id = (int)$_GET['id'];

    // Check database connection
    if (!isset($conn)) {
        throw new Exception('Database connection not established in config.php');
    }

    // Fetch registration record
    $sql = "SELECT * FROM registrations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    $stmt->bind_param('i', $registration_id);
    if (!$stmt->execute()) {
        throw new Exception('Database execute error: ' . $stmt->error);
    }
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        http_response_code(404);
        throw new Exception('Registration not found with ID: ' . $registration_id);
    }
    $reg = $result->fetch_assoc();
    $stmt->close();

    class InvoicePDF extends FPDF {
        function Header() {
            try {
                // Letterhead from S3
                $letterhead = 'https://wushu-assets.s3.ap-southeast-1.amazonaws.com/WSP+Letter.png';
                $this->Image($letterhead, 10, 5, 190); // full-width banner
                $this->Ln(35); // space after letterhead
            } catch (Exception $e) {
                // If image fails, just skip it and continue
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(0, 10, 'WUSHU SPORT ACADEMY', 0, 1, 'C');
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

    // Invoice title & status
    $invoice_number = !empty($reg['invoice_number']) ? $reg['invoice_number'] : 'INV-' . $reg['registration_number'];
    $is_paid = ($reg['payment_status'] === 'approved');
    $status_text = $is_paid ? 'PAID' : 'PENDING';

    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'INVOICE', 0, 1, 'R');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor($is_paid ? 34 : 245, $is_paid ? 197 : 158, $is_paid ? 94 : 11);
    $pdf->Cell(0, 6, $status_text, 0, 1, 'R');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(4);

    // Invoice details
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(35, 6, 'Invoice Number:', 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 6, $invoice_number, 0, 1);

    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(35, 6, 'Date Issued:', 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 6, date('d M Y', strtotime($reg['created_at'])), 0, 1);

    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(35, 6, 'Registration ID:', 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 6, $reg['registration_number'], 0, 1);
    $pdf->Ln(4);

    // Billed to
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(0, 8, 'BILLED TO', 0, 1, 'L', true);
    $pdf->SetFont('Arial', '', 10);

    $pdf->Cell(35, 6, 'Name:', 0, 0);
    $pdf->Cell(0, 6, $reg['name_en'], 0, 1);
    $pdf->Cell(35, 6, 'IC Number:', 0, 0);
    $pdf->Cell(0, 6, $reg['ic'], 0, 1);
    $pdf->Cell(35, 6, 'Email:', 0, 0);
    $pdf->Cell(0, 6, $reg['email'], 0, 1);
    $pdf->Cell(35, 6, 'Phone:', 0, 0);
    $pdf->Cell(0, 6, $reg['phone'], 0, 1);
    $pdf->Ln(4);

    // Class details
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 8, 'CLASS DETAILS', 0, 1, 'L', true);
    $pdf->SetFont('Arial', '', 10);

    $pdf->Cell(35, 6, 'Level:', 0, 0);
    $pdf->Cell(0, 6, $reg['level'], 0, 1);
    $pdf->Cell(35, 6, 'Class Count:', 0, 0);
    $pdf->Cell(0, 6, $reg['class_count'] . ' classes', 0, 1);
    $pdf->Ln(4);

    // Payment details
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 8, 'PAYMENT DETAILS', 0, 1, 'L', true);
    $pdf->SetFont('Arial', '', 10);

    $amount = (float)$reg['payment_amount'];

    $pdf->Cell(35, 6, 'Payment Date:', 0, 0);
    $pdf->Cell(0, 6, $reg['payment_date'] ?: '-', 0, 1);
    $pdf->Ln(2);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(40, 8, 'Total Amount:', 0, 0);
    $pdf->SetTextColor(0, 128, 0);
    $pdf->Cell(0, 8, 'RM ' . number_format($amount, 2), 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(8);

    // Notes
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->MultiCell(0, 5, 'Thank you for your payment. This invoice confirms your class registration and payment. Please keep this document for your records.');

    $filename = 'Invoice_' . $reg['registration_number'] . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $pdf->Output('I', $filename);

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<html><body style="font-family: Arial; padding: 20px;">';
    echo '<h1 style="color: #e74c3c;">❌ Error Generating Invoice</h1>';
    echo '<p><strong>Error Message:</strong></p>';
    echo '<pre style="background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;">' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '<p><strong>Error Details:</strong></p>';
    echo '<pre style="background: #f1f1f1; padding: 15px; border-radius: 5px;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '<hr>';
    echo '<p><strong>File Paths Being Checked:</strong></p>';
    echo '<ul>';
    echo '<li>config.php: ' . __DIR__ . '/../config.php' . ' (Exists: ' . (file_exists(__DIR__ . '/../config.php') ? 'YES ✅' : 'NO ❌') . ')</li>';
    echo '<li>fpdf.php: ' . __DIR__ . '/../fpdf.php' . ' (Exists: ' . (file_exists(__DIR__ . '/../fpdf.php') ? 'YES ✅' : 'NO ❌') . ')</li>';
    echo '</ul>';
    echo '</body></html>';
    exit;
}
