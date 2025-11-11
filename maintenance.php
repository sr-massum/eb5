<?php 

/**
 * Exchange Bridge - Maintenance Page
 * 
 * @package     ExchangeBridge
 * @author      Saieed Rahman
 * @copyright   SidMan Solutions 2025
 * @version     1.0.0
 */


// Start session
session_start();

// Define access constant
define('ALLOW_ACCESS', true);

require_once __DIR__ . '/includes/app.php';
try {
    require_once 'config/config.php';
    require_once 'config/license.php';
    require_once 'includes/db.php';
    require_once 'includes/functions.php';
    require_once 'includes/auth.php';
    require_once 'includes/security.php';
} catch (Exception $e) {
    // Fallback if includes fail
    if (!defined('SITE_URL')) {
        define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);
    }
}

// Check if maintenance mode is actually enabled (with fallback)
$maintenanceMode = function_exists('getSetting') ? getSetting('maintenance_mode', 'no') : 'yes';
if ($maintenanceMode !== 'yes') {
    // If maintenance mode is disabled, redirect to home
    $homeUrl = defined('SITE_URL') ? SITE_URL : '/';
    header("Location: " . $homeUrl);
    exit;
}

// Allow admins to bypass maintenance mode
$showAdminNotice = false;
if (class_exists('Auth') && Auth::isLoggedIn() && Auth::isAdmin()) {
    $showAdminNotice = true;
}

// Set 503 header for maintenance (not for admins)
if (!$showAdminNotice) {
    header("HTTP/1.0 503 Service Temporarily Unavailable");
    header("Status: 503 Service Temporarily Unavailable");
    header("Retry-After: 3600");
}

// Get settings with fallbacks
$siteName = function_exists('getSetting') ? getSetting('site_name', 'Exchange Bridge') : 'Exchange Bridge';
$contactEmail = function_exists('getSetting') ? getSetting('contact_email', 'support@exchangebridge.com') : 'support@exchangebridge.com';
$contactWhatsapp = function_exists('getSetting') ? getSetting('contact_whatsapp', '8801869838872') : '8801869838872';
$maintenanceMessage = function_exists('getSetting') ? getSetting('maintenance_message', 'We\'re performing scheduled maintenance to improve our services. We\'ll be back online shortly. Thank you for your patience!') : 'We\'re performing scheduled maintenance to improve our services. We\'ll be back online shortly. Thank you for your patience!';

// Admin URL with fallback
$adminUrl = defined('ADMIN_URL') ? ADMIN_URL : SITE_URL . '/admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($siteName); ?> - Under Maintenance</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .maintenance-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 90%;
            margin: 1rem;
        }

        .admin-notice {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            font-weight: 600;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .admin-notice a {
            color: white;
            text-decoration: underline;
            font-weight: bold;
        }

        .maintenance-icon {
            margin-bottom: 2rem;
            position: relative;
        }

        .gear-icon {
            font-size: 4rem;
            color: #667eea;
            animation: rotate 3s linear infinite;
        }

        .tools-icon {
            font-size: 2rem;
            color: #764ba2;
            margin: 0 0.5rem;
            animation: bounce 2s ease-in-out infinite alternate;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes bounce {
            0% { transform: translateY(0); }
            100% { transform: translateY(-10px); }
        }

        .maintenance-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .maintenance-subtitle {
            font-size: 1.2rem;
            color: #4a5568;
            margin-bottom: 2rem;
            line-height: 1.6;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .contact-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }

        .contact-text {
            font-size: 1rem;
            color: #718096;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .contact-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .contact-btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .whatsapp-btn {
            background: #25D366;
            color: white;
        }

        .whatsapp-btn:hover {
            background: #128C7E;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.3);
        }

        .email-btn {
            background: #4285F4;
            color: white;
        }

        .email-btn:hover {
            background: #3367D6;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(66, 133, 244, 0.3);
        }

        .admin-btn {
            background: #ff6b6b;
            color: white;
        }

        .admin-btn:hover {
            background: #ee5a24;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
        }

        .contact-btn i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin: 2rem 0 1rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 2px;
            animation: progress 3s ease-in-out infinite;
        }

        @keyframes progress {
            0% { width: 10%; }
            50% { width: 90%; }
            100% { width: 10%; }
        }

        .maintenance-time {
            font-size: 0.9rem;
            color: #a0aec0;
            margin-top: 1rem;
        }

        .refresh-info {
            background: #e8f4fd;
            border: 1px solid #b3d7f0;
            color: #0366d6;
            padding: 0.75rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        @media (max-width: 640px) {
            .maintenance-container {
                padding: 2rem 1.5rem;
            }
            
            .maintenance-title {
                font-size: 2rem;
            }
            
            .contact-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .contact-btn {
                width: 100%;
                max-width: 250px;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .maintenance-container {
                background: rgba(26, 32, 44, 0.95);
                color: #e2e8f0;
            }
            
            .maintenance-title {
                color: #e2e8f0;
            }
            
            .maintenance-subtitle {
                color: #a0aec0;
            }
            
            .contact-text {
                color: #cbd5e0;
            }
            
            .contact-section {
                border-top-color: #4a5568;
            }
            
            .refresh-info {
                background: rgba(232, 244, 253, 0.1);
                border-color: rgba(179, 215, 240, 0.3);
                color: #90cdf4;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <?php if ($showAdminNotice): ?>
        <div class="admin-notice">
            <i class="fas fa-user-shield mr-2"></i>
            <strong>Admin Notice:</strong> Site is in maintenance mode, but you can access it as an admin.
            <br><a href="<?php echo $adminUrl; ?>/settings/">Go to Settings</a> to disable maintenance mode.
        </div>
        <?php endif; ?>
        
        <div class="maintenance-icon">
            <i class="fas fa-cog gear-icon"></i>
            <div style="margin-top: 1rem;">
                <i class="fas fa-wrench tools-icon"></i>
                <i class="fas fa-hammer tools-icon" style="animation-delay: 0.5s;"></i>
                <i class="fas fa-screwdriver tools-icon" style="animation-delay: 1s;"></i>
            </div>
        </div>
        
        <h1 class="maintenance-title">
            <?php echo htmlspecialchars($siteName); ?><br>Under Maintenance
        </h1>
        
        <p class="maintenance-subtitle">
            <?php echo nl2br(htmlspecialchars($maintenanceMessage)); ?>
        </p>
        
        <?php if (!$showAdminNotice): ?>
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
        
        <p class="maintenance-time">
            <i class="fas fa-clock"></i> <span id="countdown">Estimated completion: 2-3 hours</span>
        </p>
        
        <div class="refresh-info">
            <i class="fas fa-info-circle mr-1"></i>
            This page will automatically refresh every 5 minutes to check if maintenance is complete.
        </div>
        <?php endif; ?>
        
        <div class="contact-section">
            <p class="contact-text">
                <i class="fas fa-exclamation-triangle" style="color: #f6ad55; margin-right: 0.5rem;"></i>
                For emergency assistance, contact our support team
            </p>
            
            <div class="contact-buttons">
                <?php if (!empty($contactWhatsapp)): ?>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $contactWhatsapp); ?>?text=<?php echo urlencode('Emergency: ' . $siteName . ' is under maintenance and I need urgent assistance.'); ?>" 
                   class="contact-btn whatsapp-btn" target="_blank">
                    <i class="fab fa-whatsapp"></i>
                    WhatsApp Support
                </a>
                <?php endif; ?>
                
                <?php if (!empty($contactEmail)): ?>
                <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>?subject=<?php echo urlencode('Emergency: ' . $siteName . ' Maintenance'); ?>&body=<?php echo urlencode('Hello, ' . $siteName . ' is under maintenance and I need urgent assistance. Please contact me as soon as possible.'); ?>" 
                   class="contact-btn email-btn">
                    <i class="fas fa-envelope"></i>
                    Email Support
                </a>
                <?php endif; ?>
                
                <?php if ($showAdminNotice): ?>
                <a href="<?php echo defined('SITE_URL') ? SITE_URL : '/'; ?>" class="contact-btn admin-btn">
                    <i class="fas fa-home"></i>
                    Continue to Site
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        <?php if (!$showAdminNotice): ?>
        // Auto refresh page every 5 minutes to check if maintenance is over
        setTimeout(function() {
            window.location.reload();
        }, 300000); // 5 minutes = 300000 milliseconds
        
        // Optional: Add a countdown timer
        let timeLeft = 3 * 60 * 60; // 3 hours in seconds
        
        function updateCountdown() {
            const hours = Math.floor(timeLeft / 3600);
            const minutes = Math.floor((timeLeft % 3600) / 60);
            const seconds = timeLeft % 60;
            
            if (timeLeft > 0) {
                const timeString = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                const countdownElement = document.getElementById('countdown');
                if (countdownElement) {
                    countdownElement.innerHTML = 
                        '<i class="fas fa-clock"></i> Estimated time remaining: ' + timeString;
                }
                timeLeft--;
            } else {
                const countdownElement = document.getElementById('countdown');
                if (countdownElement) {
                    countdownElement.innerHTML = 
                        '<i class="fas fa-check-circle" style="color: #48bb78;"></i> Maintenance should be complete. Refreshing...';
                }
                setTimeout(() => window.location.reload(), 2000);
            }
        }
        
        // Update countdown every second
        setInterval(updateCountdown, 1000);
        
        console.log('Maintenance page loaded - auto refresh enabled');
        <?php else: ?>
        console.log('Maintenance page loaded - admin bypass mode');
        <?php endif; ?>
    </script>
</body>
</html>