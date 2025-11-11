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

// Get notice ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['error_message'] = 'Invalid notice ID';
    header("Location: index.php");
    exit;
}

$db = Database::getInstance();

// Get notice data and verify it's a popup notice
$notice = $db->getRow("SELECT * FROM notices WHERE id = ? AND type = 'popup'", [$id]);

if (!$notice) {
    $_SESSION['error_message'] = 'Popup notice not found';
    header("Location: index.php");
    exit;
}

// Get media files for selection
$mediaFiles = $db->getRows("SELECT * FROM media WHERE file_type IN ('image', 'video') ORDER BY created_at DESC");

// Include header
include '../../includes/header.php';
?>

<!-- Quill Editor CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

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
}

.ql-editor {
    min-height: 200px;
    font-family: 'Poppins', Arial, sans-serif;
    font-size: 14px;
}

.quill-container {
    height: 300px;
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

.current-media-preview {
    max-width: 150px;
    height: auto;
    border-radius: 4px;
    border: 1px solid #ddd;
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
    <li class="breadcrumb-item active">Edit Notice</li>
</ol>

<!-- Page Content -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-edit mr-1"></i> Edit Popup Notice
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

                <form method="post" action="update.php" enctype="multipart/form-data" id="popupNoticeForm">
                    <input type="hidden" name="id" value="<?php echo $notice['id']; ?>">
                    <input type="hidden" id="selected_media_path" name="selected_media_path">
                    
                    <div class="form-group">
                        <label for="title" class="font-weight-bold">Title</label>
                        <input type="text" class="form-control popup-notice" id="title" name="title" 
                               value="<?php echo htmlspecialchars($notice['title']); ?>"
                               placeholder="Enter notice title">
                        <small class="form-text text-muted">Optional but recommended for better organization</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="content" class="font-weight-bold">Content <span class="text-danger">*</span></label>
                        <textarea id="content" name="content" style="display: none;"><?php echo htmlspecialchars($notice['content']); ?></textarea>
                        <div class="quill-container">
                            <div id="popup_content_editor"></div>
                        </div>
                        <small class="form-text text-muted">
                            Rich content with formatting supported. Use the image button in toolbar to add images directly.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="font-weight-bold">Media Management</label>
                        
                        <?php if (!empty($notice['image_path'])): ?>
                        <div class="mb-3 p-3 bg-light border rounded">
                            <label>Current Media:</label><br>
                            <?php 
                            $imagePath = SITE_URL . '/assets/uploads/notices/' . $notice['image_path'];
                            $extension = strtolower(pathinfo($notice['image_path'], PATHINFO_EXTENSION));
                            ?>
                            <?php if (in_array($extension, ['mp4', 'webm', 'ogg'])): ?>
                                <video class="current-media-preview" controls>
                                    <source src="<?php echo $imagePath; ?>" type="video/<?php echo $extension; ?>">
                                </video>
                            <?php else: ?>
                                <img src="<?php echo $imagePath; ?>" class="current-media-preview" alt="Current Media">
                            <?php endif; ?>
                            <br><small class="text-muted"><?php echo $notice['image_path']; ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (count($mediaFiles) > 0): ?>
                        <div class="mb-3">
                            <label>Replace with Media from Library:</label>
                            <div class="media-selector">
                                <?php foreach ($mediaFiles as $media): ?>
                                    <div class="media-item" data-id="<?php echo $media['id']; ?>" data-path="<?php echo $media['file_path']; ?>">
                                        <?php if ($media['file_type'] === 'video'): ?>
                                            <video muted>
                                                <source src="<?php echo SITE_URL . '/' . $media['file_path']; ?>" type="<?php echo $media['mime_type']; ?>">
                                            </video>
                                        <?php else: ?>
                                            <img src="<?php echo SITE_URL . '/' . $media['file_path']; ?>" alt="<?php echo htmlspecialchars($media['original_name']); ?>">
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
                        
                        <label for="notice_media">Upload New Media:</label>
                        <input type="file" class="form-control-file" id="notice_media" name="notice_media" accept="image/*,video/*">
                        <small class="form-text text-muted">
                            Upload new media to replace current. Max: 10MB for videos, 5MB for images.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="status" class="font-weight-bold">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="active" <?php echo $notice['status'] === 'active' ? 'selected' : ''; ?>>Active (Visible on website)</option>
                            <option value="inactive" <?php echo $notice['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive (Hidden from website)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Update Popup Notice
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                        <a href="view.php?id=<?php echo $notice['id']; ?>" class="btn btn-info">
                            <i class="fas fa-eye mr-2"></i>Preview
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
                    <div class="text-muted">Loading preview...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
// Initialize Quill Editor
let quill = null;

function initQuillEditor() {
    const toolbarOptions = [
        ['bold', 'italic', 'underline', 'strike'],
        ['blockquote', 'code-block'],
        [{ 'header': 1 }, { 'header': 2 }],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'script': 'sub'}, { 'script': 'super' }],
        [{ 'indent': '-1'}, { 'indent': '+1' }],
        [{ 'direction': 'rtl' }],
        [{ 'size': ['small', false, 'large', 'huge'] }],
        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'font': [] }],
        [{ 'align': [] }],
        ['link', 'image'],
        ['clean']
    ];

    quill = new Quill('#popup_content_editor', {
        theme: 'snow',
        modules: {
            toolbar: {
                container: toolbarOptions,
                handlers: {
                    image: function() {
                        selectLocalImage();
                    }
                }
            }
        }
    });

    // Set initial content
    quill.root.innerHTML = <?php echo json_encode($notice['content']); ?>;

    // Update preview on content change
    quill.on('text-change', function() {
        updatePreview();
    });
}

// Handle image upload for Quill
function selectLocalImage() {
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/*');
    input.click();

    input.onchange = () => {
        const file = input.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('file', file);

            const range = quill.getSelection();
            quill.insertText(range.index, 'Uploading image...', 'user');

            fetch('upload_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                quill.deleteText(range.index, 'Uploading image...'.length);
                
                if (data.success) {
                    quill.insertEmbed(range.index, 'image', data.location);
                } else {
                    alert('Upload failed: ' + data.message);
                }
            })
            .catch(error => {
                quill.deleteText(range.index, 'Uploading image...'.length);
                alert('Upload failed: ' + error.message);
            });
        }
    };
}

function updatePreview() {
    const title = $('#title').val();
    const content = quill ? quill.root.innerHTML : '';
    
    let previewHtml = '';
    
    if (title) {
        previewHtml += '<h4 class="popup-notice mb-3">' + title + '</h4>';
    }
    
    if (content && content !== '<p><br></p>') {
        previewHtml += '<div class="popup-notice">' + content + '</div>';
    }
    
    if (!previewHtml) {
        previewHtml = '<div class="text-muted">Add content to see preview...</div>';
    }
    
    $('#preview-container').html(previewHtml);
}

$(document).ready(function() {
    // Initialize Quill editor
    setTimeout(function() {
        initQuillEditor();
        updatePreview(); // Initial preview
    }, 100);
    
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
    });
    
    // Form submission handling
    $('#popupNoticeForm').on('submit', function(e) {
        if (quill) {
            const quillContent = quill.root.innerHTML.trim();
            if (quillContent === '<p><br></p>' || !quillContent) {
                e.preventDefault();
                alert('Content is required');
                return false;
            }
            $('#content').val(quillContent);
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>