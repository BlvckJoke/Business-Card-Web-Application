<?php
// save-category-test.php
header('Content-Type: application/json');

// Log the request
file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);
file_put_contents('debug.log', "POST data: " . file_get_contents('php://input') . "\n", FILE_APPEND);

// Simple response for testing
echo json_encode([
    'success' => true,
    'message' => 'Test successful',
    'data' => json_decode(file_get_contents('php://input'), true)
]);
?>