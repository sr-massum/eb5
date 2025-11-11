<?php 

/**
 * ExchangeBridge - Admin Panel Settings
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

// Include configuration files
require_once '../../config/config.php';
require_once '../../config/verification.php';
require_once '../../config/license.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/app.php';
require_once '../../includes/auth.php';
require_once '../../includes/security.php';

// Check if user is logged in, if not redirect to login page
if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Check if user has admin role for certain settings
$isAdmin = Auth::isAdmin();

// Get media files for logo/favicon selection
$db = Database::getInstance();
$mediaFiles = $db->getRows("SELECT * FROM media WHERE file_type = 'image' ORDER BY created_at DESC LIMIT 100");

// Check for success message
$successMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Check for error message
$errorMessage = '';
if (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debug: Log form submission
        error_log("Settings form submitted");
        
        // Handle file uploads
        $logoFile = '';
        $faviconFile = '';
        
        // Handle logo upload
        if (isset($_FILES['site_logo_upload']) && $_FILES['site_logo_upload']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../assets/uploads/media/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $result = uploadFile($_FILES['site_logo_upload'], $uploadDir);
            if ($result['success']) {
                $logoFile = 'assets/uploads/media/' . $result['filename'];
            }
        }
        
        // Handle favicon upload
        if (isset($_FILES['site_favicon_upload']) && $_FILES['site_favicon_upload']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../assets/uploads/media/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $result = uploadFile($_FILES['site_favicon_upload'], $uploadDir);
            if ($result['success']) {
                $faviconFile = 'assets/uploads/media/' . $result['filename'];
            }
        }
        
        // Process each setting with proper handling
        $settings = [
            // General Settings
            'site_name' => isset($_POST['site_name']) ? trim($_POST['site_name']) : '',
            'site_tagline' => isset($_POST['site_tagline']) ? trim($_POST['site_tagline']) : '',
            'operator_status' => isset($_POST['operator_status']) ? $_POST['operator_status'] : 'online',
            'working_hours' => isset($_POST['working_hours']) ? trim($_POST['working_hours']) : '',
            'footer_copyright' => isset($_POST['footer_copyright']) ? trim($_POST['footer_copyright']) : '',
            'enable_notification_sound' => isset($_POST['enable_notification_sound']) ? 'yes' : 'no',
            'enable_popup_notice' => isset($_POST['enable_popup_notice']) ? 'yes' : 'no',
            
            // Logo & Branding Settings
            'logo_type' => isset($_POST['logo_type']) ? $_POST['logo_type'] : 'text',
            'site_logo_text' => isset($_POST['site_logo_text']) ? trim($_POST['site_logo_text']) : '',
            'site_logo' => !empty($logoFile) ? $logoFile : (isset($_POST['selected_logo_media']) && !empty($_POST['selected_logo_media']) ? $_POST['selected_logo_media'] : getSetting('site_logo', '')),
            'site_favicon' => !empty($faviconFile) ? $faviconFile : (isset($_POST['selected_favicon_media']) && !empty($_POST['selected_favicon_media']) ? $_POST['selected_favicon_media'] : getSetting('site_favicon', '')),
            'logo_size' => isset($_POST['logo_size']) ? $_POST['logo_size'] : 'medium',
            'logo_position' => isset($_POST['logo_position']) ? $_POST['logo_position'] : 'left',
            'logo_max_width' => isset($_POST['logo_max_width']) ? intval($_POST['logo_max_width']) : 200,
            'logo_max_height' => isset($_POST['logo_max_height']) ? intval($_POST['logo_max_height']) : 60,
            
            // Extended Color Settings
            'primary_color' => isset($_POST['primary_color']) ? $_POST['primary_color'] : '#5D5CDE',
            'secondary_color' => isset($_POST['secondary_color']) ? $_POST['secondary_color'] : '#6c757d',
            'success_color' => isset($_POST['success_color']) ? $_POST['success_color'] : '#28a745',
            'warning_color' => isset($_POST['warning_color']) ? $_POST['warning_color'] : '#ffc107',
            'danger_color' => isset($_POST['danger_color']) ? $_POST['danger_color'] : '#dc3545',
            'info_color' => isset($_POST['info_color']) ? $_POST['info_color'] : '#17a2b8',
            'light_color' => isset($_POST['light_color']) ? $_POST['light_color'] : '#f8f9fa',
            'dark_color' => isset($_POST['dark_color']) ? $_POST['dark_color'] : '#343a40',
            'header_color' => isset($_POST['header_color']) ? $_POST['header_color'] : '#1E3A8A',
            'footer_color' => isset($_POST['footer_color']) ? $_POST['footer_color'] : '#1E3A8A',
            'body_bg_color' => isset($_POST['body_bg_color']) ? $_POST['body_bg_color'] : '#ffffff',
            'text_color' => isset($_POST['text_color']) ? $_POST['text_color'] : '#212529',
            'link_color' => isset($_POST['link_color']) ? $_POST['link_color'] : '#007bff',
            'border_color' => isset($_POST['border_color']) ? $_POST['border_color'] : '#dee2e6',
            
            // Navigation Settings
            'nav_style' => isset($_POST['nav_style']) ? $_POST['nav_style'] : 'horizontal',
            'nav_position' => isset($_POST['nav_position']) ? $_POST['nav_position'] : 'top',
            'show_breadcrumbs' => isset($_POST['show_breadcrumbs']) ? 'yes' : 'no',
            'custom_menu_items' => isset($_POST['custom_menu_items']) ? $_POST['custom_menu_items'] : '',
            
            // Contact Info Settings
            'contact_phone' => isset($_POST['contact_phone']) ? trim($_POST['contact_phone']) : '',
            'contact_whatsapp' => isset($_POST['contact_whatsapp']) ? trim($_POST['contact_whatsapp']) : '',
            'contact_email' => isset($_POST['contact_email']) ? trim($_POST['contact_email']) : '',
            'contact_address' => isset($_POST['contact_address']) ? trim($_POST['contact_address']) : '',
            
            // Social Media Settings
            'social_facebook' => isset($_POST['social_facebook']) ? trim($_POST['social_facebook']) : '',
            'social_twitter' => isset($_POST['social_twitter']) ? trim($_POST['social_twitter']) : '',
            'social_telegram' => isset($_POST['social_telegram']) ? trim($_POST['social_telegram']) : '',
            'social_instagram' => isset($_POST['social_instagram']) ? trim($_POST['social_instagram']) : '',
            'social_linkedin' => isset($_POST['social_linkedin']) ? trim($_POST['social_linkedin']) : '',
            'social_youtube' => isset($_POST['social_youtube']) ? trim($_POST['social_youtube']) : '',
            
            // Features Settings
            'enable_whatsapp_button' => isset($_POST['enable_whatsapp_button']) ? 'yes' : 'no',
            'whatsapp_number' => isset($_POST['whatsapp_number']) ? trim($_POST['whatsapp_number']) : '',
            'whatsapp_message' => isset($_POST['whatsapp_message']) ? trim($_POST['whatsapp_message']) : '',
            'enable_tawkto' => isset($_POST['enable_tawkto']) ? 'yes' : 'no',
            'tawkto_widget_code' => isset($_POST['tawkto_widget_code']) ? $_POST['tawkto_widget_code'] : '',
            
            // Advanced Settings - SEO
            'meta_description' => isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '',
            'meta_keywords' => isset($_POST['meta_keywords']) ? trim($_POST['meta_keywords']) : '',
            'google_site_verification' => isset($_POST['google_site_verification']) ? trim($_POST['google_site_verification']) : '',
            
            // Advanced Settings - Analytics & Tracking
            'google_analytics_code' => isset($_POST['google_analytics_code']) ? $_POST['google_analytics_code'] : '',
            'google_analytics_id' => isset($_POST['google_analytics_id']) ? trim($_POST['google_analytics_id']) : '',
            'facebook_pixel_id' => isset($_POST['facebook_pixel_id']) ? trim($_POST['facebook_pixel_id']) : '',
            'custom_tracking_code' => isset($_POST['custom_tracking_code']) ? $_POST['custom_tracking_code'] : '',
            
            // Advanced Settings - Security
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 'yes' : 'no',
            'maintenance_message' => isset($_POST['maintenance_message']) ? trim($_POST['maintenance_message']) : '',
            'enable_captcha' => isset($_POST['enable_captcha']) ? 'yes' : 'no',
            'recaptcha_site_key' => isset($_POST['recaptcha_site_key']) ? trim($_POST['recaptcha_site_key']) : '',
            'recaptcha_secret_key' => isset($_POST['recaptcha_secret_key']) ? trim($_POST['recaptcha_secret_key']) : '',
            
            // Advanced Settings - Performance
            'enable_caching' => isset($_POST['enable_caching']) ? 'yes' : 'no',
            'cache_duration' => isset($_POST['cache_duration']) ? intval($_POST['cache_duration']) : '60',
            'enable_compression' => isset($_POST['enable_compression']) ? 'yes' : 'no',
            'minify_assets' => isset($_POST['minify_assets']) ? 'yes' : 'no',
            
            // Advanced Settings - Email
            'email_method' => isset($_POST['email_method']) ? $_POST['email_method'] : 'mail',
            'smtp_host' => isset($_POST['smtp_host']) ? trim($_POST['smtp_host']) : '',
            'smtp_port' => isset($_POST['smtp_port']) ? intval($_POST['smtp_port']) : '587',
            'smtp_encryption' => isset($_POST['smtp_encryption']) ? $_POST['smtp_encryption'] : 'tls',
            'smtp_username' => isset($_POST['smtp_username']) ? trim($_POST['smtp_username']) : '',
            'smtp_password' => isset($_POST['smtp_password']) ? $_POST['smtp_password'] : '',
            'email_from_name' => isset($_POST['email_from_name']) ? trim($_POST['email_from_name']) : '',
            'email_from_address' => isset($_POST['email_from_address']) ? trim($_POST['email_from_address']) : '',
            
            // Advanced Settings - API
            'enable_api' => isset($_POST['enable_api']) ? 'yes' : 'no',
            'api_rate_limit' => isset($_POST['api_rate_limit']) ? intval($_POST['api_rate_limit']) : '60',
            'allowed_origins' => isset($_POST['allowed_origins']) ? $_POST['allowed_origins'] : '*',
            'api_require_auth' => isset($_POST['api_require_auth']) ? 'yes' : 'no',
            
            // Advanced Settings - Custom Scripts
            'header_scripts' => isset($_POST['header_scripts']) ? $_POST['header_scripts'] : '',
            'footer_scripts' => isset($_POST['footer_scripts']) ? $_POST['footer_scripts'] : '',
            'custom_css' => isset($_POST['custom_css']) ? $_POST['custom_css'] : '',
            
            // Advanced Settings - Developer
            'debug_mode' => isset($_POST['debug_mode']) ? 'yes' : 'no',
            'log_queries' => isset($_POST['log_queries']) ? 'yes' : 'no',
            'log_level' => isset($_POST['log_level']) ? $_POST['log_level'] : 'error',
            'enable_profiler' => isset($_POST['enable_profiler']) ? 'yes' : 'no',
            
            // Advanced Settings - Backup
            'auto_backup' => isset($_POST['auto_backup']) ? 'yes' : 'no',
            'backup_frequency' => isset($_POST['backup_frequency']) ? $_POST['backup_frequency'] : 'daily',
            'backup_retention' => isset($_POST['backup_retention']) ? intval($_POST['backup_retention']) : '30',
        ];
        
        // Debug: Log settings count
        error_log("Processing " . count($settings) . " settings");
        
        // Get database instance
        $success = true;
        $failed_settings = [];
        
        // Update each setting
        foreach ($settings as $key => $value) {
            $result = updateSetting($key, $value);
            if (!$result) {
                $success = false;
                $failed_settings[] = $key;
                error_log("Failed to update setting: " . $key . " = " . $value);
            } else {
                error_log("Successfully updated setting: " . $key . " = " . $value);
            }
        }
        
        if ($success) {
            $_SESSION['success_message'] = 'All settings updated successfully!';
            error_log("All settings updated successfully");
        } else {
            $_SESSION['error_message'] = 'Failed to update some settings: ' . implode(', ', $failed_settings);
            error_log("Failed to update settings: " . implode(', ', $failed_settings));
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error updating settings: ' . $e->getMessage();
        error_log("Exception in settings update: " . $e->getMessage());
    }
    
    // Redirect to refresh page
    header("Location: index.php");
    exit;
}

// Include header
include '../includes/header.php';
?>

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item active">Settings</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-cog mr-1"></i> Site Settings
        <div class="float-right">
            <small class="text-muted">Last updated: <?php echo date('M d, Y H:i:s'); ?></small>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i><?php echo $successMessage; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $errorMessage; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Debug Info (remove in production) -->
        <?php if (getSetting('debug_mode', 'no') === 'yes'): ?>
        <div class="alert alert-info">
            <strong>Debug Info:</strong> Settings form ready. Current user: <?php echo Auth::getUser()['username'] ?? 'Unknown'; ?>
        </div>
        <?php endif; ?>
        
        <form id="settingsForm" action="index.php" method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
            <!-- Add CSRF protection -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>">
            
            <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">
                        <i class="fas fa-globe mr-1"></i> General
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="branding-tab" data-toggle="tab" href="#branding" role="tab" aria-controls="branding" aria-selected="false">
                        <i class="fas fa-image mr-1"></i> Logo & Branding
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="appearance-tab" data-toggle="tab" href="#appearance" role="tab" aria-controls="appearance" aria-selected="false">
                        <i class="fas fa-paint-brush mr-1"></i> Colors & Appearance
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="navigation-tab" data-toggle="tab" href="#navigation" role="tab" aria-controls="navigation" aria-selected="false">
                        <i class="fas fa-bars mr-1"></i> Navigation
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="contact-tab" data-toggle="tab" href="#contact" role="tab" aria-controls="contact" aria-selected="false">
                        <i class="fas fa-address-card mr-1"></i> Contact Info
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="social-tab" data-toggle="tab" href="#social" role="tab" aria-controls="social" aria-selected="false">
                        <i class="fas fa-share-alt mr-1"></i> Social Media
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="features-tab" data-toggle="tab" href="#features" role="tab" aria-controls="features" aria-selected="false">
                        <i class="fas fa-star mr-1"></i> Features
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="advanced-tab" data-toggle="tab" href="#advanced" role="tab" aria-controls="advanced" aria-selected="false">
                        <i class="fas fa-code mr-1"></i> Advanced
                    </a>
                </li>
            </ul>
            
            <div class="tab-content mt-4" id="settingsTabContent">
                <!-- General Settings -->
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="site_name">Site Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="site_name" name="site_name" 
                                       value="<?php echo htmlspecialchars(getSetting('site_name', SITE_NAME)); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_tagline">Site Tagline</label>
                                <input type="text" class="form-control" id="site_tagline" name="site_tagline" 
                                       value="<?php echo htmlspecialchars(getSetting('site_tagline', 'Exchange Taka Globally')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="operator_status">Operator Status</label>
                                <select class="form-control" id="operator_status" name="operator_status">
                                    <option value="online" <?php echo getSetting('operator_status', 'online') === 'online' ? 'selected' : ''; ?>>Online</option>
                                    <option value="away" <?php echo getSetting('operator_status', 'online') === 'away' ? 'selected' : ''; ?>>Away</option>
                                    <option value="offline" <?php echo getSetting('operator_status', 'online') === 'offline' ? 'selected' : ''; ?>>Offline</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="working_hours">Working Hours</label>
                                <input type="text" class="form-control" id="working_hours" name="working_hours" 
                                       value="<?php echo htmlspecialchars(getSetting('working_hours', '9 am-11.50pm +6')); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="footer_copyright">Footer Copyright Text</label>
                                <input type="text" class="form-control" id="footer_copyright" name="footer_copyright" 
                                       value="<?php echo htmlspecialchars(getSetting('footer_copyright', '© ' . date('Y') . ' ' . getSetting('site_name', SITE_NAME) . '. All rights reserved.')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_notification_sound" name="enable_notification_sound" 
                                           <?php echo getSetting('enable_notification_sound', 'yes') === 'yes' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="enable_notification_sound">Enable Notification Sounds</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_popup_notice" name="enable_popup_notice" 
                                           <?php echo getSetting('enable_popup_notice', 'yes') === 'yes' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="enable_popup_notice">Enable Popup Notices</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Logo & Branding Settings -->
                <div class="tab-pane fade" id="branding" role="tabpanel" aria-labelledby="branding-tab">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Logo Type Selection -->
                            <div class="form-group">
                                <label>Logo Type</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="logo_type" id="logo_text" value="text" 
                                           <?php echo getSetting('logo_type', 'text') === 'text' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="logo_text">Text Logo Only</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="logo_type" id="logo_image" value="image" 
                                           <?php echo getSetting('logo_type', 'text') === 'image' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="logo_image">Image Logo Only</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="logo_type" id="logo_both" value="both" 
                                           <?php echo getSetting('logo_type', 'text') === 'both' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="logo_both">Image + Text Logo</label>
                                </div>
                            </div>
                            
                            <!-- Text Logo Settings -->
                            <div id="text-logo-settings" class="form-group">
                                <label for="site_logo_text">Logo Text</label>
                                <input type="text" class="form-control" id="site_logo_text" name="site_logo_text" 
                                       value="<?php echo htmlspecialchars(getSetting('site_logo_text', getSetting('site_name', SITE_NAME))); ?>">
                            </div>
                            
                            <!-- Image Logo Settings -->
                            <div id="image-logo-settings" class="form-group">
                                <label>Site Logo</label>
                                
                                <!-- Current Logo Display -->
                                <?php $currentLogo = getSetting('site_logo', ''); ?>
                                <?php if (!empty($currentLogo) && file_exists('../../' . $currentLogo)): ?>
                                <div class="current-logo mb-3 p-3 border rounded">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo SITE_URL . '/' . $currentLogo; ?>" 
                                             alt="Current Logo" 
                                             class="mr-3" 
                                             style="max-width: 100px; max-height: 50px; object-fit: contain;">
                                        <div class="flex-grow-1">
                                            <strong>Current Logo:</strong> <?php echo basename($currentLogo); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Logo Upload/Selection Tabs -->
                                <ul class="nav nav-tabs" id="logoTabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="upload-logo-tab" data-toggle="tab" href="#upload-logo" role="tab">
                                            <i class="fas fa-upload mr-1"></i> Upload New
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="media-logo-tab" data-toggle="tab" href="#media-logo" role="tab">
                                            <i class="fas fa-images mr-1"></i> Select from Media
                                        </a>
                                    </li>
                                </ul>
                                
                                <div class="tab-content mt-3">
                                    <!-- Upload New Logo -->
                                    <div class="tab-pane fade show active" id="upload-logo" role="tabpanel">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="site_logo_upload" name="site_logo_upload" accept="image/*">
                                            <label class="custom-file-label" for="site_logo_upload">Choose logo file</label>
                                        </div>
                                        <small class="form-text text-muted">Recommended size: 200x60 pixels. Supported formats: PNG, JPG, SVG</small>
                                    </div>
                                    
                                    <!-- Select from Media Library -->
                                    <div class="tab-pane fade" id="media-logo" role="tabpanel">
                                        <?php if (count($mediaFiles) > 0): ?>
                                        <div class="media-gallery" style="max-height: 200px; overflow-y: auto;">
                                            <div class="row">
                                                <?php foreach ($mediaFiles as $media): ?>
                                                <div class="col-md-2 col-sm-3 col-4 mb-2">
                                                    <div class="media-item-logo" data-media-path="<?php echo $media['file_path']; ?>" style="cursor: pointer; border: 2px solid transparent; border-radius: 8px; padding: 5px;">
                                                        <img src="<?php echo SITE_URL . '/' . $media['file_path']; ?>" 
                                                             alt="<?php echo htmlspecialchars($media['original_name']); ?>" 
                                                             class="img-fluid rounded" 
                                                             style="width: 100%; height: 40px; object-fit: cover;">
                                                        <small class="d-block text-center mt-1" style="font-size: 9px;"><?php echo htmlspecialchars(substr($media['original_name'], 0, 10)); ?></small>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <input type="hidden" name="selected_logo_media" id="selected_logo_media">
                                        <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            No media files found. <a href="../media/" target="_blank">Upload some images to the media library</a> first.
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Logo Positioning & Size -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="logo_position">Logo Position</label>
                                        <select class="form-control" id="logo_position" name="logo_position">
                                            <option value="left" <?php echo getSetting('logo_position', 'left') === 'left' ? 'selected' : ''; ?>>Left</option>
                                            <option value="center" <?php echo getSetting('logo_position', 'left') === 'center' ? 'selected' : ''; ?>>Center</option>
                                            <option value="right" <?php echo getSetting('logo_position', 'left') === 'right' ? 'selected' : ''; ?>>Right</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="logo_size">Logo Size</label>
                                        <select class="form-control" id="logo_size" name="logo_size">
                                            <option value="small" <?php echo getSetting('logo_size', 'medium') === 'small' ? 'selected' : ''; ?>>Small</option>
                                            <option value="medium" <?php echo getSetting('logo_size', 'medium') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                            <option value="large" <?php echo getSetting('logo_size', 'medium') === 'large' ? 'selected' : ''; ?>>Large</option>
                                            <option value="custom" <?php echo getSetting('logo_size', 'medium') === 'custom' ? 'selected' : ''; ?>>Custom</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Custom Logo Dimensions -->
                            <div id="custom-logo-size" class="row" style="display: <?php echo getSetting('logo_size', 'medium') === 'custom' ? 'block' : 'none'; ?>;">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="logo_max_width">Max Width (px)</label>
                                        <input type="number" class="form-control" id="logo_max_width" name="logo_max_width" 
                                               value="<?php echo getSetting('logo_max_width', 200); ?>" min="50" max="500">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="logo_max_height">Max Height (px)</label>
                                        <input type="number" class="form-control" id="logo_max_height" name="logo_max_height" 
                                               value="<?php echo getSetting('logo_max_height', 60); ?>" min="30" max="200">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Favicon Settings -->
                            <div class="form-group">
                                <label>Site Favicon</label>
                                
                                <!-- Current Favicon Display -->
                                <?php $currentFavicon = getSetting('site_favicon', ''); ?>
                                <?php if (!empty($currentFavicon) && file_exists('../../' . $currentFavicon)): ?>
                                <div class="current-favicon mb-3 p-3 border rounded">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo SITE_URL . '/' . $currentFavicon; ?>" 
                                             alt="Current Favicon" 
                                             class="mr-3" 
                                             style="width: 32px; height: 32px; object-fit: contain;">
                                        <div class="flex-grow-1">
                                            <strong>Current Favicon:</strong> <?php echo basename($currentFavicon); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Favicon Upload/Selection Tabs -->
                                <ul class="nav nav-tabs" id="faviconTabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="upload-favicon-tab" data-toggle="tab" href="#upload-favicon" role="tab">
                                            <i class="fas fa-upload mr-1"></i> Upload New
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="media-favicon-tab" data-toggle="tab" href="#media-favicon" role="tab">
                                            <i class="fas fa-images mr-1"></i> Select from Media
                                        </a>
                                    </li>
                                </ul>
                                
                                <div class="tab-content mt-3">
                                    <!-- Upload New Favicon -->
                                    <div class="tab-pane fade show active" id="upload-favicon" role="tabpanel">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="site_favicon_upload" name="site_favicon_upload" accept="image/*">
                                            <label class="custom-file-label" for="site_favicon_upload">Choose favicon file</label>
                                        </div>
                                        <small class="form-text text-muted">Recommended size: 32x32 pixels. Supported formats: PNG, ICO</small>
                                    </div>
                                    
                                    <!-- Select from Media Library -->
                                    <div class="tab-pane fade" id="media-favicon" role="tabpanel">
                                        <?php if (count($mediaFiles) > 0): ?>
                                        <div class="media-gallery" style="max-height: 200px; overflow-y: auto;">
                                            <div class="row">
                                                <?php foreach ($mediaFiles as $media): ?>
                                                <div class="col-md-2 col-sm-3 col-4 mb-2">
                                                    <div class="media-item-favicon" data-media-path="<?php echo $media['file_path']; ?>" style="cursor: pointer; border: 2px solid transparent; border-radius: 8px; padding: 5px;">
                                                        <img src="<?php echo SITE_URL . '/' . $media['file_path']; ?>" 
                                                             alt="<?php echo htmlspecialchars($media['original_name']); ?>" 
                                                             class="img-fluid rounded" 
                                                             style="width: 100%; height: 40px; object-fit: cover;">
                                                        <small class="d-block text-center mt-1" style="font-size: 9px;"><?php echo htmlspecialchars(substr($media['original_name'], 0, 10)); ?></small>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <input type="hidden" name="selected_favicon_media" id="selected_favicon_media">
                                        <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            No media files found. <a href="../media/" target="_blank">Upload some images to the media library</a> first.
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Logo Preview -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Logo Preview</label>
                                <div class="border rounded p-3 text-center" style="min-height: 150px; background: #f8f9fa;">
                                    <div id="logo-preview-container" style="display: flex; align-items: center; justify-content: center; height: 120px;">
                                        <div id="logo-preview">
                                            <!-- Dynamic preview will be inserted here -->
                                        </div>
                                    </div>
                                    <small class="text-muted">Logo Preview</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Colors & Appearance Settings -->
                <div class="tab-pane fade" id="appearance" role="tabpanel" aria-labelledby="appearance-tab">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Note:</strong> Color changes will be applied to the frontend design. Make sure the colors provide good contrast for accessibility.
                    </div>
                    
                    <!-- Primary Colors -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-palette mr-2"></i>Primary Colors</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="primary_color">Primary Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control" id="primary_color" name="primary_color" 
                                                   value="<?php echo getSetting('primary_color', '#5D5CDE'); ?>" style="height: 40px;">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><?php echo getSetting('primary_color', '#5D5CDE'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="secondary_color">Secondary Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control" id="secondary_color" name="secondary_color" 
                                                   value="<?php echo getSetting('secondary_color', '#6c757d'); ?>" style="height: 40px;">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><?php echo getSetting('secondary_color', '#6c757d'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="header_color">Header Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control" id="header_color" name="header_color" 
                                                   value="<?php echo getSetting('header_color', '#1E3A8A'); ?>" style="height: 40px;">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><?php echo getSetting('header_color', '#1E3A8A'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="footer_color">Footer Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control" id="footer_color" name="footer_color" 
                                                   value="<?php echo getSetting('footer_color', '#1E3A8A'); ?>" style="height: 40px;">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><?php echo getSetting('footer_color', '#1E3A8A'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Colors -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-exclamation-circle mr-2"></i>Status Colors</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="success_color">Success Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control" id="success_color" name="success_color" 
                                                   value="<?php echo getSetting('success_color', '#28a745'); ?>" style="height: 40px;">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><?php echo getSetting('success_color', '#28a745'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="warning_color">Warning Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control" id="warning_color" name="warning_color" 
                                                   value="<?php echo getSetting('warning_color', '#ffc107'); ?>" style="height: 40px;">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><?php echo getSetting('warning_color', '#ffc107'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="danger_color">Danger Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control" id="danger_color" name="danger_color" 
                                                   value="<?php echo getSetting('danger_color', '#dc3545'); ?>" style="height: 40px;">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><?php echo getSetting('danger_color', '#dc3545'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="info_color">Info Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control" id="info_color" name="info_color" 
                                                   value="<?php echo getSetting('info_color', '#17a2b8'); ?>" style="height: 40px;">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><?php echo getSetting('info_color', '#17a2b8'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Background & Text Colors -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-font mr-2"></i>Background & Text Colors</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="body_bg_color">Body Background</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control" id="body_bg_color" name="body_bg_color" 
                                                   value="<?php echo getSetting('body_bg_color', '#ffffff'); ?>" style="height: 40px;">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><?php echo getSetting('body_bg_color', '#ffffff'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="text_color">Text Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control" id="text_color" name="text_color" 
                                                   value="<?php echo getSetting('text_color', '#212529'); ?>" style="height: 40px;">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><?php echo getSetting('text_color', '#212529'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="link_color">Link Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control" id="link_color" name="link_color" 
                                                   value="<?php echo getSetting('link_color', '#007bff'); ?>" style="height: 40px;">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><?php echo getSetting('link_color', '#007bff'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="border_color">Border Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control" id="border_color" name="border_color" 
                                                   value="<?php echo getSetting('border_color', '#dee2e6'); ?>" style="height: 40px;">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><?php echo getSetting('border_color', '#dee2e6'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Colors -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-swatchbook mr-2"></i>Additional Colors</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="light_color">Light Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control" id="light_color" name="light_color" 
                                                   value="<?php echo getSetting('light_color', '#f8f9fa'); ?>" style="height: 40px;">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><?php echo getSetting('light_color', '#f8f9fa'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="dark_color">Dark Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control" id="dark_color" name="dark_color" 
                                                   value="<?php echo getSetting('dark_color', '#343a40'); ?>" style="height: 40px;">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><?php echo getSetting('dark_color', '#343a40'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation Settings -->
                <div class="tab-pane fade" id="navigation" role="tabpanel" aria-labelledby="navigation-tab">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nav_style">Navigation Style</label>
                                <select class="form-control" id="nav_style" name="nav_style">
                                    <option value="horizontal" <?php echo getSetting('nav_style', 'horizontal') === 'horizontal' ? 'selected' : ''; ?>>Horizontal</option>
                                    <option value="vertical" <?php echo getSetting('nav_style', 'horizontal') === 'vertical' ? 'selected' : ''; ?>>Vertical</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="nav_position">Navigation Position</label>
                                <select class="form-control" id="nav_position" name="nav_position">
                                    <option value="top" <?php echo getSetting('nav_position', 'top') === 'top' ? 'selected' : ''; ?>>Top</option>
                                    <option value="bottom" <?php echo getSetting('nav_position', 'top') === 'bottom' ? 'selected' : ''; ?>>Bottom</option>
                                    <option value="left" <?php echo getSetting('nav_position', 'top') === 'left' ? 'selected' : ''; ?>>Left Sidebar</option>
                                    <option value="right" <?php echo getSetting('nav_position', 'top') === 'right' ? 'selected' : ''; ?>>Right Sidebar</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="show_breadcrumbs" name="show_breadcrumbs" 
                                           <?php echo getSetting('show_breadcrumbs', 'yes') === 'yes' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="show_breadcrumbs">Show Breadcrumbs</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="custom_menu_items">Custom Menu Items (JSON Format)</label>
                                <textarea class="form-control" id="custom_menu_items" name="custom_menu_items" rows="10" 
                                          placeholder='[
  {
    "title": "Services",
    "url": "services.php",
    "icon": "fas fa-cog",
    "target": "_self"
  },
  {
    "title": "Help",
    "url": "help.php", 
    "icon": "fas fa-question-circle",
    "target": "_self"
  }
]'><?php echo htmlspecialchars(getSetting('custom_menu_items', '')); ?></textarea>
                                <small class="form-text text-muted">
                                    Add custom menu items in JSON format. Each item should have: title, url, icon (optional), target (optional).
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Navigation Tips:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Use relative URLs for internal pages (e.g., "about.php")</li>
                            <li>Use absolute URLs for external links (e.g., "https://example.com")</li>
                            <li>Icons should be FontAwesome classes (e.g., "fas fa-home")</li>
                            <li>Target can be "_self" (same window) or "_blank" (new window)</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Contact Info Settings -->
                <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contact_phone">Contact Phone</label>
                                <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                                       value="<?php echo htmlspecialchars(getSetting('contact_phone', '')); ?>" 
                                       placeholder="+1234567890">
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_whatsapp">WhatsApp Number</label>
                                <input type="tel" class="form-control" id="contact_whatsapp" name="contact_whatsapp" 
                                       value="<?php echo htmlspecialchars(getSetting('contact_whatsapp', '')); ?>" 
                                       placeholder="+1234567890">
                                <small class="form-text text-muted">Include country code (e.g., +1234567890)</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contact_email">Contact Email</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                       value="<?php echo htmlspecialchars(getSetting('contact_email', '')); ?>" 
                                       placeholder="info@example.com">
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_address">Business Address</label>
                                <textarea class="form-control" id="contact_address" name="contact_address" rows="3" 
                                          placeholder="Enter your business address"><?php echo htmlspecialchars(getSetting('contact_address', '')); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Social Media Settings -->
                <div class="tab-pane fade" id="social" role="tabpanel" aria-labelledby="social-tab">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="social_facebook">Facebook Page URL</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fab fa-facebook-f"></i></span>
                                    </div>
                                    <input type="url" class="form-control" id="social_facebook" name="social_facebook" 
                                           value="<?php echo htmlspecialchars(getSetting('social_facebook', '')); ?>" 
                                           placeholder="https://facebook.com/yourpage">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="social_twitter">Twitter Profile URL</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                                    </div>
                                    <input type="url" class="form-control" id="social_twitter" name="social_twitter" 
                                           value="<?php echo htmlspecialchars(getSetting('social_twitter', '')); ?>" 
                                           placeholder="https://twitter.com/yourusername">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="social_instagram">Instagram Profile URL</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                    </div>
                                    <input type="url" class="form-control" id="social_instagram" name="social_instagram" 
                                           value="<?php echo htmlspecialchars(getSetting('social_instagram', '')); ?>" 
                                           placeholder="https://instagram.com/yourusername">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="social_telegram">Telegram Channel/Group URL</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fab fa-telegram-plane"></i></span>
                                    </div>
                                    <input type="url" class="form-control" id="social_telegram" name="social_telegram" 
                                           value="<?php echo htmlspecialchars(getSetting('social_telegram', '')); ?>" 
                                           placeholder="https://t.me/yourchannel">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="social_linkedin">LinkedIn Profile URL</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fab fa-linkedin-in"></i></span>
                                    </div>
                                    <input type="url" class="form-control" id="social_linkedin" name="social_linkedin" 
                                           value="<?php echo htmlspecialchars(getSetting('social_linkedin', '')); ?>" 
                                           placeholder="https://linkedin.com/company/yourcompany">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="social_youtube">YouTube Channel URL</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fab fa-youtube"></i></span>
                                    </div>
                                    <input type="url" class="form-control" id="social_youtube" name="social_youtube" 
                                           value="<?php echo htmlspecialchars(getSetting('social_youtube', '')); ?>" 
                                           placeholder="https://youtube.com/c/yourchannel">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Note:</strong> These social media links will appear in your website footer and contact sections.
                    </div>
                </div>
                
                <!-- Features Settings -->
                <div class="tab-pane fade" id="features" role="tabpanel" aria-labelledby="features-tab">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>WhatsApp Integration</h5>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_whatsapp_button" name="enable_whatsapp_button" 
                                           <?php echo getSetting('enable_whatsapp_button', 'yes') === 'yes' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="enable_whatsapp_button">Enable WhatsApp Button</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="whatsapp_number">WhatsApp Number</label>
                                <input type="tel" class="form-control" id="whatsapp_number" name="whatsapp_number" 
                                       value="<?php echo htmlspecialchars(getSetting('whatsapp_number', '')); ?>" 
                                       placeholder="+1234567890">
                                <small class="form-text text-muted">Include country code without + sign</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="whatsapp_message">Default WhatsApp Message</label>
                                <textarea class="form-control" id="whatsapp_message" name="whatsapp_message" rows="3" 
                                          placeholder="Hello! I need help with..."><?php echo htmlspecialchars(getSetting('whatsapp_message', 'Hello! I need help with...')); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Live Chat Integration</h5>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_tawkto" name="enable_tawkto" 
                                           <?php echo getSetting('enable_tawkto', 'no') === 'yes' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="enable_tawkto">Enable Tawk.to Live Chat</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="tawkto_widget_code">Tawk.to Widget Code</label>
                                <input type="text" class="form-control" id="tawkto_widget_code" name="tawkto_widget_code" 
                                       value="<?php echo htmlspecialchars(getSetting('tawkto_widget_code', '')); ?>" 
                                       placeholder="6869a98370e7fd1919383828/1ivebsat9">
                                <small class="form-text text-muted">Enter only the widget ID (e.g., 6869a98370e7fd1919383828/1ivebsat9)</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Advanced Settings -->
                <div class="tab-pane fade" id="advanced" role="tabpanel" aria-labelledby="advanced-tab">
                    <!-- SEO Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-search mr-2"></i>SEO Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="meta_description">Meta Description</label>
                                        <textarea class="form-control" id="meta_description" name="meta_description" rows="3" 
                                                  maxlength="160" placeholder="Site description for search engines..."><?php echo htmlspecialchars(getSetting('meta_description', '')); ?></textarea>
                                        <small class="form-text text-muted">Max 160 characters</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="meta_keywords">Meta Keywords</label>
                                        <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" 
                                               value="<?php echo htmlspecialchars(getSetting('meta_keywords', '')); ?>" 
                                               placeholder="keyword1, keyword2, keyword3">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="google_site_verification">Google Site Verification</label>
                                        <input type="text" class="form-control" id="google_site_verification" name="google_site_verification" 
                                               value="<?php echo htmlspecialchars(getSetting('google_site_verification', '')); ?>" 
                                               placeholder="Google verification code">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Analytics & Tracking -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-line mr-2"></i>Analytics & Tracking</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="google_analytics_code">Google Analytics Code</label>
                                        <textarea class="form-control" id="google_analytics_code" name="google_analytics_code" 
                                                  rows="8" placeholder="Paste your complete Google Analytics tracking code here including script tags"><?php echo htmlspecialchars(getSetting('google_analytics_code', '')); ?></textarea>
                                        <small class="form-text text-muted">
                                            <strong>Option 1:</strong> Paste the complete Google Analytics code including script tags<br>
                                            <strong>Option 2:</strong> Use the Google Analytics ID field below for automatic code generation
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="google_analytics_id">Google Analytics ID (Alternative)</label>
                                        <input type="text" class="form-control" id="google_analytics_id" name="google_analytics_id" 
                                               value="<?php echo htmlspecialchars(getSetting('google_analytics_id', '')); ?>" 
                                               placeholder="G-XXXXXXXXXX or UA-XXXXXXXX-X">
                                        <small class="form-text text-muted">Only use this if you didn't paste the complete code above</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="facebook_pixel_id">Facebook Pixel ID</label>
                                        <input type="text" class="form-control" id="facebook_pixel_id" name="facebook_pixel_id" 
                                               value="<?php echo htmlspecialchars(getSetting('facebook_pixel_id', '')); ?>" 
                                               placeholder="1234567890123456">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="custom_tracking_code">Custom Tracking Code</label>
                                        <textarea class="form-control" id="custom_tracking_code" name="custom_tracking_code" rows="5" 
                                                  placeholder="Additional tracking scripts..."><?php echo htmlspecialchars(getSetting('custom_tracking_code', '')); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-shield-alt mr-2"></i>Security Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="maintenance_mode" name="maintenance_mode" 
                                                   <?php echo getSetting('maintenance_mode', 'no') === 'yes' ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="maintenance_mode">Maintenance Mode</label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="maintenance_message">Maintenance Message</label>
                                        <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="3" 
                                                  placeholder="Site is under maintenance..."><?php echo htmlspecialchars(getSetting('maintenance_message', 'Site is under maintenance. Please check back later.')); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="enable_captcha" name="enable_captcha" 
                                                   <?php echo getSetting('enable_captcha', 'no') === 'yes' ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="enable_captcha">Enable reCAPTCHA</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="recaptcha_site_key">reCAPTCHA Site Key</label>
                                        <input type="text" class="form-control" id="recaptcha_site_key" name="recaptcha_site_key" 
                                               value="<?php echo htmlspecialchars(getSetting('recaptcha_site_key', '')); ?>" 
                                               placeholder="6Lc...">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="recaptcha_secret_key">reCAPTCHA Secret Key</label>
                                        <input type="password" class="form-control" id="recaptcha_secret_key" name="recaptcha_secret_key" 
                                               value="<?php echo htmlspecialchars(getSetting('recaptcha_secret_key', '')); ?>" 
                                               placeholder="6Lc...">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Email Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-envelope mr-2"></i>Email Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email_method">Email Method</label>
                                        <select class="form-control" id="email_method" name="email_method">
                                            <option value="mail" <?php echo getSetting('email_method', 'mail') === 'mail' ? 'selected' : ''; ?>>PHP Mail</option>
                                            <option value="smtp" <?php echo getSetting('email_method', 'mail') === 'smtp' ? 'selected' : ''; ?>>SMTP</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email_from_name">From Name</label>
                                        <input type="text" class="form-control" id="email_from_name" name="email_from_name" 
                                               value="<?php echo htmlspecialchars(getSetting('email_from_name', getSetting('site_name', SITE_NAME))); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email_from_address">From Email Address</label>
                                        <input type="email" class="form-control" id="email_from_address" name="email_from_address" 
                                               value="<?php echo htmlspecialchars(getSetting('email_from_address', '')); ?>" 
                                               placeholder="noreply@example.com">
                                    </div>
                                </div>
                                
                                <div class="col-md-6" id="smtp-settings" style="display: <?php echo getSetting('email_method', 'mail') === 'smtp' ? 'block' : 'none'; ?>;">
                                    <div class="form-group">
                                        <label for="smtp_host">SMTP Host</label>
                                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                               value="<?php echo htmlspecialchars(getSetting('smtp_host', '')); ?>" 
                                               placeholder="smtp.gmail.com">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="smtp_port">SMTP Port</label>
                                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                               value="<?php echo getSetting('smtp_port', '587'); ?>" placeholder="587">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="smtp_encryption">SMTP Encryption</label>
                                        <select class="form-control" id="smtp_encryption" name="smtp_encryption">
                                            <option value="tls" <?php echo getSetting('smtp_encryption', 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                            <option value="ssl" <?php echo getSetting('smtp_encryption', 'tls') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            <option value="none" <?php echo getSetting('smtp_encryption', 'tls') === 'none' ? 'selected' : ''; ?>>None</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="smtp_username">SMTP Username</label>
                                        <input type="text" class="form-control" id="smtp_username" name="smtp_username" 
                                               value="<?php echo htmlspecialchars(getSetting('smtp_username', '')); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="smtp_password">SMTP Password</label>
                                        <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                               value="<?php echo htmlspecialchars(getSetting('smtp_password', '')); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Custom Scripts -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-code mr-2"></i>Custom Scripts & CSS</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="header_scripts">Header Scripts</label>
                                        <textarea class="form-control" id="header_scripts" name="header_scripts" rows="5" 
                                                  placeholder="<script>...</script>"><?php echo htmlspecialchars(getSetting('header_scripts', '')); ?></textarea>
                                        <small class="form-text text-muted">Scripts to load in &lt;head&gt;</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="footer_scripts">Footer Scripts</label>
                                        <textarea class="form-control" id="footer_scripts" name="footer_scripts" rows="5" 
                                                  placeholder="<script>...</script>"><?php echo htmlspecialchars(getSetting('footer_scripts', '')); ?></textarea>
                                        <small class="form-text text-muted">Scripts to load before &lt;/body&gt;</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="custom_css">Custom CSS</label>
                                        <textarea class="form-control" id="custom_css" name="custom_css" rows="5" 
                                                  placeholder=".custom-class { ... }"><?php echo htmlspecialchars(getSetting('custom_css', '')); ?></textarea>
                                        <small class="form-text text-muted">Custom CSS styles</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Developer Settings -->
                    <?php if ($isAdmin): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-bug mr-2"></i>Developer Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="debug_mode" name="debug_mode" 
                                                   <?php echo getSetting('debug_mode', 'no') === 'yes' ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="debug_mode">Debug Mode</label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="log_queries" name="log_queries" 
                                                   <?php echo getSetting('log_queries', 'no') === 'yes' ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="log_queries">Log Database Queries</label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="enable_profiler" name="enable_profiler" 
                                                   <?php echo getSetting('enable_profiler', 'no') === 'yes' ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="enable_profiler">Enable Performance Profiler</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="log_level">Log Level</label>
                                        <select class="form-control" id="log_level" name="log_level">
                                            <option value="error" <?php echo getSetting('log_level', 'error') === 'error' ? 'selected' : ''; ?>>Error Only</option>
                                            <option value="warning" <?php echo getSetting('log_level', 'error') === 'warning' ? 'selected' : ''; ?>>Warning & Error</option>
                                            <option value="info" <?php echo getSetting('log_level', 'error') === 'info' ? 'selected' : ''; ?>>Info, Warning & Error</option>
                                            <option value="debug" <?php echo getSetting('log_level', 'error') === 'debug' ? 'selected' : ''; ?>>All Logs</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary btn-lg" id="saveButton">
                    <i class="fas fa-save mr-1"></i> Save Settings
                </button>
                <button type="button" class="btn btn-secondary ml-2" onclick="location.reload()">
                    <i class="fas fa-undo mr-1"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.media-gallery {
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 10px;
}

.media-item-logo:hover,
.media-item-favicon:hover {
    border-color: #007bff !important;
    background-color: #f8f9fa;
}

.media-item-logo.selected,
.media-item-favicon.selected {
    border-color: #28a745 !important;
    background-color: #d4edda;
}

.current-logo,
.current-favicon {
    background-color: #f8f9fa;
}

#logo-preview {
    display: flex;
    align-items: center;
    gap: 10px;
}

#logo-preview img {
    max-width: 150px;
    max-height: 60px;
    object-fit: contain;
}

#logo-preview .logo-text {
    font-size: 18px;
    font-weight: bold;
    color: #333;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Logo type change handler
    document.querySelectorAll('input[name="logo_type"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            updateLogoVisibility();
            updateLogoPreview();
        });
    });
    
    // Logo size change handler
    document.getElementById('logo_size').addEventListener('change', function() {
        if (this.value === 'custom') {
            document.getElementById('custom-logo-size').style.display = 'block';
        } else {
            document.getElementById('custom-logo-size').style.display = 'none';
        }
        updateLogoPreview();
    });
    
    // Media library selection for logo
    document.querySelectorAll('.media-item-logo').forEach(function(item) {
        item.addEventListener('click', function() {
            document.querySelectorAll('.media-item-logo').forEach(function(i) {
                i.classList.remove('selected');
            });
            this.classList.add('selected');
            document.getElementById('selected_logo_media').value = this.dataset.mediaPath;
            
            // Clear file upload
            document.getElementById('site_logo_upload').value = '';
            document.querySelector('label[for="site_logo_upload"]').textContent = 'Choose logo file';
            
            updateLogoPreview();
        });
    });
    
    // Media library selection for favicon
    document.querySelectorAll('.media-item-favicon').forEach(function(item) {
        item.addEventListener('click', function() {
            document.querySelectorAll('.media-item-favicon').forEach(function(i) {
                i.classList.remove('selected');
            });
            this.classList.add('selected');
            document.getElementById('selected_favicon_media').value = this.dataset.mediaPath;
            
            // Clear file upload
            document.getElementById('site_favicon_upload').value = '';
            document.querySelector('label[for="site_favicon_upload"]').textContent = 'Choose favicon file';
        });
    });
    
    // File upload handlers
    document.getElementById('site_logo_upload').addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            var fileName = e.target.files[0].name;
            var nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
            
            // Clear media selection
            document.querySelectorAll('.media-item-logo').forEach(function(i) {
                i.classList.remove('selected');
            });
            document.getElementById('selected_logo_media').value = '';
            
            // Preview uploaded image
            var reader = new FileReader();
            reader.onload = function(e) {
                updateLogoPreview(e.target.result);
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });
    
    document.getElementById('site_favicon_upload').addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            var fileName = e.target.files[0].name;
            var nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
            
            // Clear media selection
            document.querySelectorAll('.media-item-favicon').forEach(function(i) {
                i.classList.remove('selected');
            });
            document.getElementById('selected_favicon_media').value = '';
        }
    });
    
    // Text logo input handler
    document.getElementById('site_logo_text').addEventListener('input', function() {
        updateLogoPreview();
    });
    
    // Color picker change handlers
    document.querySelectorAll('input[type="color"]').forEach(function(colorInput) {
        colorInput.addEventListener('change', function() {
            const span = this.parentElement.querySelector('.input-group-text');
            if (span) {
                span.textContent = this.value;
            }
        });
    });
    
    // Toggle SMTP settings visibility
    document.getElementById('email_method').addEventListener('change', function() {
        var smtpSettings = document.getElementById('smtp-settings');
        if (this.value === 'smtp') {
            smtpSettings.style.display = 'block';
        } else {
            smtpSettings.style.display = 'none';
        }
    });
    
    // Initial setup
    updateLogoVisibility();
    updateLogoPreview();
    
    function updateLogoVisibility() {
        const logoType = document.querySelector('input[name="logo_type"]:checked').value;
        const textSettings = document.getElementById('text-logo-settings');
        const imageSettings = document.getElementById('image-logo-settings');
        
        switch(logoType) {
            case 'text':
                textSettings.style.display = 'block';
                imageSettings.style.display = 'none';
                break;
            case 'image':
                textSettings.style.display = 'none';
                imageSettings.style.display = 'block';
                break;
            case 'both':
                textSettings.style.display = 'block';
                imageSettings.style.display = 'block';
                break;
        }
    }
    
    function updateLogoPreview(uploadedImage = null) {
        const logoType = document.querySelector('input[name="logo_type"]:checked').value;
        const logoText = document.getElementById('site_logo_text').value || '<?php echo getSetting("site_name", SITE_NAME); ?>';
        const selectedMedia = document.getElementById('selected_logo_media').value;
        const logoSize = document.getElementById('logo_size').value;
        const preview = document.getElementById('logo-preview');
        
        let logoHtml = '';
        
        // Determine logo size
        let logoSizeStyle = '';
        switch(logoSize) {
            case 'small':
                logoSizeStyle = 'max-width: 120px; max-height: 40px;';
                break;
            case 'medium':
                logoSizeStyle = 'max-width: 200px; max-height: 60px;';
                break;
            case 'large':
                logoSizeStyle = 'max-width: 300px; max-height: 80px;';
                break;
            case 'custom':
                const maxWidth = document.getElementById('logo_max_width').value || 200;
                const maxHeight = document.getElementById('logo_max_height').value || 60;
                logoSizeStyle = `max-width: ${maxWidth}px; max-height: ${maxHeight}px;`;
                break;
        }
        
        if (logoType === 'text') {
            logoHtml = `<div class="logo-text">${logoText}</div>`;
        } else if (logoType === 'image') {
            if (uploadedImage) {
                logoHtml = `<img src="${uploadedImage}" style="${logoSizeStyle} object-fit: contain;">`;
            } else if (selectedMedia) {
                logoHtml = `<img src="<?php echo SITE_URL; ?>/${selectedMedia}" style="${logoSizeStyle} object-fit: contain;">`;
            } else {
                const currentLogo = '<?php echo getSetting("site_logo", ""); ?>';
                if (currentLogo) {
                    logoHtml = `<img src="<?php echo SITE_URL; ?>/${currentLogo}" style="${logoSizeStyle} object-fit: contain;">`;
                } else {
                    logoHtml = '<div class="text-muted">No logo selected</div>';
                }
            }
        } else if (logoType === 'both') {
            let imageHtml = '';
            if (uploadedImage) {
                imageHtml = `<img src="${uploadedImage}" style="${logoSizeStyle} object-fit: contain;">`;
            } else if (selectedMedia) {
                imageHtml = `<img src="<?php echo SITE_URL; ?>/${selectedMedia}" style="${logoSizeStyle} object-fit: contain;">`;
            } else {
                const currentLogo = '<?php echo getSetting("site_logo", ""); ?>';
                if (currentLogo) {
                    imageHtml = `<img src="<?php echo SITE_URL; ?>/${currentLogo}" style="${logoSizeStyle} object-fit: contain;">`;
                }
            }
            logoHtml = `${imageHtml}<div class="logo-text ml-2">${logoText}</div>`;
        }
        
        preview.innerHTML = logoHtml;
    }
});

// Form validation
function validateForm() {
    // Show loading state
    const saveButton = document.getElementById('saveButton');
    saveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
    saveButton.disabled = true;
    
    // Basic validation
    const siteName = document.getElementById('site_name').value.trim();
    if (!siteName) {
        showAlert('Site name is required!');
        saveButton.innerHTML = '<i class="fas fa-save mr-1"></i> Save Settings';
        saveButton.disabled = false;
        return false;
    }
    
    // Validate email if provided
    const contactEmail = document.getElementById('contact_email').value.trim();
    if (contactEmail && !isValidEmail(contactEmail)) {
        showAlert('Please enter a valid contact email address!');
        saveButton.innerHTML = '<i class="fas fa-save mr-1"></i> Save Settings';
        saveButton.disabled = false;
        return false;
    }
    
    // Validate WhatsApp number if WhatsApp button is enabled
    const whatsappEnabled = document.getElementById('enable_whatsapp_button').checked;
    const whatsappNumber = document.getElementById('whatsapp_number').value.trim();
    if (whatsappEnabled && !whatsappNumber) {
        showAlert('WhatsApp number is required when WhatsApp button is enabled!');
        saveButton.innerHTML = '<i class="fas fa-save mr-1"></i> Save Settings';
        saveButton.disabled = false;
        return false;
    }
    
    // Validate JSON for custom menu items
    const customMenuItems = document.getElementById('custom_menu_items').value.trim();
    if (customMenuItems) {
        try {
            JSON.parse(customMenuItems);
        } catch (e) {
            showAlert('Custom menu items must be valid JSON format!');
            saveButton.innerHTML = '<i class="fas fa-save mr-1"></i> Save Settings';
            saveButton.disabled = false;
            return false;
        }
    }
    
    return true;
}

// Custom alert function
function showAlert(message) {
    const modal = document.createElement('div');
    modal.className = 'fixed-top';
    modal.innerHTML = `
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Alert</h5>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="this.closest('.fixed-top').remove()">OK</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Email validation helper
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Auto-save notification
let changesMade = false;
document.getElementById('settingsForm').addEventListener('input', function() {
    changesMade = true;
});

// Warn user about unsaved changes
window.addEventListener('beforeunload', function(e) {
    if (changesMade) {
        const confirmationMessage = 'You have unsaved changes. Are you sure you want to leave?';
        e.returnValue = confirmationMessage;
        return confirmationMessage;
    }
});

// Reset changes flag on successful save
document.getElementById('settingsForm').addEventListener('submit', function() {
    changesMade = false;
});

console.log('Settings form initialized');
</script>

<?php include '../includes/footer.php'; ?>