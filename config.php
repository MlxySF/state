<?php
// Prevent any output before headers
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$servername = "localhost";
$username = "mlxysf_state";      // CHANGE THIS
$password = "BIrh57MoXE6dZ";      // CHANGE THIS
$dbname = "mlxysf_state";      // CHANGE THIS

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode([
            'success' => false, 
            'error' => 'Database connection failed: ' . $conn->connect_error
        ]));
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode([
        'success' => false, 
        'error' => 'Connection error: ' . $e->getMessage()
    ]));
}
?>
