<?php
/**
 * Exchange Bridge - Main Index Entry
 * 
 * @package     ExchangeBridge
 * @author      Saieed Rahman
 * @copyright   SidMan Solutions 2025
 * @version     1.0.0
 */

// Start session
session_start();

// Define access constant FIRST
define('ALLOW_ACCESS', true);

// Define constants first
if (!defined('EB_SCRIPT_RUNNING')) {
    define('EB_SCRIPT_RUNNING', true);
}
require_once __DIR__ . '/includes/app.php';
function basicLicenseCheck() {
    $licenseFile = __DIR__ . '/config/license.php';
    $verificationFile = __DIR__ . '/config/verification.php';
    $installLock = __DIR__ . '/config/install.lock';
    
    // Check if installation is complete
    if (!file_exists($installLock)) {
        return false;
    }
    
    // Check license config exists
    if (!file_exists($licenseFile)) {
        return false;
    }
    
    // Include license config
    include_once $licenseFile;
    
    // Check if license key is defined and has correct format
    if (!defined('LICENSE_KEY')) {
        return false;
    }
    
    $licenseKey = LICENSE_KEY;
    
    // Validate your specific license key format: EB-S65MG-V84H5-QQNDF-DDAHC
    if (!preg_match('/^EB-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$/', $licenseKey)) {
        return false;
    }
    
    // Check verification file if exists
    if (file_exists($verificationFile)) {
        $verification = include $verificationFile;
        if (is_array($verification)) {
            // Check if license is active
            if (isset($verification['status']) && $verification['status'] !== 'active') {
                return false;
            }
            
            // Check domain authorization
            $currentDomain = strtolower(preg_replace('/^www\./i', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
            if (isset($verification['domain']) && $verification['domain'] !== '*' && $verification['domain'] !== $currentDomain) {
                return false;
            }
        }
    }
    
    return true;
}

// Perform basic license check
if (!basicLicenseCheck()) {
    // Simple license error without complex protection system
    http_response_code(403);
    include __DIR__ . '/templates/license_error.php';
    exit;
}

// Include configuration files
require_once 'config/config.php';
require_once 'config/verification.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';

// Initialize security class
$security = Security::getInstance();

// Check for banned IPs
$security->checkBanStatus();

// Rate limiting for homepage visits (100 visits per hour per IP)
$clientIp = $security->getClientIp();
if (!$security->checkRateLimit("homepage_visit_{$clientIp}", 100, 3600)) {
    http_response_code(429);
    exit('Too many requests. Please try again later.');
}

// Check maintenance mode AFTER all functions are loaded
include_once 'includes/maintenance_check.php';

// Set timezone for consistent time display
date_default_timezone_set(getSetting('site_timezone', 'Asia/Dhaka'));

// Include header
include 'templates/header.php';
?>

<!-- Main Content -->
<main class="flex-grow container mx-auto p-4 md:p-6">
    <?php include 'templates/exchange-form.php'; ?>
    
    <!-- Features section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700 card-hover">
            <div class="flex flex-col items-center text-center">
                <div class="bg-blue-100 dark:bg-blue-900 text-primary dark:text-blue-300 p-3 rounded-full mb-3">
                    <i class="fas fa-bolt text-xl"></i>
                </div>
                <h3 class="font-bold mb-2">Fast Processing</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Quick and efficient currency exchange in minutes</p>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700 card-hover">
            <div class="flex flex-col items-center text-center">
                <div class="bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300 p-3 rounded-full mb-3">
                    <i class="fas fa-shield-alt text-xl"></i>
                </div>
                <h3 class="font-bold mb-2">Secure Transactions</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Encrypted and protected exchange process</p>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700 card-hover">
            <div class="flex flex-col items-center text-center">
                <div class="bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-300 p-3 rounded-full mb-3">
                    <i class="fas fa-headset text-xl"></i>
                </div>
                <h3 class="font-bold mb-2">24/7 Support</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Always available to assist with your exchange needs</p>
            </div>
        </div>
    </div>
    
    <!-- Two Column Layout - FIXED: Proper 3:1 ratio for desktop, natural order for mobile -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Left Column (3/4 width) -->
        <div class="lg:col-span-3 space-y-6">
            <!-- Transaction Tracker -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 section-bg animated-border">
                <div class="track-header rounded-t-lg px-4 py-3">
                    <h2 class="text-xl font-bold text-white mb-0 flex items-center">
                        <i class="fas fa-search mr-2"></i> Track Transaction
                    </h2>
                </div>
                
                <div class="section-content p-6">
                    <form class="mb-4" action="track.php" method="get">
                        <!-- CSRF Protection for track form -->
                        <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">
                        <div class="relative">
                            <input type="text" name="ref" placeholder="Enter Transaction ID" maxlength="20" pattern="[A-Za-z0-9\-]+" 
                                   class="block w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md py-3 px-4 text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                   title="Transaction ID can only contain letters, numbers and hyphens">
                            <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-primary hover:bg-blue-700 text-white p-2 rounded-md transition-colors">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Latest Exchanges -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-card overflow-hidden section-bg animated-border">
                <div class="table-header rounded-t-lg px-4 py-3 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-white">
                        <i class="fas fa-history mr-2"></i> Latest Exchanges
                    </h2>
                    <div class="flex items-center space-x-2">
                        <a href="track.php" class="text-white text-sm hover:underline flex items-center">
                            <i class="fas fa-search mr-1"></i> Track Exchange
                        </a>
                    </div>
                </div>
                
                <div class="overflow-x-auto section-content">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">S/N</th>
                                <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Exchange Direction</th>
                                <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Username</th>
                                <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php
                            $recentExchanges = getRecentExchanges(5);
                            $totalExchanges = count($recentExchanges);
                            
                            foreach ($recentExchanges as $index => $exchange):
                                // Calculate backwards serial number (newest = 1, oldest = highest number)
                                $serialNumber = $totalExchanges - $index;
                            ?>
                            <tr class="animate-row hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150">
                                <td class="py-2 px-4 whitespace-nowrap font-semibold"><?php echo $serialNumber; ?></td>
                                <td class="py-2 px-4">
                                    <div class="flex items-center">
                                        <div class="payment-icon <?php echo $exchange['from_bg_class'] ?: 'bg-blue-100 text-blue-500'; ?> w-7 h-7 rounded-full flex items-center justify-center">
                                            <?php if ($exchange['from_logo']): ?>
                                                <img src="<?php echo ASSETS_URL; ?>/uploads/currencies/<?php echo htmlspecialchars($exchange['from_logo']); ?>" alt="<?php echo htmlspecialchars($exchange['from_currency_name']); ?>" class="w-6 h-6 object-contain rounded-full">
                                            <?php else: ?>
                                                <i class="<?php echo $exchange['from_icon_class'] ?: 'fas fa-money-bill-wave'; ?> text-xs"></i>
                                            <?php endif; ?>
                                        </div>
                                        <span class="ml-1 text-sm"><?php echo htmlspecialchars($exchange['from_currency_name']); ?></span>
                                        <i class="fas fa-arrow-right mx-2 text-gray-400"></i>
                                        <div class="payment-icon <?php echo $exchange['to_bg_class'] ?: 'bg-gray-100 text-gray-500'; ?> w-7 h-7 rounded-full flex items-center justify-center">
                                            <?php if ($exchange['to_logo']): ?>
                                                <img src="<?php echo ASSETS_URL; ?>/uploads/currencies/<?php echo htmlspecialchars($exchange['to_logo']); ?>" alt="<?php echo htmlspecialchars($exchange['to_currency_name']); ?>" class="w-6 h-6 object-contain rounded-full">
                                            <?php else: ?>
                                                <i class="<?php echo $exchange['to_icon_class'] ?: 'fas fa-money-bill-wave'; ?> text-xs"></i>
                                            <?php endif; ?>
                                        </div>
                                        <span class="ml-1 text-sm"><?php echo htmlspecialchars($exchange['to_currency_name']); ?></span>
                                    </div>
                                </td>
                                <td class="py-2 px-4 whitespace-nowrap"><?php echo htmlspecialchars($security->sanitizeInput($exchange['customer_name'], 'string')); ?></td>
                                <td class="py-2 px-4 whitespace-nowrap">
                                    <span class="status-<?php echo $exchange['status']; ?> px-2 py-0.5 rounded text-xs"><?php echo ucfirst($exchange['status']); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($recentExchanges)): ?>
                            <tr>
                                <td colspan="4" class="py-4 px-4 text-center text-gray-500 dark:text-gray-400">No exchanges found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tutorial & Ads Section - Single Content Carousel -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-card overflow-hidden section-bg animated-border">
                <div class="tutorial-header rounded-t-lg px-4 py-3">
                    <h2 class="text-xl font-semibold text-white flex items-center justify-between">
                        <span><i class="fas fa-play-circle mr-2"></i> Tutorial & Information</span>
                        <div class="tutorial-counter text-sm opacity-75" id="tutorialCounter" style="display: none;">
                            <span id="currentSlide">1</span> / <span id="totalSlides">1</span>
                        </div>
                    </h2>
                </div>
                
                <div class="section-content relative">
                    <?php
                    try {
                        $db = Database::getInstance();
                        
                        // Try multiple query variations to find what works
                        $activeTutorials = [];
                        
                        // Try different query approaches
                        $queries = [
                            "SELECT * FROM tutorials_ads WHERE status = 'active' AND display_on_homepage = 1 ORDER BY priority ASC, created_at DESC",
                            "SELECT * FROM tutorials_ads WHERE status = 'active' AND display_on_homepage > 0 ORDER BY priority ASC, created_at DESC",
                            "SELECT * FROM tutorials_ads WHERE status = 'active' AND display_on_homepage = '1' ORDER BY priority ASC, created_at DESC",
                            "SELECT * FROM tutorials_ads WHERE status = 'active' ORDER BY priority ASC, created_at DESC"
                        ];
                        
                        foreach ($queries as $query) {
                            try {
                                $activeTutorials = $db->getRows($query);
                                if (!empty($activeTutorials)) {
                                    break; // Found working query
                                }
                            } catch (Exception $e) {
                                continue; // Try next query
                            }
                        }
                        
                        if (!empty($activeTutorials)):
                    ?>
                    <!-- Tutorial Carousel Container -->
                    <div class="tutorial-carousel-container relative">
                        <div class="tutorial-carousel overflow-hidden" id="tutorialCarousel">
                            <div class="tutorial-slides flex transition-transform duration-300 ease-in-out" id="tutorialSlides">
                                <?php foreach ($activeTutorials as $index => $tutorial): ?>
                                <div class="tutorial-slide w-full flex-shrink-0" data-slide="<?php echo $index; ?>">
                                    <div class="tutorial-content p-6">
                                        <!-- Media Container -->
                                        <div class="tutorial-media-container mb-4">
                                            <?php if ($tutorial['type'] === 'video_upload' && $tutorial['file_path']): ?>
                                                <!-- Uploaded Video -->
                                                <?php
                                                $videoPath = 'uploads/tutorials/' . htmlspecialchars($tutorial['file_path']);
                                                if (file_exists($videoPath)):
                                                ?>
                                                <div class="video-wrapper">
                                                    <video class="tutorial-video w-full rounded-lg shadow-lg" controls poster="<?php echo $tutorial['thumbnail'] ? 'uploads/tutorials/' . htmlspecialchars($tutorial['thumbnail']) : ''; ?>">
                                                        <source src="<?php echo $videoPath; ?>" type="video/mp4">
                                                        Your browser does not support the video tag.
                                                    </video>
                                                </div>
                                                <?php else: ?>
                                                <div class="error-media">
                                                    <div class="bg-red-100 dark:bg-red-900 rounded-lg p-8 text-center">
                                                        <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                                                        <p class="text-red-700 dark:text-red-300">Video file not found</p>
                                                        <p class="text-red-600 dark:text-red-400 text-sm mt-2"><?php echo $videoPath; ?></p>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            
                                            <?php elseif ($tutorial['type'] === 'image_upload' && $tutorial['file_path']): ?>
                                                <!-- Uploaded Image -->
                                                <?php
                                                $imagePath = 'uploads/tutorials/' . htmlspecialchars($tutorial['file_path']);
                                                if (file_exists($imagePath)):
                                                ?>
                                                <div class="image-wrapper">
                                                    <img src="<?php echo $imagePath; ?>" 
                                                         alt="<?php echo htmlspecialchars($tutorial['title']); ?>" 
                                                         class="tutorial-image w-full rounded-lg shadow-lg object-contain">
                                                </div>
                                                <?php else: ?>
                                                <div class="error-media">
                                                    <div class="bg-red-100 dark:bg-red-900 rounded-lg p-8 text-center">
                                                        <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                                                        <p class="text-red-700 dark:text-red-300">Image file not found</p>
                                                        <p class="text-red-600 dark:text-red-400 text-sm mt-2"><?php echo $imagePath; ?></p>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            
                                            <?php elseif ($tutorial['type'] === 'youtube_embed' && $tutorial['embed_code']): ?>
                                                <!-- YouTube Embed -->
                                                <?php
                                                $youtubeUrl = $tutorial['embed_code'];
                                                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $youtubeUrl, $matches);
                                                if (isset($matches[1])):
                                                ?>
                                                <div class="youtube-wrapper">
                                                    <div class="aspect-w-16 aspect-h-9">
                                                        <iframe class="tutorial-iframe w-full rounded-lg shadow-lg" 
                                                                src="https://www.youtube.com/embed/<?php echo htmlspecialchars($matches[1]); ?>?rel=0&modestbranding=1" 
                                                                frameborder="0" 
                                                                allowfullscreen>
                                                        </iframe>
                                                    </div>
                                                </div>
                                                <?php else: ?>
                                                <div class="error-media">
                                                    <div class="bg-yellow-100 dark:bg-yellow-900 rounded-lg p-8 text-center">
                                                        <i class="fas fa-exclamation-triangle text-yellow-500 text-4xl mb-4"></i>
                                                        <p class="text-yellow-700 dark:text-yellow-300">Invalid YouTube URL</p>
                                                        <p class="text-yellow-600 dark:text-yellow-400 text-sm mt-2"><?php echo htmlspecialchars(substr($youtubeUrl, 0, 50)); ?>...</p>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            
                                            <?php elseif ($tutorial['type'] === 'facebook_embed' && $tutorial['embed_code']): ?>
                                                <!-- Facebook Embed -->
                                                <div class="facebook-wrapper">
                                                    <div class="aspect-w-16 aspect-h-9">
                                                        <iframe class="tutorial-iframe w-full rounded-lg shadow-lg" 
                                                                src="https://www.facebook.com/plugins/video.php?href=<?php echo urlencode($tutorial['embed_code']); ?>&show_text=false&width=800" 
                                                                frameborder="0" 
                                                                allowfullscreen>
                                                        </iframe>
                                                    </div>
                                                </div>
                                            
                                            <?php elseif ($tutorial['type'] === 'google_drive_embed' && $tutorial['embed_code']): ?>
                                                <!-- Google Drive Embed -->
                                                <?php
                                                preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $tutorial['embed_code'], $matches);
                                                if (isset($matches[1])):
                                                ?>
                                                <div class="drive-wrapper">
                                                    <div class="aspect-w-16 aspect-h-9">
                                                        <iframe class="tutorial-iframe w-full rounded-lg shadow-lg" 
                                                                src="https://drive.google.com/file/d/<?php echo htmlspecialchars($matches[1]); ?>/preview" 
                                                                frameborder="0">
                                                        </iframe>
                                                    </div>
                                                </div>
                                                <?php else: ?>
                                                <div class="error-media">
                                                    <div class="bg-yellow-100 dark:bg-yellow-900 rounded-lg p-8 text-center">
                                                        <i class="fas fa-exclamation-triangle text-yellow-500 text-4xl mb-4"></i>
                                                        <p class="text-yellow-700 dark:text-yellow-300">Invalid Google Drive URL</p>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            
                                            <?php elseif ($tutorial['type'] === 'adsense' && $tutorial['adsense_code']): ?>
                                                <!-- Google AdSense -->
                                                <div class="adsense-wrapper">
                                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center min-h-[300px] flex items-center justify-center">
                                                        <?php echo $tutorial['adsense_code']; ?>
                                                    </div>
                                                </div>
                                            
                                            <?php else: ?>
                                                <!-- Default placeholder -->
                                                <div class="placeholder-media">
                                                    <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-12 text-center">
                                                        <i class="fas fa-video text-6xl text-gray-400 mb-4"></i>
                                                        <p class="text-gray-500 dark:text-gray-400 text-lg">Content Preview</p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Content Information -->
                                        <div class="tutorial-info text-center">
                                            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-3">
                                                <?php echo htmlspecialchars($tutorial['title']); ?>
                                            </h3>
                                            <?php if ($tutorial['description']): ?>
                                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed max-w-3xl mx-auto">
                                                <?php echo htmlspecialchars($tutorial['description']); ?>
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Navigation Controls -->
                        <?php if (count($activeTutorials) > 1): ?>
                        <div class="tutorial-navigation">
                            <!-- Previous Button -->
                            <button class="nav-btn nav-prev" id="tutorialPrev">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            
                            <!-- Next Button -->
                            <button class="nav-btn nav-next" id="tutorialNext">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            
                            <!-- Dots Indicator -->
                            <div class="tutorial-dots absolute bottom-4 left-1/2 transform -translate-x-1/2">
                                <div class="flex space-x-2">
                                    <?php foreach ($activeTutorials as $index => $tutorial): ?>
                                    <button class="tutorial-dot w-3 h-3 rounded-full transition-all duration-200 <?php echo $index === 0 ? 'bg-white' : 'bg-white bg-opacity-40'; ?>" 
                                            data-slide="<?php echo $index; ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-12">
                        <i class="fas fa-video text-6xl text-gray-400 mb-6"></i>
                        <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-400 mb-2">No Tutorials Available</h3>
                        <p class="text-gray-500 dark:text-gray-500">
                            Check back later for helpful tutorials and information.
                        </p>
                        <a href="debug_tutorials.php" class="text-blue-500 hover:underline mt-4 inline-block">
                            <i class="fas fa-bug mr-1"></i> Debug Tutorials
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php
                    } catch (Exception $e) {
                    ?>
                    <div class="text-center py-12">
                        <i class="fas fa-exclamation-triangle text-6xl text-red-400 mb-6"></i>
                        <h3 class="text-xl font-semibold text-red-600 mb-2">Error Loading Tutorials</h3>
                        <p class="text-red-500 mb-4"><?php echo htmlspecialchars($e->getMessage()); ?></p>
                        <a href="debug_tutorials.php" class="text-blue-500 hover:underline">
                            <i class="fas fa-bug mr-1"></i> Debug Tutorials
                        </a>
                    </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Right Column (1/4 width) - Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Our Reserves -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-card overflow-hidden section-bg animated-border">
                <div class="reserve-header rounded-t-lg px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                    <h2 class="text-xl font-semibold text-center text-white">
                        <i class="fas fa-wallet mr-2"></i> Our Reserves
                    </h2>
                </div>
                
                <div class="reserve-container section-content">
                    <?php
                    try {
                        $reserves = getCurrencyReserves();
                        foreach ($reserves as $reserve):
                    ?>
                    <div class="p-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="payment-icon <?php echo $reserve['background_class'] ?: 'bg-pink-100 text-pink-500'; ?> w-8 h-8 rounded-full flex items-center justify-center">
                                <?php if ($reserve['logo']): ?>
                                    <img src="<?php echo ASSETS_URL; ?>/uploads/currencies/<?php echo htmlspecialchars($reserve['logo']); ?>" alt="<?php echo htmlspecialchars($reserve['name']); ?>" class="w-7 h-7 object-contain rounded-full">
                                <?php else: ?>
                                    <i class="<?php echo $reserve['icon_class'] ?: 'fas fa-money-bill-wave'; ?> text-sm"></i>
                                <?php endif; ?>
                            </div>
                            <span class="ml-2 text-sm"><?php echo htmlspecialchars($reserve['name']); ?></span>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-sm"><?php echo number_format($reserve['amount'], 2); ?> <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($reserve['display_name'] ?: $reserve['currency_code']); ?></span></div>
                        </div>
                    </div>
                    <?php 
                        endforeach;
                    } catch (Exception $e) {
                        error_log("Error loading reserves: " . $e->getMessage());
                    ?>
                    <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                        Unable to load reserves at the moment
                    </div>
                    <?php } ?>
                    
                    <?php if (empty($reserves)): ?>
                    <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                        No reserves found
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Exchange Rates -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-card overflow-hidden section-bg animated-border">
                <div class="exchange-rate-header rounded-t-lg px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                    <h2 class="text-xl font-semibold text-center text-white">
                        <i class="fas fa-chart-line mr-2"></i> Today Exchange Rate
                    </h2>
                </div>
                
                <div class="section-content">
                    <?php
                    try {
                        // Get exchange rates that are marked for homepage display
                        $db = Database::getInstance();
                        
                        // Check if new columns exist
                        $hasNewColumns = false;
                        try {
                            $checkColumns = $db->query("SHOW COLUMNS FROM exchange_rates LIKE 'display_on_homepage'");
                            if ($checkColumns && $checkColumns->rowCount() > 0) {
                                $hasNewColumns = true;
                            }
                        } catch (Exception $e) {
                            // Ignore if columns don't exist yet
                        }
                        
                        if ($hasNewColumns) {
                            // New system with we_buy, we_sell and homepage display toggle
                            $homepageRates = $db->getRows(
                                "SELECT er.*, 
                                 fc.name as from_currency_name, fc.display_name as from_display_name, fc.logo as from_logo, fc.background_class as from_bg_class, fc.icon_class as from_icon_class
                                 FROM exchange_rates er
                                 JOIN currencies fc ON er.from_currency = fc.code
                                 WHERE er.status = 'active' AND fc.status = 'active' AND er.display_on_homepage = 1
                                 AND (er.we_buy > 0 OR er.we_sell > 0)
                                 ORDER BY fc.name ASC"
                            );
                            
                            if (!empty($homepageRates)):
                            ?>
                            <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-700">
                                <div class="grid grid-cols-3 gap-2 text-center text-sm font-semibold">
                                    <div>We Accept</div>
                                    <div>We Buy</div>
                                    <div>We Sell</div>
                                </div>
                            </div>
                            
                            <?php foreach ($homepageRates as $rate): ?>
                            <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="grid grid-cols-3 gap-2 items-center">
                                    <!-- Currency Name with Logo -->
                                    <div class="flex items-center">
                                        <div class="payment-icon <?php echo $rate['from_bg_class'] ?: 'bg-blue-100 text-blue-500'; ?> w-7 h-7 rounded-full flex items-center justify-center">
                                            <?php if ($rate['from_logo']): ?>
                                                <img src="<?php echo ASSETS_URL; ?>/uploads/currencies/<?php echo htmlspecialchars($rate['from_logo']); ?>" alt="<?php echo htmlspecialchars($rate['from_currency_name']); ?>" class="w-6 h-6 object-contain rounded-full">
                                            <?php else: ?>
                                                <i class="<?php echo $rate['from_icon_class'] ?: 'fas fa-money-bill-wave'; ?> text-xs"></i>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-xs ml-1"><?php echo htmlspecialchars($rate['from_currency_name']); ?></span>
                                    </div>
                                    
                                    <!-- We Buy Rate -->
                                    <div class="text-center">
                                        <div class="font-bold text-xs">
                                            <?php echo ($rate['we_buy'] > 0) ? number_format($rate['we_buy'], 2) : '-'; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- We Sell Rate -->
                                    <div class="text-center">
                                        <div class="font-bold text-xs">
                                            <?php echo ($rate['we_sell'] > 0) ? number_format($rate['we_sell'], 2) : '-'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php else: ?>
                            <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                                No exchange rates configured for homepage display
                            </div>
                            <?php endif; ?>
                            
                            <?php
                        } else {
                            // Fallback to old system for backwards compatibility
                            $exchangeRates = $db->getRows(
                                "SELECT er.*, 
                                 fc.name as from_currency_name, fc.display_name as from_display_name, fc.logo as from_logo, fc.background_class as from_bg_class, fc.icon_class as from_icon_class,
                                 tc.name as to_currency_name, tc.display_name as to_display_name, tc.logo as to_logo, tc.background_class as to_bg_class, tc.icon_class as to_icon_class
                                 FROM exchange_rates er
                                 JOIN currencies fc ON er.from_currency = fc.code
                                 JOIN currencies tc ON er.to_currency = tc.code
                                 WHERE er.status = 'active' AND fc.status = 'active' AND tc.status = 'active'
                                 ORDER BY er.from_currency, er.to_currency LIMIT 5"
                            );
                            
                            if (!empty($exchangeRates)):
                            ?>
                            <?php foreach ($exchangeRates as $rate): ?>
                            <div class="p-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                <div class="flex items-center">
                                    <!-- From Currency -->
                                    <div class="payment-icon <?php echo $rate['from_bg_class'] ?: 'bg-blue-100 text-blue-500'; ?> w-7 h-7 rounded-full flex items-center justify-center">
                                        <?php if ($rate['from_logo']): ?>
                                            <img src="<?php echo ASSETS_URL; ?>/uploads/currencies/<?php echo htmlspecialchars($rate['from_logo']); ?>" alt="<?php echo htmlspecialchars($rate['from_currency_name']); ?>" class="w-6 h-6 object-contain rounded-full">
                                        <?php else: ?>
                                            <i class="<?php echo $rate['from_icon_class'] ?: 'fas fa-money-bill-wave'; ?> text-xs"></i>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-xs ml-1"><?php echo htmlspecialchars($rate['from_display_name'] ?: $rate['from_currency']); ?></span>
                                    
                                    <!-- Arrow -->
                                    <i class="fas fa-arrow-right mx-2 text-gray-400 text-xs"></i>
                                    
                                    <!-- To Currency -->
                                    <div class="payment-icon <?php echo $rate['to_bg_class'] ?: 'bg-green-100 text-green-500'; ?> w-7 h-7 rounded-full flex items-center justify-center">
                                        <?php if ($rate['to_logo']): ?>
                                            <img src="<?php echo ASSETS_URL; ?>/uploads/currencies/<?php echo htmlspecialchars($rate['to_logo']); ?>" alt="<?php echo htmlspecialchars($rate['to_currency_name']); ?>" class="w-6 h-6 object-contain rounded-full">
                                        <?php else: ?>
                                            <i class="<?php echo $rate['to_icon_class'] ?: 'fas fa-money-bill-wave'; ?> text-xs"></i>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-xs ml-1"><?php echo htmlspecialchars($rate['to_display_name'] ?: $rate['to_currency']); ?></span>
                                </div>
                                
                                <div class="text-right">
                                    <div class="font-bold text-xs">
                                        1 <?php echo htmlspecialchars($rate['from_display_name'] ?: $rate['from_currency']); ?> = 
                                        <?php echo number_format($rate['rate'], 4); ?> 
                                        <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($rate['to_display_name'] ?: $rate['to_currency']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php else: ?>
                            <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                                No exchange rates available at the moment
                            </div>
                            <?php endif; ?>
                            <?php
                        }
                    } catch (Exception $e) {
                        error_log("Error loading exchange rates: " . $e->getMessage());
                    ?>
                    <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                        Unable to load exchange rates at the moment
                    </div>
                    <?php } ?>
                </div>
            </div>
            
            <!-- Testimonials -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-card overflow-hidden section-bg animated-border">
                <div class="testimonial-header rounded-t-lg px-4 py-3 border-b border-gray-200 dark:border-gray-600 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-white">
                        <i class="fas fa-comment-dots mr-2"></i> Testimonials
                    </h2>
                    <div class="flex items-center space-x-2">
                        <a href="write-review.php" class="text-white text-sm hover:underline flex items-center">
                            <i class="fas fa-plus mr-1"></i> Write a Review
                        </a>
                        <?php 
                        try {
                            $testimonialCount = count(getActiveTestimonials());
                            if ($testimonialCount > 0):
                        ?>
                        <span class="bg-white text-yellow-600 rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold"><?php echo $testimonialCount; ?></span>
                        <?php 
                            endif;
                        } catch (Exception $e) {
                            error_log("Error loading testimonial count: " . $e->getMessage());
                        }
                        ?>
                    </div>
                </div>
                
                <div class="p-4 section-content">
                    <?php
                    try {
                        $testimonials = getActiveTestimonials(3);
                        foreach ($testimonials as $testimonial):
                    ?>
                    <div class="testimonial-card bg-gray-50 dark:bg-gray-700 p-3 mb-4 rounded">
                        <div class="flex justify-between items-center mb-2">
                            <div class="text-sm text-gray-500 dark:text-gray-400">by <?php echo htmlspecialchars($security->sanitizeInput($testimonial['name'], 'string')); ?></div>
                            <div class="star-rating">
                                <?php for ($i = 0; $i < $testimonial['rating']; $i++): ?>
                                <i class="fas fa-star text-yellow-400"></i>
                                <?php endfor; ?>
                                <?php for ($i = $testimonial['rating']; $i < 5; $i++): ?>
                                <i class="far fa-star text-gray-300"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php if ($testimonial['from_currency'] && $testimonial['to_currency']): ?>
                        <div class="flex items-center text-sm mb-2">
                            <div class="payment-icon <?php echo $testimonial['from_bg_class'] ?: 'bg-green-500 text-white'; ?> w-6 h-6 rounded-full flex items-center justify-center">
                                <?php if ($testimonial['from_logo']): ?>
                                    <img src="<?php echo ASSETS_URL; ?>/uploads/currencies/<?php echo htmlspecialchars($testimonial['from_logo']); ?>" alt="<?php echo htmlspecialchars($testimonial['from_currency_name']); ?>" class="w-5 h-5 object-contain rounded-full">
                                <?php else: ?>
                                    <i class="<?php echo $testimonial['from_icon_class'] ?: 'fas fa-money-bill-wave'; ?> text-xs"></i>
                                <?php endif; ?>
                            </div>
                            <span class="ml-1 text-xs"><?php echo htmlspecialchars($testimonial['from_currency_name']); ?></span>
                            <i class="fas fa-arrow-right mx-2 text-gray-400"></i>
                            <div class="payment-icon <?php echo $testimonial['to_bg_class'] ?: 'bg-blue-500 text-white'; ?> w-6 h-6 rounded-full flex items-center justify-center">
                                <?php if ($testimonial['to_logo']): ?>
                                    <img src="<?php echo ASSETS_URL; ?>/uploads/currencies/<?php echo htmlspecialchars($testimonial['to_logo']); ?>" alt="<?php echo htmlspecialchars($testimonial['to_currency_name']); ?>" class="w-5 h-5 object-contain rounded-full">
                                <?php else: ?>
                                    <i class="<?php echo $testimonial['to_icon_class'] ?: 'fas fa-money-bill-wave'; ?> text-xs"></i>
                                <?php endif; ?>
                            </div>
                            <span class="ml-1 text-xs"><?php echo htmlspecialchars($testimonial['to_currency_name']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="font-semibold text-sm"><?php echo htmlspecialchars($security->sanitizeInput($testimonial['message'], 'string')); ?></div>
                    </div>
                    <?php 
                        endforeach;
                    } catch (Exception $e) {
                        error_log("Error loading testimonials: " . $e->getMessage());
                    ?>
                    <div class="text-center text-gray-500 dark:text-gray-400 py-4">
                        Unable to load testimonials at the moment
                    </div>
                    <?php } ?>
                    
                    <?php if (empty($testimonials)): ?>
                    <div class="text-center text-gray-500 dark:text-gray-400 py-4">
                        No testimonials yet. Be the first to leave a review!
                        <br>
                        <a href="write-review.php" class="text-primary hover:underline mt-2 inline-block">
                            <i class="fas fa-plus mr-1"></i> Write a Review
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Enhanced CSS - ANIMATED BORDERS RESTORED -->
<style>
/* Base Styles */
.payment-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    flex-shrink: 0;
}

.payment-icon img {
    border-radius: 50%;
    object-fit: cover;
}

.star-rating i {
    font-size: 0.75rem;
}

.animate-row {
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card-hover {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.section-bg {
    position: relative;
    overflow: hidden;
}

/* ANIMATED BORDERS - Non-blue color on hover */
.animated-border {
    border: 1px solid #e5e7eb;
    transition: border-color 0.3s ease;
}

.animated-border:hover {
    border-color: #9ca3af;
}

/* Header Styles */
.track-header {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
}

.table-header {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.tutorial-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.reserve-header {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.exchange-rate-header {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.testimonial-header {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

/* Tutorial Carousel Styles */
.tutorial-carousel-container {
    min-height: 500px;
    max-height: 600px;
}

.tutorial-carousel {
    height: 100%;
}

.tutorial-slide {
    min-height: 500px;
}

.tutorial-content {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.tutorial-media-container {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    max-height: 400px;
}

/* Media Wrappers */
.video-wrapper,
.image-wrapper,
.youtube-wrapper,
.facebook-wrapper,
.drive-wrapper,
.adsense-wrapper {
    width: 100%;
    max-width: 800px;
    margin: 0 auto;
}

/* Video Styles */
.tutorial-video {
    max-height: 400px;
    width: 100%;
    object-fit: contain;
}

/* Image Styles */
.tutorial-image {
    max-height: 400px;
    width: 100%;
    object-fit: contain;
}

/* Iframe Styles - Responsive */
.aspect-w-16 {
    position: relative;
    width: 100%;
}

.aspect-w-16:before {
    content: '';
    display: block;
    padding-top: 56.25%; /* 16:9 aspect ratio */
}

.tutorial-iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

/* Navigation Buttons */
.nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 10;
    backdrop-filter: blur(10px);
}

.nav-btn:hover {
    background: rgba(0, 0, 0, 0.9);
    transform: translateY(-50%) scale(1.1);
}

.nav-prev {
    left: 20px;
}

.nav-next {
    right: 20px;
}

/* Dots Indicator */
.tutorial-dots {
    z-index: 10;
}

.tutorial-dot {
    cursor: pointer;
    transition: all 0.3s ease;
}

.tutorial-dot:hover {
    transform: scale(1.2);
}

.tutorial-dot.active {
    background: white !important;
    transform: scale(1.3);
}

/* Tutorial Info */
.tutorial-info {
    padding: 0 20px;
}

.tutorial-meta {
    font-size: 0.875rem;
}

/* Error Media Styles */
.error-media {
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
}

/* Responsive Design */
@media (max-width: 768px) {
    .tutorial-carousel-container {
        min-height: 400px;
        max-height: 500px;
    }
    
    .tutorial-slide {
        min-height: 400px;
    }
    
    .tutorial-media-container {
        max-height: 300px;
    }
    
    .tutorial-video,
    .tutorial-image {
        max-height: 250px;
    }
    
    .nav-btn {
        width: 40px;
        height: 40px;
    }
    
    .nav-prev {
        left: 10px;
    }
    
    .nav-next {
        right: 10px;
    }
    
    .tutorial-content {
        padding: 1rem;
    }
    
    .tutorial-info h3 {
        font-size: 1.125rem;
    }
}

@media (max-width: 480px) {
    .tutorial-carousel-container {
        min-height: 350px;
        max-height: 450px;
    }
    
    .tutorial-media-container {
        max-height: 200px;
    }
    
    .tutorial-video,
    .tutorial-image {
        max-height: 180px;
    }
    
    .tutorial-meta {
        flex-direction: column;
        space-y: 2;
    }
    
    .tutorial-meta > span {
        margin-bottom: 0.5rem;
    }
}

/* Large Screens - Better sidebar positioning */
@media (min-width: 1024px) {
    .lg\:col-span-3 {
        grid-column: span 3 / span 3;
    }
    
    .lg\:col-span-1 {
        grid-column: span 1 / span 1;
    }
    
    .lg\:grid-cols-4 {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

/* Dark Mode Enhancements */
.dark .tutorial-item {
    background-color: #374151;
}

.dark .tutorial-info h3 {
    color: #f9fafb;
}

.dark .tutorial-info p {
    color: #d1d5db;
}

.dark .nav-btn {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.dark .nav-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Auto-play pause on hover */
.tutorial-carousel-container:hover .tutorial-slides {
    animation-play-state: paused;
}

/* Ensure proper stacking context */
.section-bg {
    position: relative;
    z-index: 1;
}
</style>

<!-- Enhanced JavaScript for Tutorial Carousel -->
<script>
// Dark mode detection
if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    document.documentElement.classList.add('dark');
}
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
    if (event.matches) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
});

class TutorialCarousel {
    constructor() {
        this.carousel = document.getElementById('tutorialCarousel');
        this.slides = document.getElementById('tutorialSlides');
        this.prevBtn = document.getElementById('tutorialPrev');
        this.nextBtn = document.getElementById('tutorialNext');
        this.dots = document.querySelectorAll('.tutorial-dot');
        this.counter = document.getElementById('tutorialCounter');
        this.currentSlideSpan = document.getElementById('currentSlide');
        this.totalSlidesSpan = document.getElementById('totalSlides');
        
        this.currentSlide = 0;
        this.totalSlides = this.dots.length;
        this.autoPlayInterval = null;
        this.autoPlayDelay = 8000; // 8 seconds
        
        this.init();
    }
    
    init() {
        if (this.totalSlides <= 1) {
            return; // No need for carousel with single item
        }
        
        // Show counter if multiple slides
        if (this.counter) {
            this.counter.style.display = 'block';
            this.updateCounter();
        }
        
        // Bind events
        this.bindEvents();
        
        // Start autoplay
        this.startAutoPlay();
        
        // Pause on hover
        if (this.carousel) {
            this.carousel.addEventListener('mouseenter', () => this.pauseAutoPlay());
            this.carousel.addEventListener('mouseleave', () => this.startAutoPlay());
            
            // Pause on focus (for accessibility)
            this.carousel.addEventListener('focusin', () => this.pauseAutoPlay());
            this.carousel.addEventListener('focusout', () => this.startAutoPlay());
        }
    }
    
    bindEvents() {
        // Navigation buttons
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', () => this.prevSlide());
        }
        
        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => this.nextSlide());
        }
        
        // Dots navigation
        this.dots.forEach((dot, index) => {
            dot.addEventListener('click', () => this.goToSlide(index));
        });
        
        // Keyboard navigation
        if (this.carousel) {
            this.carousel.addEventListener('keydown', (e) => {
                switch(e.key) {
                    case 'ArrowLeft':
                        e.preventDefault();
                        this.prevSlide();
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        this.nextSlide();
                        break;
                    case 'Home':
                        e.preventDefault();
                        this.goToSlide(0);
                        break;
                    case 'End':
                        e.preventDefault();
                        this.goToSlide(this.totalSlides - 1);
                        break;
                }
            });
        }
        
        // Touch events for mobile
        this.bindTouchEvents();
    }
    
    bindTouchEvents() {
        if (!this.carousel) return;
        
        let startX = 0;
        let endX = 0;
        const threshold = 50; // Minimum distance for swipe
        
        this.carousel.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        }, { passive: true });
        
        this.carousel.addEventListener('touchend', (e) => {
            endX = e.changedTouches[0].clientX;
            const distance = startX - endX;
            
            if (Math.abs(distance) > threshold) {
                if (distance > 0) {
                    this.nextSlide(); // Swipe left, go to next
                } else {
                    this.prevSlide(); // Swipe right, go to previous
                }
            }
        }, { passive: true });
    }
    
    goToSlide(index) {
        if (index < 0 || index >= this.totalSlides || !this.slides) return;
        
        this.currentSlide = index;
        const translateX = -index * 100;
        this.slides.style.transform = `translateX(${translateX}%)`;
        
        this.updateDots();
        this.updateCounter();
        
        // Pause videos when changing slides
        this.pauseAllVideos();
        
        // Restart autoplay
        this.restartAutoPlay();
    }
    
    nextSlide() {
        const nextIndex = (this.currentSlide + 1) % this.totalSlides;
        this.goToSlide(nextIndex);
    }
    
    prevSlide() {
        const prevIndex = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
        this.goToSlide(prevIndex);
    }
    
    updateDots() {
        this.dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === this.currentSlide);
            if (index === this.currentSlide) {
                dot.style.background = 'white';
                dot.style.transform = 'scale(1.3)';
            } else {
                dot.style.background = 'rgba(255, 255, 255, 0.4)';
                dot.style.transform = 'scale(1)';
            }
        });
    }
    
    updateCounter() {
        if (this.currentSlideSpan && this.totalSlidesSpan) {
            this.currentSlideSpan.textContent = this.currentSlide + 1;
            this.totalSlidesSpan.textContent = this.totalSlides;
        }
    }
    
    pauseAllVideos() {
        if (!this.carousel) return;
        
        const videos = this.carousel.querySelectorAll('video');
        videos.forEach(video => {
            video.pause();
        });
    }
    
    startAutoPlay() {
        if (this.totalSlides <= 1) return;
        
        this.autoPlayInterval = setInterval(() => {
            this.nextSlide();
        }, this.autoPlayDelay);
    }
    
    pauseAutoPlay() {
        if (this.autoPlayInterval) {
            clearInterval(this.autoPlayInterval);
            this.autoPlayInterval = null;
        }
    }
    
    restartAutoPlay() {
        this.pauseAutoPlay();
        this.startAutoPlay();
    }
}

// Initialize carousel when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tutorial carousel
    const tutorialCarousel = new TutorialCarousel();
    
    // Other existing functionality
    const sensitiveElements = document.querySelectorAll('.payment-icon, .testimonial-card, .tutorial-item');
    sensitiveElements.forEach(element => {
        element.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });
    });
    
    // Track form loading state
    const trackForm = document.querySelector('form[action="track.php"]');
    if (trackForm) {
        trackForm.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                submitBtn.disabled = true;
            }
        });
    }
    
    // Page visibility API for pausing autoplay when tab is hidden
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            tutorialCarousel.pauseAutoPlay();
        } else {
            tutorialCarousel.startAutoPlay();
        }
    });
});
</script>

<?php include 'templates/footer.php'; ?>