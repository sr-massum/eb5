<?php 

/**
 * ExchangeBridge - Admin Panel Contact Page
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
require_once '../../../config/config.php';
require_once '../../../config/verification.php';
require_once '../../../config/license.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/app.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/security.php';

// Set UTF-8 encoding headers
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: " . ADMIN_URL . "/login.php");
    exit;
}

$user = Auth::getUser();
$db = Database::getInstance();

// Set MySQL connection to UTF-8
$db->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

// Handle form submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_contact_page') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        // Page content
        $title = sanitizeInput($_POST['title']);
        $content = sanitizeInput($_POST['content']);
        $meta_title = sanitizeInput($_POST['meta_title']);
        $meta_description = sanitizeInput($_POST['meta_description']);
        $meta_keywords = sanitizeInput($_POST['meta_keywords']);
        
        // Contact settings
        $contact_address = sanitizeInput($_POST['contact_address']);
        $contact_email = sanitizeInput($_POST['contact_email']);
        $contact_phone = sanitizeInput($_POST['contact_phone']);
        $contact_whatsapp = sanitizeInput($_POST['contact_whatsapp']);
        $working_hours = sanitizeInput($_POST['working_hours']);
        $contact_website = sanitizeInput($_POST['contact_website']);
        $google_maps_link = sanitizeInput($_POST['google_maps_link']);
        
        // Handle featured image upload for SEO
        $featured_image = '';
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['featured_image'], '../../../assets/uploads/seo/', ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
            if ($uploadResult['success']) {
                $featured_image = $uploadResult['filename'];
            } else {
                $_SESSION['error_message'] = 'Image upload failed: ' . $uploadResult['message'];
            }
        }
        
        // Update page content
        $updateData = [
            'title' => $title,
            'content' => $content,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'meta_keywords' => $meta_keywords,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($featured_image) {
            $updateData['featured_image'] = $featured_image;
        }
        
        $pageResult = $db->update('pages', $updateData, 'slug = ?', ['contact']);
        
        // Update contact settings
        $settingsUpdated = true;
        $settingsUpdated &= updateSetting('contact_address', $contact_address);
        $settingsUpdated &= updateSetting('contact_email', $contact_email);
        $settingsUpdated &= updateSetting('contact_phone', $contact_phone);
        $settingsUpdated &= updateSetting('contact_whatsapp', $contact_whatsapp);
        $settingsUpdated &= updateSetting('working_hours', $working_hours);
        $settingsUpdated &= updateSetting('contact_website', $contact_website);
        $settingsUpdated &= updateSetting('google_maps_link', $google_maps_link);
        
        if ($pageResult && $settingsUpdated && !isset($_SESSION['error_message'])) {
            $_SESSION['success_message'] = 'Contact page updated successfully!';
        } elseif (!isset($_SESSION['error_message'])) {
            $_SESSION['error_message'] = 'Failed to update contact page. Please try again.';
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $_SESSION['error_message'] = 'Invalid security token. Please try again.';
    }
}

// Get contact page content
$contactPage = getPageBySlug('contact');
if (!$contactPage) {
    // Create default contact page if doesn't exist
    $db->insert('pages', [
        'slug' => 'contact',
        'title' => 'Contact Us',
        'content' => 'We would love to hear from you! Please feel free to contact us using the form or the contact details below.',
        'meta_title' => 'Contact Us - ' . getSetting('site_name', 'Exchange Bridge'),
        'meta_description' => 'Get in touch with us for any questions or support. We are here to help you.',
        'status' => 'active'
    ]);
    $contactPage = getPageBySlug('contact');
}

// Check for messages
$successMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$errorMessage = '';
if (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Include header
include '../../includes/header.php';
?>

<style>
.page-content {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif !important;
    line-height: 1.8 !important;
    font-weight: 400;
}

.form-control {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif !important;
    font-size: 16px !important;
    line-height: 1.8 !important;
}

.bengali-input {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', Arial, sans-serif !important;
    font-size: 16px !important;
    line-height: 1.8 !important;
}

.featured-image-preview {
    max-width: 300px;
    max-height: 200px;
    border-radius: 8px;
    margin-top: 10px;
}
</style>

<!-- Breadcrumbs -->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="<?php echo ADMIN_URL; ?>/index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item">
        <a href="<?php echo ADMIN_URL; ?>/pages/">Pages</a>
    </li>
    <li class="breadcrumb-item active">Edit Contact Page</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-address-book mr-1"></i> Edit Contact Page
        <a href="<?php echo SITE_URL; ?>/contact.php" target="_blank" class="btn btn-info btn-sm float-right ml-2">
            <i class="fas fa-external-link-alt"></i> Preview Page
        </a>
        <a href="<?php echo ADMIN_URL; ?>/pages/" class="btn btn-secondary btn-sm float-right">
            <i class="fas fa-arrow-left"></i> Back to Pages
        </a>
    </div>
    <div class="card-body">
        <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" accept-charset="UTF-8" id="contactPageForm">
            <input type="hidden" name="action" value="update_contact_page">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <!-- Page Content Section -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-file-alt mr-2"></i>Page Content (পেইজ কন্টেন্ট)</h6>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="title">Page Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control page-content bengali-input" 
                               value="<?php echo htmlspecialchars($contactPage['title'] ?? 'Contact Us', ENT_QUOTES, 'UTF-8'); ?>" 
                               required placeholder="Enter page title">
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Introduction Text (ভূমিকা)</label>
                        <textarea name="content" id="content" class="form-control page-content bengali-input" rows="4" 
                                  placeholder="Brief introduction text for contact page"><?php echo htmlspecialchars($contactPage['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <small class="form-text text-muted">This text will appear at the top of the contact page</small>
                    </div>
                </div>
            </div>
            
            <!-- Contact Information Section -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-address-card mr-2"></i>Contact Information (যোগাযোগের তথ্য)</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contact_address">Address (ঠিকানা) <span class="text-danger">*</span></label>
                                <textarea name="contact_address" id="contact_address" class="form-control page-content bengali-input" rows="3" 
                                          required placeholder="Enter your full address"><?php echo htmlspecialchars(getSetting('contact_address', 'Dhaka, Bangladesh'), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contact_email">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="contact_email" id="contact_email" class="form-control" 
                                       value="<?php echo htmlspecialchars(getSetting('contact_email', 'support@exchangebridge.com'), ENT_QUOTES, 'UTF-8'); ?>" 
                                       required placeholder="contact@example.com">
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_phone">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="contact_phone" id="contact_phone" class="form-control" 
                                       value="<?php echo htmlspecialchars(getSetting('contact_phone', '+8801869838872'), ENT_QUOTES, 'UTF-8'); ?>" 
                                       required placeholder="+8801234567890">
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_whatsapp">WhatsApp Number</label>
                                <input type="text" name="contact_whatsapp" id="contact_whatsapp" class="form-control" 
                                       value="<?php echo htmlspecialchars(getSetting('contact_whatsapp', '8801869838872'), ENT_QUOTES, 'UTF-8'); ?>" 
                                       placeholder="8801234567890">
                                <small class="form-text text-muted">Without + sign</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="working_hours">Working Hours (কর্মঘন্টা) <span class="text-danger">*</span></label>
                                <input type="text" name="working_hours" id="working_hours" class="form-control page-content bengali-input" 
                                       value="<?php echo htmlspecialchars(getSetting('working_hours', '9 am-11.50pm +6'), ENT_QUOTES, 'UTF-8'); ?>" 
                                       required placeholder="9 am-11.50pm +6">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contact_website">Website URL</label>
                                <input type="url" name="contact_website" id="contact_website" class="form-control" 
                                       value="<?php echo htmlspecialchars(getSetting('contact_website', SITE_URL), ENT_QUOTES, 'UTF-8'); ?>" 
                                       placeholder="https://example.com">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="google_maps_link">Google Maps Embed Link</label>
                        <textarea name="google_maps_link" id="google_maps_link" class="form-control" rows="3" 
                                  placeholder="Paste Google Maps embed iframe link here"><?php echo htmlspecialchars(getSetting('google_maps_link', ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <small class="form-text text-muted">Get embed link from Google Maps → Share → Embed a map</small>
                    </div>
                </div>
            </div>
            
            <!-- SEO Settings -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-search mr-2"></i>SEO Settings (এসইও সেটিংস)</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="meta_title">Meta Title</label>
                                <input type="text" name="meta_title" id="meta_title" class="form-control page-content bengali-input" 
                                       value="<?php echo htmlspecialchars($contactPage['meta_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder="Contact Us - Your Site Name">
                                <small class="form-text text-muted">Recommended length: 50-60 characters</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="featured_image">Featured Image (SEO Only)</label>
                                <input type="file" name="featured_image" id="featured_image" class="form-control-file" accept="image/*">
                                <small class="form-text text-muted">For SEO/Social Media. Max: 2MB.</small>
                                <?php if (!empty($contactPage['featured_image'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo SITE_URL; ?>/assets/uploads/seo/<?php echo htmlspecialchars($contactPage['featured_image']); ?>" 
                                             alt="Current Featured Image" class="featured-image-preview img-thumbnail">
                                        <br><small class="text-muted">Current SEO image</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_description">Meta Description</label>
                        <textarea name="meta_description" id="meta_description" class="form-control page-content bengali-input" 
                                  rows="3" placeholder="Get in touch with us for any questions or support"><?php echo htmlspecialchars($contactPage['meta_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <small class="form-text text-muted">Recommended length: 150-160 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_keywords">Meta Keywords</label>
                        <input type="text" name="meta_keywords" id="meta_keywords" class="form-control page-content bengali-input" 
                               value="<?php echo htmlspecialchars($contactPage['meta_keywords'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="contact, support, help, address, phone">
                        <small class="form-text text-muted">Separate multiple keywords with commas</small>
                    </div>
                </div>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save mr-2"></i>Update Contact Page
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
document.getElementById('contactPageForm').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const email = document.getElementById('contact_email').value.trim();
    const phone = document.getElementById('contact_phone').value.trim();
    const address = document.getElementById('contact_address').value.trim();
    
    if (!title || !email || !phone || !address) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return false;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        e.preventDefault();
        alert('Please enter a valid email address.');
        return false;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>