<?php
session_start();
require_once "config/db.php";
if (!isset($_SESSION['user_id']) || !isset($_SESSION['names'])) {
    header("Location: index.php");
    exit;
}

// Check if user is admin
$isAdmin = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT role FROM User WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $isAdmin = ($row['role'] === 'ADMIN');
    }
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

// Load countries
$countries = [];
$countriesJsonFilePath = __DIR__ . '/json/countries.json';

if (file_exists($countriesJsonFilePath)) {
    $countriesJson = file_get_contents($countriesJsonFilePath);
    $countries = json_decode($countriesJson, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($countries)) {
        $countries = ["United States", "United Kingdom", "Canada", "Australia", "Germany"];
        error_log("Error parsing countries.json: " . json_last_error_msg());
    }
    
    sort($countries);
} else {
    $defaultCountries = [
        "United States", "United Kingdom", "Canada", "Australia", 
        "Germany", "France", "Japan", "China", "India"
    ];
    
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
        $categories = ["Bakery & Pastry Shop", "Restaurant", "Fast Food", "Food Truck", 
                      "Grocery Store", "Butcher Shop", "Catering Service", "Distillery", "Farmers Market"];
        error_log("Error parsing category.json: " . json_last_error_msg());
    }
    
    sort($categories);
} else {
    $defaultCategories = [
        "Bakery & Pastry Shop", "Restaurant", "Café & Coffee Shop", "Fast Food", "Food Truck", 
        "Grocery Store", "Supermarket", "Butcher Shop", "Ice Cream Parlor", "Catering Service", 
        "Brewery", "Winery/Vineyard", "Distillery", "Bar/Pub", "Pizzeria", "Food Delivery Service", 
        "Organic Food Store", "Farmers Market", "Juice Bar", "Dessert Shop", "Candy Store", 
        "Chocolate Shop", "Tea House", "Food Court Vendor", "BBQ Restaurant", "Seafood Restaurant", 
        "Steakhouse", "Sushi Restaurant", "Vegetarian/Vegan Restaurant", "Food Manufacturing"
    ];
    
    file_put_contents($categoriesJsonFilePath, json_encode($defaultCategories, JSON_PRETTY_PRINT));
    $categories = $defaultCategories;
    sort($categories);
}

// Get card count
$cardCount = 0;
if (isset($_SESSION['users'])) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM business_cards WHERE users = ?");
    $stmt->bind_param("i", $_SESSION['users']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $cardCount = $row['count'];
    }
    $stmt->close();
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
    <style>
        /* Animations */
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

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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

        /* Camera modal styles */
        .camera-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .camera-container {
            width: 100%;
            max-width: 500px;
            background: #1f2937;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }

        .camera-preview {
            position: relative;
            background: black;
            width: 100%;
            height: 400px;
            overflow: hidden;
        }

        .camera-frame {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 280px;
            height: 175px;
            border: 3px solid #10b981;
            border-radius: 8px;
            box-shadow: 0 0 0 1000px rgba(0,0,0,0.7);
            pointer-events: none;
        }

        .frame-corner {
            position: absolute;
            width: 24px;
            height: 24px;
            border: 3px solid #10b981;
        }

        .corner-tl {
            top: -3px;
            left: -3px;
            border-right: none;
            border-bottom: none;
            border-radius: 8px 0 0 0;
        }

        .corner-tr {
            top: -3px;
            right: -3px;
            border-left: none;
            border-bottom: none;
            border-radius: 0 8px 0 0;
        }

        .corner-bl {
            bottom: -3px;
            left: -3px;
            border-right: none;
            border-top: none;
            border-radius: 0 0 0 8px;
        }

        .corner-br {
            bottom: -3px;
            right: -3px;
            border-left: none;
            border-top: none;
            border-radius: 0 0 8px 0;
        }

        .camera-controls {
            padding: 20px;
            background: #111827;
        }

        .capture-btn {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            border: 4px solid rgba(255,255,255,0.8);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            transition: all 0.2s;
        }

        .capture-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 30px rgba(0,0,0,0.4);
        }

        .capture-btn:active {
            transform: scale(0.95);
        }

        .control-btn {
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
            font-size: 20px;
            backdrop-filter: blur(10px);
            transition: all 0.2s;
        }

        .control-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.05);
        }

        /* Camera select dropdown */
        .camera-select {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 8px;
            padding: 8px 12px;
            width: 100%;
            max-width: 300px;
            margin: 0 auto 15px;
            backdrop-filter: blur(10px);
            cursor: pointer;
        }

        .camera-select option {
            background: #1f2937;
            color: white;
        }

        /* Toggle switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #4b5563;
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: #10b981;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }

        /* Loading spinner */
        .fa-spinner {
            animation: spin 1s linear infinite;
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .notification.success {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .notification.error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .notification.info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

        /* Admin badge */
        .admin-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }

        /* Error modal */
        .error-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 10001;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-modal-content {
            background: white;
            border-radius: 12px;
            padding: 25px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        /* iOS specific fixes */
        @supports (-webkit-touch-callout: none) {
            /* iOS only styles */
            .camera-preview {
                height: 450px;
            }
            
            .capture-btn {
                width: 80px;
                height: 80px;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen p-4 md:p-6">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <header class="bg-white shadow-sm mb-6">
            <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-user-circle text-blue-500 text-2xl"></i>
                    <span class="text-gray-800 font-semibold text-lg">
                        Welcome, <?php echo htmlspecialchars($_SESSION['names']); ?>
                    </span>
                </div>

                <div class="flex items-center space-x-4">
                    <?php if ($isAdmin): ?>
                        <a href="create_users.php" 
                           class="flex items-center space-x-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-user-plus"></i>
                        </a>
                    <?php endif; ?>
                    
                    <button onclick="openLogoutModal()"
                        class="flex items-center space-x-2 text-red-600 hover:text-red-700 font-medium">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Cards Display Section -->
            <div class="lg:w-1/2 space-y-6">
    <!-- Saved Cards Section -->
    <section class="bg-white rounded-xl shadow-md p-4 md:p-6" aria-labelledby="cards-heading">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-200">
            <h2 id="cards-heading" class="text-lg md:text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-list-alt text-purple-500 mr-2 text-lg" aria-hidden="true"></i>
                <span>Saved Cards</span>
            </h2>
            <div class="text-xs md:text-sm text-gray-500" id="cardsCount" aria-live="polite">
                <?php echo $cardCount . ' card' . ($cardCount != 1 ? 's' : ''); ?>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="view_all_cards.php" class="w-full inline-flex justify-center items-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <span>View All Cards</span>
                <i class="fas fa-arrow-right ml-2" aria-hidden="true"></i>
            </a>
        </div>
    </section>

    <!-- Tariff Rate Section -->
    <section class="bg-white rounded-xl shadow-md p-4 md:p-6" aria-labelledby="tariff-heading">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-200">
            <h2 id="tariff-heading" class="text-lg md:text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-table text-purple-500 mr-2 text-lg" aria-hidden="true"></i>
                <span>Tariff Rate</span>
            </h2>
            <div class="text-xs md:text-sm text-gray-500" id="tariffCount" aria-live="polite">
                <?php echo $tariffCount . ' rate' . ($tariffCount != 1 ? 's' : ''); ?>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="view_tariff_table.php" class="w-full inline-flex justify-center items-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <span>View Table</span>
                <i class="fas fa-arrow-right ml-2" aria-hidden="true"></i>
            </a>
        </div>
    </section>
</div>

            <!-- Add New Card Form -->
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
                        <div class="bg-gradient-to-r from-gray-50 to-blue-50 p-4 rounded-xl border border-gray-200 mb-4 shadow-sm">
                            <h3 class="text-lg font-medium text-gray-800 mb-3">
                                <i class="fas fa-camera mr-2 text-blue-500"></i>
                                Card Photos
                            </h3>
                            
                            <div class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                <div class="flex items-start">
                                    <i class="fas fa-lightbulb text-yellow-500 mt-1 mr-2"></i>
                                    <div class="text-sm text-gray-700">
                                        <span class="font-semibold">Tip:</span> Works with all cameras - laptop, PC, tablet, and phone.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                                <!-- Front Photo -->
                                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-id-card mr-1 text-blue-500"></i>
                                        Front Side
                                    </label>
                                    <div class="relative">
                                        <!-- Preview Container -->
                                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center h-48 cursor-pointer hover:border-blue-400 transition-all duration-300 mb-3 shadow-inner bg-gradient-to-b from-gray-50 to-white"
                                             id="frontPreviewContainer">
                                            <div id="frontPreview" class="h-full flex flex-col items-center justify-center bg-white">
                                                <i class="fas fa-camera text-gray-300 text-4xl mb-3"></i>
                                                <span class="text-sm text-gray-500 font-medium">Click to use camera</span>
                                                <span class="text-xs text-gray-400 mt-1">or upload photo</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Hidden inputs -->
                                        <input type="file" id="frontPhoto" name="front_photo" accept="image/*" 
                                               class="hidden" onchange="handleImageUpload(this, 'front')">
                                        <input type="hidden" id="frontPhotoData" name="front_photo_data">
                                        
                                        <!-- Action buttons -->
                                        <div class="flex flex-wrap gap-2 justify-center">
                                            <button type="button" onclick="startCamera('front')" 
                                                    class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white text-sm rounded-lg hover:from-green-600 hover:to-green-700 flex items-center shadow-md hover:shadow-lg transition-all">
                                                <i class="fas fa-camera mr-2"></i>
                                                <span>Use Camera</span>
                                            </button>
                                            <button type="button" onclick="document.getElementById('frontPhoto').click()"
                                                    class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 flex items-center border border-gray-200">
                                                <i class="fas fa-upload mr-2"></i>
                                                <span>Upload</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Back Photo -->
                                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-id-card-alt mr-1 text-blue-500"></i>
                                        Back Side
                                    </label>
                                    <div class="relative">
                                        <!-- Preview Container -->
                                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center h-48 cursor-pointer hover:border-blue-400 transition-all duration-300 mb-3 shadow-inner bg-gradient-to-b from-gray-50 to-white"
                                             id="backPreviewContainer">
                                            <div id="backPreview" class="h-full flex flex-col items-center justify-center bg-white">
                                                <i class="fas fa-camera text-gray-300 text-4xl mb-3"></i>
                                                <span class="text-sm text-gray-500 font-medium">Click to use camera</span>
                                                <span class="text-xs text-gray-400 mt-1">or upload photo</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Hidden inputs -->
                                        <input type="file" id="backPhoto" name="back_photo" accept="image/*" 
                                               class="hidden" onchange="handleImageUpload(this, 'back')">
                                        <input type="hidden" id="backPhotoData" name="back_photo_data">
                                        
                                        <!-- Action buttons -->
                                        <div class="flex flex-wrap gap-2 justify-center">
                                            <button type="button" onclick="startCamera('back')" 
                                                    class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white text-sm rounded-lg hover:from-green-600 hover:to-green-700 flex items-center shadow-md hover:shadow-lg transition-all">
                                                <i class="fas fa-camera mr-2"></i>
                                                <span>Use Camera</span>
                                            </button>
                                            <button type="button" onclick="document.getElementById('backPhoto').click()"
                                                    class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 flex items-center border border-gray-200">
                                                <i class="fas fa-upload mr-2"></i>
                                                <span>Upload</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>

                        <!-- Date -->
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
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50 hidden transition-all duration-300">
        <div class="relative max-w-4xl max-h-[90vh] mx-4">
            <!-- Close button -->
            <button onclick="closeModal()" class="absolute -top-12 right-0 text-white hover:text-gray-300 text-2xl z-50">
                <i class="fas fa-times-circle"></i>
            </button>
            
            <!-- Image container -->
            <div class="bg-white rounded-xl overflow-hidden shadow-2xl">
                <img id="modalImage" src="" alt="Preview" class="max-w-full max-h-[70vh] object-contain">
                
                <!-- Caption -->
                <div id="modalCaption" class="bg-white px-6 py-4 text-center text-gray-700 border-t">
                    Loading...
                </div>
                
                <!-- Action buttons -->
                <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3">
                    <button onclick="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
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

    <!-- Camera Error Modal (will be created dynamically) -->

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
    
    <script>
    // ==================== GLOBAL VARIABLES ====================
    let cameraStream = null;
    let isCameraActive = false;
    let currentCameraSide = null;
    let availableCameras = [];
    
    // ==================== UTILITY FUNCTIONS ====================
    
    function showNotification(message, type = 'success') {
        // Remove existing notifications
        const existing = document.querySelector('.notification');
        if (existing) existing.remove();
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
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
    
    function stopCamera() {
        if (cameraStream) {
            const tracks = cameraStream.getTracks();
            tracks.forEach(track => {
                track.stop();
                console.log('Camera track stopped:', track.label);
            });
            cameraStream = null;
        }
        isCameraActive = false;
        console.log('Camera stopped');
    }
    
    // ==================== CAMERA FUNCTIONS FOR ALL DEVICES ====================
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || 
                 (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    // Main function to start camera (works on all devices)
    async function startCamera(side) {
        console.log('Starting camera for:', side);
        currentCameraSide = side;
        
        try {
            showNotification('Requesting camera access...', 'info');
            
            // Stop any existing camera
            stopCamera();
            
            // Check if mediaDevices API is available
            if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
                throw new Error('Camera API not supported in this browser');
            }
            
            // First, get list of available cameras
            await enumerateCameras();
            
            // Then try to get camera stream
            const stream = await getBestAvailableCamera();
            
            // Create camera modal with camera selection dropdown if multiple cameras
            createCameraModal(side, stream);
            
        } catch (error) {
            console.error('Camera access failed:', error);
            
            if (isIOS) {
                showIOSPermissionInstructions();
            } else {
                handleCameraError(error);
            }
        }
    }
    
    // Enumerate all available cameras
    async function enumerateCameras() {
        try {
            // Request permission first by asking for any camera
            const tempStream = await navigator.mediaDevices.getUserMedia({ video: true });
            tempStream.getTracks().forEach(track => track.stop());
            
            // Now enumerate devices
            const devices = await navigator.mediaDevices.enumerateDevices();
            availableCameras = devices.filter(device => device.kind === 'videoinput');
            
            console.log('Available cameras:', availableCameras.map(c => ({
                label: c.label || 'Unnamed camera',
                deviceId: c.deviceId
            })));
            
        } catch (error) {
            console.warn('Could not enumerate cameras:', error);
            availableCameras = [];
        }
    }
    
    // Get the best available camera (prefer back camera on mobile, any camera on desktop)
    async function getBestAvailableCamera() {
        // If we have enumerated cameras, use that info
        if (availableCameras.length > 0) {
            // For mobile, try to find back camera first
            if (isMobile) {
                // Look for back camera
                const backCamera = availableCameras.find(camera => {
                    const label = camera.label.toLowerCase();
                    return label.includes('back') || label.includes('rear') || label.includes('environment');
                });
                
                if (backCamera) {
                    console.log('Found back camera:', backCamera.label);
                    return await navigator.mediaDevices.getUserMedia({
                        video: {
                            deviceId: { exact: backCamera.deviceId },
                            width: { ideal: 1920 },
                            height: { ideal: 1080 }
                        },
                        audio: false
                    });
                }
            }
            
            // If no specific camera preference or no back camera found, use the first camera
            // But let user choose if multiple cameras
            if (availableCameras.length === 1) {
                console.log('Using only available camera:', availableCameras[0].label);
                return await navigator.mediaDevices.getUserMedia({
                    video: {
                        deviceId: { exact: availableCameras[0].deviceId },
                        width: { ideal: 1920 },
                        height: { ideal: 1080 }
                    },
                    audio: false
                });
            }
        }
        
        // Fallback: let browser choose best camera
        console.log('Letting browser choose camera');
        return await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 1920 },
                height: { ideal: 1080 },
                facingMode: isMobile ? { ideal: 'environment' } : undefined
            },
            audio: false
        });
    }
    
    // Switch to a specific camera by device ID
    async function switchCamera(deviceId) {
        if (!deviceId) return;
        
        try {
            stopCamera();
            
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    deviceId: { exact: deviceId },
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                },
                audio: false
            });
            
            // Update video element
            const video = document.getElementById('cameraVideo');
            if (video) {
                video.srcObject = stream;
            }
            
            cameraStream = stream;
            
            // Update camera info
            updateCameraInfo(stream);
            
            showNotification('Camera switched', 'success');
            
        } catch (error) {
            console.error('Failed to switch camera:', error);
            showNotification('Failed to switch camera', 'error');
        }
    }
    
    // Update camera info display
    function updateCameraInfo(stream) {
        const cameraInfo = document.getElementById('cameraInfo');
        if (!cameraInfo) return;
        
        try {
            const track = stream.getVideoTracks()[0];
            if (track) {
                const settings = track.getSettings();
                const label = track.label || 'Camera';
                cameraInfo.innerHTML = `
                    📷 ${label} | ${settings.width || '?'}×${settings.height || '?'}
                `;
            }
        } catch (infoError) {
            cameraInfo.innerHTML = 'Camera - Ready';
        }
    }
    
    function handleCameraError(error) {
        console.error('Camera error details:', error);
        
        let errorMessage = 'Camera Error: ';
        let errorDetails = '';
        
        if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
            errorMessage = 'Camera Permission Denied';
            errorDetails = 'Please allow camera access in your browser settings.';
            showCameraErrorModal(errorMessage, errorDetails, true);
        } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
            errorMessage = 'No Camera Found';
            errorDetails = 'No camera detected on this device.';
            showCameraErrorModal(errorMessage, errorDetails, false);
        } else if (error.name === 'NotSupportedError') {
            errorMessage = 'Browser Not Supported';
            errorDetails = 'Camera is not supported in this browser. Please use Chrome, Safari, or Firefox.';
            showCameraErrorModal(errorMessage, errorDetails, false);
        } else if (error.name === 'NotReadableError' || error.name === 'AbortError') {
            errorMessage = 'Camera Is In Use';
            errorDetails = 'The camera is being used by another application. Please close other apps using the camera.';
            showCameraErrorModal(errorMessage, errorDetails, true);
        } else {
            errorMessage = 'Could Not Access Camera';
            errorDetails = 'Please use the upload option instead.';
            showCameraErrorModal(errorMessage, errorDetails, false);
        }
        
        // Fallback to file upload
        setTimeout(() => {
            if (currentCameraSide) {
                document.getElementById(currentCameraSide + 'Photo').click();
            }
        }, 1500);
    }
    
    function showCameraErrorModal(title, message, allowRetry = true) {
        // Remove existing error modal
        const existingModal = document.getElementById('cameraErrorModal');
        if (existingModal) existingModal.remove();
        
        // Create error modal
        const modalHTML = `
        <div id="cameraErrorModal" class="error-modal">
            <div class="error-modal-content">
                <div class="mb-4">
                    <div class="flex justify-center mb-3">
                        <i class="fas fa-exclamation-triangle text-red-500 text-5xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">${title}</h3>
                    <p class="text-gray-600">${message}</p>
                </div>
                
                <div class="flex flex-col gap-3">
                    ${allowRetry ? `
                    <button onclick="retryCamera()" 
                            class="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors">
                        Try Again
                    </button>
                    ` : ''}
                    <button onclick="useUploadOption()" 
                            class="px-4 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-medium transition-colors">
                        Upload Photo Instead
                    </button>
                    <button onclick="closeErrorModal()" 
                            class="px-4 py-3 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>`;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
    
    function showIOSPermissionInstructions() {
        const modalHTML = `
        <div id="cameraErrorModal" class="error-modal" style="overflow-y: auto; padding: 20px;">
            <div class="error-modal-content" style="max-width: 450px; max-height: 90vh; overflow-y: auto;">
                <div class="mb-4">
                    <div class="flex justify-center mb-3">
                        <i class="fas fa-camera text-blue-500 text-5xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2 text-center">📱 Camera Access Required</h3>
                    
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Please allow camera access</strong><br>
                                    Tap "Allow" when prompted, or check Settings > Safari > Camera
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-start bg-gray-50 p-3 rounded-lg">
                            <div class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center flex-shrink-0 mt-0.5">
                                1
                            </div>
                            <div class="ml-3">
                                <p class="font-medium text-gray-800">Tap the "AA" icon in address bar</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start bg-gray-50 p-3 rounded-lg">
                            <div class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center flex-shrink-0 mt-0.5">
                                2
                            </div>
                            <div class="ml-3">
                                <p class="font-medium text-gray-800">Select "Website Settings"</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start bg-gray-50 p-3 rounded-lg">
                            <div class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center flex-shrink-0 mt-0.5">
                                3
                            </div>
                            <div class="ml-3">
                                <p class="font-medium text-gray-800">Set "Camera" to <strong>"Allow"</strong></p>
                            </div>
                        </div>
                        
                        <div class="flex items-start bg-gray-50 p-3 rounded-lg">
                            <div class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center flex-shrink-0 mt-0.5">
                                4
                            </div>
                            <div class="ml-3">
                                <p class="font-medium text-gray-800">Refresh the page</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-col gap-3 mt-6">
                    <button onclick="window.location.reload()" 
                            class="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors">
                        Refresh Page After Allowing
                    </button>
                    <button onclick="useUploadOption()" 
                            class="px-4 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-medium transition-colors">
                        Upload Photo Instead
                    </button>
                    <button onclick="closeErrorModal()" 
                            class="px-4 py-3 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>`;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
    
    function retryCamera() {
        const errorModal = document.getElementById('cameraErrorModal');
        if (errorModal) errorModal.remove();
        
        if (currentCameraSide) {
            setTimeout(() => {
                startCamera(currentCameraSide);
            }, 500);
        }
    }
    
    function useUploadOption() {
        const errorModal = document.getElementById('cameraErrorModal');
        if (errorModal) errorModal.remove();
        
        if (currentCameraSide) {
            document.getElementById(currentCameraSide + 'Photo').click();
        }
    }
    
    function closeErrorModal() {
        const errorModal = document.getElementById('cameraErrorModal');
        if (errorModal) errorModal.remove();
    }
    
    function createCameraModal(side, stream) {
        // Remove existing modal
        const existingModal = document.getElementById('cameraModal');
        if (existingModal) existingModal.remove();
        
        // Remove error modal if exists
        const errorModal = document.getElementById('cameraErrorModal');
        if (errorModal) errorModal.remove();
        
        // Create camera selection dropdown if multiple cameras
        let cameraSelectHTML = '';
        if (availableCameras.length > 1) {
            cameraSelectHTML = `
                <div class="mb-4 text-center">
                    <select id="cameraSelect" class="camera-select" onchange="handleCameraSelect(this.value)">
                        ${availableCameras.map((camera, index) => `
                            <option value="${camera.deviceId}" ${index === 0 ? 'selected' : ''}>
                                ${camera.label || `Camera ${index + 1}`}
                            </option>
                        `).join('')}
                    </select>
                </div>
            `;
        }
        
        // Create modal HTML
        const modalHTML = `
        <div id="cameraModal" class="camera-modal-overlay">
            <div class="camera-container">
                <!-- Header -->
                <div style="padding: 15px 20px; background: #111827; color: white;">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 style="font-size: 18px; font-weight: bold; margin: 0;">
                                <i class="fas fa-camera mr-2"></i>
                                ${side === 'front' ? 'Front' : 'Back'} Side
                            </h3>
                            <p style="margin: 5px 0 0 0; font-size: 14px; opacity: 0.8;">
                                Align business card within the green frame
                            </p>
                        </div>
                        <button id="closeCameraBtn" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Camera Selection (if multiple cameras) -->
                ${cameraSelectHTML}
                
                <!-- Camera Preview -->
                <div class="camera-preview">
                    <video id="cameraVideo" autoplay playsinline 
                           style="width: 100%; height: 100%; object-fit: cover;"></video>
                    
                    <!-- Alignment Frame -->
                    <div class="camera-frame">
                        <div class="frame-corner corner-tl"></div>
                        <div class="frame-corner corner-tr"></div>
                        <div class="frame-corner corner-bl"></div>
                        <div class="frame-corner corner-br"></div>
                    </div>
                    
                    <!-- Camera Info -->
                    <div id="cameraInfo" style="position: absolute; bottom: 10px; left: 10px; background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                        Camera - Loading...
                    </div>
                </div>
                
                <!-- Controls -->
                <div class="camera-controls">
                    <!-- Auto-crop Toggle -->
                    <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                        <label style="display: flex; align-items: center; color: white; cursor: pointer;">
                            <div class="toggle-switch" style="margin-right: 10px;">
                                <input type="checkbox" id="autoCropToggle" checked>
                                <span class="toggle-slider"></span>
                            </div>
                            <span style="font-weight: 500;">Auto-crop after capture</span>
                        </label>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div style="display: flex; justify-content: center; align-items: center; gap: 40px;">
                        <!-- Flash button (if supported) -->
                        <button id="toggleFlashBtn" class="control-btn" title="Toggle Flash">
                            <i class="fas fa-bolt"></i>
                        </button>
                        
                        <!-- Capture button -->
                        <button id="captureBtn" class="capture-btn" title="Capture Photo">
                            <i class="fas fa-camera"></i>
                        </button>
                        
                        <!-- Cancel button -->
                        <button id="cancelBtn" class="control-btn" title="Cancel">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>`;
        
        // Add modal to document
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Get elements
        const modal = document.getElementById('cameraModal');
        const video = document.getElementById('cameraVideo');
        const closeBtn = document.getElementById('closeCameraBtn');
        const captureBtn = document.getElementById('captureBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const flashBtn = document.getElementById('toggleFlashBtn');
        const cameraInfo = document.getElementById('cameraInfo');
        
        // Set video stream
        video.srcObject = stream;
        cameraStream = stream;
        isCameraActive = true;
        
        // Handle video errors
        video.onerror = function(e) {
            console.error('Video error:', e);
            cameraInfo.innerHTML = 'Camera Error';
            showNotification('Camera error. Please try again.', 'error');
        };
        
        // Update camera info when video is playing
        video.onplaying = function() {
            updateCameraInfo(stream);
            showNotification('Camera ready!', 'success');
        };
        
        // Handle video ended (camera disconnected)
        video.onended = function() {
            console.log('Camera stream ended');
            showNotification('Camera disconnected', 'error');
            modal.remove();
        };
        
        // Capture photo
        captureBtn.onclick = async function() {
            await capturePhotoWithAutoCrop(video, side);
        };
        
        // Close/Cancel button
        closeBtn.onclick = cancelBtn.onclick = function() {
            stopCamera();
            modal.remove();
        };
        
        // Flash/torch
        let flashOn = false;
        flashBtn.onclick = async function() {
            const track = stream.getVideoTracks()[0];
            
            if (track && track.getCapabilities && track.getCapabilities().torch) {
                try {
                    flashOn = !flashOn;
                    await track.applyConstraints({
                        advanced: [{ torch: flashOn }]
                    });
                    
                    flashBtn.innerHTML = flashOn ? '<i class="fas fa-bolt" style="color: #fbbf24;"></i>' : '<i class="fas fa-bolt"></i>';
                    showNotification(flashOn ? 'Flash ON' : 'Flash OFF', 'info');
                } catch (error) {
                    console.error('Flash not supported:', error);
                    showNotification('Flash not available', 'error');
                }
            } else {
                showNotification('Flash not supported on this device', 'info');
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
            if (e.key === 'Escape' && isCameraActive) {
                stopCamera();
                modal.remove();
            }
        });
        
        // Focus on capture button
        setTimeout(() => captureBtn.focus(), 100);
    }
    
    // Handle camera selection from dropdown
    async function handleCameraSelect(deviceId) {
        await switchCamera(deviceId);
    }
    
    async function capturePhotoWithAutoCrop(video, side) {
        if (!video || video.readyState !== 4) {
            showNotification('Camera not ready', 'error');
            return;
        }
        
        const captureBtn = document.getElementById('captureBtn');
        const originalHTML = captureBtn.innerHTML;
        captureBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        captureBtn.disabled = true;
        
        try {
            // Create canvas
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Set canvas dimensions
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Draw video frame
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Check auto-crop setting
            const autoCropToggle = document.getElementById('autoCropToggle');
            const shouldAutoCrop = autoCropToggle ? autoCropToggle.checked : true;
            
            let finalImageData;
            
            if (shouldAutoCrop) {
                // Auto-crop to business card ratio
                const cropWidth = canvas.width * 0.7;
                const cropHeight = cropWidth / 1.6;
                const cropX = (canvas.width - cropWidth) / 2;
                const cropY = (canvas.height - cropHeight) / 2;
                
                // Create cropped canvas
                const croppedCanvas = document.createElement('canvas');
                croppedCanvas.width = cropWidth;
                croppedCanvas.height = cropHeight;
                const cropCtx = croppedCanvas.getContext('2d');
                
                cropCtx.drawImage(
                    canvas,
                    cropX, cropY, cropWidth, cropHeight,
                    0, 0, cropWidth, cropHeight
                );
                
                finalImageData = croppedCanvas.toDataURL('image/jpeg', 0.85);
            } else {
                finalImageData = canvas.toDataURL('image/jpeg', 0.9);
            }
            
            // Compress image for mobile
            const compressedData = await compressImage(finalImageData);
            
            // Store image data
            document.getElementById(side + 'PhotoData').value = compressedData;
            
            // Update thumbnail preview
            const previewDiv = document.getElementById(side + 'Preview');
            const container = document.getElementById(side + 'PreviewContainer');
            
            // Calculate file size
            const base64Length = compressedData.length - (compressedData.indexOf('base64,') + 7);
            const bytes = base64Length * 0.75;
            const kb = Math.round(bytes / 1024);
            
            // Set thumbnail preview
            previewDiv.innerHTML = `
                <div class="relative h-full w-full">
                    <img src="${compressedData}" class="h-full w-full object-cover rounded-lg cursor-pointer" alt="${side} photo preview"
                         onclick="viewCapturedPhoto('${compressedData}', '${side}')">
                    <button type="button" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center hover:bg-red-600 z-10 shadow-md" 
                            onclick="removePhoto('${side}', event)">×</button>
                    <div class="absolute bottom-1 left-1 bg-black/70 text-white text-xs px-2 py-1 rounded flex items-center z-10">
                        <i class="fas fa-camera mr-1"></i> Captured
                    </div>
                    ${shouldAutoCrop ? '<div class="absolute top-1 left-1 bg-green-500 text-white text-xs px-2 py-1 rounded z-10">Auto-cropped</div>' : ''}
                </div>
            `;
            
            // Update container style
            container.classList.remove('border-gray-300', 'border-dashed');
            container.classList.add('border-green-500', 'border-2', 'border-solid', 'bg-white');
            container.style.backgroundImage = 'none';
            
            // Show success notification
            showNotification(`✅ ${side === 'front' ? 'Front' : 'Back'} photo captured!`, 'success');
            
            // Open image in modal for preview
            setTimeout(() => {
                viewCapturedPhoto(compressedData, side);
            }, 300);
            
            // Close camera modal
            stopCamera();
            const modal = document.getElementById('cameraModal');
            if (modal) modal.remove();
            
        } catch (error) {
            console.error('Capture error:', error);
            showNotification('Error capturing photo', 'error');
        } finally {
            captureBtn.innerHTML = originalHTML;
            captureBtn.disabled = false;
        }
    }
    
    // View captured photo in modal
    function viewCapturedPhoto(imageData, side) {
        const modal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        const modalCaption = document.getElementById('modalCaption');
        
        modalImage.src = imageData;
        modalCaption.innerHTML = `
            <div class="flex items-center justify-center gap-2">
                <i class="fas fa-id-card ${side === 'front' ? 'text-blue-500' : 'text-purple-500'}"></i>
                Business Card - ${side === 'front' ? 'Front Side' : 'Back Side'}
                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full ml-2">
                    <i class="fas fa-check-circle mr-1"></i> Just captured
                </span>
            </div>
        `;
        modal.classList.remove('hidden');
    }
    
    // ==================== IMAGE COMPRESSION ====================
    
    async function compressImage(imageData, maxWidth = 1200, quality = 0.8) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                // Calculate new dimensions
                let width = img.width;
                let height = img.height;
                
                if (width > maxWidth) {
                    const ratio = maxWidth / width;
                    width = maxWidth;
                    height = height * ratio;
                }
                
                // Set canvas dimensions
                canvas.width = width;
                canvas.height = height;
                
                // Draw and compress
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(img, 0, 0, width, height);
                
                // Convert to JPEG
                const compressedData = canvas.toDataURL('image/jpeg', quality);
                resolve(compressedData);
            };
            
            img.onerror = reject;
            img.src = imageData;
        });
    }
    
    // ==================== PHOTO PREVIEW FUNCTIONS ====================
    
    function handleImageUpload(input, side) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            
            // Validate file size
            if (file.size > 10 * 1024 * 1024) {
                showNotification('File is too large. Maximum size is 10MB.', 'error');
                input.value = '';
                return;
            }
            
            // Validate file type
            if (!file.type.match('image.*')) {
                showNotification('Please select an image file.', 'error');
                input.value = '';
                return;
            }
            
            const reader = new FileReader();
            
            reader.onload = function(event) {
                const imageData = event.target.result;
                
                // Store image data
                document.getElementById(side + 'PhotoData').value = imageData;
                
                // Update thumbnail preview
                const previewDiv = document.getElementById(side + 'Preview');
                const container = document.getElementById(side + 'PreviewContainer');
                
                previewDiv.innerHTML = `
                    <div class="relative h-full w-full">
                        <img src="${imageData}" class="h-full w-full object-cover rounded-lg cursor-pointer" alt="${side} photo preview"
                             onclick="viewCapturedPhoto('${imageData}', '${side}')">
                        <button type="button" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center hover:bg-red-600 z-10 shadow-md" 
                                onclick="removePhoto('${side}', event)">×</button>
                        <div class="absolute bottom-1 left-1 bg-black/70 text-white text-xs px-2 py-1 rounded flex items-center z-10">
                            <i class="fas fa-upload mr-1"></i> Uploaded
                        </div>
                    </div>
                `;
                
                container.classList.remove('border-gray-300', 'border-dashed');
                container.classList.add('border-green-500', 'border-2', 'border-solid', 'bg-white');
                container.style.backgroundImage = 'none';
                
                showNotification(`${side === 'front' ? 'Front' : 'Back'} photo uploaded!`, 'success');
                
                // Open in modal for preview
                setTimeout(() => {
                    viewCapturedPhoto(imageData, side);
                }, 300);
            };
            
            reader.onerror = function() {
                showNotification('Error reading file', 'error');
                input.value = '';
            };
            
            reader.readAsDataURL(file);
        }
    }
    
    function removePhoto(side, event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        
        const previewDiv = document.getElementById(side + 'Preview');
        const container = document.getElementById(side + 'PreviewContainer');
        const fileInput = document.getElementById(side + 'Photo');
        const dataInput = document.getElementById(side + 'PhotoData');
        
        // Reset preview with proper HTML structure
        previewDiv.innerHTML = `
            <div class="h-full flex flex-col items-center justify-center">
                <i class="fas fa-camera text-gray-300 text-4xl mb-3"></i>
                <span class="text-sm text-gray-500 font-medium">Click to use camera</span>
                <span class="text-xs text-gray-400 mt-1">or upload photo</span>
            </div>
        `;
        
        // Reset container
        container.classList.remove('border-green-500', 'border-2', 'border-solid', 'bg-white');
        container.classList.add('border-gray-300', 'border-dashed');
        container.style.backgroundImage = '';
        
        // Clear inputs
        if (fileInput) fileInput.value = '';
        if (dataInput) dataInput.value = '';
    }
    
    // ==================== FORM VALIDATION ====================
    
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
        
        // Validate base64 data
        if (frontPhotoData && !frontPhotoData.startsWith('data:image')) {
            alert('Invalid front photo data. Please capture the photo again.');
            return false;
        }
        
        if (backPhotoData && !backPhotoData.startsWith('data:image')) {
            alert('Invalid back photo data. Please capture the photo again.');
            return false;
        }
        
        console.log("Form validation passed");
        return true;
    }
    
    // ==================== LOGOUT FUNCTIONS ====================
    
    function openLogoutModal() {
        document.getElementById('logoutModal').classList.remove('hidden');
    }
    
    function closeLogoutModal() {
        document.getElementById('logoutModal').classList.add('hidden');
    }
    
    function confirmLogout() {
        window.location.href = 'logout.php';
    }
    
    // ==================== MODAL FUNCTIONS ====================
    
    function closeModal() {
        document.getElementById('imageModal').classList.add('hidden');
    }
    
    // Test camera support
    function testCameraSupport() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showNotification('Camera API not supported in this browser', 'error');
        } else {
            console.log('Camera API is supported');
        }
    }
    
    // ==================== INITIALIZATION ====================
    
    document.addEventListener('DOMContentLoaded', function() {
        testCameraSupport();
        
        // Set today's date
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('date').value = today;
        
        // Handle photo file inputs
        document.getElementById('frontPhoto')?.addEventListener('change', function(e) {
            handleImageUpload(this, 'front');
        });
        
        document.getElementById('backPhoto')?.addEventListener('change', function(e) {
            handleImageUpload(this, 'back');
        });
        
        // Close modal when clicking outside
        document.getElementById('imageModal')?.addEventListener('click', function(e) {
            if (e.target.id === 'imageModal') {
                closeModal();
            }
        });
        
     
     // Initialize Select2
if (typeof $.fn.select2 !== 'undefined') {
    // Initialize country dropdown with auto-focus
    $('#country').select2({
        placeholder: "Type to search for a country...",
        allowClear: true,
        width: '100%',
        minimumInputLength: 0,
        dropdownAutoWidth: true
    }).on('select2:open', function() {
        // Focus the search box when dropdown opens
        setTimeout(function() {
            document.querySelector('.select2-search__field').focus();
        }, 100);
    });

    // Initialize category dropdown with auto-focus
    $('#category').select2({
        placeholder: "Select or type a category...",
        allowClear: true,
        width: '100%',
        tags: true,
        minimumInputLength: 0,
        dropdownAutoWidth: true,
        createTag: function(params) {
            const term = $.trim(params.term);
            if (term === '') return null;
            
            return {
                id: term,
                text: term,
                isNew: true
            };
        }
    }).on('select2:open', function() {
        // Focus the search box when dropdown opens
        setTimeout(function() {
            document.querySelector('.select2-search__field').focus();
        }, 100);
    });
}
        
        // Form submit handler
        const form = document.getElementById('cardForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    return false;
                }
                
                // Show loading state
                const submitBtn = document.getElementById('submitBtn');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
                submitBtn.disabled = true;
            });
        }
        
        // Clean up camera on page unload
        window.addEventListener('beforeunload', function() {
            stopCamera();
        });
        
        // Handle page visibility changes
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && isCameraActive) {
                console.log('Page hidden, stopping camera');
                stopCamera();
                const cameraModal = document.getElementById('cameraModal');
                if (cameraModal) cameraModal.remove();
            }
        });
        
        // Add click event to preview containers to trigger camera
        document.getElementById('frontPreviewContainer').addEventListener('click', function(e) {
            if (e.target.closest('button') && e.target.closest('button').classList.contains('absolute')) {
                // Don't trigger camera if clicking remove button
                return;
            }
            startCamera('front');
        });
        
        document.getElementById('backPreviewContainer').addEventListener('click', function(e) {
            if (e.target.closest('button') && e.target.closest('button').classList.contains('absolute')) {
                // Don't trigger camera if clicking remove button
                return;
            }
            startCamera('back');
        });
    });
    </script>
</body>
</html>