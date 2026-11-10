<?php
// save_category.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Log for debugging (check your server logs)
error_log("Save category endpoint called");

// Get the raw POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log what we received
error_log("Received data: " . print_r($data, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'Method not allowed',
        'received_method' => $_SERVER['REQUEST_METHOD']
    ]);
    exit();
}

if (!$data || !isset($data['category'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'No data or category provided',
        'received' => $data
    ]);
    exit();
}

$newCategory = trim($data['category']);

// Validate the category
if (empty($newCategory)) {
    echo json_encode(['success' => false, 'message' => 'Category name cannot be empty']);
    exit();
}

// Category file path
$categoriesFile = __DIR__ . '/json/category.json';

// Check if file exists and is writable
if (!file_exists($categoriesFile)) {
    // Create the file with empty array
    file_put_contents($categoriesFile, json_encode([]));
}

if (!is_writable($categoriesFile)) {
    echo json_encode(['success' => false, 'message' => 'Category file is not writable']);
    exit();
}

try {
    // Read current categories
    $categoriesJson = file_get_contents($categoriesFile);
    $categories = json_decode($categoriesJson, true);
    
    // If file is empty or invalid JSON, start fresh
    if (!is_array($categories)) {
        $categories = [];
    }
    
    // Check for duplicates (case-insensitive)
    $categoryExists = false;
    foreach ($categories as $cat) {
        if (strcasecmp(trim($cat), $newCategory) === 0) {
            $categoryExists = true;
            break;
        }
    }
    
    if ($categoryExists) {
        echo json_encode([
            'success' => true, // Still success because category exists
            'message' => 'Category already exists',
            'category' => $newCategory
        ]);
        exit();
    }
    
    // Add new category
    $categories[] = $newCategory;
    
    // Remove duplicates and sort
    $categories = array_unique($categories);
    sort($categories);
    
    // Save back to file
    $result = file_put_contents($categoriesFile, json_encode($categories, JSON_PRETTY_PRINT));
    
    if ($result === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to write to file']);
        exit();
    }
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Category added successfully',
        'category' => $newCategory,
        'total_categories' => count($categories)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>