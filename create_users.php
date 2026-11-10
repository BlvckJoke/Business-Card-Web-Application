<?php
session_start();
require_once "config/db.php";

// Check if user is logged in
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
    $stmt->close();
}

// Redirect non-admin users
if (!$isAdmin) {
    header("Location: info_card.php");
    exit;
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $names = trim($_POST['names'] ?? '');
    $email = trim($_POST['email'] ?? '');
   
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = trim($_POST['role'] ?? 'DIRECTOR');

    // Validate inputs
    $errors = [];

    if (empty($names)) {
        $errors[] = "Full name is required";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM User WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email already exists";
        }
        $stmt->close();
    }



    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (!in_array($role, ['DIRECTOR', 'ADMIN'])) {
        $role = 'DIRECTOR';
    }

    if (empty($errors)) {
        $status = "ACTIVE";
        
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO User (user_name, email, passwords, role, status,created_at) VALUES (?, ?, ?, ?,?, NOW())");
        $stmt->bind_param("sssss", $names, $email, $password, $role, $status);
        
        if ($stmt->execute()) {
            $success_message = "User created successfully!";
            
            // Clear form fields
            $names = $email = $role = '';
        } else {
            $error_message = "Error creating user: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Get existing users for display
$users = [];
$stmt = $conn->prepare("SELECT id, user_name, email, role, created_at FROM User ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Users - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom styles */
        .admin-badge {
            background: linear-gradient(135deg, #4c63b6 0%, #5d3a8c 100%);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .role-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .role-admin {
            background: linear-gradient(135deg, #667eea 0%, #4a6bd8 100%);
            color: white;
        }
        
        .role-user {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 4px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background: #ef4444; width: 25%; }
        .strength-fair { background: #f59e0b; width: 50%; }
        .strength-good { background: #10b981; width: 75%; }
        .strength-strong { background: #10b981; width: 100%; }
        
        .notification {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .table-container {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .table-container table {
            min-width: 800px;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .form-container {
                padding: 1rem;
            }
            
            .table-container {
                font-size: 14px;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="info_card.php" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            
            <div class="flex items-center space-x-4">

                <button onclick="openLogoutModal()"
                    class="flex items-center space-x-2 text-red-600 hover:text-red-700 font-medium">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-4 md:p-6">
        <!-- Page Title -->
        <div class="mb-6">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-user-plus text-blue-500 mr-3"></i>
                User Management
            </h1>
            <p class="text-gray-600 mt-2">Create and manage user accounts</p>
        </div>

        <!-- Notifications -->
        <?php if ($success_message): ?>
            <div class="notification mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $success_message; ?>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-green-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="notification mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo $error_message; ?>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Create User Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md p-6 form-container">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6 pb-3 border-b border-gray-200">
                        <i class="fas fa-user-plus text-green-500 mr-2"></i>
                        Create New User
                    </h2>
                    
                    <form method="POST" action="" id="createUserForm" onsubmit="return validateUserForm()">
                        <!-- Full Name -->
                        <div class="mb-4">
                            <label for="names" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-user mr-1 text-gray-500"></i>
                                Full Name *
                            </label>
                            <input type="text" id="names" name="names" 
                                   value="<?php echo htmlspecialchars($_POST['names'] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                   placeholder="Enter full name" required>
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-envelope mr-1 text-gray-500"></i>
                                Email Address *
                            </label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                   placeholder="user@example.com" required>
                        </div>

                    

                        <!-- Password -->
                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-lock mr-1 text-gray-500"></i>
                                Password *
                            </label>
                            <div class="relative">
                                <input type="password" id="password" name="password" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                       placeholder="Minimum 8 characters" required
                                       onkeyup="checkPasswordStrength()">
                                <button type="button" onclick="togglePassword('password')" 
                                        class="absolute right-3 top-3 text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordStrength" class="password-strength"></div>
                            <div id="passwordFeedback" class="text-xs text-gray-500 mt-1"></div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-lock mr-1 text-gray-500"></i>
                                Confirm Password *
                            </label>
                            <div class="relative">
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                       placeholder="Re-enter password" required
                                       onkeyup="checkPasswordMatch()">
                                <button type="button" onclick="togglePassword('confirm_password')" 
                                        class="absolute right-3 top-3 text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordMatch" class="text-xs mt-1"></div>
                        </div>

                        <!-- User Role -->
                        <div class="mb-6">
                            <label for="role" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-user-tag mr-1 text-gray-500"></i>
                                User Role *
                            </label>
                            <select id="role" name="role" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                <option value="DIRECTOR" <?php echo ($_POST['role'] ?? 'DIRECTOR') === 'DIRECTOR' ? 'selected' : ''; ?>>Director</option>
                                <option value="ADMIN" <?php echo ($_POST['role'] ?? '') === 'ADMIN' ? 'selected' : ''; ?>>Administrator</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">
                                Administrators have full access to all features including user management
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" id="submitBtn"
                                class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold py-3 px-4 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                            <i class="fas fa-user-plus mr-2"></i>
                            Create User Account
                        </button>
                    </form>
                </div>
            </div>

            <!-- Users List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center mb-6 pb-3 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-users text-blue-500 mr-2"></i>
                            Existing Users
                        </h2>
                        <div class="text-sm text-gray-500">
                            Total: <?php echo count($users); ?> user(s)
                        </div>
                    </div>

                    <?php if (empty($users)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-users text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500">No users found</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($users as $user): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-4 px-4">
                                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($user['user_name']); ?></div>
                                            </td>
                                            <td class="py-4 px-4">
                                                <div class="text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                                            </td>
                                            <td class="py-4 px-4">
                                                <span class="role-badge <?php echo $user['role'] === 'ADMIN' ? 'role-admin' : 'role-user'; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-4 text-sm text-gray-500">
                                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                            </td>
                                            <td class="py-4 px-4">
                                                <div class="flex space-x-2">
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <button onclick="openDeleteModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['names']); ?>')"
                                                                class="text-red-600 hover:text-red-800 text-sm px-3 py-1 rounded border border-red-200 hover:bg-red-50">
                                                            <i class="fas fa-trash-alt mr-1"></i> Delete
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 text-sm px-3 py-1">
                                                            <i class="fas fa-info-circle mr-1"></i> Current User
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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

    <!-- Delete User Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                Delete User
            </h2>
            <p class="text-gray-600 mb-4">
                Are you sure you want to delete user: <span id="deleteUserName" class="font-semibold"></span>?
            </p>
            <p class="text-sm text-red-600 bg-red-50 p-3 rounded-lg mb-6">
                <i class="fas fa-warning mr-1"></i>
                Warning: This action cannot be undone. All data associated with this user will be permanently deleted.
            </p>
            <div class="flex justify-end gap-3">
                <button onclick="closeDeleteModal()"
                    class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300">
                    Cancel
                </button>
                <button onclick="confirmDelete()"
                    class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">
                    Delete User
                </button>
            </div>
            <input type="hidden" id="deleteUserId">
        </div>
    </div>

    <script>
        // Password strength checker
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('passwordStrength');
            const feedback = document.getElementById('passwordFeedback');
            
            let strength = 0;
            let feedbackText = '';
            
            // Length check
            if (password.length >= 8) strength++;
            
            // Contains lowercase
            if (/[a-z]/.test(password)) strength++;
            
            // Contains uppercase
            if (/[A-Z]/.test(password)) strength++;
            
            // Contains numbers
            if (/[0-9]/.test(password)) strength++;
            
            // Contains special characters
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Update strength bar
            strengthBar.className = 'password-strength ';
            
            if (password.length === 0) {
                strengthBar.className = 'password-strength';
                feedbackText = '';
            } else if (strength <= 2) {
                strengthBar.className += 'strength-weak';
                feedbackText = 'Weak password';
            } else if (strength === 3) {
                strengthBar.className += 'strength-fair';
                feedbackText = 'Fair password';
            } else if (strength === 4) {
                strengthBar.className += 'strength-good';
                feedbackText = 'Good password';
            } else {
                strengthBar.className += 'strength-strong';
                feedbackText = 'Strong password!';
            }
            
            feedback.textContent = feedbackText;
        }
        
        // Check password match
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                matchDiv.className = 'text-xs mt-1';
            } else if (password === confirmPassword) {
                matchDiv.innerHTML = '<i class="fas fa-check text-green-500 mr-1"></i> Passwords match';
                matchDiv.className = 'text-xs mt-1 text-green-600';
            } else {
                matchDiv.innerHTML = '<i class="fas fa-times text-red-500 mr-1"></i> Passwords do not match';
                matchDiv.className = 'text-xs mt-1 text-red-600';
            }
        }
        
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
        // Form validation
        function validateUserForm() {
            const names = document.getElementById('names').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!names) {
                alert('Please enter full name');
                document.getElementById('names').focus();
                return false;
            }
            
            if (!email) {
                alert('Please enter email address');
                document.getElementById('email').focus();
                return false;
            }
            
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address');
                document.getElementById('email').focus();
                return false;
            }
            
            if (password.length < 8) {
                alert('Password must be at least 8 characters long');
                document.getElementById('password').focus();
                return false;
            }
            
            if (password !== confirmPassword) {
                alert('Passwords do not match');
                document.getElementById('confirm_password').focus();
                return false;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating User...';
            submitBtn.disabled = true;
            
            return true;
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
        
        // Delete user functions
        let userToDelete = null;
        
        function openDeleteModal(userId, userName) {
            userToDelete = userId;
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        
        function closeDeleteModal() {
            userToDelete = null;
            document.getElementById('deleteModal').classList.add('hidden');
        }
        
        function confirmDelete() {
            if (!userToDelete) return;
            
            // Show loading state
            const button = document.querySelector('#deleteModal button[onclick="confirmDelete()"]');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Deleting...';
            button.disabled = true;
            
            // Submit delete request
            const formData = new FormData();
            formData.append('user_id', userToDelete);
            formData.append('action', 'delete_user');
            
            fetch('delete_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to update user list
                    window.location.reload();
                } else {
                    alert('Error deleting user: ' + data.message);
                    closeDeleteModal();
                }
            })
            .catch(error => {
                alert('Error deleting user: ' + error);
                closeDeleteModal();
            });
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Check password strength on load if there's a value
            if (document.getElementById('password').value) {
                checkPasswordStrength();
                checkPasswordMatch();
            }
            
            // Close modals on background click
            document.getElementById('logoutModal').addEventListener('click', function(e) {
                if (e.target === this) closeLogoutModal();
            });
            
            document.getElementById('deleteModal').addEventListener('click', function(e) {
                if (e.target === this) closeDeleteModal();
            });
            
            // Handle escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeLogoutModal();
                    closeDeleteModal();
                }
            });
        });
    </script>
</body>
</html>