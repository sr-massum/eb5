<?php 

/**
 * ExchangeBridge - Admin Panel Receipt
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

// Check if user has admin role
$isAdmin = Auth::isAdmin();

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

// Handle logo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['receipt_logo']) && $_FILES['receipt_logo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../../assets/uploads/media/';
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileType = $_FILES['receipt_logo']['type'];
    $fileSize = $_FILES['receipt_logo']['size'];
    
    if (in_array($fileType, $allowedTypes) && $fileSize <= 5000000) { // 5MB max
        $fileName = 'receipt_logo_' . time() . '.' . pathinfo($_FILES['receipt_logo']['name'], PATHINFO_EXTENSION);
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['receipt_logo']['tmp_name'], $uploadPath)) {
            updateSetting('receipt_logo_path', $fileName);
            updateSetting('receipt_logo_enabled', 'yes');
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process each setting
    $settings = [
        // Header Settings
        'receipt_site_name' => isset($_POST['receipt_site_name']) ? sanitizeInput($_POST['receipt_site_name']) : '',
        'receipt_site_tagline' => isset($_POST['receipt_site_tagline']) ? sanitizeInput($_POST['receipt_site_tagline']) : '',
        'receipt_logo_enabled' => isset($_POST['receipt_logo_enabled']) ? 'yes' : 'no',
        
        // Footer Settings
        'receipt_website' => isset($_POST['receipt_website']) ? sanitizeInput($_POST['receipt_website']) : '',
        'receipt_email' => isset($_POST['receipt_email']) ? sanitizeInput($_POST['receipt_email']) : '',
        'receipt_phone' => isset($_POST['receipt_phone']) ? sanitizeInput($_POST['receipt_phone']) : '',
        
        // Color Settings
        'receipt_bg_color' => isset($_POST['receipt_bg_color']) ? sanitizeInput($_POST['receipt_bg_color']) : '',
        'receipt_header_color' => isset($_POST['receipt_header_color']) ? sanitizeInput($_POST['receipt_header_color']) : '',
        'receipt_footer_color' => isset($_POST['receipt_footer_color']) ? sanitizeInput($_POST['receipt_footer_color']) : '',
        'receipt_section_color' => isset($_POST['receipt_section_color']) ? sanitizeInput($_POST['receipt_section_color']) : '',
        'receipt_accent_color' => isset($_POST['receipt_accent_color']) ? sanitizeInput($_POST['receipt_accent_color']) : '',
        'receipt_content_bg_color' => isset($_POST['receipt_content_bg_color']) ? sanitizeInput($_POST['receipt_content_bg_color']) : '',
        'receipt_watermark_color' => isset($_POST['receipt_watermark_color']) ? sanitizeInput($_POST['receipt_watermark_color']) : '',
        
        // Text Settings
        'receipt_watermark_text' => isset($_POST['receipt_watermark_text']) ? sanitizeInput($_POST['receipt_watermark_text']) : '',
        'receipt_customer_details_title' => isset($_POST['receipt_customer_details_title']) ? sanitizeInput($_POST['receipt_customer_details_title']) : '',
        'receipt_exchange_details_title' => isset($_POST['receipt_exchange_details_title']) ? sanitizeInput($_POST['receipt_exchange_details_title']) : '',
        
        // Labels
        'receipt_name_label' => isset($_POST['receipt_name_label']) ? sanitizeInput($_POST['receipt_name_label']) : '',
        'receipt_email_label' => isset($_POST['receipt_email_label']) ? sanitizeInput($_POST['receipt_email_label']) : '',
        'receipt_phone_label' => isset($_POST['receipt_phone_label']) ? sanitizeInput($_POST['receipt_phone_label']) : '',
        'receipt_address_label' => isset($_POST['receipt_address_label']) ? sanitizeInput($_POST['receipt_address_label']) : '',
        'receipt_from_label' => isset($_POST['receipt_from_label']) ? sanitizeInput($_POST['receipt_from_label']) : '',
        'receipt_to_label' => isset($_POST['receipt_to_label']) ? sanitizeInput($_POST['receipt_to_label']) : '',
        'receipt_rate_label' => isset($_POST['receipt_rate_label']) ? sanitizeInput($_POST['receipt_rate_label']) : '',
        'receipt_sent_label' => isset($_POST['receipt_sent_label']) ? sanitizeInput($_POST['receipt_sent_label']) : '',
        'receipt_received_label' => isset($_POST['receipt_received_label']) ? sanitizeInput($_POST['receipt_received_label']) : '',
        'receipt_website_label' => isset($_POST['receipt_website_label']) ? sanitizeInput($_POST['receipt_website_label']) : '',
        'receipt_email_footer_label' => isset($_POST['receipt_email_footer_label']) ? sanitizeInput($_POST['receipt_email_footer_label']) : '',
        'receipt_phone_footer_label' => isset($_POST['receipt_phone_footer_label']) ? sanitizeInput($_POST['receipt_phone_footer_label']) : '',
    ];
    
    // Update each setting
    $db = Database::getInstance();
    $success = true;
    
    foreach ($settings as $key => $value) {
        if (!updateSetting($key, $value)) {
            $success = false;
        }
    }
    
    if ($success) {
        $_SESSION['success_message'] = 'Receipt settings updated successfully';
    } else {
        $_SESSION['error_message'] = 'Failed to update some settings';
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
    <li class="breadcrumb-item active">Receipt Settings</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-receipt mr-1"></i> Receipt Settings
    </div>
    <div class="card-body">
        <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $successMessage; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $errorMessage; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <form action="index.php" method="post" enctype="multipart/form-data">
            <ul class="nav nav-tabs" id="receiptTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="header-tab" data-toggle="tab" href="#header" role="tab" aria-controls="header" aria-selected="true">
                        <i class="fas fa-heading mr-1"></i> Header Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="footer-tab" data-toggle="tab" href="#footer" role="tab" aria-controls="footer" aria-selected="false">
                        <i class="fas fa-info-circle mr-1"></i> Footer Info
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="colors-tab" data-toggle="tab" href="#colors" role="tab" aria-controls="colors" aria-selected="false">
                        <i class="fas fa-palette mr-1"></i> Colors
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="labels-tab" data-toggle="tab" href="#labels" role="tab" aria-controls="labels" aria-selected="false">
                        <i class="fas fa-tags mr-1"></i> Labels & Text
                    </a>
                </li>
            </ul>
            
            <div class="tab-content mt-4" id="receiptTabContent">
                <!-- Header Settings -->
                <div class="tab-pane fade show active" id="header" role="tabpanel" aria-labelledby="header-tab">
                    <h5 class="mb-3"><i class="fas fa-heading text-primary mr-2"></i>Header Settings</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="receipt_site_name">Site Name</label>
                                <input type="text" class="form-control" id="receipt_site_name" name="receipt_site_name" value="<?php echo htmlspecialchars(getSetting('receipt_site_name', getSetting('site_name', SITE_NAME))); ?>">
                                <small class="form-text text-muted">Company/Site name shown in receipt header</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="receipt_site_tagline">Site Tagline</label>
                                <input type="text" class="form-control" id="receipt_site_tagline" name="receipt_site_tagline" value="<?php echo htmlspecialchars(getSetting('receipt_site_tagline', getSetting('site_tagline', 'Exchange Taka Globally'))); ?>">
                                <small class="form-text text-muted">Tagline shown below the site name</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="receipt_logo">Logo Upload</label>
                                <input type="file" class="form-control-file" id="receipt_logo" name="receipt_logo" accept="image/*">
                                <small class="form-text text-muted">Upload a logo image (max 5MB). Recommended size: 48x48px or larger</small>
                                <?php if (getSetting('receipt_logo_enabled', 'no') === 'yes' && !empty(getSetting('receipt_logo_path'))): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo SITE_URL; ?>/assets/uploads/media/<?php echo getSetting('receipt_logo_path'); ?>" alt="Current Logo" class="img-thumbnail" style="max-width: 100px;">
                                        <p class="text-muted">Current logo</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="receipt_logo_enabled" name="receipt_logo_enabled" <?php echo getSetting('receipt_logo_enabled', 'no') === 'yes' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="receipt_logo_enabled">Enable Logo (Use logo instead of icon)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer Settings -->
                <div class="tab-pane fade" id="footer" role="tabpanel" aria-labelledby="footer-tab">
                    <h5 class="mb-3"><i class="fas fa-info-circle text-primary mr-2"></i>Footer Information</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="receipt_website">Website URL</label>
                                <input type="text" class="form-control" id="receipt_website" name="receipt_website" value="<?php echo htmlspecialchars(getSetting('receipt_website', getSetting('site_url', SITE_URL))); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="receipt_email">Contact Email</label>
                                <input type="email" class="form-control" id="receipt_email" name="receipt_email" value="<?php echo htmlspecialchars(getSetting('receipt_email', getSetting('contact_email', 'support@exchangebridge.com'))); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="receipt_phone">Contact Phone</label>
                                <input type="text" class="form-control" id="receipt_phone" name="receipt_phone" value="<?php echo htmlspecialchars(getSetting('receipt_phone', getSetting('contact_phone', '+8801869838872'))); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Colors -->
                <div class="tab-pane fade" id="colors" role="tabpanel" aria-labelledby="colors-tab">
                    <h5 class="mb-3"><i class="fas fa-palette text-primary mr-2"></i>Color Settings</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="receipt_bg_color">Background Color</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="receipt_bg_color" name="receipt_bg_color" value="<?php echo htmlspecialchars(getSetting('receipt_bg_color', '#ffffff')); ?>">
                                    <div class="input-group-append">
                                        <span class="input-group-text p-0">
                                            <input type="color" class="border-0" style="width: 40px; height: 30px;" 
                                                value="<?php echo htmlspecialchars(getSetting('receipt_bg_color', '#ffffff')); ?>"
                                                onchange="document.getElementById('receipt_bg_color').value = this.value;">
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="receipt_header_color">Header Color</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="receipt_header_color" name="receipt_header_color" value="<?php echo htmlspecialchars(getSetting('receipt_header_color', '#1e3a8a')); ?>">
                                    <div class="input-group-append">
                                        <span class="input-group-text p-0">
                                            <input type="color" class="border-0" style="width: 40px; height: 30px;" 
                                                value="<?php echo htmlspecialchars(getSetting('receipt_header_color', '#1e3a8a')); ?>"
                                                onchange="document.getElementById('receipt_header_color').value = this.value;">
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="receipt_footer_color">Footer Color</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="receipt_footer_color" name="receipt_footer_color" value="<?php echo htmlspecialchars(getSetting('receipt_footer_color', '#1e3a8a')); ?>">
                                    <div class="input-group-append">
                                        <span class="input-group-text p-0">
                                            <input type="color" class="border-0" style="width: 40px; height: 30px;" 
                                                value="<?php echo htmlspecialchars(getSetting('receipt_footer_color', '#1e3a8a')); ?>"
                                                onchange="document.getElementById('receipt_footer_color').value = this.value;">
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="receipt_section_color">Section Color</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="receipt_section_color" name="receipt_section_color" value="<?php echo htmlspecialchars(getSetting('receipt_section_color', '#1e40af')); ?>">
                                    <div class="input-group-append">
                                        <span class="input-group-text p-0">
                                            <input type="color" class="border-0" style="width: 40px; height: 30px;" 
                                                value="<?php echo htmlspecialchars(getSetting('receipt_section_color', '#1e40af')); ?>"
                                                onchange="document.getElementById('receipt_section_color').value = this.value;">
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="receipt_accent_color">Accent Color</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="receipt_accent_color" name="receipt_accent_color" value="<?php echo htmlspecialchars(getSetting('receipt_accent_color', '#fde047')); ?>">
                                    <div class="input-group-append">
                                        <span class="input-group-text p-0">
                                            <input type="color" class="border-0" style="width: 40px; height: 30px;" 
                                                value="<?php echo htmlspecialchars(getSetting('receipt_accent_color', '#fde047')); ?>"
                                                onchange="document.getElementById('receipt_accent_color').value = this.value;">
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="receipt_content_bg_color">Content Background</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="receipt_content_bg_color" name="receipt_content_bg_color" value="<?php echo htmlspecialchars(getSetting('receipt_content_bg_color', '#f8fafc')); ?>">
                                    <div class="input-group-append">
                                        <span class="input-group-text p-0">
                                            <input type="color" class="border-0" style="width: 40px; height: 30px;" 
                                                value="<?php echo htmlspecialchars(getSetting('receipt_content_bg_color', '#f8fafc')); ?>"
                                                onchange="document.getElementById('receipt_content_bg_color').value = this.value;">
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="receipt_watermark_color">Watermark Color</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="receipt_watermark_color" name="receipt_watermark_color" value="<?php echo htmlspecialchars(getSetting('receipt_watermark_color', '#f3f4f6')); ?>">
                                    <div class="input-group-append">
                                        <span class="input-group-text p-0">
                                            <input type="color" class="border-0" style="width: 40px; height: 30px;" 
                                                value="<?php echo htmlspecialchars(getSetting('receipt_watermark_color', '#f3f4f6')); ?>"
                                                onchange="document.getElementById('receipt_watermark_color').value = this.value;">
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="receipt_watermark_text">Watermark Text</label>
                                <input type="text" class="form-control" id="receipt_watermark_text" name="receipt_watermark_text" value="<?php echo htmlspecialchars(getSetting('receipt_watermark_text', 'EXCHANGE')); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Labels & Text -->
                <div class="tab-pane fade" id="labels" role="tabpanel" aria-labelledby="labels-tab">
                    <h5 class="mb-3"><i class="fas fa-tags text-primary mr-2"></i>Labels & Text</h5>
                    
                    <!-- Section Titles -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Section Titles</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="receipt_customer_details_title">Customer Details Title</label>
                                        <input type="text" class="form-control" id="receipt_customer_details_title" name="receipt_customer_details_title" value="<?php echo htmlspecialchars(getSetting('receipt_customer_details_title', 'Customer Details')); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="receipt_exchange_details_title">Exchange Details Title</label>
                                        <input type="text" class="form-control" id="receipt_exchange_details_title" name="receipt_exchange_details_title" value="<?php echo htmlspecialchars(getSetting('receipt_exchange_details_title', 'Exchange Details')); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Field Labels -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Field Labels</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="receipt_name_label">Name Label</label>
                                        <input type="text" class="form-control" id="receipt_name_label" name="receipt_name_label" value="<?php echo htmlspecialchars(getSetting('receipt_name_label', 'Name')); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="receipt_email_label">Email Label</label>
                                        <input type="text" class="form-control" id="receipt_email_label" name="receipt_email_label" value="<?php echo htmlspecialchars(getSetting('receipt_email_label', 'Email')); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="receipt_phone_label">Phone Label</label>
                                        <input type="text" class="form-control" id="receipt_phone_label" name="receipt_phone_label" value="<?php echo htmlspecialchars(getSetting('receipt_phone_label', 'Phone')); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="receipt_address_label">Address Label</label>
                                        <input type="text" class="form-control" id="receipt_address_label" name="receipt_address_label" value="<?php echo htmlspecialchars(getSetting('receipt_address_label', 'Address')); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="receipt_from_label">From Label</label>
                                        <input type="text" class="form-control" id="receipt_from_label" name="receipt_from_label" value="<?php echo htmlspecialchars(getSetting('receipt_from_label', 'From')); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="receipt_to_label">To Label</label>
                                        <input type="text" class="form-control" id="receipt_to_label" name="receipt_to_label" value="<?php echo htmlspecialchars(getSetting('receipt_to_label', 'To')); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="receipt_rate_label">Rate Label</label>
                                        <input type="text" class="form-control" id="receipt_rate_label" name="receipt_rate_label" value="<?php echo htmlspecialchars(getSetting('receipt_rate_label', 'Rate')); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="receipt_sent_label">Sent Label</label>
                                        <input type="text" class="form-control" id="receipt_sent_label" name="receipt_sent_label" value="<?php echo htmlspecialchars(getSetting('receipt_sent_label', 'Sent')); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="receipt_received_label">Received Label</label>
                                        <input type="text" class="form-control" id="receipt_received_label" name="receipt_received_label" value="<?php echo htmlspecialchars(getSetting('receipt_received_label', 'Received')); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer Labels -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Footer Labels</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="receipt_website_label">Website Label</label>
                                        <input type="text" class="form-control" id="receipt_website_label" name="receipt_website_label" value="<?php echo htmlspecialchars(getSetting('receipt_website_label', 'Website')); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="receipt_email_footer_label">Email Footer Label</label>
                                        <input type="text" class="form-control" id="receipt_email_footer_label" name="receipt_email_footer_label" value="<?php echo htmlspecialchars(getSetting('receipt_email_footer_label', 'Email Address')); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="receipt_phone_footer_label">Phone Footer Label</label>
                                        <input type="text" class="form-control" id="receipt_phone_footer_label" name="receipt_phone_footer_label" value="<?php echo htmlspecialchars(getSetting('receipt_phone_footer_label', 'Contact Number')); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Save Receipt Settings
                </button>
                <a href="<?php echo SITE_URL; ?>/receipt.php?ref=TEST123" target="_blank" class="btn btn-info ml-2">
                    <i class="fas fa-eye mr-1"></i> Preview Receipt
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>