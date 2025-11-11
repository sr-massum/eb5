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
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_about_page') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $title = sanitizeInput($_POST['title']);
        $content = $_POST['content']; // TinyMCE content
        $meta_title = sanitizeInput($_POST['meta_title']);
        $meta_description = sanitizeInput($_POST['meta_description']);
        $meta_keywords = sanitizeInput($_POST['meta_keywords']);
        
        // Update the about page
        $result = $db->update('pages', [
            'title' => $title,
            'content' => $content,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'meta_keywords' => $meta_keywords,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'slug = ?', ['about']);
        
        if ($result) {
            $_SESSION['success_message'] = 'About page updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update about page. Please try again.';
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $_SESSION['error_message'] = 'Invalid security token. Please try again.';
    }
}

// Get about page content
$aboutPage = getPageBySlug('about');
if (!$aboutPage) {
    // Create default about page if doesn't exist
    $db->insert('pages', [
        'slug' => 'about',
        'title' => 'About Us',
        'content' => '<h2>Welcome to ' . getSetting('site_name', 'Exchange Bridge') . '</h2><p>This is the default about page content. Please edit this content through the admin panel.</p>',
        'meta_title' => 'About Us - ' . getSetting('site_name', 'Exchange Bridge'),
        'meta_description' => 'Learn more about ' . getSetting('site_name', 'Exchange Bridge') . ' and our services.',
        'status' => 'active'
    ]);
    $aboutPage = getPageBySlug('about');
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

<!-- Enhanced TinyMCE for Bengali/Multilingual support -->
<script src="https://cdn.tiny.cloud/1/hhiyirqkh3fnrmgjs7nq6tpk6nqb62m3vww7smgrz7kjfv6v/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<style>
/* Enhanced Bengali font support styles */
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@100;200;300;400;500;600;700;800;900&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

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
    direction: ltr;
    unicode-bidi: embed;
    text-align: left;
}

/* TinyMCE specific styles for Bengali */
.tox .tox-edit-area iframe {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif !important;
}

.mce-content-body {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif !important;
    font-size: 16px !important;
    line-height: 1.8 !important;
}

.preview-content {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif !important;
    line-height: 1.8;
    text-align: justify;
    padding: 20px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background-color: #f8f9fa;
    margin-top: 15px;
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
    <li class="breadcrumb-item active">Edit About Page</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-edit mr-1"></i> Edit About Page
        <a href="<?php echo SITE_URL; ?>/about.php" target="_blank" class="btn btn-info btn-sm float-right ml-2">
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

        <form method="POST" action="" accept-charset="UTF-8" id="aboutPageForm">
            <input type="hidden" name="action" value="update_about_page">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="title">Page Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control page-content bengali-input" 
                               value="<?php echo htmlspecialchars($aboutPage['title'] ?? 'About Us', ENT_QUOTES, 'UTF-8'); ?>" 
                               required placeholder="Enter page title (English/বাংলা)">
                        <small class="form-text text-muted">This will be displayed as the page heading</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="page_url">Page URL</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><?php echo SITE_URL; ?>/</span>
                            </div>
                            <input type="text" class="form-control" value="about.php" readonly>
                        </div>
                        <small class="form-text text-muted">The URL for this page</small>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="content">Page Content (পেইজ কন্টেন্ট) <span class="text-danger">*</span></label>
                <textarea name="content" id="content" class="form-control page-content bengali-input" rows="15" required><?php echo htmlspecialchars($aboutPage['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                <small class="form-text text-muted">Rich content with formatting, images, and links. Support for বাংলা and English mixed text.</small>
            </div>
            
            <!-- SEO Settings -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-search mr-2"></i>SEO Settings (এসইও সেটিংস)</h6>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="meta_title">Meta Title (মেটা শিরোনাম)</label>
                        <input type="text" name="meta_title" id="meta_title" class="form-control page-content bengali-input" 
                               value="<?php echo htmlspecialchars($aboutPage['meta_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="SEO optimized title for search engines">
                        <small class="form-text text-muted">Recommended length: 50-60 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_description">Meta Description (মেটা বিবরণ)</label>
                        <textarea name="meta_description" id="meta_description" class="form-control page-content bengali-input" 
                                  rows="3" placeholder="Brief description for search engines"><?php echo htmlspecialchars($aboutPage['meta_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <small class="form-text text-muted">Recommended length: 150-160 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_keywords">Meta Keywords (মেটা কীওয়ার্ড)</label>
                        <input type="text" name="meta_keywords" id="meta_keywords" class="form-control page-content bengali-input" 
                               value="<?php echo htmlspecialchars($aboutPage['meta_keywords'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="keyword1, keyword2, keyword3">
                        <small class="form-text text-muted">Separate multiple keywords with commas</small>
                    </div>
                </div>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save mr-2"></i>Update About Page
                </button>
                <button type="button" class="btn btn-info btn-lg ml-2" onclick="previewContent()">
                    <i class="fas fa-eye mr-2"></i>Preview Content
                </button>
            </div>
        </form>
        
        <!-- Content Preview -->
        <div id="contentPreview" class="mt-4" style="display: none;">
            <h5><i class="fas fa-eye mr-2"></i>Content Preview:</h5>
            <div id="previewArea" class="preview-content"></div>
        </div>
    </div>
</div>

<!-- Include jQuery and Bootstrap -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
// Enhanced TinyMCE initialization with Bengali support
tinymce.init({
    selector: '#content',
    height: 500,
    language: 'en',
    entity_encoding: 'raw',
    encoding: 'UTF-8',
    plugins: 'advlist autolink lists link image charmap print preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste help wordcount emoticons',
    toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | link image media | code fullscreen | emoticons | help',
    content_style: `
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@100;200;300;400;500;600;700;800;900&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
        body { 
            font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'SutonnyMJ', 'Kalpurush', Arial, sans-serif !important; 
            font-size: 16px !important; 
            line-height: 1.8 !important;
            direction: ltr;
            unicode-bidi: embed;
            font-weight: 400;
            text-rendering: optimizeLegibility;
            -webkit-font-feature-settings: "kern" 1;
            font-feature-settings: "kern" 1;
        }
        p { 
            font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'SutonnyMJ', 'Kalpurush', Arial, sans-serif !important; 
            line-height: 1.8 !important; 
            font-size: 16px !important;
            font-weight: 400;
        }
        strong, b { 
            font-weight: 700 !important; 
            font-family: 'Noto Sans Bengali', 'Hind Siliguri', Arial, sans-serif !important;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif !important;
            font-weight: 600 !important;
            line-height: 1.6 !important;
        }
        li {
            font-family: 'Noto Sans Bengali', 'Hind Siliguri', Arial, sans-serif !important;
            line-height: 1.8 !important;
        }
    `,
    font_formats: 'Noto Sans Bengali=Noto Sans Bengali; Hind Siliguri=Hind Siliguri; SutonnyMJ=SutonnyMJ; Kalpurush=Kalpurush; Poppins=Poppins; Arial=arial,helvetica,sans-serif; Times New Roman=times new roman,times,serif;',
    formats: {
        bold: {inline: 'strong'},
        italic: {inline: 'em'},
        underline: {inline: 'u'},
        strikethrough: {inline: 'strike'}
    },
    setup: function (editor) {
        editor.on('change', function () {
            editor.save();
        });
        
        // Set default font and encoding
        editor.on('init', function() {
            editor.getBody().style.fontFamily = "'Noto Sans Bengali', 'Hind Siliguri', 'SutonnyMJ', 'Kalpurush', Arial, sans-serif";
            editor.getBody().style.fontSize = "16px";
            editor.getBody().style.lineHeight = "1.8";
            editor.getBody().style.direction = "ltr";
            editor.getBody().style.unicodeBidi = "embed";
            editor.getBody().style.textRendering = "optimizeLegibility";
        });
    },
    style_formats: [
        {title: 'Bengali Text', inline: 'span', styles: {'font-family': "'Noto Sans Bengali', 'Hind Siliguri', 'SutonnyMJ', Arial, sans-serif", 'font-weight': '400', 'font-size': '16px'}},
        {title: 'English Text', inline: 'span', styles: {'font-family': "'Poppins', 'Roboto', Arial, sans-serif", 'font-weight': '400', 'font-size': '14px'}},
        {title: 'Bold Bengali', inline: 'strong', styles: {'font-family': "'Noto Sans Bengali', 'Hind Siliguri', Arial, sans-serif", 'font-weight': '700'}},
        {title: 'Bold English', inline: 'strong', styles: {'font-family': "'Poppins', 'Roboto', Arial, sans-serif", 'font-weight': '700'}},
        {title: 'Bengali Heading', block: 'h3', styles: {'font-family': "'Noto Sans Bengali', 'Hind Siliguri', Arial, sans-serif", 'font-weight': '600'}}
    ],
    menubar: 'file edit view insert format tools table help',
    toolbar_mode: 'sliding',
    contextmenu: 'link image table',
    image_advtab: true,
    image_caption: true,
    quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote quickimage quicktable',
    noneditable_noneditable_class: 'mceNonEditable',
    toolbar_sticky: true,
    autosave_ask_before_unload: true,
    autosave_interval: '30s',
    autosave_prefix: '{path}{query}-{id}-',
    autosave_restore_when_empty: false,
    autosave_retention: '2m',
    paste_data_images: true,
    paste_as_text: false,
    paste_preprocess: function(plugin, args) {
        // Preserve Bengali characters during paste
        args.content = args.content;
    },
    init_instance_callback: function(editor) {
        // Ensure Bengali characters are properly handled
        editor.getBody().style.fontFamily = "'Noto Sans Bengali', 'Hind Siliguri', 'SutonnyMJ', 'Kalpurush', Arial, sans-serif";
    }
});

// Preview content function
function previewContent() {
    // Save TinyMCE content
    tinymce.triggerSave();
    
    const title = document.getElementById('title').value;
    const content = document.getElementById('content').value;
    
    if (!content.trim()) {
        alert('Please enter some content to preview.');
        return;
    }
    
    // Show preview
    const previewDiv = document.getElementById('contentPreview');
    const previewArea = document.getElementById('previewArea');
    
    let previewHtml = '';
    if (title.trim()) {
        previewHtml += `<h1 style="font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif; font-weight: 600; margin-bottom: 20px;">${title}</h1>`;
    }
    previewHtml += content;
    
    previewArea.innerHTML = previewHtml;
    previewDiv.style.display = 'block';
    
    // Scroll to preview
    previewDiv.scrollIntoView({ behavior: 'smooth' });
}

// Form submission handler
document.getElementById('aboutPageForm').addEventListener('submit', function(e) {
    // Save TinyMCE content before submit
    tinymce.triggerSave();
    
    const title = document.getElementById('title').value.trim();
    const content = document.getElementById('content').value.trim();
    
    if (!title) {
        e.preventDefault();
        alert('Please enter a page title.');
        return false;
    }
    
    if (!content) {
        e.preventDefault();
        alert('Please enter page content.');
        return false;
    }
    
    return true;
});

// Character count for meta fields
document.getElementById('meta_title').addEventListener('input', function() {
    const length = this.value.length;
    const small = this.nextElementSibling;
    if (length > 60) {
        small.className = 'form-text text-danger';
        small.textContent = `Meta title is too long (${length} characters). Recommended: 50-60 characters.`;
    } else if (length < 30) {
        small.className = 'form-text text-warning';
        small.textContent = `Meta title might be too short (${length} characters). Recommended: 50-60 characters.`;
    } else {
        small.className = 'form-text text-success';
        small.textContent = `Good length (${length} characters). Recommended: 50-60 characters.`;
    }
});

document.getElementById('meta_description').addEventListener('input', function() {
    const length = this.value.length;
    const small = this.nextElementSibling;
    if (length > 160) {
        small.className = 'form-text text-danger';
        small.textContent = `Meta description is too long (${length} characters). Recommended: 150-160 characters.`;
    } else if (length < 120) {
        small.className = 'form-text text-warning';
        small.textContent = `Meta description might be too short (${length} characters). Recommended: 150-160 characters.`;
    } else {
        small.className = 'form-text text-success';
        small.textContent = `Good length (${length} characters). Recommended: 150-160 characters.`;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>