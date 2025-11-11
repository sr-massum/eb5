<?php 

/**
 * ExchangeBridge - Admin Header Template
 *
 * package     ExchangeBridge
 * author      Saieed Rahman
 * copyright   SidMan Solution 2025
 * version     1.0.0
 */


// Start session
session_start();

// Define access constant
define('ALLOW_ACCESS', true);


header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

// Prevent direct access
if (!defined('ALLOW_ACCESS')) {
    header("HTTP/1.1 403 Forbidden");
    exit("Direct access forbidden");
}

// Check if user is logged in, if not redirect to login page
if (!Auth::isLoggedIn()) {
    header("Location: " . ADMIN_URL . "/login.php");
    exit;
}

// Get user info
$user = Auth::getUser();

// Function to check if current page is active
function isActiveMenu($page) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    $currentDir = basename(dirname($_SERVER['PHP_SELF']));
    $parentDir = basename(dirname(dirname($_SERVER['PHP_SELF'])));
    
    if ($page === 'index.php' && $currentPage === 'index.php') {
        return true;
    }
    
    // Check for pages submenu
    if ($page === 'pages' && ($currentDir === 'pages' || $parentDir === 'pages')) {
        return true;
    }
    
    // Check for notices submenu
    if ($page === 'notices' && ($currentDir === 'notices' || $parentDir === 'notices')) {
        return true;
    }
    
    // Check for tutorials submenu
    if ($page === 'tutorials' && ($currentDir === 'tutorials' || $parentDir === 'tutorials')) {
        return true;
    }
    
    return ($currentDir === $page || $currentPage === $page);
}

// Function to check if submenu item is active
function isActiveSubmenu($submenu) {
    $currentDir = basename(dirname($_SERVER['PHP_SELF']));
    $parentDir = basename(dirname(dirname($_SERVER['PHP_SELF'])));
    
    // For pages submenu items
    if ($parentDir === 'pages' && $currentDir === $submenu) {
        return true;
    }
    
    // For tutorials submenu items
    if ($parentDir === 'tutorials' && $currentDir === $submenu) {
        return true;
    }
    // For tutorials root pages (index.php, edit.php in tutorials directory)
    if ($currentDir === 'tutorials' && $submenu === '') {
        return true;
    }
    
    // For notices submenu items
    if ($parentDir === 'notices' && $currentDir === $submenu) {
        return true;
    }
    // For notices root pages
    if ($currentDir === 'notices' && $submenu === '') {
        return true;
    }
    
    return false;
}

// Set timezone to match server timezone
$timezone = getSetting('site_timezone', 'Asia/Dhaka');
date_default_timezone_set($timezone);

// Get current server time
$currentServerTime = date('h:i:s A'); // 12-hour format with AM/PM
$currentServerDate = date('d-m-Y');
$currentServerDateTime = date('Y-m-d H:i:s');
$currentTimestamp = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo getSetting('site_name', SITE_NAME); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Google Fonts for multilingual support -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?php echo ADMIN_URL; ?>/assets/css/admin.css">
    <!-- Custom styles based on settings -->
    <style>
        :root {
            --primary-color: <?php echo getSetting('primary_color', '#5D5CDE'); ?>;
            --secondary-color: <?php echo getSetting('secondary_color', '#4BB74B'); ?>;
            --header-color: <?php echo getSetting('header_color', '#1E3A8A'); ?>;
            --sidebar-bg: #343a40;
            --sidebar-color: #fff;
        }
        
        * {
            font-family: 'Hind Siliguri', 'Poppins', 'Roboto', sans-serif;
        }
        
        .multilingual-text {
            font-family: 'Hind Siliguri', 'Poppins', 'Roboto', sans-serif;
        }
        
        .bg-primary {
            background-color: var(--primary-color) !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: color-mod(var(--primary-color) shade(10%));
            border-color: color-mod(var(--primary-color) shade(10%));
        }
        
        .sidebar {
            background-color: var(--sidebar-bg);
            color: var(--sidebar-color);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        /* Submenu Styles */
        .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
            background-color: rgba(0, 0, 0, 0.2);
        }
        
        .submenu.show {
            max-height: 300px;
        }
        
        .submenu .nav-link {
            font-size: 0.9rem;
            padding-left: 2.5rem;
            border-left: 2px solid transparent;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        
        .submenu .nav-link:hover {
            border-left-color: var(--primary-color);
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .submenu .nav-link.active {
            border-left-color: var(--primary-color);
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .menu-toggle {
            cursor: pointer;
            position: relative;
        }
        
        .menu-toggle::after {
            content: '\f078';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            float: right;
            transition: transform 0.3s ease;
            margin-top: 2px;
        }
        
        .menu-toggle.expanded::after {
            transform: rotate(180deg);
        }
        
        .card-header {
            background-color: rgba(0, 0, 0, 0.03);
        }
        
        .navbar-dark {
            background-color: var(--header-color) !important;
        }
        
        /* Time and Date Display Styles */
        .admin-clock {
            font-family: 'Courier New', monospace;
            font-weight: 700;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 0.5rem;
            border-radius: 0.25rem;
            margin-right: 1rem;
            color: white;
            text-align: center;
            line-height: 1.2;
        }
        
        .admin-clock .time {
            font-size: 1.1rem;
        }
        
        .admin-clock .date {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        /* Hidden input for admin time */
        #admin-current-time {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <a class="navbar-brand" href="<?php echo ADMIN_URL; ?>/index.php">
            <i class="fas fa-exchange-alt mr-2"></i>
            <?php echo getSetting('site_name', SITE_NAME); ?> Admin
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <!-- Admin Clock -->
                <li class="nav-item d-none d-md-block">
                    <div class="admin-clock">
                        <div id="current-time" class="time"><?php echo $currentServerTime; ?></div>
                        <div id="current-date" class="date"><?php echo $currentServerDate; ?></div>
                    </div>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($user['username']); ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/profile.php">
                            <i class="fas fa-user-cog mr-2"></i> Profile
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/logout.php">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>" target="_blank">
                        <i class="fas fa-external-link-alt mr-1"></i> View Site
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Hidden inputs for admin time -->
    <input type="hidden" id="admin-current-time" name="admin_current_time" value="<?php echo $currentServerDateTime; ?>">
    <input type="hidden" id="admin-timestamp" name="admin_timestamp" value="<?php echo $currentTimestamp; ?>">
    <input type="hidden" id="admin-timezone" name="admin_timezone" value="<?php echo $timezone; ?>">

    <!-- Page container -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-none d-md-block sidebar">
                <div class="sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveMenu('index.php') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/index.php">
                                <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveMenu('exchanges') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/exchanges/">
                                <i class="fas fa-exchange-alt mr-2"></i> Exchanges
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveMenu('currencies') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/currencies/">
                                <i class="fas fa-money-bill-wave mr-2"></i> Currencies
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveMenu('rates') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/rates/">
                                <i class="fas fa-chart-line mr-2"></i> Exchange Rates
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveMenu('reserves') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/reserves/">
                                <i class="fas fa-wallet mr-2"></i> Reserves
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveMenu('testimonials') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/testimonials/">
                                <i class="fas fa-comments mr-2"></i> Testimonials
                            </a>
                        </li>
                        
                        <!-- Pages with Submenu -->
                        <li class="nav-item">
                            <a class="nav-link menu-toggle <?php echo isActiveMenu('pages') ? 'active expanded' : ''; ?>" 
                               href="<?php echo ADMIN_URL; ?>/pages/" 
                               onclick="toggleSubmenu(event, 'pages-submenu')">
                                <i class="fas fa-file-alt mr-2"></i> Pages
                            </a>
                            <!-- Submenu for pages -->
                            <ul class="nav flex-column submenu <?php echo isActiveMenu('pages') ? 'show' : ''; ?>" id="pages-submenu">
                                <li class="nav-item">
                                    <a class="nav-link <?php echo isActiveSubmenu('about') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/pages/about/">
                                        <i class="fas fa-info-circle mr-1"></i> About Page
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo isActiveSubmenu('privacy') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/pages/privacy/">
                                        <i class="fas fa-shield-alt mr-1"></i> Privacy Policy
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo isActiveSubmenu('terms') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/pages/terms/">
                                        <i class="fas fa-file-contract mr-1"></i> Terms & Conditions
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo isActiveSubmenu('contact') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/pages/contact/">
                                        <i class="fas fa-address-book mr-1"></i> Contact Page
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo isActiveSubmenu('faq') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/pages/faq/">
                                        <i class="fas fa-question-circle mr-1"></i> FAQ Page
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveMenu('blog') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/blog/">
                                <i class="fas fa-newspaper mr-2"></i> Blog
                            </a>
                        </li>
                        
                        <!-- Notices with Submenu -->
                        <li class="nav-item">
                            <a class="nav-link menu-toggle <?php echo isActiveMenu('notices') ? 'active expanded' : ''; ?>" 
                               href="<?php echo ADMIN_URL; ?>/notices/" 
                               onclick="toggleSubmenu(event, 'notices-submenu')">
                                <i class="fas fa-bell mr-2"></i> Notices
                            </a>
                            <!-- Submenu for notices -->
                            <ul class="nav flex-column submenu <?php echo isActiveMenu('notices') ? 'show' : ''; ?>" id="notices-submenu">
                                <li class="nav-item">
                                    <a class="nav-link <?php echo isActiveSubmenu('scroll') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/notices/scroll/">
                                        <i class="fas fa-scroll mr-1"></i> Scrolling Notices
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo isActiveSubmenu('popup') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/notices/popup/">
                                        <i class="fas fa-window-restore mr-1"></i> Popup Notices
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo isActiveSubmenu('') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/notices/">
                                        <i class="fas fa-list mr-1"></i> All Notices
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                        <!-- Tutorials/Ads with Submenu -->
                        <li class="nav-item">
                            <a class="nav-link menu-toggle <?php echo isActiveMenu('tutorials') ? 'active expanded' : ''; ?>" 
                               href="<?php echo ADMIN_URL; ?>/tutorials/" 
                               onclick="toggleSubmenu(event, 'tutorials-submenu')">
                                <i class="fas fa-play-circle mr-2"></i> Tutorials/Ads
                            </a>
                            <!-- Submenu for tutorials -->
                            <ul class="nav flex-column submenu <?php echo isActiveMenu('tutorials') ? 'show' : ''; ?>" id="tutorials-submenu">
                                <li class="nav-item">
                                    <a class="nav-link <?php echo isActiveSubmenu('video') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/tutorials/video/">
                                        <i class="fas fa-video mr-1"></i> Video Uploads
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo isActiveSubmenu('embed') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/tutorials/embed/">
                                        <i class="fas fa-code mr-1"></i> Video Embeds
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo isActiveSubmenu('adsense') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/tutorials/adsense/">
                                        <i class="fas fa-ad mr-1"></i> Google AdSense
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo isActiveSubmenu('') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/tutorials/">
                                        <i class="fas fa-list mr-1"></i> All Tutorials
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveMenu('media') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/media/">
                                <i class="fas fa-photo-video mr-2"></i> Media
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveMenu('site-user') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/site-user/">
                                <i class="fas fa-users mr-2"></i> Admin & Users
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveMenu('floating-buttons') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/floating-buttons/">
                                <i class="fas fa-life-ring mr-2"></i> Floating Buttons
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveMenu('exchange-wizard') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/exchange-wizard/">
                                <i class="fas fa-magic mr-2"></i> Exchange Wizard
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveMenu('receipts') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/receipts/">
                                <i class="fas fa-receipt mr-2"></i> Receipt
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveMenu('settings') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/settings/">
                                <i class="fas fa-cog mr-2"></i> Settings
                            </a>
                        </li>
                        
                        <?php if (Auth::isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveMenu('global-seo') ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/global-seo/">
                                <i class="fas fa-search mr-2"></i> SEO Settings
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
            
            <!-- Main content -->
            <main role="main" class="col-md-10 ml-sm-auto px-4 content">

<script>
// Server time initialization
let serverTimestamp = parseInt(document.getElementById('admin-timestamp').value) * 1000;
let clientStartTime = Date.now();

// Real-time clock functionality - synchronized with server time (12-hour format)
function updateClock() {
    // Calculate current server time based on elapsed time
    const elapsedTime = Date.now() - clientStartTime;
    const currentServerTime = new Date(serverTimestamp + elapsedTime);
    
    // Format time as 12-hour format with AM/PM
    let hours = currentServerTime.getHours();
    const minutes = currentServerTime.getMinutes().toString().padStart(2, '0');
    const seconds = currentServerTime.getSeconds().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    
    // Convert to 12-hour format
    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    const hoursFormatted = hours.toString().padStart(2, '0');
    
    const timeString = `${hoursFormatted}:${minutes}:${seconds} ${ampm}`;
    
    // Format date as DD-MM-YYYY
    const day = currentServerTime.getDate().toString().padStart(2, '0');
    const month = (currentServerTime.getMonth() + 1).toString().padStart(2, '0');
    const year = currentServerTime.getFullYear();
    const dateString = `${day}-${month}-${year}`;
    
    // Update the display
    const timeElement = document.getElementById('current-time');
    const dateElement = document.getElementById('current-date');
    
    if (timeElement) timeElement.textContent = timeString;
    if (dateElement) dateElement.textContent = dateString;
    
    // Store the current time in a hidden input to be used for transactions (24-hour format for server)
    const adminTimeInput = document.getElementById('admin-current-time');
    if (adminTimeInput) {
        const hours24 = currentServerTime.getHours().toString().padStart(2, '0');
        adminTimeInput.value = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')} ${hours24}:${minutes}:${seconds}`;
    }
}

// Submenu toggle functionality
function toggleSubmenu(event, submenuId) {
    event.preventDefault();
    const submenu = document.getElementById(submenuId);
    const toggle = event.currentTarget;
    
    if (submenu.classList.contains('show')) {
        submenu.classList.remove('show');
        toggle.classList.remove('expanded');
    } else {
        submenu.classList.add('show');
        toggle.classList.add('expanded');
    }
}

// Update clock immediately and then every second
document.addEventListener('DOMContentLoaded', function() {
    updateClock();
    setInterval(updateClock, 1000);
    
    // Synchronize with server every 5 minutes to prevent drift
    setInterval(function() {
        fetch(window.location.origin + '/api/get_server_time.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.timestamp) {
                    serverTimestamp = parseInt(data.timestamp) * 1000;
                    clientStartTime = Date.now();
                }
            })
            .catch(error => {
                console.error('Error syncing server time:', error);
            });
    }, 300000); // 5 minutes
});
</script>