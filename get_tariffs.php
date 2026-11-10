<?php
// api/get_tariffs.php
header('Content-Type: application/json');

// Database configuration
$host = '156.38.230.252';
$dbname = 'belliunasd_bc';
$username = 'belliunasd_bc';
$password = 'YAEX4rP3n83gzm98nda8';

try {
    // Create MySQL connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get search parameters
    $hs_code = isset($_GET['hs_code']) ? $_GET['hs_code'] : '';
    $search = isset($_GET['wildcard']) ? $_GET['wildcard'] : '';
    $country = isset($_GET['country']) ? $_GET['country'] : '';
    
    // Build the query - removed country filter since there's no country column
    $sql = "SELECT 
                headings.description AS headingses,
                subheadings.description as suheadingses,
                tariff_items.hs_code,
                tariff_items.description,
                tariff_items.rate_general,
                tariff_items.rate_eu_uk,
                tariff_items.rate_efta,
                tariff_items.rate_sadc,
                tariff_items.rate_afcfta,
                tariff_items.full_search_text 
            FROM tariff_items
            INNER JOIN headings ON headings.id = tariff_items.heading_id
            INNER JOIN subheadings ON subheadings.id = tariff_items.subheading_id
            WHERE 1=1";
    
    $params = [];
    
    // Add filters
    if (!empty($hs_code)) {
        $sql .= " AND tariff_items.hs_code = :hs_code";
        $params[':hs_code'] = $hs_code;
    }
    
    if (!empty($search)) {
        $sql .= " AND (tariff_items.full_search_text LIKE :search OR tariff_items.hs_code LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Note: No country filter since there's no country column in tariff_items
    
    // Add sorting
    $sql .= " ORDER BY tariff_items.hs_code ASC";
    
    // Prepare and execute
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Fetch results
    $tariffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'data' => $tariffs,
        'total' => count($tariffs)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>