<?php
// Invoice PDF Generator
// Generates invoice PDF using TCPDF or FPDF library

// Simple invoice generator without requiring external libraries
// Uses PHP's built-in functionality to create a basic PDF

function generateInvoicePDF($registrationData) {
    // For now, create a simple HTML-based invoice that can be converted to PDF
    // In production, you'd use TCPDF or FPDF library
    
    $invoiceHTML = getInvoiceHTML($registrationData);
    
    // Convert HTML to PDF using wkhtmltopdf if available, or return HTML
    // For simplicity, we'll create a basic text-based format
    return createSimpleInvoicePDF($registrationData);
}

function createSimpleInvoicePDF($data) {
    // Create a simple PDF using FPDF-compatible approach
    // This is a minimal implementation - for production use TCPDF
    
    $invoiceNumber = 'INV-' . $data['registration_number'];
    $invoiceDate = date('Y-m-d');
    
    // Generate PDF content
    require_once __DIR__ . '/simple_pdf.php';
    
    $pdf = new SimplePDF();
    $pdf->addPage();
    
    // Header
    $pdf->setFont('Arial', 'B', 20);
    $pdf->cell(0, 10, 'WUSHU SPORT ACADEMY', 0, 1, 'C');
    $pdf->setFont('Arial', '', 10);
    $pdf->cell(0, 5, 'Official Payment Invoice', 0, 1, 'C');
    $pdf->ln(10);
    
    // Invoice details
    $pdf->setFont('Arial', 'B', 12);
    $pdf->cell(0, 8, 'INVOICE', 0, 1);
    $pdf->setFont('Arial', '', 10);
    $pdf->cell(50, 6, 'Invoice Number:', 0, 0);
    $pdf->cell(0, 6, $invoiceNumber, 0, 1);
    $pdf->cell(50, 6, 'Invoice Date:', 0, 0);
    $pdf->cell(0, 6, $invoiceDate, 0, 1);
    $pdf->cell(50, 6, 'Registration Number:', 0, 0);
    $pdf->cell(0, 6, $data['registration_number'], 0, 1);
    $pdf->ln(5);
    
    // Student details
    $pdf->setFont('Arial', 'B', 12);
    $pdf->cell(0, 8, 'STUDENT INFORMATION', 0, 1);
    $pdf->setFont('Arial', '', 10);
    $pdf->cell(50, 6, 'Name:', 0, 0);
    $pdf->cell(0, 6, $data['name_en'], 0, 1);
    $pdf->cell(50, 6, 'IC Number:', 0, 0);
    $pdf->cell(0, 6, $data['ic'], 0, 1);
    $pdf->cell(50, 6, 'Email:', 0, 0);
    $pdf->cell(0, 6, $data['email'], 0, 1);
    $pdf->cell(50, 6, 'Phone:', 0, 0);
    $pdf->cell(0, 6, $data['phone'], 0, 1);
    $pdf->ln(5);
    
    // Course details
    $pdf->setFont('Arial', 'B', 12);
    $pdf->cell(0, 8, 'COURSE DETAILS', 0, 1);
    $pdf->setFont('Arial', '', 10);
    $pdf->cell(50, 6, 'Classes:', 0, 0);
    $pdf->multiCell(0, 6, $data['events'], 0, 1);
    $pdf->cell(50, 6, 'Schedule:', 0, 0);
    $pdf->multiCell(0, 6, $data['schedule'], 0, 1);
    $pdf->ln(5);
    
    // Payment details
    $pdf->setFont('Arial', 'B', 12);
    $pdf->cell(0, 8, 'PAYMENT DETAILS', 0, 1);
    $pdf->line(10, $pdf->getY(), 200, $pdf->getY());
    $pdf->ln(2);
    
    $pdf->setFont('Arial', 'B', 10);
    $pdf->cell(100, 8, 'Description', 1, 0, 'C');
    $pdf->cell(40, 8, 'Amount (RM)', 1, 1, 'C');
    
    $pdf->setFont('Arial', '', 10);
    $pdf->cell(100, 8, 'Registration Fee - ' . $data['events'], 1, 0);
    $pdf->cell(40, 8, number_format($data['payment_amount'], 2), 1, 1, 'R');
    
    $pdf->setFont('Arial', 'B', 10);
    $pdf->cell(100, 8, 'Total Amount', 1, 0, 'R');
    $pdf->cell(40, 8, 'RM ' . number_format($data['payment_amount'], 2), 1, 1, 'R');
    
    $pdf->ln(10);
    
    // Footer
    $pdf->setFont('Arial', 'I', 9);
    $pdf->multiCell(0, 5, 'Payment Status: APPROVED\nPayment Date: ' . $data['payment_date'] . '\n\nThank you for choosing Wushu Sport Academy!', 0, 'C');
    
    return $pdf->output('S'); // Return as string
}

function getInvoiceHTML($data) {
    $invoiceNumber = 'INV-' . $data['registration_number'];
    $invoiceDate = date('Y-m-d');
    
    return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; color: #2c3e50; }
        .invoice-details { margin: 20px 0; }
        .invoice-details table { width: 100%; }
        .invoice-details td { padding: 5px; }
        .invoice-details td:first-child { font-weight: bold; width: 150px; }
        .payment-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .payment-table th, .payment-table td { border: 1px solid #ddd; padding: 10px; }
        .payment-table th { background: #3498db; color: white; }
        .total { background: #ecf0f1; font-weight: bold; }
        .footer { text-align: center; margin-top: 40px; color: #7f8c8d; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>WUSHU SPORT ACADEMY</h1>
        <p>Official Payment Invoice</p>
    </div>
    
    <div class="invoice-details">
        <h2>Invoice: ' . htmlspecialchars($invoiceNumber) . '</h2>
        <table>
            <tr><td>Invoice Date:</td><td>' . htmlspecialchars($invoiceDate) . '</td></tr>
            <tr><td>Registration Number:</td><td>' . htmlspecialchars($data['registration_number']) . '</td></tr>
            <tr><td>Student Name:</td><td>' . htmlspecialchars($data['name_en']) . '</td></tr>
            <tr><td>IC Number:</td><td>' . htmlspecialchars($data['ic']) . '</td></tr>
            <tr><td>Email:</td><td>' . htmlspecialchars($data['email']) . '</td></tr>
            <tr><td>Phone:</td><td>' . htmlspecialchars($data['phone']) . '</td></tr>
        </table>
    </div>
    
    <h3>Course Details</h3>
    <table class="invoice-details">
        <tr><td>Classes:</td><td>' . htmlspecialchars($data['events']) . '</td></tr>
        <tr><td>Schedule:</td><td>' . htmlspecialchars($data['schedule']) . '</td></tr>
    </table>
    
    <h3>Payment Details</h3>
    <table class="payment-table">
        <tr>
            <th>Description</th>
            <th>Amount (RM)</th>
        </tr>
        <tr>
            <td>Registration Fee - ' . htmlspecialchars($data['events']) . '</td>
            <td style="text-align: right;">' . number_format($data['payment_amount'], 2) . '</td>
        </tr>
        <tr class="total">
            <td style="text-align: right;">Total Amount</td>
            <td style="text-align: right;">RM ' . number_format($data['payment_amount'], 2) . '</td>
        </tr>
    </table>
    
    <div class="footer">
        <p><strong>Payment Status: APPROVED</strong></p>
        <p>Payment Date: ' . htmlspecialchars($data['payment_date']) . '</p>
        <p>Thank you for choosing Wushu Sport Academy!</p>
    </div>
</body>
</html>
    ';
}

?>