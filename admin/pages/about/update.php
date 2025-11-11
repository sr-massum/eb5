<?php 

/**
 * ExchangeBridge - Admin Panel About Page
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
require_once '../../../includes/auth.php';
require_once '../../../includes/app.php';
require_once '../../../includes/security.php';



// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: ../../login.php");
    exit;
}

// Get page ID
$page_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$page_id) {
    $_SESSION['error_message'] = 'Invalid page ID!';
    header("Location: index.php");
    exit;
}

// Get about page from database
$db = Database::getInstance();
$aboutPage = $db->getRow("SELECT * FROM pages WHERE id = ? AND slug = 'about'", [$page_id]);

if (!$aboutPage) {
    $_SESSION['error_message'] = 'About page not found!';
    header("Location: index.php");
    exit;
}

// Include header
include '../../includes/header.php';
?>

<!-- TinyMCE Script -->
<script src="https://cdn.tiny.cloud/1/hhiyirqkh3fnrmgjs7nq6tpk6nqb62m3vww7smgrz7kjfv6v/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>

<!-- Breadcrumbs -->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item">
        <a href="../index.php">Pages</a>
    </li>
    <li class="breadcrumb-item">
        <a href="index.php">About Us</a>
    </li>
    <li class="breadcrumb-item active">Edit</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-edit mr-1"></i> Edit About Us Page
    </div>
    <div class="card-body">
        <form action="save.php" method="POST" id="aboutForm">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="page_id" value="<?php echo $aboutPage['id']; ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="title">Page Title *</label>
                        <input type="text" name="title" id="title" class="form-control" 
                               value="<?php echo htmlspecialchars($aboutPage['title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Page Content *</label>
                        <textarea name="content" id="content" class="form-control tinymce-editor" rows="20"><?php echo htmlspecialchars($aboutPage['content']); ?></textarea>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="active" <?php echo $aboutPage['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $aboutPage['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="show_in_menu" id="show_in_menu" class="custom-control-input" 
                                   <?php echo $aboutPage['show_in_menu'] ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="show_in_menu">
                                Show in Navigation Menu
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="menu_order">Menu Order</label>
                        <input type="number" name="menu_order" id="menu_order" class="form-control" 
                               value="<?php echo $aboutPage['menu_order']; ?>" min="0">
                    </div>
                    
                    <hr>
                    
                    <h6>SEO Settings</h6>
                    
                    <div class="form-group">
                        <label for="meta_title">Meta Title</label>
                        <input type="text" name="meta_title" id="meta_title" class="form-control" 
                               value="<?php echo htmlspecialchars($aboutPage['meta_title']); ?>" maxlength="60">
                        <small class="form-text text-muted">Recommended: 50-60 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_description">Meta Description</label>
                        <textarea name="meta_description" id="meta_description" class="form-control" rows="3" maxlength="160"><?php echo htmlspecialchars($aboutPage['meta_description']); ?></textarea>
                        <small class="form-text text-muted">Recommended: 150-160 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_keywords">Meta Keywords</label>
                        <input type="text" name="meta_keywords" id="meta_keywords" class="form-control" 
                               value="<?php echo htmlspecialchars($aboutPage['meta_keywords']); ?>">
                        <small class="form-text text-muted">Comma-separated keywords</small>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update About Page
                </button>
                <a href="index.php" class="btn btn-secondary ml-2">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <a href="<?php echo SITE_URL; ?>/about" target="_blank" class="btn btn-info ml-2">
                    <i class="fas fa-eye"></i> Preview
                </a>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize TinyMCE
    tinymce.init({
        selector: '.tinymce-editor',
        height: 500,
        menubar: false,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | ' +
            'bold italic forecolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | link image | code preview | help',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
    });
    
    // Form submission
    $('#aboutForm').on('submit', function() {
        tinymce.triggerSave();
    });
});
</script>

<?php include '../../includes/footer.php'; ?>