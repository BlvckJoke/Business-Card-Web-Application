<?php
header('Content-Type: application/json');

// Database connection details - UPDATE THESE WITH YOUR ACTUAL DATABASE CREDENTIALS
$host = '156.38.230.252';
$dbname = 'belliunasd_bc';
$username = 'belliunasd_bc';
$password = 'YAEX4rP3n83gzm98nda8';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query to get distinct countries from your tariffs table
    // Adjust the table name and column names based on your actual database structure
    $stmt = $pdo->query("
    SELECT DISTINCT 
    category as category, 
    country as country 
FROM country_import_categories
WHERE category IS NOT NULL 
AND category != ''
ORDER BY country
    ");
    
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no countries found in database, provide default list
    if (empty($countries)) {
        $countries = [
            ['category' => 'ZA', 'country' => 'South Africa'],
            ['category' => 'US', 'country' => 'United States'],
            ['category' => 'GB', 'country' => 'United Kingdom'],
            ['category' => 'DE', 'country' => 'Germany'],
            ['category' => 'FR', 'country' => 'France'],
            ['category' => 'CN', 'country' => 'China'],
            ['category' => 'JP', 'country' => 'Japan'],
            ['category' => 'IN', 'country' => 'India'],
            ['category' => 'BR', 'country' => 'Brazil'],
            ['category' => 'NG', 'country' => 'Nigeria'],
            ['category' => 'KE', 'country' => 'Kenya'],
            ['category' => 'EG', 'country' => 'Egypt'],
            ['category' => 'GH', 'country' => 'Ghana'],
            ['category' => 'TZ', 'country' => 'Tanzania'],
            ['category' => 'UG', 'country' => 'Uganda'],
            ['category' => 'RW', 'country' => 'Rwanda']
        ];
    }
    
    // Add "All Countries" at the beginning
    array_unshift($countries, ['category' => 'ALL', 'country' => 'All Countries2']);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $countries
    ]);
    
} catch(PDOException $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>