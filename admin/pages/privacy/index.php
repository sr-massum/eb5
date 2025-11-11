<?php 

/**
 * ExchangeBridge - Admin Panel Privacy Policy Page
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
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_privacy_page') {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $title = sanitizeInput($_POST['title']);
        $content = $_POST['content']; // TinyMCE content
        $meta_title = sanitizeInput($_POST['meta_title']);
        $meta_description = sanitizeInput($_POST['meta_description']);
        $meta_keywords = sanitizeInput($_POST['meta_keywords']);
        
        // Handle featured image upload for SEO
        $featured_image = '';
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['featured_image'], '../../../assets/uploads/seo/', ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024); // 2MB max
            if ($uploadResult['success']) {
                $featured_image = $uploadResult['filename'];
            } else {
                $_SESSION['error_message'] = 'Image upload failed: ' . $uploadResult['message'];
            }
        }
        
        $updateData = [
            'title' => $title,
            'content' => $content,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'meta_keywords' => $meta_keywords,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Add featured image if uploaded
        if ($featured_image) {
            $updateData['featured_image'] = $featured_image;
        }
        
        // Update the privacy page
        $result = $db->update('pages', $updateData, 'slug = ?', ['privacy']);
        
        if ($result && !isset($_SESSION['error_message'])) {
            $_SESSION['success_message'] = 'Privacy Policy updated successfully!';
        } elseif (!isset($_SESSION['error_message'])) {
            $_SESSION['error_message'] = 'Failed to update Privacy Policy. Please try again.';
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $_SESSION['error_message'] = 'Invalid security token. Please try again.';
    }
}

// Get privacy page content
$privacyPage = getPageBySlug('privacy');
if (!$privacyPage) {
    // Create default privacy page if doesn't exist
    $db->insert('pages', [
        'slug' => 'privacy',
        'title' => 'Privacy Policy',
        'content' => '<h2>Privacy Policy</h2><p>This privacy policy outlines how we collect, use, and protect your personal information when you use our services.</p><p>Please add your privacy policy content here.</p>',
        'meta_title' => 'Privacy Policy - ' . getSetting('site_name', 'Exchange Bridge'),
        'meta_description' => 'Read our privacy policy to understand how we protect your data and privacy.',
        'status' => 'active'
    ]);
    $privacyPage = getPageBySlug('privacy');
}

// Get media files for selection
$mediaFiles = $db->getRows("SELECT * FROM media WHERE file_type IN ('image', 'video') ORDER BY created_at DESC LIMIT 100");

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

<!-- Enhanced TinyMCE for Multilingual support -->
<script src="https://cdn.tiny.cloud/1/hhiyirqkh3fnrmgjs7nq6tpk6nqb62m3vww7smgrz7kjfv6v/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<style>
/* Enhanced font support styles */
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

.featured-image-preview {
    max-width: 300px;
    max-height: 200px;
    border-radius: 8px;
    margin-top: 10px;
}

/* Media selector styles */
.media-selector {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    background-color: #f8f9fa;
}

.media-item {
    display: inline-block;
    margin: 5px;
    cursor: pointer;
    border: 2px solid transparent;
    border-radius: 4px;
    padding: 5px;
    position: relative;
    transition: all 0.3s ease;
}

.media-item:hover {
    border-color: #007bff;
    transform: scale(1.05);
}

.media-item.selected {
    border-color: #28a745;
    background-color: #e8f5e8;
}

.media-item img, .media-item video {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    display: block;
}

.media-item-name {
    font-size: 10px;
    text-align: center;
    margin-top: 2px;
    color: #666;
    word-break: break-all;
}

/* Media upload area */
.media-upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.media-upload-area:hover {
    border-color: #007bff;
    background-color: #e7f3ff;
}

.media-upload-area.dragover {
    border-color: #28a745;
    background-color: #e8f5e8;
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
    <li class="breadcrumb-item active">Edit Privacy Policy</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-shield-alt mr-1"></i> Edit Privacy Policy
        <a href="<?php echo SITE_URL; ?>/privacy.php" target="_blank" class="btn btn-info btn-sm float-right ml-2">
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

        <form method="POST" action="" enctype="multipart/form-data" accept-charset="UTF-8" id="privacyPageForm">
            <input type="hidden" name="action" value="update_privacy_page">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="title">Page Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control page-content" 
                               value="<?php echo htmlspecialchars($privacyPage['title'] ?? 'Privacy Policy', ENT_QUOTES, 'UTF-8'); ?>" 
                               required placeholder="Enter page title">
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
                            <input type="text" class="form-control" value="privacy.php" readonly>
                        </div>
                        <small class="form-text text-muted">The URL for this page</small>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="content">Privacy Policy Content <span class="text-danger">*</span></label>
                <textarea name="content" id="content" class="form-control page-content" rows="15" required><?php echo htmlspecialchars($privacyPage['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                <small class="form-text text-muted">Rich content with formatting, images, and links. Use Insert > Image/Media to add files directly into content.</small>
            </div>
            
            <!-- Media Library Section -->
            <div class="form-group">
                <label class="font-weight-bold">Media Library</label>
                
                <?php if (count($mediaFiles) > 0): ?>
                <div class="mb-3">
                    <label>Select from Media Library (Optional):</label>
                    <div class="media-selector">
                        <?php foreach ($mediaFiles as $media): ?>
                            <?php 
                            $mediaUrl = SITE_URL . '/' . $media['file_path'];
                            $extension = strtolower(pathinfo($media['file_path'], PATHINFO_EXTENSION));
                            ?>
                            <div class="media-item" data-id="<?php echo $media['id']; ?>" data-path="<?php echo $media['file_path']; ?>">
                                <?php if (in_array($extension, ['mp4', 'webm', 'ogg'])): ?>
                                    <video muted>
                                        <source src="<?php echo $mediaUrl; ?>" type="<?php echo $media['mime_type']; ?>">
                                    </video>
                                <?php else: ?>
                                    <img src="<?php echo $mediaUrl; ?>" alt="<?php echo htmlspecialchars($media['original_name']); ?>" 
                                         onerror="this.style.display='none'">
                                <?php endif; ?>
                                <div class="media-item-name">
                                    <?php echo mb_substr($media['original_name'], 0, 12); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mb-2"><strong>OR</strong></div>
                <?php endif; ?>
                
                <div class="media-upload-area" id="media-upload-area">
                    <input type="file" id="page_media" name="page_media" accept="image/*,video/*" style="display: none;">
                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                    <p class="mb-2">Drag & drop media files here or click to browse</p>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('page_media').click();">
                        Browse Files
                    </button>
                    <small class="form-text text-muted mt-2">
                        Upload images (JPG, PNG, GIF, WebP) up to 5MB or videos (MP4, WebM, OGG) up to 10MB.
                    </small>
                </div>
            </div>
            
            <!-- SEO Settings -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-search mr-2"></i>SEO Settings</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="meta_title">Meta Title</label>
                                <input type="text" name="meta_title" id="meta_title" class="form-control page-content" 
                                       value="<?php echo htmlspecialchars($privacyPage['meta_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder="SEO optimized title for search engines">
                                <small class="form-text text-muted">Recommended length: 50-60 characters</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="featured_image">Featured Image (SEO Only) <i class="fas fa-info-circle" title="This image is for SEO purposes only and will not be displayed on the page"></i></label>
                                <input type="file" name="featured_image" id="featured_image" class="form-control-file" accept="image/*">
                                <small class="form-text text-muted">For SEO/Social Media. Max: 2MB. Not displayed on page.</small>
                                <?php if (!empty($privacyPage['featured_image'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo SITE_URL; ?>/assets/uploads/seo/<?php echo htmlspecialchars($privacyPage['featured_image']); ?>" 
                                             alt="Current Featured Image" class="featured-image-preview img-thumbnail">
                                        <br><small class="text-muted">Current SEO image</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_description">Meta Description</label>
                        <textarea name="meta_description" id="meta_description" class="form-control page-content" 
                                  rows="3" placeholder="Brief description for search engines"><?php echo htmlspecialchars($privacyPage['meta_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <small class="form-text text-muted">Recommended length: 150-160 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_keywords">Meta Keywords</label>
                        <input type="text" name="meta_keywords" id="meta_keywords" class="form-control page-content" 
                               value="<?php echo htmlspecialchars($privacyPage['meta_keywords'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="privacy policy, data protection, security">
                        <small class="form-text text-muted">Separate multiple keywords with commas</small>
                    </div>
                </div>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save mr-2"></i>Update Privacy Policy
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
// Get current directory path for upload handler
const currentPath = window.location.pathname;
const basePath = currentPath.substring(0, currentPath.lastIndexOf('/')) + '/';

// Enhanced TinyMCE initialization with media upload support
tinymce.init({
    selector: '#content',
    height: 500,
    language: 'en',
    entity_encoding: 'raw',
    encoding: 'UTF-8',
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons'
    ],
    toolbar: 'undo redo | blocks | ' +
        'bold italic forecolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'removeformat | image media link | code preview | help',
    content_style: `
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@100;200;300;400;500;600;700;800;900&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
        body { 
            font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', 'Roboto', Arial, sans-serif !important; 
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
            font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif !important; 
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
        img {
            max-width: 100% !important;
            height: auto !important;
            border-radius: 4px;
            margin: 10px 0;
            display: block;
        }
        video {
            max-width: 100% !important;
            height: auto !important;
            border-radius: 4px;
            margin: 10px 0;
        }
    `,
    
    // Critical fixes for image visibility
    convert_urls: false,
    relative_urls: false,
    remove_script_host: false,
    document_base_url: '<?php echo SITE_URL; ?>/',
    
    // Image upload configuration
    automatic_uploads: true,
    images_upload_url: basePath + 'upload_handler.php',
    images_upload_credentials: true,
    images_reuse_filename: false,
    
    images_upload_handler: function (blobInfo, progress) {
        return new Promise(function(resolve, reject) {
            const xhr = new XMLHttpRequest();
            xhr.withCredentials = false;
            xhr.open('POST', basePath + 'upload_handler.php');
            
            xhr.upload.onprogress = function (e) {
                progress(e.loaded / e.total * 100);
            };
            
            xhr.onload = function() {
                console.log('Upload response status:', xhr.status);
                console.log('Upload response text:', xhr.responseText);
                
                if (xhr.status === 403) {
                    reject({message: 'HTTP Error: ' + xhr.status, remove: true});
                    return;
                }
                
                if (xhr.status < 200 || xhr.status >= 300) {
                    reject('HTTP Error: ' + xhr.status);
                    return;
                }
                
                let json;
                try {
                    json = JSON.parse(xhr.responseText);
                } catch(e) {
                    console.error('JSON Parse Error:', e);
                    reject('Invalid JSON response: ' + xhr.responseText);
                    return;
                }
                
                console.log('Upload response JSON:', json);
                
                if (!json || !json.success || typeof json.location != 'string') {
                    reject(json.message || 'Invalid JSON: ' + xhr.responseText);
                    return;
                }
                
                console.log('✅ Image uploaded successfully:', json.location);
                resolve(json.location);
            };
            
            xhr.onerror = function () {
                reject('Image upload failed due to a XHR Transport error. Code: ' + xhr.status);
            };
            
            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            
            xhr.send(formData);
        });
    },
    
    setup: function (editor) {
        editor.on('change keyup', function () {
            editor.save();
        });
        
        // Set default font and encoding
        editor.on('init', function() {
            editor.getBody().style.fontFamily = "'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif";
            editor.getBody().style.fontSize = "16px";
            editor.getBody().style.lineHeight = "1.8";
            editor.getBody().style.direction = "ltr";
            editor.getBody().style.unicodeBidi = "embed";
            editor.getBody().style.textRendering = "optimizeLegibility";
        });
    },
    menubar: 'file edit view insert format tools table help',
    toolbar_mode: 'sliding',
    contextmenu: 'link image table',
    image_advtab: true,
    image_caption: true,
    quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote quickimage quicktable',
    toolbar_sticky: true,
    autosave_ask_before_unload: true,
    autosave_interval: '30s',
    paste_data_images: true,
    paste_as_text: false
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

$(document).ready(function() {
    // Set document encoding
    document.charset = "UTF-8";
    
    // Media selection functionality
    $('.media-item').click(function() {
        $('.media-item').removeClass('selected');
        $(this).addClass('selected');
        
        const mediaPath = $(this).data('path');
        const mediaUrl = '<?php echo SITE_URL; ?>/' + mediaPath;
        
        // Insert selected media into TinyMCE
        const editor = tinymce.get('content');
        if (editor) {
            const extension = mediaPath.split('.').pop().toLowerCase();
            let mediaHtml = '';
            
            if (['mp4', 'webm', 'ogg'].includes(extension)) {
                mediaHtml = `<video controls style="max-width:100%;"><source src="${mediaUrl}"></video>`;
            } else {
                mediaHtml = `<img src="${mediaUrl}" alt="Selected media" style="max-width:100%;">`;
            }
            
            editor.insertContent(mediaHtml);
        }
    });
    
    // Clear selection when file input is used
    $('#page_media').change(function() {
        if (this.files && this.files[0]) {
            $('.media-item').removeClass('selected');
        }
    });
    
    // Drag and drop functionality
    function setupDragDrop(uploadArea, fileInput) {
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                // Clear media library selection
                $('.media-item').removeClass('selected');
            }
        });
        
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });
    }
    
    // Setup drag and drop
    setupDragDrop(document.getElementById('media-upload-area'), document.getElementById('page_media'));
    
    // Form submission handling
    $('#privacyPageForm').on('submit', function(e) {
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
        
        // Show loading state
        $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Updating...');
        
        return true;
    });
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