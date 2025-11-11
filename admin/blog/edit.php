<?php 

/**
 * ExchangeBridge - Admin Panel Edit Blog
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


// Set UTF-8 encoding headers
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

$user = Auth::getUser();
$db = Database::getInstance();

// Set MySQL connection to UTF-8
$db->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

// Get post ID
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$postId) {
    $_SESSION['error_message'] = 'Invalid post ID';
    header("Location: index.php");
    exit;
}

// Get existing post data
$post = $db->getRow("SELECT * FROM blog_posts WHERE id = ?", [$postId]);

if (!$post) {
    $_SESSION['error_message'] = 'Post not found';
    header("Location: index.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? sanitizeInput($_POST['title']) : '';
    $slug = isset($_POST['slug']) ? sanitizeInput($_POST['slug']) : '';
    $excerpt = isset($_POST['excerpt']) ? sanitizeInput($_POST['excerpt']) : '';
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'draft';
    $metaTitle = isset($_POST['meta_title']) ? sanitizeInput($_POST['meta_title']) : '';
    $metaDescription = isset($_POST['meta_description']) ? sanitizeInput($_POST['meta_description']) : '';
    $metaKeywords = isset($_POST['meta_keywords']) ? sanitizeInput($_POST['meta_keywords']) : '';
    $selectedMediaPath = isset($_POST['selected_media_path']) ? sanitizeInput($_POST['selected_media_path']) : '';
    
    // Handle featured image upload
    $featuredImage = $post['featured_image']; // Keep existing image by default
    $uploadDir = '../../assets/uploads/blog/';
    
    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Handle featured image upload
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $fileName = $_FILES['featured_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = 'featured_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
            $uploadPath = $uploadDir . $newFileName;
            
            if ($_FILES['featured_image']['size'] <= 5 * 1024 * 1024) { // 5MB limit
                if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $uploadPath)) {
                    // Delete old featured image if exists
                    if (!empty($post['featured_image']) && file_exists($uploadDir . $post['featured_image'])) {
                        unlink($uploadDir . $post['featured_image']);
                    }
                    $featuredImage = $newFileName;
                    chmod($uploadPath, 0644);
                } else {
                    $_SESSION['error_message'] = 'Failed to upload featured image';
                }
            } else {
                $_SESSION['error_message'] = 'Featured image size too large. Max: 5MB';
            }
        } else {
            $_SESSION['error_message'] = 'Invalid featured image format. Allowed: ' . implode(', ', $allowedExtensions);
        }
    }
    
    // Handle blog media upload
    $mediaPath = '';
    if (!empty($selectedMediaPath)) {
        // Clean and validate the selected media path
        $selectedMediaPath = str_replace(['../', './'], '', $selectedMediaPath);
        $sourcePath = '../../' . $selectedMediaPath;
        
        if (file_exists($sourcePath)) {
            $extension = pathinfo($selectedMediaPath, PATHINFO_EXTENSION);
            $newFileName = 'blog_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            $targetPath = $uploadDir . $newFileName;
            
            if (copy($sourcePath, $targetPath)) {
                $mediaPath = $newFileName;
                chmod($targetPath, 0644);
            }
        }
    }
    
    // Handle direct file upload if no media selected from library
    if (empty($mediaPath) && isset($_FILES['blog_media']) && $_FILES['blog_media']['error'] === UPLOAD_ERR_OK) {
        $fileName = $_FILES['blog_media']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = 'blog_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
            $uploadPath = $uploadDir . $newFileName;
            
            $maxSize = in_array($fileExtension, ['mp4', 'webm', 'ogg']) ? 10 * 1024 * 1024 : 5 * 1024 * 1024;
            
            if ($_FILES['blog_media']['size'] <= $maxSize) {
                if (move_uploaded_file($_FILES['blog_media']['tmp_name'], $uploadPath)) {
                    $mediaPath = $newFileName;
                    chmod($uploadPath, 0644);
                } else {
                    $_SESSION['error_message'] = 'Failed to upload blog media file';
                }
            } else {
                $_SESSION['error_message'] = 'File size too large. Max: ' . round($maxSize / 1024 / 1024, 1) . 'MB';
            }
        } else {
            $_SESSION['error_message'] = 'Invalid file format. Allowed: ' . implode(', ', $allowedExtensions);
        }
    }
    
    // Auto-generate slug if empty
    if (empty($slug) && !empty($title)) {
        $slug = createSlug($title);
    }
    
    // Validate form data
    if (empty(trim($title))) {
        $_SESSION['error_message'] = 'Title is required';
    } elseif (empty(trim($content))) {
        $_SESSION['error_message'] = 'Content is required';
    } elseif (empty(trim($slug))) {
        $_SESSION['error_message'] = 'Slug is required';
    } else {
        // Check if slug already exists (excluding current post)
        $existingPost = $db->getRow("SELECT id FROM blog_posts WHERE slug = ? AND id != ?", [$slug, $postId]);
        if ($existingPost) {
            $_SESSION['error_message'] = 'Slug already exists. Please choose a different one.';
        } else {
            try {
                // Update blog post
                $postData = [
                    'title' => $title,
                    'slug' => $slug,
                    'content' => $content,
                    'excerpt' => $excerpt,
                    'status' => $status,
                    'meta_title' => $metaTitle,
                    'meta_description' => $metaDescription,
                    'meta_keywords' => $metaKeywords,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Add featured image if changed
                if (!empty($featuredImage)) {
                    $postData['featured_image'] = $featuredImage;
                }
                
                $updated = $db->update('blog_posts', $postData, "id = $postId");
                
                if ($updated !== false) {
                    // Log the media files to media table
                    if (!empty($featuredImage) && isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                        $mediaData = [
                            'filename' => $featuredImage,
                            'original_name' => $_FILES['featured_image']['name'],
                            'file_path' => 'assets/uploads/blog/' . $featuredImage,
                            'file_size' => $_FILES['featured_image']['size'],
                            'mime_type' => $_FILES['featured_image']['type'],
                            'file_type' => 'image',
                            'uploaded_by' => $user['id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                        
                        try {
                            $db->insert('media', $mediaData);
                        } catch (Exception $e) {
                            // Continue even if media logging fails
                        }
                    }
                    
                    if (!empty($mediaPath)) {
                        $mediaData = [
                            'filename' => $mediaPath,
                            'original_name' => !empty($_FILES['blog_media']['name']) ? $_FILES['blog_media']['name'] : $mediaPath,
                            'file_path' => 'assets/uploads/blog/' . $mediaPath,
                            'file_size' => !empty($_FILES['blog_media']['size']) ? $_FILES['blog_media']['size'] : filesize($uploadDir . $mediaPath),
                            'mime_type' => !empty($_FILES['blog_media']['type']) ? $_FILES['blog_media']['type'] : mime_content_type($uploadDir . $mediaPath),
                            'file_type' => 'image',
                            'uploaded_by' => $user['id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                        
                        try {
                            $db->insert('media', $mediaData);
                        } catch (Exception $e) {
                            // Continue even if media logging fails
                        }
                    }
                    
                    $_SESSION['success_message'] = 'Blog post updated successfully';
                    header("Location: index.php");
                    exit;
                } else {
                    $_SESSION['error_message'] = 'Failed to update blog post';
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Get media files for selection
$mediaFiles = $db->getRows("SELECT * FROM media WHERE file_type IN ('image', 'video') ORDER BY created_at DESC LIMIT 100");

// Include header
include '../includes/header.php';
?>

<!-- Enhanced TinyMCE for Multilingual support -->
<script src="https://cdn.tiny.cloud/1/hhiyirqkh3fnrmgjs7nq6tpk6nqb62m3vww7smgrz7kjfv6v/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<style>
/* Enhanced font support styles */
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@100;200;300;400;500;600;700;800;900&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');

/* Force UTF-8 encoding for all text */
* {
    -webkit-font-feature-settings: "kern" 1;
    font-feature-settings: "kern" 1;
    text-rendering: optimizeLegibility;
}

/* Primary font family */
.blog-content {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', 'Roboto', Arial, sans-serif !important;
    line-height: 1.8 !important;
    direction: ltr;
    unicode-bidi: embed;
    font-weight: 400;
    font-size: 16px;
}

/* Mixed language support */
.multilingual-text {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', 'Roboto', Arial, sans-serif !important;
    line-height: 1.8 !important;
    font-weight: 400;
}

/* Form controls with font support */
.form-control {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif !important;
    font-size: 16px !important;
    line-height: 1.8 !important;
    font-weight: 400 !important;
}

textarea.form-control {
    font-size: 16px !important;
    line-height: 1.8 !important;
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', Arial, sans-serif !important;
}

input[type="text"], input[type="email"], input[type="password"] {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif !important;
    font-size: 16px !important;
}

/* Labels and text */
label, .form-text {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif !important;
    font-weight: 500;
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

.slug-preview {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 0.375rem 0.75rem;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    color: #495057;
}

/* Editor container */
.editor-container {
    min-height: 400px;
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

/* Preview content */
.preview-content {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif !important;
    line-height: 1.8;
    text-align: justify;
    padding: 15px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background-color: #f8f9fa;
    max-height: 400px;
    overflow-y: auto;
}

.preview-content img, .preview-content video {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
    margin: 10px 0;
}

.current-image-preview {
    max-width: 200px;
    border-radius: 8px;
    margin-top: 10px;
}
</style>

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item">
        <a href="index.php">Blog Management</a>
    </li>
    <li class="breadcrumb-item active">Edit Post</li>
</ol>

<!-- Page Content -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-edit mr-1"></i> Edit Blog Post
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error_message']); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <form action="edit.php?id=<?php echo $postId; ?>" method="POST" enctype="multipart/form-data" accept-charset="UTF-8" id="editPostForm">
                    <input type="hidden" id="selected_media_path" name="selected_media_path">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="title">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="title" class="form-control blog-content" required placeholder="Enter post title" value="<?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="slug">Slug <span class="text-danger">*</span></label>
                                <input type="text" name="slug" id="slug" class="form-control" required value="<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                                <small class="form-text text-muted">URL-friendly version of the title</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>URL Preview:</label>
                        <div class="slug-preview" id="url-preview">../../blog-single.php?slug=<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="excerpt">Excerpt</label>
                        <textarea name="excerpt" id="excerpt" class="form-control blog-content" rows="3" placeholder="Brief description of the post"><?php echo htmlspecialchars($post['excerpt'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <small class="form-text text-muted">Brief description of the post</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Content <span class="text-danger">*</span></label>
                        <div class="editor-container">
                            <textarea name="content" id="content" class="form-control blog-content" rows="10" required><?php echo htmlspecialchars($post['content'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <small class="form-text text-muted">Rich content with formatting, images, and links. Use Insert > Image/Media to add files directly into content.</small>
                    </div>
                    
                    <!-- Media Library Section -->
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
                            <input type="file" id="blog_media" name="blog_media" accept="image/*,video/*" style="display: none;">
                            <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                            <p class="mb-2">Drag & drop media files here or click to browse</p>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('blog_media').click();">
                                Browse Files
                            </button>
                            <small class="form-text text-muted mt-2">
                                Upload images (JPG, PNG, GIF, WebP) up to 5MB or videos (MP4, WebM, OGG) up to 10MB.
                            </small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="draft" <?php echo ($post['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                    <option value="published" <?php echo ($post['status'] === 'published') ? 'selected' : ''; ?>>Published</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="featured_image">Featured Image</label>
                                <input type="file" name="featured_image" id="featured_image" class="form-control-file" accept="image/*">
                                <?php if (!empty($post['featured_image'])): ?>
                                    <div class="mt-2">
                                        <img src="../../assets/uploads/blog/<?php echo htmlspecialchars($post['featured_image'], ENT_QUOTES, 'UTF-8'); ?>" 
                                             alt="Current Featured Image" class="current-image-preview">
                                        <br><small class="text-muted">Current featured image (upload new to replace)</small>
                                    </div>
                                <?php endif; ?>
                                <small class="form-text text-muted">Recommended size: 800x400px. Max size: 5MB</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SEO Fields -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">SEO Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="meta_title">Meta Title</label>
                                <input type="text" name="meta_title" id="meta_title" class="form-control blog-content" placeholder="SEO optimized title" value="<?php echo htmlspecialchars($post['meta_title'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_description">Meta Description</label>
                                <textarea name="meta_description" id="meta_description" class="form-control blog-content" rows="2" placeholder="Brief description for search engines"><?php echo htmlspecialchars($post['meta_description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_keywords">Meta Keywords</label>
                                <input type="text" name="meta_keywords" id="meta_keywords" class="form-control blog-content" placeholder="keyword1, keyword2, keyword3" value="<?php echo htmlspecialchars($post['meta_keywords'], ENT_QUOTES, 'UTF-8'); ?>">
                                <small class="form-text text-muted">Separate with commas</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Update Post
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
                <div class="preview-content" id="preview-container">
                    <div class="text-muted">Loading preview...</div>
                </div>
            </div>
        </div>
        
        <!-- SEO Preview -->
        <div class="card mt-3">
            <div class="card-header bg-success text-white">
                <i class="fas fa-search mr-2"></i>SEO Preview
            </div>
            <div class="card-body">
                <div id="seo-preview">
                    <div class="text-muted">Loading SEO preview...</div>
                </div>
            </div>
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

// Enhanced TinyMCE initialization with media upload support - FIXED VERSION
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
    images_upload_base_path: '<?php echo SITE_URL; ?>/',
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
                    reject('Invalid JSON response: ' + xhr.responseText);
                    return;
                }
                
                if (!json || !json.success || typeof json.location != 'string') {
                    reject(json.message || 'Invalid JSON: ' + xhr.responseText);
                    return;
                }
                
                // Debug log - you can remove this after testing
                console.log('Image uploaded successfully:', json.location);
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
            updatePreview();
        });
        
        // Set default font and encoding
        editor.on('init', function() {
            editor.getBody().style.fontFamily = "'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif";
            editor.getBody().style.fontSize = "16px";
            editor.getBody().style.lineHeight = "1.8";
            editor.getBody().style.direction = "ltr";
            editor.getBody().style.unicodeBidi = "embed";
            editor.getBody().style.textRendering = "optimizeLegibility";
            updatePreview();
        });
        
        // Debug image loads in editor
        editor.on('NodeChange', function(e) {
            const images = editor.getBody().querySelectorAll('img');
            images.forEach(function(img) {
                img.onerror = function() {
                    console.error('Failed to load image:', img.src);
                };
                img.onload = function() {
                    console.log('Image loaded successfully:', img.src);
                };
            });
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

$(document).ready(function() {
    // Set document encoding
    document.charset = "UTF-8";
    
    // Function to create slug from title
    function createSlug(text) {
        // Basic transliteration for Bengali characters
        const bengaliToEnglish = {
            'অ': 'a', 'আ': 'aa', 'ই': 'i', 'ঈ': 'ii', 'উ': 'u', 'ঊ': 'uu', 'এ': 'e', 'ঐ': 'oi', 'ও': 'o', 'ঔ': 'ou',
            'ক': 'k', 'খ': 'kh', 'গ': 'g', 'ঘ': 'gh', 'চ': 'ch', 'ছ': 'chh', 'জ': 'j', 'ঝ': 'jh', 'ট': 't', 'ঠ': 'th',
            'ড': 'd', 'ঢ': 'dh', 'ণ': 'n', 'ত': 't', 'থ': 'th', 'দ': 'd', 'ধ': 'dh', 'ন': 'n', 'প': 'p', 'ফ': 'ph',
            'ব': 'b', 'ভ': 'bh', 'ম': 'm', 'য': 'y', 'র': 'r', 'ল': 'l', 'শ': 'sh', 'ষ': 'sh', 'স': 's', 'হ': 'h',
            'ড়': 'r', 'ঢ়': 'rh', 'য়': 'y', 'ৎ': 't', 'ং': 'ng', 'ঃ': 'h', 'ঁ': 'n'
        };
        
        let slug = text.toLowerCase();
        
        // Replace Bengali characters
        for (let bengali in bengaliToEnglish) {
            slug = slug.replace(new RegExp(bengali, 'g'), bengaliToEnglish[bengali]);
        }
        
        return slug
            .replace(/[^\w\s-]/g, '') // Remove special characters
            .replace(/[\s_-]+/g, '-') // Replace spaces and underscores with hyphens
            .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
    }
    
    // Auto-generate slug from title
    $('#title').on('input', function() {
        var title = $(this).val();
        var slug = createSlug(title);
        $('#slug').val(slug);
        $('#url-preview').text('../../blog-single.php?slug=' + slug);
        updateSEOPreview();
    });
    
    // Update URL preview when slug is manually changed
    $('#slug').on('input', function() {
        var slug = $(this).val();
        $('#url-preview').text('../../blog-single.php?slug=' + slug);
    });
    
    // Update previews when content changes
    $('#excerpt, #meta_title, #meta_description').on('input', function() {
        updatePreview();
        updateSEOPreview();
    });
    
    // Media selection functionality
    $('.media-item').click(function() {
        $('.media-item').removeClass('selected');
        $(this).addClass('selected');
        
        const mediaPath = $(this).data('path');
        $('#selected_media_path').val(mediaPath);
    });
    
    // Clear selection when file input is used
    $('#blog_media').change(function() {
        if (this.files && this.files[0]) {
            $('.media-item').removeClass('selected');
            $('#selected_media_path').val('');
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
                $('#selected_media_path').val('');
            }
        });
        
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });
    }
    
    // Setup drag and drop
    setupDragDrop(document.getElementById('media-upload-area'), document.getElementById('blog_media'));
    
    // Form submission handling
    $('#editPostForm').on('submit', function(e) {
        const title = $('#title').val().trim();
        const content = tinymce.get('content') ? tinymce.get('content').getContent().trim() : '';
        
        if (!title) {
            e.preventDefault();
            alert('Title is required');
            return false;
        }
        
        if (!content) {
            e.preventDefault();
            alert('Content is required');
            return false;
        }
        
        // Show loading state
        $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Updating...');
    });
    
    // Initial preview update
    setTimeout(function() {
        updatePreview();
        updateSEOPreview();
    }, 1000);
});

function updatePreview() {
    const title = $('#title').val();
    const excerpt = $('#excerpt').val();
    const content = tinymce.get('content') ? tinymce.get('content').getContent() : '';
    
    let previewHtml = '';
    
    if (title) {
        previewHtml += '<h2 class="blog-content mb-3">' + escapeHtml(title) + '</h2>';
    }
    
    if (excerpt) {
        previewHtml += '<p class="blog-content text-muted mb-3"><em>' + escapeHtml(excerpt) + '</em></p>';
    }
    
    if (content && content.trim() !== '') {
        previewHtml += '<div class="blog-content">' + content + '</div>';
    }
    
    if (!previewHtml) {
        previewHtml = '<div class="text-muted">Add content to see preview...</div>';
    }
    
    $('#preview-container').html(previewHtml);
}

function updateSEOPreview() {
    const title = $('#meta_title').val() || $('#title').val();
    const description = $('#meta_description').val() || $('#excerpt').val();
    const slug = $('#slug').val();
    
    let seoHtml = '';
    
    if (title || description) {
        seoHtml += '<div class="seo-result">';
        seoHtml += '<h6 class="text-primary mb-1">' + (title ? escapeHtml(title) : 'No title') + '</h6>';
        seoHtml += '<div class="text-success small mb-1">../../blog-single.php?slug=' + (slug || 'your-post-slug') + '</div>';
        seoHtml += '<div class="text-muted small">' + (description ? escapeHtml(description) : 'No meta description') + '</div>';
        seoHtml += '</div>';
    } else {
        seoHtml = '<div class="text-muted">Fill in title and meta description to see SEO preview...</div>';
    }
    
    $('#seo-preview').html(seoHtml);
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
</script>

<?php include '../includes/footer.php'; ?>