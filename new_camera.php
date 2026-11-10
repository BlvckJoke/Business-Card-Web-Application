<?php
session_start();
require_once "config/db.php";
if (!isset($_SESSION['user_id']) || !isset($_SESSION['names'])) {
    header("Location: index.php");
    exit;
}

// Display success message
if (isset($_GET['success']) && $_GET['success'] == 1) {
    echo '
    <div class="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg shadow-lg z-50" role="alert">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span>Business card saved successfully!</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-green-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    ';
}

// Display error message
if (isset($_GET['error']) && $_GET['error'] == 1) {
    echo '
    <div class="fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-lg z-50" role="alert">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span>Error saving business card. Please try again.</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-red-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    ';
}

$countries = [];
$countriesJsonFilePath = __DIR__ . '/json/countries.json';

if (file_exists($countriesJsonFilePath)) {
    $countriesJson = file_get_contents($countriesJsonFilePath);
    $countries = json_decode($countriesJson, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($countries)) {
        // Fallback to basic countries if JSON is invalid
        $countries = ["United States", "United Kingdom", "Canada", "Australia", "Germany"];
        error_log("Error parsing countries.json: " . json_last_error_msg());
    }
    
    sort($countries);
} else {
    // Create the JSON file if it doesn't exist
    $defaultCountries = [
        "United States", "United Kingdom", "Canada", "Australia", 
        "Germany", "France", "Japan", "China", "India"
    ];
    
    // Create the JSON file
    file_put_contents($countriesJsonFilePath, json_encode($defaultCountries, JSON_PRETTY_PRINT));
    $countries = $defaultCountries;
    sort($countries);
}

// Load categories
$categories = [];
$categoriesJsonFilePath = __DIR__ . '/json/category.json';

if (file_exists($categoriesJsonFilePath)) {
    $categoriesJson = file_get_contents($categoriesJsonFilePath);
    $categories = json_decode($categoriesJson, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($categories)) {
        // Fallback to basic categories if JSON is invalid
        $categories = ["Bakery & Pastry Shop", "Restaurant", "Fast Food", "Food Truck", 
                      "Grocery Store", "Butcher Shop", "Catering Service", "Distillery", "Farmers Market"];
        error_log("Error parsing category.json: " . json_last_error_msg());
    }
    
    sort($categories);
} else {
    // Create the JSON file if it doesn't exist
    $defaultCategories = [
        "Bakery & Pastry Shop", "Restaurant", "Café & Coffee Shop", "Fast Food", "Food Truck", 
        "Grocery Store", "Supermarket", "Butcher Shop", "Ice Cream Parlor", "Catering Service", 
        "Brewery", "Winery/Vineyard", "Distillery", "Bar/Pub", "Pizzeria", "Food Delivery Service", 
        "Organic Food Store", "Farmers Market", "Juice Bar", "Dessert Shop", "Candy Store", 
        "Chocolate Shop", "Tea House", "Food Court Vendor", "BBQ Restaurant", "Seafood Restaurant", 
        "Steakhouse", "Sushi Restaurant", "Vegetarian/Vegan Restaurant", "Food Manufacturing"
    ];
    
    // Create the JSON file with more categories
    file_put_contents($categoriesJsonFilePath, json_encode($defaultCategories, JSON_PRETTY_PRINT));
    $categories = $defaultCategories;
    sort($categories);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Card Info Collector</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <style>
/* Animation for notifications */
@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Modal styles */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    width: 400px;
    max-width: 90%;
}

/* Select2 custom styling */
.select2-container--default .select2-selection--single {
    height: 42px;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 42px;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px;
}

/* Hidden utility */
.hidden {
    display: none !important;
}

/* Camera grid overlay - Rule of thirds */
.camera-grid {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 10;
    display: block !important;
}

.camera-grid-line {
    position: absolute;
    background: rgba(255, 255, 255, 0.6);
    box-shadow: 0 0 2px rgba(0, 0, 0, 0.3);
}

/* Vertical lines */
.camera-grid-line.vertical {
    width: 2px;
    height: 100%;
    top: 0;
}

.camera-grid-line.vertical.line-1 {
    left: 33.33%;
}

.camera-grid-line.vertical.line-2 {
    left: 66.66%;
}

/* Horizontal lines */
.camera-grid-line.horizontal {
    width: 100%;
    height: 2px;
    left: 0;
}

.camera-grid-line.horizontal.line-3 {
    top: 33.33%;
}

.camera-grid-line.horizontal.line-4 {
    top: 66.66%;
}

/* Camera video container */
.camera-video-container {
    position: relative;
    display: inline-block;
    max-width: 100%;
    max-height: 70vh;
    border-radius: 8px;
    overflow: hidden;
}
/* Cropper.js custom styles */
.cropper-view-box,
.cropper-face {
    border-radius: 4px;
}

.cropper-view-box {
    box-shadow: 0 0 0 1000px rgba(0, 0, 0, 0.5);
    outline: 2px solid rgba(255, 255, 255, 0.9);
    outline-color: rgba(59, 130, 246, 0.8);
}

.cropper-line {
    background-color: rgba(59, 130, 246, 0.8);
}

.cropper-point {
    background-color: rgba(59, 130, 246, 0.9);
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.cropper-point.point-se {
    background-color: #10b981;
}

/* Cropping modal styles */
#cropModal .cropper-container {
    max-height: 60vh;
}

#cropImage {
    max-width: 100%;
    max-height: 60vh;
}

/* Crop aspect ratio buttons active state */
.bg-blue-100[data-active="true"] {
    background-color: #3b82f6;
    color: white;
}
/* Grid toggle button */
.grid-toggle-btn {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 20;
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    transition: background 0.3s;
}

.grid-toggle-btn:hover {
    background: rgba(0, 0, 0, 0.7);
}

.grid-toggle-btn.active {
    background: rgba(59, 130, 246, 0.8);
}
</style>
</head>
<body class="bg-gray-50 min-h-screen p-4 md:p-6">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <!-- Top Header -->
<header class="bg-white shadow-sm mb-6">
    <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
        <!-- User Name -->
        <div class="flex items-center space-x-2">
            <i class="fas fa-user-circle text-blue-500 text-2xl"></i>
            <span class="text-gray-800 font-semibold text-lg">
    Welcome, <?php echo htmlspecialchars($_SESSION['names']); ?>
</span>

        </div>

        <!-- Logout -->
        <button onclick="openLogoutModal()"
            class="flex items-center space-x-2 text-red-600 hover:text-red-700 font-medium">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </button>
    </div>
</header>

        <div class="flex flex-col lg:flex-row gap-6">
                 <!-- Cards Display Section -->
                 <div class="lg:w-1/2">
    <div class="bg-white rounded-xl shadow-md p-4 md:p-6">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-200">
            <h2 class="text-lg md:text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-list-alt text-purple-500 mr-2 text-lg"></i>
                <span class="mobile-text-sm md:text-base">Saved Cards</span>
            </h2>
            <div class="text-xs md:text-sm text-gray-500" id="cardsCount">0 cards</div>
        </div>

        <!-- Cards Container -->
        <div id="cardList" class="space-y-3 md:space-y-4 max-h-[500px] md:max-h-[600px] overflow-y-auto pr-1 md:pr-2">
            <?php
            $user_id = $_SESSION['user_id'];

            $sql = "SELECT * FROM business_cards WHERE users = ? ORDER BY date_added DESC";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                // Hide empty state
                echo '<script>document.getElementById("emptyState").classList.add("hidden");</script>';
                $cardCount = $result->num_rows;
                $cardText = $cardCount . ' card' . ($cardCount !== 1 ? 's' : '');
                echo '<script>document.getElementById("cardsCount").textContent = "' . $cardText . '";</script>';

                while ($card = $result->fetch_assoc()) {
                    $frontPhoto = !empty($card['front_photo']) ? htmlspecialchars($card['front_photo']) : '';
                    $backPhoto  = !empty($card['back_photo']) ? htmlspecialchars($card['back_photo']) : '';
                    $company    = htmlspecialchars($card['company']);
                    $category   = htmlspecialchars($card['category']);
                    $country    = htmlspecialchars($card['country']);
                    $note       = htmlspecialchars($card['note']);
                    $date       = date('M d, Y', strtotime($card['date_added']));
                    $cardId     = $card['id'];
                    
                    // Truncate company name on mobile if too long
                    $companyDisplay = strlen($company) > 30 ? substr($company, 0, 30) . '...' : $company;
                    $companyFull = strlen($company) > 30 ? 'title="' . htmlspecialchars($company) . '"' : '';
                    
                    // Truncate category/country on mobile
                    $categoryDisplay = strlen($category) > 20 ? substr($category, 0, 20) . '...' : $category;
                    $countryDisplay = strlen($country) > 15 ? substr($country, 0, 15) . '...' : $country;
                    ?>
                    
                    <!-- Mobile-optimized card -->
                    <div class="bg-gray-50 rounded-lg border border-gray-200 p-3 md:p-4 hover:border-blue-300 transition card-hover mobile-card"
                         onclick="viewCardDetails(<?php echo $cardId; ?>)">
                        
                        <!-- Top section with company and delete -->
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1 mr-2">
                                <h3 class="font-semibold text-gray-800 text-sm md:text-base hover:text-blue-600 transition-colors truncate"
                                    <?php echo $companyFull; ?>>
                                    <?php echo $companyDisplay; ?>
                                </h3>
                                
                                <!-- Category and Country badges -->
                                <div class="flex flex-wrap gap-1 md:gap-2 mt-2">
                                    <span class="inline-flex items-center bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full max-w-[120px] md:max-w-none truncate">
                                        <i class="fas fa-tag mr-1 text-xs"></i>
                                        <span class="truncate" title="<?php echo htmlspecialchars($category); ?>">
                                            <?php echo $categoryDisplay; ?>
                                        </span>
                                    </span>
                                    <span class="inline-flex items-center bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full max-w-[100px] md:max-w-none truncate">
                                        <i class="fas fa-globe mr-1 text-xs"></i>
                                        <span class="truncate" title="<?php echo htmlspecialchars($country); ?>">
                                            <?php echo $countryDisplay; ?>
                                        </span>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Delete button - larger on mobile -->
                            <button onclick="deleteCard(event, <?php echo $cardId; ?>)"
                                    class="text-red-500 hover:text-red-700 p-2 transition-colors relative z-10 mobile-touch"
                                    aria-label="Delete card">
                                <i class="fas fa-trash text-sm md:text-base"></i>
                            </button>
                        </div>
                        
                        <!-- Note with line clamp -->
                        <?php if (!empty($note)): ?>
                        <div class="mb-2">
                            <p class="text-gray-600 text-xs md:text-sm mobile-line-clamp" 
                               title="<?php echo htmlspecialchars($note); ?>">
                                <?php echo nl2br(htmlspecialchars($note)); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Bottom section with date and action buttons -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mt-3 pt-3 border-t border-gray-100">
                            <!-- Date -->
                            <div class="flex items-center text-gray-500 text-xs">
                                <i class="far fa-calendar mr-1"></i>
                                <span><?php echo $date; ?></span>
                            </div>
                            
                            <!-- Action buttons -->
                            <div class="flex flex-wrap gap-2 sm:gap-3 button-spacing">
                                <!-- View Details link -->
                                <a href="card_details.php?id=<?php echo $cardId; ?>" 
                                   class="inline-flex items-center text-blue-600 hover:text-blue-800 text-xs md:text-sm transition-colors z-10 relative px-2 py-1 bg-blue-50 rounded"
                                   onclick="event.stopPropagation()">
                                    <i class="fas fa-external-link-alt mr-1 text-xs"></i>
                                    <span class="hidden sm:inline">View</span>
                                    <span class="sm:hidden">Details</span>
                                </a>
                         
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            } else {
                // Show empty state
                echo '
                <div id="emptyState" class="text-center py-8">
                    <i class="fas fa-inbox text-gray-300 text-4xl md:text-5xl mb-3"></i>
                    <h3 class="text-gray-500 font-medium text-base md:text-lg mb-1">No cards yet</h3>
                    <p class="text-gray-400 text-xs md:text-sm">Add your first business card using the form</p>
                </div>';
            }
            ?>
        </div>
    </div>
</div>
            <div class="lg:w-1/2">
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6 pb-3 border-b border-gray-200">
                        <i class="fas fa-plus-circle text-green-500 mr-2"></i>
                        Add New Card
                    </h2>
                    
                    <form id="cardForm" method="POST" action="save_card.php" enctype="multipart/form-data" onsubmit="return validateForm();">
    <!-- Company Name -->
    <div class="mb-4">
        <label for="company" class="block text-sm font-medium text-gray-700 mb-1">
            <i class="fas fa-building mr-1 text-gray-500"></i>
            Company Name *
        </label>
        <input type="text" id="company" name="company" required
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
            placeholder="Enter company name">
    </div>

    <!-- Category -->
    <div class="mb-4">
        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
            <i class="fas fa-tag mr-1 text-gray-500"></i>
            Category *
        </label>
        <select id="category" name="category" required
        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
        <option value="">Select a category</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?php echo htmlspecialchars($category); ?>">
                <?php echo htmlspecialchars($category); ?>
            </option>
        <?php endforeach; ?>
    </select>
    </div>

    <!-- Country -->
 <!-- Country -->
<div class="mb-4">
    <label for="country" class="block text-sm font-medium text-gray-700 mb-1">
        <i class="fas fa-globe mr-1 text-gray-500"></i>
        Country *
    </label>
    <select id="country" name="country" required
        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
        <option value="">Select a country</option>
        <?php foreach ($countries as $country): ?>
            <option value="<?php echo htmlspecialchars($country); ?>">
                <?php echo htmlspecialchars($country); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

    <!-- Photo Capture Section -->

    
    <!-- Replace the entire Photo Capture Section -->
<div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-4">
    <h3 class="text-lg font-medium text-gray-800 mb-3">
        <i class="fas fa-camera mr-2 text-blue-500"></i>
        Card Photos <span class="text-sm text-gray-600">(Crop & Align)</span>
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <!-- Front Photo -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-id-card mr-1"></i>
                Front Side
            </label>
            <div class="relative">
                <!-- Preview Container -->
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 text-center h-40 cursor-pointer hover:border-blue-400 transition-colors mb-2"
                     onclick="startCropping('front')"
                     id="frontPreviewContainer">
                    <div id="frontPreview" class="h-full flex flex-col items-center justify-center">
                        <i class="fas fa-camera text-gray-400 text-3xl mb-2"></i>
                        <span class="text-sm text-gray-500">Click to add & crop</span>
                    </div>
                </div>
                
                <!-- Hidden file input -->
                <input type="file" id="frontPhoto" name="front_photo" accept="image/*" capture="environment" 
                       class="hidden" onchange="handleImageForCropping(this, 'front')">
                <!-- Hidden input for cropped base64 data -->
                <input type="hidden" id="frontPhotoData" name="front_photo_data">
                
                <!-- Action buttons -->
                <div class="flex flex-wrap gap-2 justify-center">
                    <button type="button" onclick="capturePhoto('front')" 
                            class="px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 flex items-center">
                        <i class="fas fa-camera mr-1"></i>Take Photo
                    </button>
                    <button type="button" onclick="document.getElementById('frontPhoto').click()"
                            class="px-3 py-1 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300 flex items-center">
                        <i class="fas fa-upload mr-1"></i>Upload
                    </button>
                    <button type="button" onclick="startCropping('front')" id="frontCropBtn"
                            class="px-3 py-1 bg-purple-500 text-white text-sm rounded hover:bg-purple-600 flex items-center hidden">
                        <i class="fas fa-crop mr-1"></i>Crop
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Back Photo (similar structure) -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-id-card-alt mr-1"></i>
                Back Side
            </label>
            <div class="relative">
                <!-- Preview Container -->
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 text-center h-40 cursor-pointer hover:border-blue-400 transition-colors mb-2"
                     onclick="startCropping('back')"
                     id="backPreviewContainer">
                    <div id="backPreview" class="h-full flex flex-col items-center justify-center">
                        <i class="fas fa-camera text-gray-400 text-3xl mb-2"></i>
                        <span class="text-sm text-gray-500">Click to add & crop</span>
                    </div>
                </div>
                
                <!-- Hidden file input -->
                <input type="file" id="backPhoto" name="back_photo" accept="image/*" capture="environment" 
                       class="hidden" onchange="handleImageForCropping(this, 'back')">
                <!-- Hidden input for cropped base64 data -->
                <input type="hidden" id="backPhotoData" name="back_photo_data">
                
                <!-- Action buttons -->
                <div class="flex flex-wrap gap-2 justify-center">
                    <button type="button" onclick="capturePhoto('back')" 
                            class="px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 flex items-center">
                        <i class="fas fa-camera mr-1"></i>Take Photo
                    </button>
                    <button type="button" onclick="document.getElementById('backPhoto').click()"
                            class="px-3 py-1 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300 flex items-center">
                        <i class="fas fa-upload mr-1"></i>Upload
                    </button>
                    <button type="button" onclick="startCropping('back')" id="backCropBtn"
                            class="px-3 py-1 bg-purple-500 text-white text-sm rounded hover:bg-purple-600 flex items-center hidden">
                        <i class="fas fa-crop mr-1"></i>Crop
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="text-xs text-gray-500 flex items-center">
        <i class="fas fa-info-circle mr-2"></i>
        Take or upload photos, then crop to focus on the business card
    </div>
</div>


    <!-- Date (Auto-filled) -->
    <div class="mb-4">
        <label for="date" class="block text-sm font-medium text-gray-700 mb-1">
            <i class="fas fa-calendar mr-1 text-gray-500"></i>
            Date
        </label>
        <input type="date" id="date" name="date_added" required
            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-700">
    </div>

    <!-- Note -->
    <div class="mb-6">
        <label for="note" class="block text-sm font-medium text-gray-700 mb-1">
            <i class="fas fa-sticky-note mr-1 text-gray-500"></i>
            Note
        </label>
        <textarea id="note" name="note" rows="3"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
            placeholder="Add any additional notes..."></textarea>
    </div>

    <!-- Submit Button -->
    <button type="submit" id="submitBtn"
        class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3 px-4 rounded-lg shadow-md hover:shadow-lg transition duration-300 transform hover:-translate-y-0.5">
        <i class="fas fa-save mr-2"></i>
        Save Business Card
    </button>
</form>
                </div>
            </div>

     


    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl p-4 m-4 max-w-2xl max-h-[90vh]">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Photo Preview</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <img id="modalImage" src="" alt="Preview" class="max-w-full max-h-[70vh] rounded">
            <p id="modalCaption" class="text-center text-gray-600 mt-2"></p>
        </div>
    </div>


    <div id="logoutModal" class="fixed inset-0 bg-black/50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-xs p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">
                Confirm Logout
            </h2>
            <p class="text-gray-600 mb-6">
                Are you sure you want to log out?
            </p>
            <div class="flex justify-end gap-3">
                <button onclick="closeLogoutModal()"
                    class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300">
                    Cancel
                </button>
                <button onclick="confirmLogout()"
                    class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">
                    Logout
                </button>
            </div>
        </div>
    </div>
<!-- Modal for adding new category -->
<div id="newCategoryModal" class="modal hidden">
    <div class="modal-content">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Add New Category</h3>
            <button type="button" class="modal-close text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mb-4">
            <label for="newCategoryInput" class="block text-sm font-medium text-gray-700 mb-1">
                Category Name
            </label>
            <input type="text" id="newCategoryInput" class="w-full p-2 border border-gray-300 rounded">
        </div>
        <div class="flex justify-end space-x-2">
            <button type="button" class="modal-cancel px-4 py-2 text-gray-700 border border-gray-300 rounded">
                Cancel
            </button>
            <button type="button" id="saveNewCategoryBtn" class="px-4 py-2 bg-blue-600 text-white rounded">
                Save Category
            </button>
        </div>
    </div>
</div>
<!-- Cropping Modal -->
<div id="cropModal" class="fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-[9999] hidden">
    <div class="bg-white rounded-xl p-4 m-4 max-w-4xl w-full max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Crop Image</h3>
            <div class="flex items-center gap-2">
                <button onclick="rotateImage(-90)" class="p-2 hover:bg-gray-100 rounded">
                    <i class="fas fa-rotate-left"></i>
                </button>
                <button onclick="rotateImage(90)" class="p-2 hover:bg-gray-100 rounded">
                    <i class="fas fa-rotate-right"></i>
                </button>
                <button onclick="resetCrop()" class="p-2 hover:bg-gray-100 rounded text-sm">
                    Reset
                </button>
                <button onclick="closeCropModal()" class="p-2 hover:bg-gray-100 rounded">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <!-- Cropper Container -->
        <div class="flex-1 overflow-hidden">
            <img id="cropImage" src="" alt="Image to crop" 
                 class="max-w-full max-h-[60vh] block mx-auto">
        </div>
        
        <!-- Crop Aspect Ratio Options -->
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Aspect Ratio</label>
            <div class="flex flex-wrap gap-2 mb-4">
                <button onclick="setCropRatio(1.6)" class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-sm">
                    Business Card (1.6:1)
                </button>
                <button onclick="setCropRatio(1.78)" class="px-3 py-1 bg-gray-100 text-gray-700 rounded text-sm">
                    16:9
                </button>
                <button onclick="setCropRatio(1.33)" class="px-3 py-1 bg-gray-100 text-gray-700 rounded text-sm">
                    4:3
                </button>
                <button onclick="setCropRatio(1)" class="px-3 py-1 bg-gray-100 text-gray-700 rounded text-sm">
                    Square (1:1)
                </button>
                <button onclick="setCropRatio(NaN)" class="px-3 py-1 bg-gray-100 text-gray-700 rounded text-sm">
                    Free
                </button>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex justify-end gap-3 pt-4 border-t">
            <button onclick="closeCropModal()" 
                    class="px-4 py-2 text-gray-700 border border-gray-300 rounded hover:bg-gray-50">
                Cancel
            </button>
            <button onclick="applyCrop()" 
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                <i class="fas fa-check mr-1"></i> Apply Crop
            </button>
        </div>
    </div>
</div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Add Select2 JS - REQUIRED for search functionality -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>

<script>
// Global variable to track grid visibility
let isGridVisible = true;

// Global functions that need to be accessible

// View photo in modal
function viewPhoto(side, imagePath) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const modalCaption = document.getElementById('modalCaption');
    
    modalImage.src = imagePath;
    modalCaption.textContent = `Business Card ${side === 'front' ? 'Front Side' : 'Back Side'}`;
    modal.classList.remove('hidden');
}


    
    // Function to load existing images from uploads folder
function loadExistingPhoto(side, filename) {
    // Assuming your uploads folder is accessible via URL
    const imageUrl = `/uploads/${filename}`;  // or full path like 'https://yoursite.com/uploads/filename.jpg'
    
    // Store the URL instead of base64
    document.getElementById(side + 'PhotoData').value = imageUrl;
    
    // Create preview with the URL
    const previewDiv = document.getElementById(side + 'Preview');
    previewDiv.innerHTML = `
        <img src="${imageUrl}" class="h-full w-full object-contain rounded" alt="${side} photo preview">
        <button type="button" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center" 
                onclick="removePhoto('${side}', event)">×</button>
    `;
    previewDiv.classList.add('relative');
}

// Handle file selection for photos
function handleFileSelect(input, previewId, dataInputId) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        
        reader.onload = function(event) {
            // Store base64 data
            document.getElementById(dataInputId).value = event.target.result;
            
            // Create preview
            const previewDiv = document.getElementById(previewId);
            previewDiv.innerHTML = `
                <img src="${event.target.result}" class="h-full w-full object-contain rounded" alt="photo preview">
                <button type="button" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center" 
                        onclick="removePhoto('${previewId === 'frontPreview' ? 'front' : 'back'}', event)">×</button>
            `;
            previewDiv.classList.add('relative');
        };
        
        reader.readAsDataURL(file);
    }
}

// Remove photo
function removePhoto(side, event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    
    const previewDiv = document.getElementById(side + 'Preview');
    const fileInput = document.getElementById(side + 'Photo');
    const dataInput = document.getElementById(side + 'PhotoData');
    
    // Reset preview
    previewDiv.innerHTML = `
        <i class="fas fa-camera text-gray-400 text-3xl mb-2"></i>
        <span class="text-sm text-gray-500">No photo</span>
    `;
    previewDiv.classList.remove('relative');
    
    // Clear inputs
    if (fileInput) fileInput.value = '';
    if (dataInput) dataInput.value = '';
}


// Close modal
function closeModal() {
    document.getElementById('imageModal').classList.add('hidden');
}

// Logout functions
function openLogoutModal() {
    document.getElementById('logoutModal').classList.remove('hidden');
}

function closeLogoutModal() {
    document.getElementById('logoutModal').classList.add('hidden');
}

function confirmLogout() {
    window.location.href = 'logout.php';
}

// Delete a card
function deleteCard(cardId) {
    if (!confirm('Are you sure you want to delete this card?')) return;
    
    fetch('delete_card.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'card_id=' + cardId
    })
    .then(response => response.text())
    .then(data => {
        if (data === 'success') {
            window.location.reload();
        } else {
            alert('Error deleting card: ' + data);
        }
    })
    .catch(error => {
        alert('Error deleting card: ' + error);
    });
}

// Capture photo using device camera
function capturePhoto(side) {
    // Check if we're on mobile/device with camera
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        useCameraWithAlignmentGrid(side);
    } else {
        // Fallback: trigger file input with camera capture
        const fileInput = document.getElementById(side + 'Photo');
        if (fileInput) {
            fileInput.setAttribute('capture', 'environment');
            fileInput.click();
            setTimeout(() => fileInput.removeAttribute('capture'), 100);
        }
    }
}

function useCameraWithAlignmentGrid(side) {
    stopCamera();
    
    navigator.mediaDevices.getUserMedia({ 
        video: { 
            facingMode: 'environment',
            width: { ideal: 1920 },
            height: { ideal: 1080 }
        },
        audio: false 
    })
    .then(function(stream) {
        createAlignmentCameraModal(side, stream);
    })
    .catch(function(error) {
        console.error('Camera error:', error);
        const fileInput = document.getElementById(side + 'Photo');
        if (fileInput) {
            fileInput.setAttribute('capture', 'environment');
            fileInput.click();
            setTimeout(() => fileInput.removeAttribute('capture'), 100);
        }
    });
}

function createAlignmentCameraModal(side, stream) {
    // Create modal container
    const modal = document.createElement('div');
    modal.id = 'cameraModal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.95);
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    `;
    
    // Create main container
    const mainContainer = document.createElement('div');
    mainContainer.style.cssText = `
        width: 90%;
        max-width: 500px;
        display: flex;
        flex-direction: column;
        align-items: center;
    `;
    
    // Create instruction text
    const instruction = document.createElement('div');
    instruction.innerHTML = `
        <div style="
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            backdrop-filter: blur(10px);
        ">
            <div style="font-weight: bold; margin-bottom: 5px;">
                <i class="fas fa-align-center" style="margin-right: 8px;"></i>
                Align Card with Grid
            </div>
            <div style="font-size: 14px; opacity: 0.9;">
                Position the business card within the frame
            </div>
        </div>
    `;
    
    // Create video container with border
    const videoContainer = document.createElement('div');
    videoContainer.style.cssText = `
        position: relative;
        width: 100%;
        max-width: 400px;
        aspect-ratio: 1.6; /* Business card ratio */
        border: 3px solid rgba(255, 255, 255, 0.8);
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 0 20px rgba(0,0,0,0.5);
        margin: 0 auto;
    `;
    
    // Create video element
    const video = document.createElement('video');
    video.id = 'cameraVideo';
    video.autoplay = true;
    video.playsInline = true;
    video.srcObject = stream;
    video.style.cssText = `
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    `;
    
    // Create alignment grid overlay
    const gridOverlay = document.createElement('div');
    gridOverlay.id = 'alignmentGrid';
    gridOverlay.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
    `;
    
    // Create card outline (ideal business card size)
    const cardOutline = document.createElement('div');
    cardOutline.style.cssText = `
        position: absolute;
        top: 10%;
        left: 10%;
        width: 80%;
        height: 80%;
        border: 2px dashed rgba(59, 130, 246, 0.8);
        border-radius: 4px;
        box-shadow: 0 0 0 1000px rgba(0, 0, 0, 0.4);
    `;
    
    // Create corner guides
    const corners = [
        { top: '10%', left: '10%' }, // top-left
        { top: '10%', left: '90%' }, // top-right
        { top: '90%', left: '10%' }, // bottom-left
        { top: '90%', left: '90%' }  // bottom-right
    ];
    
    corners.forEach((corner, index) => {
        const cornerGuide = document.createElement('div');
        cornerGuide.style.cssText = `
            position: absolute;
            top: ${corner.top};
            left: ${corner.left};
            width: 20px;
            height: 20px;
            border: 2px solid rgba(59, 130, 246, 0.8);
            background: rgba(59, 130, 246, 0.2);
            transform: translate(-50%, -50%);
        `;
        
        // Add diagonal lines to corners
        const line1 = document.createElement('div');
        line1.style.cssText = `
            position: absolute;
            width: 12px;
            height: 2px;
            background: rgba(59, 130, 246, 0.8);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(45deg);
        `;
        
        const line2 = document.createElement('div');
        line2.style.cssText = `
            position: absolute;
            width: 12px;
            height: 2px;
            background: rgba(59, 130, 246, 0.8);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
        `;
        
        cornerGuide.appendChild(line1);
        cornerGuide.appendChild(line2);
        cardOutline.appendChild(cornerGuide);
    });
    
    // Create center alignment lines
    const centerLineVertical = document.createElement('div');
    centerLineVertical.style.cssText = `
        position: absolute;
        top: 10%;
        left: 50%;
        width: 1px;
        height: 80%;
        background: linear-gradient(to bottom, 
            transparent 0%, 
            rgba(255, 255, 255, 0.3) 20%, 
            rgba(255, 255, 255, 0.6) 50%, 
            rgba(255, 255, 255, 0.3) 80%, 
            transparent 100%);
        transform: translateX(-50%);
    `;
    
    const centerLineHorizontal = document.createElement('div');
    centerLineHorizontal.style.cssText = `
        position: absolute;
        top: 50%;
        left: 10%;
        width: 80%;
        height: 1px;
        background: linear-gradient(to right, 
            transparent 0%, 
            rgba(255, 255, 255, 0.3) 20%, 
            rgba(255, 255, 255, 0.6) 50%, 
            rgba(255, 255, 255, 0.3) 80%, 
            transparent 100%);
        transform: translateY(-50%);
    `;
    
    // Create edge detection lines (for alignment feedback)
    const edgeLines = [
        { class: 'top-edge', style: 'top: 10%; left: 0; width: 100%; height: 2px;' },
        { class: 'bottom-edge', style: 'top: 90%; left: 0; width: 100%; height: 2px;' },
        { class: 'left-edge', style: 'top: 0; left: 10%; width: 2px; height: 100%;' },
        { class: 'right-edge', style: 'top: 0; left: 90%; width: 2px; height: 100%;' }
    ];
    
    edgeLines.forEach(edge => {
        const line = document.createElement('div');
        line.className = edge.class;
        line.style.cssText = `
            position: absolute;
            ${edge.style}
            background: rgba(255, 255, 255, 0.2);
        `;
        gridOverlay.appendChild(line);
    });
    
    // Add all grid elements
    gridOverlay.appendChild(cardOutline);
    gridOverlay.appendChild(centerLineVertical);
    gridOverlay.appendChild(centerLineHorizontal);
    
    // Create controls container
    const controls = document.createElement('div');
    controls.style.cssText = `
        margin-top: 20px;
        display: flex;
        gap: 15px;
        align-items: center;
    `;
    
    // Create capture button (large and prominent)
    const captureBtn = document.createElement('button');
    captureBtn.innerHTML = '<i class="fas fa-camera fa-2x"></i>';
    captureBtn.style.cssText = `
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3B82F6, #1D4ED8);
        color: white;
        border: 4px solid rgba(255, 255, 255, 0.8);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        transition: transform 0.2s, box-shadow 0.2s;
    `;
    
    captureBtn.onmouseenter = function() {
        this.style.transform = 'scale(1.05)';
        this.style.boxShadow = '0 6px 20px rgba(0,0,0,0.4)';
    };
    
    captureBtn.onmouseleave = function() {
        this.style.transform = 'scale(1)';
        this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.3)';
    };
    
    // Create cancel button
    const cancelBtn = document.createElement('button');
    cancelBtn.innerHTML = '<i class="fas fa-times fa-lg"></i>';
    cancelBtn.style.cssText = `
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
        color: white;
        border: 2px solid rgba(255,255,255,0.3);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(10px);
    `;
    
    // Create flash toggle button
    const flashBtn = document.createElement('button');
    flashBtn.innerHTML = '<i class="fas fa-bolt"></i>';
    flashBtn.title = 'Toggle Flash';
    flashBtn.style.cssText = `
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
        color: white;
        border: 2px solid rgba(255,255,255,0.3);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(10px);
    `;
    
    // Create grid toggle button
    const gridToggleBtn = document.createElement('button');
    gridToggleBtn.innerHTML = '<i class="fas fa-th-large"></i>';
    gridToggleBtn.title = 'Toggle Grid';
    gridToggleBtn.style.cssText = `
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(59, 130, 246, 0.8);
        color: white;
        border: 2px solid rgba(255,255,255,0.3);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(10px);
    `;
    
    // Add instruction for flash (if supported)
    const flashSupport = document.createElement('div');
    flashSupport.innerHTML = `
        <div style="
            color: rgba(255,255,255,0.7);
            font-size: 12px;
            margin-top: 10px;
            text-align: center;
            padding: 8px;
            background: rgba(0,0,0,0.3);
            border-radius: 4px;
        ">
            <i class="fas fa-lightbulb" style="margin-right: 5px;"></i>
            Ensure good lighting for best results
        </div>
    `;
    
    // Build the modal
    videoContainer.appendChild(video);
    videoContainer.appendChild(gridOverlay);
    
    controls.appendChild(cancelBtn);
    controls.appendChild(captureBtn);
    controls.appendChild(flashBtn);
    controls.appendChild(gridToggleBtn);
    
    mainContainer.appendChild(instruction);
    mainContainer.appendChild(videoContainer);
    mainContainer.appendChild(controls);
    mainContainer.appendChild(flashSupport);
    
    modal.appendChild(mainContainer);
    document.body.appendChild(modal);
    
    // Event Listeners
    let isGridVisible = true;
    let isFlashOn = false;
    
    // Capture photo
    captureBtn.onclick = function() {
        captureBtn.innerHTML = '<i class="fas fa-spinner fa-spin fa-2x"></i>';
        captureBtn.disabled = true;
        
        capturePhotoFromStream(side, stream, video);
    };
    
    // Cancel
    cancelBtn.onclick = function() {
        stopCamera();
        modal.remove();
    };
    
    // Toggle grid
    gridToggleBtn.onclick = function() {
        isGridVisible = !isGridVisible;
        gridOverlay.style.display = isGridVisible ? 'block' : 'none';
        gridToggleBtn.style.background = isGridVisible ? 
            'rgba(59, 130, 246, 0.8)' : 'rgba(255,255,255,0.1)';
    };
    
    // Toggle flash (simulated - for real flash you'd need torch API)
    flashBtn.onclick = function() {
        isFlashOn = !isFlashOn;
        flashBtn.style.background = isFlashOn ? 
            'rgba(255, 255, 100, 0.8)' : 'rgba(255,255,255,0.1)';
        flashBtn.style.color = isFlashOn ? '#000' : '#fff';
        
        // Add visual flash effect (for demo)
        if (isFlashOn) {
            const flashOverlay = document.createElement('div');
            flashOverlay.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.3);
                z-index: 1;
                animation: flashEffect 0.3s;
            `;
            videoContainer.appendChild(flashOverlay);
            setTimeout(() => flashOverlay.remove(), 300);
        }
    };
    
    // Close on background click
    modal.onclick = function(e) {
        if (e.target === modal) {
            stopCamera();
            modal.remove();
        }
    };
    
    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            stopCamera();
            modal.remove();
        }
    });
    
    // Add flash animation style
    const style = document.createElement('style');
    style.textContent = `
        @keyframes flashEffect {
            0% { opacity: 0; }
            50% { opacity: 0.5; }
            100% { opacity: 0; }
        }
        
        @keyframes pulseOutline {
            0% { border-color: rgba(59, 130, 246, 0.8); }
            50% { border-color: rgba(34, 197, 94, 0.8); }
            100% { border-color: rgba(59, 130, 246, 0.8); }
        }
        
        .aligned {
            animation: pulseOutline 1s infinite;
        }
    `;
    document.head.appendChild(style);
}


// Use camera directly
function useCameraDirectly(side) {
    // Stop any existing streams
    stopCamera();
    
    navigator.mediaDevices.getUserMedia({ 
        video: { 
            facingMode: 'environment',
            width: { ideal: 1920 },
            height: { ideal: 1080 }
        },
        audio: false 
    })
    .then(function(stream) {
        // Create camera modal
        createCameraModal(side, stream);
    })
    .catch(function(error) {
        console.error('Camera error:', error);
        // Fallback to file input
        const fileInput = document.getElementById(side + 'Photo');
        if (fileInput) {
            fileInput.setAttribute('capture', 'environment');
            fileInput.click();
            setTimeout(() => fileInput.removeAttribute('capture'), 100);
        }
    });
}

function createCameraModal(side, stream) {
    // Create modal container
    const modal = document.createElement('div');
    modal.id = 'cameraModal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.95);
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    `;
    
    // Create video container with grid - explicit positioning for mobile
    const videoContainer = document.createElement('div');
    videoContainer.className = 'camera-video-container';
    videoContainer.style.cssText = `
        position: relative;
        display: inline-block;
        max-width: 100%;
        max-height: 70vh;
        border-radius: 8px;
        overflow: hidden;
    `;
    
    // Create video element
    const video = document.createElement('video');
    video.id = 'cameraVideo';
    video.autoplay = true;
    video.playsInline = true;
    video.srcObject = stream;
    video.style.cssText = `
        max-width: 100%;
        max-height: 70vh;
        transform: scaleX(-1); /* Mirror for selfie view */
        display: block;
    `;
    
    // Create camera grid overlay with inline styles for mobile compatibility
    const gridOverlay = document.createElement('div');
    gridOverlay.className = 'camera-grid';
    gridOverlay.id = 'cameraGrid';
    gridOverlay.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 10;
        display: block;
    `;
    
    // Create grid lines with inline styles for guaranteed visibility
    const gridLines = [
        { type: 'vertical', position: '33.33%', name: 'line-1' },
        { type: 'vertical', position: '66.66%', name: 'line-2' },
        { type: 'horizontal', position: '33.33%', name: 'line-3' },
        { type: 'horizontal', position: '66.66%', name: 'line-4' }
    ];
    
    gridLines.forEach(lineConfig => {
        const line = document.createElement('div');
        line.className = `camera-grid-line ${lineConfig.type} ${lineConfig.name}`;
        
        if (lineConfig.type === 'vertical') {
            line.style.cssText = `
                position: absolute;
                left: ${lineConfig.position};
                top: 0;
                width: 2px;
                height: 100%;
                background: rgba(255, 255, 255, 0.7);
                box-shadow: 0 0 3px rgba(0, 0, 0, 0.5);
            `;
        } else {
            line.style.cssText = `
                position: absolute;
                top: ${lineConfig.position};
                left: 0;
                width: 100%;
                height: 2px;
                background: rgba(255, 255, 255, 0.7);
                box-shadow: 0 0 3px rgba(0, 0, 0, 0.5);
            `;
        }
        
        gridOverlay.appendChild(line);
    });
    
    // Debug: Log grid creation
    console.log('Camera grid created with', gridOverlay.children.length, 'lines');
    console.log('Grid overlay styles:', gridOverlay.style.cssText);
    
    // Create grid toggle button
    const gridToggleBtn = document.createElement('button');
    gridToggleBtn.className = 'grid-toggle-btn active';
    gridToggleBtn.innerHTML = '<i class="fas fa-th"></i> Grid';
    gridToggleBtn.title = 'Toggle alignment grid';
    gridToggleBtn.onclick = function(e) {
        e.stopPropagation();
        toggleGrid();
    };
    
    // Append video and grid to container
    videoContainer.appendChild(video);
    videoContainer.appendChild(gridOverlay);
    videoContainer.appendChild(gridToggleBtn);
    
    // Create controls container
    const controls = document.createElement('div');
    controls.style.cssText = `
        margin-top: 20px;
        display: flex;
        gap: 15px;
        align-items: center;
    `;
    
    // Create capture button
    const captureBtn = document.createElement('button');
    captureBtn.innerHTML = '<i class="fas fa-camera fa-2x"></i>';
    captureBtn.style.cssText = `
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: white;
        color: #333;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    `;
    
    // Create cancel button
    const cancelBtn = document.createElement('button');
    cancelBtn.innerHTML = '<i class="fas fa-times fa-2x"></i>';
    cancelBtn.style.cssText = `
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    `;
    
    // Create flip camera button
    const flipBtn = document.createElement('button');
    flipBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
    flipBtn.title = 'Flip Camera';
    flipBtn.style.cssText = `
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    `;
    
    // Append elements
    controls.appendChild(cancelBtn);
    controls.appendChild(captureBtn);
    controls.appendChild(flipBtn);
    
    modal.appendChild(videoContainer);
    modal.appendChild(controls);
    document.body.appendChild(modal);
    
    // Add event listeners
    let currentFacingMode = 'environment';
    
    // Capture photo
    captureBtn.onclick = function() {
        capturePhotoFromStream(side, stream, video);
    };
    
    // Cancel
    cancelBtn.onclick = function() {
        stopCamera();
        modal.remove();
    };
    
    // Flip camera
    flipBtn.onclick = function() {
        currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
        restartCameraWithFacingMode(currentFacingMode, side);
    };
    
    // Close on background click
    modal.onclick = function(e) {
        if (e.target === modal) {
            stopCamera();
            modal.remove();
        }
    };
    
    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            stopCamera();
            modal.remove();
        }
    });
}

// Toggle grid visibility
function toggleGrid() {
    isGridVisible = !isGridVisible;
    const grid = document.getElementById('cameraGrid');
    const toggleBtn = document.querySelector('.grid-toggle-btn');
    
    if (grid && toggleBtn) {
        if (isGridVisible) {
            grid.style.display = 'block';
            toggleBtn.classList.add('active');
        } else {
            grid.style.display = 'none';
            toggleBtn.classList.remove('active');
        }
    }
}

function compressImage(imageData, maxWidth = 1200, quality = 0.7) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = function() {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Calculate new dimensions maintaining aspect ratio
            let width = img.width;
            let height = img.height;
            
            if (width > maxWidth) {
                height = (height * maxWidth) / width;
                width = maxWidth;
            }
            
            // Set canvas dimensions
            canvas.width = width;
            canvas.height = height;
            
            // Draw and compress image
            ctx.drawImage(img, 0, 0, width, height);
            
            // Convert to JPEG with specified quality
            const compressedData = canvas.toDataURL('image/jpeg', quality);
            
            // Calculate size reduction
            const originalSize = imageData.length;
            const compressedSize = compressedData.length;
            const reduction = ((originalSize - compressedSize) / originalSize * 100).toFixed(1);
            
            console.log(`Compressed: ${(originalSize/1024).toFixed(1)}KB → ${(compressedSize/1024).toFixed(1)}KB (${reduction}% reduction)`);
            
            resolve(compressedData);
        };
        
        img.onerror = function() {
            reject(new Error('Failed to load image for compression'));
        };
        
        img.src = imageData;
    });
}

function handleFileSelect(input, previewId, dataInputId) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validate file
        if (!file.type.match('image.*')) {
            alert('Please select an image file (JPEG, PNG, GIF)');
            input.value = '';
            return;
        }
        
        if (file.size > 10 * 1024 * 1024) { // 10MB limit (original)
            alert('Image size must be less than 10MB');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = async function(event) {
            const originalData = event.target.result;
            const side = previewId === 'frontPreview' ? 'front' : 'back';
            
            try {
                // Show loading state
                const previewDiv = document.getElementById(previewId);
                previewDiv.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full">
                        <i class="fas fa-spinner fa-spin text-blue-500 text-2xl mb-2"></i>
                        <span class="text-sm text-gray-500">Compressing image...</span>
                    </div>
                `;
                
                // Compress the image
                const compressedData = await compressImage(originalData, 1200, 0.7);
                
                // Store compressed data
                document.getElementById(dataInputId).value = compressedData;
                
                // Update preview with compressed image
                updatePhotoPreview(side, compressedData);
                
                showNotification('Image compressed and ready!', 'success');
                
            } catch (error) {
                console.error('Compression error:', error);
                // Fallback to original image
                document.getElementById(dataInputId).value = originalData;
                updatePhotoPreview(side, originalData);
                showNotification('Using original image', 'info');
            }
        };
        
        reader.onerror = function(error) {
            console.error('File reading error:', error);
            alert('Error reading file. Please try again.');
            input.value = '';
        };
        
        reader.readAsDataURL(file);
    }
}

// Updated capturePhotoFromStream function with compression
async function capturePhotoFromStream(side, stream, video) {
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    
    // Set canvas dimensions
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    // Draw video frame
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    // Get image data
    const originalData = canvas.toDataURL('image/jpeg', 0.9);
    
    try {
        // Compress the image
        const compressedData = await compressImage(originalData, 1200, 0.8);
        
        // Store compressed data
        document.getElementById(side + 'PhotoData').value = compressedData;
        
        // Update preview
        updatePhotoPreview(side, compressedData);
        
        // Show success message
        showNotification(`✅ ${side === 'front' ? 'Front' : 'Back'} photo captured!`, 'success');
        
    } catch (error) {
        console.error('Error:', error);
        // Fallback
        document.getElementById(side + 'PhotoData').value = originalData;
        updatePhotoPreview(side, originalData);
        showNotification('Photo saved (no compression)', 'info');
    }
    
    // Cleanup
    stopCamera();
    const modal = document.getElementById('cameraModal');
    if (modal) modal.remove();
}

// Update CSS styles for better grid visibility
const additionalStyles = `
    /* Enhanced camera grid styles */
    .camera-grid {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 10;
    }
    
    .grid-line {
        position: absolute;
        background: rgba(255, 255, 255, 0.6);
        box-shadow: 0 0 2px rgba(0, 0, 0, 0.5);
    }
    
    .grid-line.vertical {
        width: 1px;
        height: 100%;
        top: 0;
    }
    
    .grid-line.horizontal {
        width: 100%;
        height: 1px;
        left: 0;
    }
    
    .corner-guide {
        position: absolute;
        width: 20px;
        height: 20px;
        border: 2px solid rgba(59, 130, 246, 0.8);
    }
    
    .corner-guide::before,
    .corner-guide::after {
        content: '';
        position: absolute;
        background: rgba(59, 130, 246, 0.8);
    }
    
    .corner-guide.top-left {
        top: 0;
        left: 0;
        border-right: none;
        border-bottom: none;
    }
    
    .corner-guide.top-right {
        top: 0;
        right: 0;
        border-left: none;
        border-bottom: none;
    }
    
    .corner-guide.bottom-left {
        bottom: 0;
        left: 0;
        border-right: none;
        border-top: none;
    }
    
    .corner-guide.bottom-right {
        bottom: 0;
        right: 0;
        border-left: none;
        border-top: none;
    }
    
    .card-frame {
        position: absolute;
        top: 10%;
        left: 10%;
        width: 80%;
        height: 80%;
        border: 2px dashed rgba(255, 255, 255, 0.7);
        border-radius: 8px;
    }
`;

// Add the styles to the document
const styleSheet = document.createElement("style");
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);


function updatePhotoPreview(side, imageData) {
    const previewDiv = document.getElementById(side + 'Preview');
    
    // Calculate image size
    const base64Length = imageData.length - 'data:image/jpeg;base64,'.length;
    const bytes = base64Length * 0.75;
    const kb = Math.round(bytes / 1024);
    
    previewDiv.innerHTML = `
        <img src="${imageData}" class="h-full w-full object-contain rounded" alt="${side} photo preview">
        <button type="button" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center hover:bg-red-600" 
                onclick="removePhoto('${side}', event)">×</button>
        <div class="absolute bottom-1 left-1 bg-black/70 text-white text-xs px-2 py-1 rounded">
            ${kb} KB
        </div>
    `;
    previewDiv.classList.add('relative');
}

// Stop camera
function stopCamera() {
    const video = document.getElementById('cameraVideo');
    if (video && video.srcObject) {
        const tracks = video.srcObject.getTracks();
        tracks.forEach(track => track.stop());
    }
}


// Convert image to JPEG for better compression
function convertToJpeg(imageData) {
    return new Promise((resolve) => {
        const img = new Image();
        img.onload = function() {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Set canvas size to image size
            canvas.width = img.width;
            canvas.height = img.height;
            
            // Draw image
            ctx.drawImage(img, 0, 0);
            
            // Convert to JPEG with 85% quality
            const jpegData = canvas.toDataURL('image/jpeg', 0.85);
            resolve(jpegData);
        };
        img.src = imageData;
    });
}
// Form validation
function validateForm() {
    // Check required text fields
    const required = ['company', 'category', 'country', 'date'];
    
    for (let i = 0; i < required.length; i++) {
        const field = required[i];
        const input = document.getElementById(field);
        
        if (!input || !input.value.trim()) {
            let fieldName = field.replace('_', ' ');
            if (field === 'date') fieldName = 'date';
            alert(`Please fill in ${fieldName}`);
            if (input) input.focus();
            return false;
        }
    }
    // Simple and reliable form validation
function validateForm() {
    console.log("Validating form...");
    
    // Check required fields
    const requiredFields = ['company', 'category', 'country', 'date'];
    
    for (const fieldId of requiredFields) {
        const field = document.getElementById(fieldId);
        if (!field || !field.value.trim()) {
            alert(`Please fill in ${fieldId.replace('_', ' ')}`);
            if (field) field.focus();
            return false;
        }
    }
    
    // Check photos
    const frontPhotoData = document.getElementById('frontPhotoData').value;
    const backPhotoData = document.getElementById('backPhotoData').value;
    const frontFileInput = document.getElementById('frontPhoto');
    const backFileInput = document.getElementById('backPhoto');
    
    const hasFrontPhoto = frontPhotoData || (frontFileInput && frontFileInput.files.length > 0);
    const hasBackPhoto = backPhotoData || (backFileInput && backFileInput.files.length > 0);
    
    if (!hasFrontPhoto && !hasBackPhoto) {
        if (!confirm('No photos provided. Save without photos?')) {
            return false;
        }
    }
    
    // Validate base64 data if present
    if (frontPhotoData && !frontPhotoData.startsWith('data:image')) {
        alert('Invalid front photo data. Please capture the photo again.');
        return false;
    }
    
    if (backPhotoData && !backPhotoData.startsWith('data:image')) {
        alert('Invalid back photo data. Please capture the photo again.');
        return false;
    }
    
    // Warn if images are still too large after compression
    const maxSizeKB = 500; // Max 500KB per image
    
    [frontPhotoData, backPhotoData].forEach((data, index) => {
        if (data) {
            const sizeKB = (data.length * 0.75) / 1024; // Approximate size in KB
            if (sizeKB > maxSizeKB) {
                const side = index === 0 ? 'Front' : 'Back';
                if (!confirm(`${side} image is ${sizeKB.toFixed(0)}KB (max recommended: ${maxSizeKB}KB). Continue anyway?`)) {
                    return false;
                }
            }
        }
    });

    console.log("Form validation passed");
    return true;
}

// Add form submit event listener
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('cardForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateForm()) {
                return false;
            }
            
            // Show compression stats before submit
            const frontData = document.getElementById('frontPhotoData').value;
            const backData = document.getElementById('backPhotoData').value;
            
            if (frontData) {
                const sizeKB = (frontData.length * 0.75) / 1024;
                console.log(`Front image size: ${sizeKB.toFixed(1)}KB`);
            }
            
            if (backData) {
                const sizeKB = (backData.length * 0.75) / 1024;
                console.log(`Back image size: ${sizeKB.toFixed(1)}KB`);
            }
            
            // Update button state
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Uploading...';
            submitBtn.disabled = true;
            
            // Submit form
            setTimeout(() => {
                form.submit();
            }, 100);
        });
    }
});
    // Validate country specifically
    const countryField = document.getElementById('country');
    let countryValue = '';
    
    if (countryField.tagName === 'SELECT') {
        countryValue = countryField.value;
    }
    
    if (!countryValue) {
        alert('Please select a country');
        countryField.focus();
        return false;
    }
    
    // Check if at least one photo is provided
    const frontPhotoData = document.getElementById('frontPhotoData').value;
    const backPhotoData = document.getElementById('backPhotoData').value;
    const frontFileInput = document.getElementById('frontPhoto');
    const backFileInput = document.getElementById('backPhoto');
    
    const hasFrontPhoto = frontPhotoData || (frontFileInput && frontFileInput.files.length > 0);
    const hasBackPhoto = backPhotoData || (backFileInput && backFileInput.files.length > 0);
    
    if (!hasFrontPhoto && !hasBackPhoto) {
        if (!confirm('No photos provided. Continue without photos?')) {
            return false;
        }
    }
    
    // Show loading state on button
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
        submitBtn.disabled = true;
    }
    
    return true;
}

// Function to save new category
function saveNewCategory(categoryName) {
    console.log('Attempting to save category:', categoryName);
    
    return $.ajax({
        url: 'save_category.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            category: categoryName,
            action: 'add_category',
            timestamp: new Date().toISOString()
        }),
        success: function(response, status, xhr) {
            console.log('Server response:', response);
            console.log('Status:', status);
            console.log('Content-Type:', xhr.getResponseHeader('Content-Type'));
            
            return response;
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error Details:');
            console.error('Status:', status);
            console.error('Error:', error);
            console.error('Response Text:', xhr.responseText);
            console.error('Status Code:', xhr.status);
            console.error('Ready State:', xhr.readyState);
            
            // Return a rejected promise
            return $.Deferred().reject(xhr, status, error);
        }
    });
}

function viewCardDetails(cardId) {
    // Only navigate on click, not on touch scroll
    if (!isScrolling) {
        window.location.href = 'card_details.php?id=' + cardId;
    }
}

// Track scrolling to prevent accidental clicks
let isScrolling = false;
let scrollTimer;

window.addEventListener('scroll', function() {
    isScrolling = true;
    clearTimeout(scrollTimer);
    
    scrollTimer = setTimeout(function() {
        isScrolling = false;
    }, 150); // Reset scrolling flag 150ms after scroll ends
});

// Enhanced delete function for mobile
function deleteCard(event, cardId) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    
    // Mobile-friendly confirmation
    if (window.innerWidth <= 640) {
        // Use a styled modal for mobile
        showMobileConfirm('Delete Card', 'Are you sure you want to delete this card?', function() {
            performDelete(cardId);
        });
    } else {
        if (confirm('Are you sure you want to delete this card?')) {
            performDelete(cardId);
        }
    }
}

// Function to show notification
// Show notification
function showNotification(message, type = 'success') {
    // Remove existing notifications
    const existing = document.querySelector('.custom-notification');
    if (existing) existing.remove();
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `custom-notification fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} mr-2"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}
// Load existing categories into Select2
function loadCategories() {
    $.ajax({
        url: 'json/category.json',
        method: 'GET',
        dataType: 'json',
        success: function(categories) {
            // Clear existing options except the placeholder
            $('#category').find('option:not(:first)').remove();
            
            // Add categories to select
            $.each(categories, function(index, category) {
                $('#category').append(new Option(category, category));
            });
            
            // Refresh Select2
            $('#category').trigger('change.select2');
        },
        error: function() {
            console.error('Failed to load categories');
        }
    });
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Set today's date
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('date').value = today;
    
    // Handle photo file inputs
    document.getElementById('frontPhoto')?.addEventListener('change', function(e) {
        handleFileSelect(this, 'frontPreview', 'frontPhotoData');
    });
    
    document.getElementById('backPhoto')?.addEventListener('change', function(e) {
        handleFileSelect(this, 'backPreview', 'backPhotoData');
    });
    
    // Close modal when clicking outside
    document.getElementById('imageModal')?.addEventListener('click', function(e) {
        if (e.target.id === 'imageModal') {
            closeModal();
        }
    });
    
    // Initialize Select2 for country dropdown
    if (typeof $.fn.select2 !== 'undefined') {
        // Initialize country dropdown
        $('#country').select2({
            placeholder: "Type to search for a country...",
            allowClear: true,
            width: '100%',
            dropdownParent: $('#country').parent(),
            matcher: function(params, data) {
                if ($.trim(params.term) === '') {
                    return data;
                }
                
                if (typeof data.text === 'undefined') {
                    return null;
                }
                
                if (data.text.toUpperCase().indexOf(params.term.toUpperCase()) > -1) {
                    return data;
                }
                
                return null;
            },
            minimumInputLength: 0,
            language: {
                noResults: function() {
                    return "No countries found";
                },
                searching: function() {
                    return "Searching...";
                }
            }
        });
        
        // Add search icon styling for country
        setTimeout(function() {
            $('#country').siblings('.select2-container').find('.select2-selection--single').prepend(
                '<i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>'
            );
            $('#country').siblings('.select2-container').find('.select2-selection__rendered').css('padding-left', '30px');
        }, 100);
        
        console.log('Select2 initialized for country dropdown');
        
        // Initialize category dropdown with tagging
        $('#category').select2({
            placeholder: "Select or type a new category...",
            allowClear: true,
            width: '100%',
            tags: true, // Enable tagging
            createTag: function(params) {
                const term = $.trim(params.term);
                
                if (term === '') {
                    return null;
                }
                
                return {
                    id: term,
                    text: term,
                    isNew: true
                };
            },
            templateResult: function(data) {
                if (!data.id) return data.text;
                
                const $result = $('<span>').text(data.text);
                
                if (data.isNew) {
                    $result.append(
                        $('<span>').addClass('bg-green-100 text-green-800 text-xs px-2 py-1 rounded ml-2').text('new')
                    );
                }
                
                return $result;
            },
            language: {
                noResults: function() {
                    return "No categories found. Type to create a new one!";
                },
                searching: function() {
                    return "Searching...";
                }
            }
        });
        
        // Add tag icon styling for category
        setTimeout(function() {
            $('#category').siblings('.select2-container').find('.select2-selection--single').prepend(
                '<i class="fas fa-tag absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>'
            );
            $('#category').siblings('.select2-container').find('.select2-selection__rendered').css('padding-left', '30px');
        }, 100);
        
        console.log('Select2 initialized for category dropdown');
        
        // Handle new category creation
        // Replace the existing select2:select handler with this:
$('#category').on('select2:select', function(e) {
    const selectedData = e.params.data;
    
    // Check if this is a new category
    if (selectedData.isNew) {
        const newCategory = selectedData.text.trim();
        
        if (!newCategory) {
            alert('Category name cannot be empty');
            $('#category').val(null).trigger('change');
            return;
        }
        
        console.log('New category detected:', newCategory);
        
        // Show loading state
        const originalText = $('#category').data('original-text') || '';
        $('#category').data('original-text', newCategory);
        
        // Make AJAX call
        $.ajax({
            url: 'save_category.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ category: newCategory }),
            dataType: 'json', // Explicitly expect JSON
            success: function(response) {
                console.log('Response received:', response);
                
                // Check if response indicates success
                if (response && response.success) {
                    // Remove the temporary tag
                    $('#category option[value="' + newCategory + '"]').remove();
                    
                    // Add as permanent option
                    const $select = $('#category');
                    const newOption = new Option(newCategory, newCategory, false, true);
                    $select.append(newOption).trigger('change');
                    
                    console.log('Category added to dropdown:', newCategory);
                    
                    // Optional: Show a subtle notification
                    const $notification = $('<div>')
                        .text('✓ Category "' + newCategory + '" saved')
                        .css({
                            'position': 'fixed',
                            'top': '10px',
                            'right': '10px',
                            'background': '#10b981',
                            'color': 'white',
                            'padding': '8px 12px',
                            'borderRadius': '4px',
                            'zIndex': '9999'
                        });
                    
                    $('body').append($notification);
                    setTimeout(() => $notification.remove(), 3000);
                    
                } else {
                    // Handle error
                    const errorMsg = response && response.message ? response.message : 'Failed to save category';
                    console.error('Save failed:', errorMsg);
                    
                    // Reset the dropdown
                    $('#category').val(null).trigger('change');
                    
                    // Show error alert
                    alert('Error: ' + errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                
                // Try to parse error response
                let errorMsg = 'Network error';
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse && errorResponse.message) {
                        errorMsg = errorResponse.message;
                    }
                } catch (e) {
                    // If can't parse, use raw response
                    errorMsg = xhr.responseText || error;
                }
                
                alert('Save failed: ' + errorMsg);
                $('#category').val(null).trigger('change');
            },
            complete: function() {
                // Clear temporary data
                $('#category').removeData('original-text');
            }
        });
    }
});
        
        // Load initial categories
        loadCategories();
        
        // Add modal functionality if you have a modal button
        $('#addCategoryModalBtn').on('click', function() {
            $('#newCategoryModal').show();
            $('#newCategoryInput').focus();
        });
        
        $('#saveNewCategoryBtn').on('click', function() {
            const newCategory = $('#newCategoryInput').val().trim();
            
            if (!newCategory) {
                alert('Please enter a category name');
                return;
            }
            
            saveNewCategory(newCategory).then(function(response) {
                const data = JSON.parse(response);
                
                if (data.success) {
                    // Add to select2 dropdown
                    const $select = $('#category');
                    const newOption = new Option(newCategory, newCategory, true, true);
                    $select.append(newOption).trigger('change');
                    
                    // Close modal
                    $('#newCategoryModal').hide();
                    $('#newCategoryInput').val('');
                    
                    showNotification('Category added successfully!', 'success');
                } else {
                    alert(data.message);
                }
            }).catch(function(error) {
                alert('Error saving category');
            });
        });
        
        // Close modal handlers
        $('.modal-close, .modal-cancel').on('click', function() {
            $('#newCategoryModal').hide();
            $('#newCategoryInput').val('');
        });
        
        // Click outside to close modal
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('modal')) {
                $('.modal').hide();
            }
        });
        
    } else {
        console.error('Select2 library not loaded!');
        
        // Fallback for category input
        const categoryInput = document.getElementById('category');
        if (categoryInput) {
            // Add "other" option
            const otherOption = document.createElement('option');
            otherOption.value = 'other';
            otherOption.textContent = 'Other (specify)';
            categoryInput.appendChild(otherOption);
            
            // Create text input for "other"
            const otherInput = document.createElement('input');
            otherInput.type = 'text';
            otherInput.id = 'otherCategory';
            otherInput.className = 'hidden mt-2 p-2 border rounded w-full';
            otherInput.placeholder = 'Enter new category...';
            categoryInput.parentNode.appendChild(otherInput);
            
            // Show/hide other input
            categoryInput.addEventListener('change', function() {
                if (this.value === 'other') {
                    otherInput.classList.remove('hidden');
                } else {
                    otherInput.classList.add('hidden');
                }
            });
        }
    }
});

// Global variables for cropping
let cropper = null;
let currentCroppingSide = null;
let originalImageData = null;

// Handle image for cropping
function handleImageForCropping(input, side) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        
        reader.onload = function(event) {
            // Store original image data
            originalImageData = event.target.result;
            currentCroppingSide = side;
            
            // Show crop button
            document.getElementById(side + 'CropBtn').classList.remove('hidden');
            
            // Show preview
            updatePreview(side, originalImageData, false);
            
            // Auto-open crop modal
            setTimeout(() => startCropping(side), 300);
        };
        
        reader.readAsDataURL(file);
    }
}

// Start cropping process
function startCropping(side) {
    // If no image exists, trigger file input
    const dataInput = document.getElementById(side + 'PhotoData');
    if (!dataInput.value && !originalImageData) {
        document.getElementById(side + 'Photo').click();
        return;
    }
    
    currentCroppingSide = side;
    
    // Use existing image data or original
    const imageData = dataInput.value || originalImageData;
    
    if (!imageData) {
        alert('Please select an image first');
        return;
    }
    
    // Store original if not already stored
    if (!originalImageData) {
        originalImageData = imageData;
    }
    
    // Show modal and initialize cropper
    showCropModal(imageData);
}

// Show crop modal and initialize cropper
function showCropModal(imageData) {
    const modal = document.getElementById('cropModal');
    const imageElement = document.getElementById('cropImage');
    
    // Set image source
    imageElement.src = imageData;
    
    // Show modal
    modal.classList.remove('hidden');
    
    // Initialize cropper after image loads
    imageElement.onload = function() {
        if (cropper) {
            cropper.destroy();
        }
        
        cropper = new Cropper(imageElement, {
            aspectRatio: 1.6, // Business card ratio (3.5" x 2" = 1.75:1, rounded to 1.6)
            viewMode: 2, // Restrict crop box to not exceed canvas
            dragMode: 'move',
            autoCropArea: 0.8,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
            ready: function() {
                // Optional: Set initial crop to center
                const containerData = cropper.getContainerData();
                const cropBoxWidth = containerData.width * 0.8;
                const cropBoxHeight = cropBoxWidth / 1.6;
                
                cropper.setCropBoxData({
                    left: (containerData.width - cropBoxWidth) / 2,
                    top: (containerData.height - cropBoxHeight) / 2,
                    width: cropBoxWidth,
                    height: cropBoxHeight
                });
            }
        });
    };
}

// Close crop modal
function closeCropModal() {
    document.getElementById('cropModal').classList.add('hidden');
    
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
    
    currentCroppingSide = null;
}

// Apply crop and update preview
function applyCrop() {
    if (!cropper || !currentCroppingSide) return;
    
    // Get cropped canvas
    const canvas = cropper.getCroppedCanvas({
        width: 800, // Output width
        height: 500, // Output height (800/1.6 = 500)
        imageSmoothingQuality: 'high'
    });
    
    // Convert to base64
    const croppedDataUrl = canvas.toDataURL('image/jpeg', 0.85);
    
    // Update the hidden input with cropped data
    document.getElementById(currentCroppingSide + 'PhotoData').value = croppedDataUrl;
    
    // Update preview
    updatePreview(currentCroppingSide, croppedDataUrl, true);
    
    // Hide crop button since we have a cropped image
    document.getElementById(currentCroppingSide + 'CropBtn').classList.add('hidden');
    
    // Show success message
    showNotification('Image cropped successfully!', 'success');
    
    // Close modal
    closeCropModal();
}

// Update preview with cropped image
function updatePreview(side, imageData, isCropped) {
    const previewDiv = document.getElementById(side + 'Preview');
    const container = document.getElementById(side + 'PreviewContainer');
    
    // Calculate size
    const base64Length = imageData.length - 'data:image/jpeg;base64,'.length;
    const bytes = base64Length * 0.75;
    const kb = Math.round(bytes / 1024);
    
    previewDiv.innerHTML = `
        <img src="${imageData}" class="h-full w-full object-contain rounded" alt="${side} photo preview">
        <button type="button" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center hover:bg-red-600" 
                onclick="removePhoto('${side}', event)">×</button>
        <div class="absolute bottom-1 left-1 bg-black/70 text-white text-xs px-2 py-1 rounded">
            ${kb} KB ${isCropped ? '✓' : ''}
        </div>
    `;
    previewDiv.classList.add('relative');
    
    // Change border color if cropped
    if (isCropped) {
        container.classList.remove('border-gray-300');
        container.classList.add('border-green-400', 'border-2');
    }
}

// Set crop aspect ratio
function setCropRatio(ratio) {
    if (cropper) {
        cropper.setAspectRatio(ratio);
    }
}

// Rotate image
function rotateImage(degrees) {
    if (cropper) {
        cropper.rotate(degrees);
    }
}

// Reset crop
function resetCrop() {
    if (cropper) {
        cropper.reset();
        cropper.setAspectRatio(1.6);
    }
}

// Enhanced capturePhotoFromStream with auto-cropping option
async function capturePhotoFromStream(side, stream, video) {
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    
    // Set canvas dimensions
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    // Draw video frame
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    // Get image data
    const capturedData = canvas.toDataURL('image/jpeg', 0.9);
    
    // Store original data
    originalImageData = capturedData;
    currentCroppingSide = side;
    
    // Stop camera
    stopCamera();
    const modal = document.getElementById('cameraModal');
    if (modal) modal.remove();
    
    // Auto-open crop modal after capture
    setTimeout(() => {
        showCropModal(capturedData);
        showNotification('Photo captured! You can now crop it.', 'info');
    }, 500);
}

// Update removePhoto function to reset cropping state
function removePhoto(side, event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    
    // Reset preview
    const previewDiv = document.getElementById(side + 'Preview');
    const container = document.getElementById(side + 'PreviewContainer');
    const fileInput = document.getElementById(side + 'Photo');
    const dataInput = document.getElementById(side + 'PhotoData');
    const cropBtn = document.getElementById(side + 'CropBtn');
    
    // Reset preview
    previewDiv.innerHTML = `
        <i class="fas fa-camera text-gray-400 text-3xl mb-2"></i>
        <span class="text-sm text-gray-500">No photo</span>
    `;
    previewDiv.classList.remove('relative');
    
    // Reset container border
    container.classList.remove('border-green-400', 'border-2');
    container.classList.add('border-gray-300');
    
    // Hide crop button
    if (cropBtn) cropBtn.classList.add('hidden');
    
    // Clear inputs
    if (fileInput) fileInput.value = '';
    if (dataInput) dataInput.value = '';
    
    // Reset cropping state if this was the current side
    if (currentCroppingSide === side) {
        currentCroppingSide = null;
        originalImageData = null;
    }
}
</script>
</body>
</html>