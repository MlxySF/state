<?php
// Invoice Generator
// Generates invoice HTML for registration payments

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