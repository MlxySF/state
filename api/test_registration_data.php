<?php
// Diagnostic script to test registration data
// Access this at: your-domain.com/api/test_registration_data.php

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Registration Data Diagnostic Tool</h1>";
echo "<p>This will show you exactly what data is being received.</p>";

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST Request Received</h2>";
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    echo "<h3>Raw POST Data (first 1000 chars):</h3>";
    echo "<pre>" . htmlspecialchars(substr($input, 0, 1000)) . "...</pre>";
    
    echo "<h3>Decoded JSON Data:</h3>";
    if ($data) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Has Data?</th><th>Type</th><th>Length/Value</th></tr>";
        
        $important_fields = ['signature_base64', 'schedule', 'events', 'signed_pdf_base64', 'payment_receipt_base64'];
        
        foreach ($important_fields as $field) {
            echo "<tr>";
            echo "<td><strong>{$field}</strong></td>";
            
            if (isset($data[$field])) {
                $value = $data[$field];
                echo "<td style='color: green;'>YES</td>";
                echo "<td>" . gettype($value) . "</td>";
                
                if (is_string($value)) {
                    $len = strlen($value);
                    if ($len > 100) {
                        echo "<td>Length: {$len} chars<br>";
                        echo "Starts with: " . htmlspecialchars(substr($value, 0, 50)) . "...</td>";
                    } else {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                } else {
                    echo "<td>" . htmlspecialchars(print_r($value, true)) . "</td>";
                }
            } else {
                echo "<td style='color: red;'>NO</td>";
                echo "<td>-</td>";
                echo "<td>MISSING</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<h3>All Received Fields:</h3>";
        echo "<pre>" . htmlspecialchars(print_r(array_keys($data), true)) . "</pre>";
    } else {
        echo "<p style='color: red;'>Failed to decode JSON data!</p>";
    }
    
} else {
    // Show test form
    echo "<h2>Send Test Data</h2>";
    echo "<p>Use this form to test what data your registration form is sending.</p>";
    echo '<form method="POST">';
    echo '<p><label>Signature Base64:<br><textarea name="signature_base64" rows="3" cols="80">data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA</textarea></label></p>';
    echo '<p><label>Schedule:<br><input type="text" name="schedule" value="Monday 3-5pm, Wednesday 3-5pm" size="80"></label></p>';
    echo '<p><label>Events:<br><input type="text" name="events" value="Taolu, Sanda" size="80"></label></p>';
    echo '<p><button type="submit">Test Submit</button></p>';
    echo '</form>';
    
    echo "<hr>";
    echo "<h2>Or send via JavaScript:</h2>";
    echo "<pre>";
    echo htmlspecialchars("
fetch('test_registration_data.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        signature_base64: 'data:image/png;base64,ABC123...',
        schedule: 'Monday 3-5pm',
        events: 'Taolu, Sanda'
    })
}).then(r => r.text()).then(html => {
    document.body.innerHTML = html;
});
    ");
    echo "</pre>";
}

// Show database column info
echo "<hr>";
echo "<h2>Database Column Information</h2>";

require_once __DIR__ . '/../config.php';

$result = $conn->query("SHOW COLUMNS FROM registrations WHERE Field IN ('signature_base64', 'schedule', 'events')");

if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['Type']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Expected types:</strong></p>";
    echo "<ul>";
    echo "<li>signature_base64: <strong>longtext</strong></li>";
    echo "<li>schedule: <strong>text</strong> or <strong>varchar(500)</strong></li>";
    echo "<li>events: <strong>text</strong> or <strong>varchar(500)</strong></li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'>Error checking database: " . $conn->error . "</p>";
}

$conn->close();
?>