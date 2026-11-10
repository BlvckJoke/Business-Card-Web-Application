<?php
session_start();
require_once "config/db.php";
require_once "BusinessCardDetector.php";

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Handle different request types
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Create upload directory
    $uploadDir = 'uploads/' . date('Y/m/d/');
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Create temp directory
    $tempDir = 'temp/';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        
        $file = $_FILES['image'];
        $side = $_POST['side'] ?? 'front';
        $userId = $_SESSION['user_id'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type']);
            exit;
        }
        
        // Validate file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'File too large (max 10MB)']);
            exit;
        }
        
        // Generate unique filename
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        $originalFilename = "{$userId}_{$side}_{$timestamp}_{$random}.{$extension}";
        $originalPath = $uploadDir . $originalFilename;
        
        // Save original file
        if (move_uploaded_file($file['tmp_name'], $originalPath)) {
            
            // Initialize detector
            $detector = new BusinessCardDetector(true); // Enable debug
            
            // Process the image
            $croppedPath = $uploadDir . "cropped_{$userId}_{$side}_{$timestamp}_{$random}.jpg";
            
            $result = $detector->detectAndCrop($originalPath, $croppedPath, [
                'min_confidence' => 30,
                'aspect_ratio' => 1.75,
                'max_size' => 1600,
                'padding' => 10
            ]);
            
            if ($result['success']) {
                // Prepare response
                $response = [
                    'success' => true,
                    'original_url' => '/' . $originalPath,
                    'cropped_url' => '/' . $croppedPath,
                    'confidence' => $result['confidence'],
                    'method' => $result['method_details'] ?? $result['method'] ?? 'unknown',
                    'bounds' => [
                        'x' => $result['x'],
                        'y' => $result['y'],
                        'width' => $result['width'],
                        'height' => $result['height']
                    ]
                ];
                
                // Clean up temp files
                cleanTempFiles($tempDir);
                
                echo json_encode($response);
                
            } else {
                // If detection failed, use original
                echo json_encode([
                    'success' => true,
                    'original_url' => '/' . $originalPath,
                    'cropped_url' => '/' . $originalPath, // Same as original
                    'confidence' => 0,
                    'method' => 'original',
                    'bounds' => [
                        'x' => 0,
                        'y' => 0,
                        'width' => getimagesize($originalPath)[0],
                        'height' => getimagesize($originalPath)[1]
                    ],
                    'message' => 'Using original image (detection failed)'
                ]);
            }
            
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
        }
        
    } elseif (isset($_POST['image_data'])) {
        // Handle base64 image data
        $side = $_POST['side'] ?? 'front';
        $userId = $_SESSION['user_id'];
        
        $base64Data = $_POST['image_data'];
        
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
            $data = substr($base64Data, strpos($base64Data, ',') + 1);
            $data = base64_decode($data);
            $extension = $matches[1];
            
            // Generate filename
            $timestamp = time();
            $random = bin2hex(random_bytes(8));
            $originalFilename = "{$userId}_{$side}_{$timestamp}_{$random}.{$extension}";
            $originalPath = $uploadDir . $originalFilename;
            
            // Save base64 image
            if (file_put_contents($originalPath, $data)) {
                
                // Initialize detector
                $detector = new BusinessCardDetector(true);
                
                // Process the image
                $croppedPath = $uploadDir . "cropped_{$userId}_{$side}_{$timestamp}_{$random}.jpg";
                
                $result = $detector->detectAndCrop($originalPath, $croppedPath, [
                    'min_confidence' => 30,
                    'aspect_ratio' => 1.75,
                    'max_size' => 1600,
                    'padding' => 10
                ]);
                
                if ($result['success']) {
                    echo json_encode([
                        'success' => true,
                        'original_url' => '/' . $originalPath,
                        'cropped_url' => '/' . $croppedPath,
                        'confidence' => $result['confidence'],
                        'method' => $result['method_details'] ?? $result['method'] ?? 'unknown'
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'original_url' => '/' . $originalPath,
                        'cropped_url' => '/' . $originalPath,
                        'confidence' => 0,
                        'method' => 'original'
                    ]);
                }
                
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to save base64 image']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid base64 data']);
        }
        
    } else {
        echo json_encode(['success' => false, 'error' => 'No image provided']);
    }
    
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

/**
 * Clean up old temp files
 */
function cleanTempFiles($tempDir, $maxAge = 3600) {
    if (!is_dir($tempDir)) return;
    
    $files = glob($tempDir . '*');
    $now = time();
    
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file)) > $maxAge) {
            unlink($file);
        }
    }
}
?>