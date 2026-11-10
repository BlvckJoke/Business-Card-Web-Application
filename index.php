<?php
session_start();

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#707070">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- Splash screen images for iOS -->
    <link rel="apple-touch-startup-image" href="splash-iphone.png" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)">
    <link rel="apple-touch-startup-image" href="splash-iphonex.png" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#D70040',
                        secondary: '#FF3131',
                        accent: '#d17373'
                    }
                }
            }
        }
    </script>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 16px;
            margin: 0;
        }

        /* Your Background Image */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* Replace this with your image path */
            background-image: url('back.png');
            /* If your image is in a different folder, use:
            background-image: url('images/background.jpg');
            background-image: url('../images/background.jpg');
            background-image: url('/assets/background.jpg');
            */
            background-size: cover;
            background-position: 50% 60%;
            background-repeat: no-repeat;
            background-attachment: fixed;
            z-index: -2;
        }

        /* Dark overlay for better readability - adjust opacity as needed */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
           
            z-index: -1;
        }

        /* Splash Screen */
        #splash-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease-out, visibility 0.5s ease-out;
        }

        #splash-screen.fade-out {
            opacity: 0;
            visibility: hidden;
        }

        .splash-logo {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
            animation: pulse 1.5s ease-in-out infinite;
        }

        .splash-logo img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }

        .splash-title {
            color: white;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .splash-loader {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .splash-progress {
            width: 200px;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            margin-top: 20px;
            overflow: hidden;
            position: relative;
        }

        .splash-progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 0%;
            background: white;
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        .splash-version {
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
            margin-top: 20px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        /* Main content container */
        #main-content {
            opacity: 0;
            transition: opacity 0.3s ease;
            width: 100%;
            max-width: 448px;
            margin: 0 auto;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1;
        }

        #main-content.visible {
            opacity: 1;
        }

        /* Login card with glass morphism effect */
        .login-card {
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            margin: 16px;
            animation: slideUp 0.5s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Header with semi-transparent background */
        .login-card .bg-gradient-to-r {
            background: linear-gradient(135deg, rgba(112, 112, 112, 0.9), rgba(47, 47, 47, 0.95));
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Shake animation for errors */
        .shake {
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%,100%{transform:translateX(0)}
            25%{transform:translateX(-5px)}
            75%{transform:translateX(5px)}
        }

        /* Form inputs with glass effect */
        /* Force input text to be dark */
.login-card input {
    color: #1f2937 !important;
    background: white !important;
}

.login-card input::placeholder {
    color: #9ca3af !important;
}

        /* Small screens */
        @media (max-width: 480px) {
            body {
                padding: 12px;
            }
            
            .login-card {
                margin: 8px;
            }
        }

        /* Landscape orientation */
        @media (max-height: 600px) and (orientation: landscape) {
            body {
                align-items: flex-start;
                padding-top: 20px;
                padding-bottom: 20px;
            }
            
            .login-card {
                margin: 10px auto;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .login-card {
                background: rgba(30, 30, 30, 0.95);
            }
            
            .login-card label {
                color: #e0e0e0;
            }
            
            .login-card input {
                background: rgba(50, 50, 50, 0.9);
                color: white;
                border-color: rgba(255, 255, 255, 0.1);
            }
            
            .login-card input::placeholder {
                color: #999;
            }
            
            .login-card p {
                color: #ccc;
            }
        }
    </style>
</head>

<body class="m-0 p-4 flex items-center justify-center min-h-screen w-full">
    <div id="splash-screen">
        <div class="splash-logo">
            <img src="logo.png" alt="Logo">
        </div>
        <div class="splash-title">Secure Login</div>
        <div class="splash-loader"></div>
        <div class="splash-progress">
            <div class="splash-progress-bar" id="progress-bar"></div>
        </div>
        <div class="splash-version">Version 1.0.0</div>
    </div>

    <!-- Main Content -->
    <div id="main-content">
        <div class="login-card">
            <!-- Header -->
            <div class="bg-gradient-to-r from-primary to-secondary text-white p-6 text-center">
                <div class="flex justify-center mb-4">
                    <div class="bg-white/20 p-3 rounded-full w-20 h-20 flex items-center justify-center backdrop-blur-sm">
                        <img src="logo.png" alt="Logo" class="w-14 h-14 rounded-full object-cover">
                    </div>
                </div>
                <h1 class="text-2xl font-bold tracking-wide">Welcome Back</h1>
                <p class="text-white/80 text-sm mt-1">Please sign in to continue</p>
            </div>

            <!-- Login Form -->
            <div class="p-6 sm:p-8">
                <form id="loginForm" class="space-y-5" action="process.php" method="POST">
                    <?php if ($error): ?>
                        <div class="bg-red-100/90 backdrop-blur-sm border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Email -->
                    <div>
                        <label class="block text-gray-700 mb-2 text-sm font-medium">Email Address</label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-3 top-3.5 text-gray-400"></i>
                            <input
                                type="email"
                                id="loginEmail"
                                name="email"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition bg-white/90 backdrop-blur-sm"
                                placeholder="you@example.com"
                                required>
                        </div>
                        <p id="loginEmailError" class="text-red-500 text-xs mt-1 hidden">Please enter a valid email</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-gray-700 mb-2 text-sm font-medium">Password</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3 top-3.5 text-gray-400"></i>
                            <input
    type="password"
    id="loginPassword"
    name="password"
    class="w-full pl-10 pr-10 py-3 border border-gray-400 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition bg-white text-gray-900 backdrop-blur-sm"
    placeholder="••••••••"
    required>
                            <button type="button" id="togglePassword"
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <p id="loginPasswordError" class="text-red-500 text-xs mt-1 hidden">Password must be at least 6 characters</p>
                    </div>

                    <!-- Forgot Password Link -->
                    <div class="text-right">
                        <a href="#" class="text-sm text-primary hover:text-secondary transition">
                            Forgot Password?
                        </a>
                    </div>

                    <button type="submit"
                        class="w-full bg-primary hover:bg-secondary text-white py-3 rounded-lg font-semibold transition transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center backdrop-blur-sm bg-opacity-90">
                        <i class="fas fa-sign-in-alt mr-2"></i> Sign In
                    </button>


                    
                </form>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    <div id="successMessage"
        class="hidden fixed top-5 right-5 bg-green-100/90 backdrop-blur-sm border border-green-400 text-green-700 px-6 py-4 rounded-lg shadow-lg z-50">
        <i class="fas fa-check-circle mr-2"></i>
        Login successful! Redirecting...
    </div>

    <script>
    const splashScreen = document.getElementById('splash-screen');
    const mainContent = document.getElementById('main-content');
    const progressBar = document.getElementById('progress-bar');

    // Function to update progress bar
    function updateProgress(percent) {
        progressBar.style.width = percent + '%';
    }

    // Simulate loading progress
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 100) progress = 100;
        updateProgress(progress);
        
        if (progress === 100) {
            clearInterval(interval);
        }
    }, 200);

    // Wait for everything to load
    window.addEventListener('load', () => {
        // Ensure progress reaches 100%
        updateProgress(100);
        
        // Small delay to show 100% progress
        setTimeout(() => {
            splashScreen.classList.add('fade-out');
            mainContent.classList.add('visible');
            
            // Remove splash from DOM after animation
            setTimeout(() => {
                splashScreen.style.display = 'none';
            }, 500);
        }, 500);
    });

    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('loginPassword');
    
    togglePassword.addEventListener('click', () => {
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;
        togglePassword.innerHTML = type === 'password' 
            ? '<i class="fas fa-eye"></i>' 
            : '<i class="fas fa-eye-slash"></i>';
    });

    // Service Worker registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(error => {
                console.log('ServiceWorker registration failed:', error);
            });
        });
    }

    // Handle page cache (back/forward navigation)
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            splashScreen.style.display = 'none';
            mainContent.classList.add('visible');
        }
    });

    // Basic form validation
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('loginEmail');
    const emailError = document.getElementById('loginEmailError');
    const passwordError = document.getElementById('loginPasswordError');

    loginForm.addEventListener('submit', (e) => {
        let isValid = true;

        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailInput.value)) {
            emailError.classList.remove('hidden');
            emailInput.classList.add('border-red-500');
            isValid = false;
        } else {
            emailError.classList.add('hidden');
            emailInput.classList.remove('border-red-500');
        }

        // Password validation
        if (passwordInput.value.length < 5) {
            passwordError.classList.remove('hidden');
            passwordInput.classList.add('border-red-500');
            isValid = false;
        } else {
            passwordError.classList.add('hidden');
            passwordInput.classList.remove('border-red-500');
        }

        if (!isValid) {
            e.preventDefault();
        }
    });

    // Remove error styling on input
    emailInput.addEventListener('input', () => {
        emailError.classList.add('hidden');
        emailInput.classList.remove('border-red-500');
    });

    passwordInput.addEventListener('input', () => {
        passwordError.classList.add('hidden');
        passwordInput.classList.remove('border-red-500');
    });
    </script>
</body>
</html>