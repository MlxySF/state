<?php
// download_invoice.php - generates and downloads invoice PDF without Composer
// Uses FPDF in the same way as MlxySF/student/generate_registration_invoice.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../fpdf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    exit('Registration ID is required');
}

$registration_id = (int)$_GET['id'];

// Fetch registration record
$sql = "SELECT * FROM registrations WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit('Database error');
}
$stmt->bind_param('i', $registration_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(404);
    exit('Registration not found');
}
$reg = $result->fetch_assoc();
$stmt->close();

class InvoicePDF extends FPDF {
    function Header() {
        // Letterhead from S3
        $letterhead = 'https://wushu-assets.s3.ap-southeast-1.amazonaws.com/WSP+Letter.png';
        $this->Image($letterhead, 10, 5, 190); // full-width banner
        $this->Ln(35); // space after letterhead
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
