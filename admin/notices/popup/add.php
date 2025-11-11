<?php 

/**
 * ExchangeBridge - Admin Panel Popup Notice
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

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: ../../login.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? sanitizeInput($_POST['title']) : '';
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active';
    $selectedMediaPath = isset($_POST['selected_media_path']) ? sanitizeInput($_POST['selected_media_path']) : '';
    
    // Handle media selection or upload
    $mediaPath = '';
    $uploadDir = '../../../assets/uploads/notices/';
    
    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Check if media was selected from library
    if (!empty($selectedMediaPath)) {
        // Clean and validate the selected media path
        $selectedMediaPath = str_replace(['../', './'], '', $selectedMediaPath);
        $sourcePath = '../../../' . $selectedMediaPath;
        
        if (file_exists($sourcePath)) {
            $extension = pathinfo($selectedMediaPath, PATHINFO_EXTENSION);
            $newFileName = 'popup_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            $targetPath = $uploadDir . $newFileName;
            
            if (copy($sourcePath, $targetPath)) {
                $mediaPath = $newFileName;
                chmod($targetPath, 0644); // Set proper permissions
            } else {
                $_SESSION['error_message'] = 'Failed to copy selected media file';
            }
        } else {
            $_SESSION['error_message'] = 'Selected media file does not exist';
        }
    }
    
    // Handle direct file upload if no media selected from library
    if (empty($mediaPath) && isset($_FILES['notice_media']) && $_FILES['notice_media']['error'] === UPLOAD_ERR_OK) {
        $fileName = $_FILES['notice_media']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = 'popup_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
            $uploadPath = $uploadDir . $newFileName;
            
            // Set size limits
            $maxSize = in_array($fileExtension, ['mp4', 'webm', 'ogg']) ? 10 * 1024 * 1024 : 5 * 1024 * 1024;
            
            if ($_FILES['notice_media']['size'] <= $maxSize) {
                if (move_uploaded_file($_FILES['notice_media']['tmp_name'], $uploadPath)) {
                    $mediaPath = $newFileName;
                    chmod($uploadPath, 0644); // Set proper permissions
                } else {
                    $_SESSION['error_message'] = 'Failed to upload media file';
                }
            } else {
                $_SESSION['error_message'] = 'File size too large. Max: ' . round($maxSize / 1024 / 1024, 1) . 'MB';
            }
        } else {
            $_SESSION['error_message'] = 'Invalid file format. Allowed: ' . implode(', ', $allowedExtensions);
        }
    }
    
    // Validate form data
    if (empty(trim($content))) {
        $_SESSION['error_message'] = 'Content is required';
    } else {
        try {
            // Insert popup notice
            $db = Database::getInstance();
            $noticeData = [
                'type' => 'popup',
                'title' => $title,
                'content' => $content,
                'status' => $status,
                'position' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Add media path if exists
            if (!empty($mediaPath)) {
                $noticeData['image_path'] = $mediaPath;
            }
            
            $noticeId = $db->insert('notices', $noticeData);
            
            if ($noticeId) {
                // Log the media file to media table
                if (!empty($mediaPath)) {
                    $mediaData = [
                        'filename' => $mediaPath,
                        'original_name' => !empty($_FILES['notice_media']['name']) ? $_FILES['notice_media']['name'] : $mediaPath,
                        'file_path' => 'assets/uploads/notices/' . $mediaPath,
                        'file_size' => !empty($_FILES['notice_media']['size']) ? $_FILES['notice_media']['size'] : filesize($uploadDir . $mediaPath),
                        'mime_type' => !empty($_FILES['notice_media']['type']) ? $_FILES['notice_media']['type'] : mime_content_type($uploadDir . $mediaPath),
                        'file_type' => 'image',
                        'uploaded_by' => $_SESSION['user_id'] ?? 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    try {
                        $db->insert('media', $mediaData);
                    } catch (Exception $e) {
                        // Continue even if media logging fails
                    }
                }
                
                $_SESSION['success_message'] = 'Popup notice added successfully';
                header("Location: index.php");
                exit;
            } else {
                $_SESSION['error_message'] = 'Failed to add popup notice to database';
                
                // Clean up uploaded file if database insert failed
                if (!empty($mediaPath) && file_exists($uploadDir . $mediaPath)) {
                    unlink($uploadDir . $mediaPath);
                }
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
            
            // Clean up uploaded file if error occurred
            if (!empty($mediaPath) && file_exists($uploadDir . $mediaPath)) {
                unlink($uploadDir . $mediaPath);
            }
        }
    }
}

// Get media files for selection
$db = Database::getInstance();
$mediaFiles = $db->getRows("SELECT * FROM media WHERE file_type IN ('image', 'video') ORDER BY created_at DESC LIMIT 50");

// Include header
include '../../includes/header.php';
?>

<!-- TinyMCE Editor -->
<script src="https://cdn.tiny.cloud/1/hhiyirqkh3fnrmgjs7nq6tpk6nqb62m3vww7smgrz7kjfv6v/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<style>
.popup-notice {
    font-family: 'Poppins', Arial, sans-serif;
    font-size: 14px;
    line-height: 1.6;
}

.media-selector {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
}

.media-item {
    display: inline-block;
    margin: 5px;
    cursor: pointer;
    border: 2px solid transparent;
    border-radius: 4px;
    padding: 5px;
    position: relative;
}

.media-item:hover {
    border-color: #007bff;
}

.media-item.selected {
    border-color: #28a745;
    background-color: #f8f9fa;
}

.media-item img, .media-item video {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    display: block;
}

.preview-card {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    background-color: #f8f9fa;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-height: 400px;
    overflow-y: auto;
}

.editor-container {
    min-height: 400px;
}

.preview-card img, .preview-card video {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
    margin: 10px 0;
}
</style>

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item">
        <a href="../index.php">Notices</a>
    </li>
    <li class="breadcrumb-item">
        <a href="index.php">Popup Notices</a>
    </li>
    <li class="breadcrumb-item active">Add New</li>
</ol>

<!-- Page Content -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-plus mr-1"></i> Add New Popup Notice
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" id="popupNoticeForm">
                    <input type="hidden" id="selected_media_path" name="selected_media_path">
                    
                    <div class="form-group">
                        <label for="title" class="font-weight-bold">Title</label>
                        <input type="text" class="form-control popup-notice" id="title" name="title" 
                               placeholder="Enter notice title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        <small class="form-text text-muted">Optional but recommended for better organization</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="content" class="font-weight-bold">Content <span class="text-danger">*</span></label>
                        <div class="editor-container">
                            <textarea id="content" name="content"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                        </div>
                        <small class="form-text text-muted">
                            Rich content editor with image/video support. Use Insert > Image/Media to add files directly into content.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="font-weight-bold">Additional Media Library</label>
                        
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
                                        <div class="text-center" style="font-size: 10px; margin-top: 2px;">
                                            <?php echo mb_substr($media['original_name'], 0, 12); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="mb-2"><strong>OR</strong></div>
                        <?php endif; ?>
                        
                        <label for="notice_media">Upload Media File (Optional):</label>
                        <input type="file" class="form-control-file" id="notice_media" name="notice_media" accept="image/*,video/*">
                        <small class="form-text text-muted">
                            Upload images (JPG, PNG, GIF, WebP) up to 5MB or videos (MP4, WebM, OGG) up to 10MB.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="status" class="font-weight-bold">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active (Visible on website)</option>
                            <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive (Hidden from website)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Add Popup Notice
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Live Preview -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="fas fa-eye mr-2"></i>Live Preview
            </div>
            <div class="card-body">
                <div class="preview-card" id="preview-container">
                    <div class="text-muted">Add content to see preview...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
// Get current directory path for upload handler
const currentPath = window.location.pathname;
const basePath = currentPath.substring(0, currentPath.lastIndexOf('/')) + '/';

// Enhanced TinyMCE initialization - FIXED VERSION
tinymce.init({
    selector: '#content',
    height: 400,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | ' +
        'bold italic forecolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'removeformat | image media link | code preview | help',
    content_style: `
        body { 
            font-family: Poppins, Arial, sans-serif; 
            font-size: 14px;
            line-height: 1.6;
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
    
    // Image upload configuration with better error handling
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
                
                // Test if the image URL is accessible
                const testImg = new Image();
                testImg.onload = function() {
                    console.log('✅ Image accessible at:', json.location);
                    resolve(json.location);
                };
                testImg.onerror = function() {
                    console.error('❌ Image not accessible at:', json.location);
                    // Still resolve with the URL, TinyMCE will handle it
                    resolve(json.location);
                };
                testImg.src = json.location;
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
            updatePreview();
        });
        
        // Enhanced image debugging
        editor.on('NodeChange', function(e) {
            const images = editor.getBody().querySelectorAll('img');
            images.forEach(function(img, index) {
                console.log('Image ' + index + ':', {
                    src: img.src,
                    complete: img.complete,
                    naturalWidth: img.naturalWidth,
                    naturalHeight: img.naturalHeight
                });
                
                if (!img.complete || img.naturalWidth === 0) {
                    console.error('Image failed to load:', img.src);
                    // Try to fix the src
                    if (img.src.indexOf('/assets/uploads/notices/') !== -1) {
                        const filename = img.src.split('/').pop();
                        const newSrc = '<?php echo SITE_URL; ?>/assets/uploads/notices/' + filename;
                        console.log('Trying alternative src:', newSrc);
                        img.src = newSrc;
                    }
                }
            });
        });
        
        // Add image load event listeners
        editor.on('SetContent', function() {
            setTimeout(function() {
                const images = editor.getBody().querySelectorAll('img');
                images.forEach(function(img) {
                    img.addEventListener('error', function() {
                        console.error('Image load error:', this.src);
                    });
                    
                    img.addEventListener('load', function() {
                        console.log('Image loaded successfully:', this.src);
                    });
                });
            }, 100);
        });
        
        // Update preview on init
        editor.on('init', function() {
            updatePreview();
        });
    }
});

function updatePreview() {
    const title = $('#title').val();
    const content = tinymce.get('content') ? tinymce.get('content').getContent() : '';
    
    let previewHtml = '';
    
    if (title) {
        previewHtml += '<h4 class="popup-notice mb-3">' + escapeHtml(title) + '</h4>';
    }
    
    if (content && content.trim() !== '') {
        previewHtml += '<div class="popup-notice">' + content + '</div>';
    }
    
    if (!previewHtml) {
        previewHtml = '<div class="text-muted">Add content to see preview...</div>';
    }
    
    $('#preview-container').html(previewHtml);
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

$(document).ready(function() {
    // Debug information
    console.log('Site URL:', '<?php echo SITE_URL; ?>');
    console.log('Current path:', window.location.href);
    
    // Title change preview update
    $('#title').on('input', function() {
        updatePreview();
    });
    
    // Media selection functionality
    $('.media-item').click(function() {
        $('.media-item').removeClass('selected');
        $(this).addClass('selected');
        
        const mediaPath = $(this).data('path');
        $('#selected_media_path').val(mediaPath);
        
        // Show selected media in preview if no content
        const currentContent = tinymce.get('content') ? tinymce.get('content').getContent() : '';
        if (!currentContent.trim()) {
            const mediaUrl = '<?php echo SITE_URL; ?>/' + mediaPath;
            const extension = mediaPath.split('.').pop().toLowerCase();
            
            let mediaHtml = '';
            if (['mp4', 'webm', 'ogg'].includes(extension)) {
                mediaHtml = '<video controls style="max-width:100%;"><source src="' + mediaUrl + '"></video>';
            } else {
                mediaHtml = '<img src="' + mediaUrl + '" style="max-width:100%;" alt="Selected media">';
            }
            
            $('#preview-container').html('<div class="popup-notice">' + mediaHtml + '</div>');
        }
    });
    
    // Clear selection when file input is used
    $('#notice_media').change(function() {
        if (this.files && this.files[0]) {
            $('.media-item').removeClass('selected');
            $('#selected_media_path').val('');
        }
    });
    
    // Form submission handling
    $('#popupNoticeForm').on('submit', function(e) {
        const content = tinymce.get('content') ? tinymce.get('content').getContent() : '';
        
        if (!content || content.trim() === '') {
            e.preventDefault();
            alert('Content is required');
            return false;
        }
        
        // Show loading state
        $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Adding...');
    });
    
    // Initial preview update
    setTimeout(updatePreview, 500);
});
</script>

<?php include '../../includes/footer.php'; ?>