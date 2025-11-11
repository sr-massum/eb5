<?php 

/**
 * ExchangeBridge - Admin Panel Notices
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

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Get all notices with ordering
$db = Database::getInstance();
$notices = $db->getRows("SELECT * FROM notices ORDER BY type, position ASC, created_at DESC");

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

// Get media files for selection
$mediaFiles = $db->getRows("SELECT * FROM media WHERE file_type IN ('image', 'video') ORDER BY created_at DESC");

// Include header
include '../includes/header.php';
?>

<!-- Quill Editor CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<style>
.notice-content {
    font-family: 'Poppins', Arial, sans-serif;
    line-height: 1.6;
}

.scrolling-notice {
    font-family: 'Poppins', Arial, sans-serif;
    font-weight: 500;
    font-size: 14px;
    line-height: 1.6;
}

.popup-notice {
    font-family: 'Poppins', Arial, sans-serif;
    font-size: 14px;
    line-height: 1.6;
}

.notice-image, .notice-video {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 10px 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.preview-content {
    font-family: 'Poppins', Arial, sans-serif;
    line-height: 1.6;
    text-align: justify;
    padding: 15px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background-color: #f8f9fa;
}

.scrolling-preview {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 0.375rem;
    padding: 0.75rem;
    overflow: hidden;
    white-space: nowrap;
    position: relative;
}

.scrolling-preview .scrolling-text {
    display: inline-block;
    animation: scroll-left 15s linear infinite;
    font-family: 'Poppins', Arial, sans-serif;
    font-weight: 500;
    font-size: 14px;
}

@keyframes scroll-left {
    0% { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
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

.position-badge {
    background-color: #6c757d;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
}

/* Quill Editor Customization */
.ql-editor {
    min-height: 200px;
    font-family: 'Poppins', Arial, sans-serif;
    font-size: 14px;
}

.quill-container {
    height: 300px;
}

.ql-toolbar {
    border-top: 1px solid #ccc;
    border-left: 1px solid #ccc;
    border-right: 1px solid #ccc;
}

.ql-container {
    border-bottom: 1px solid #ccc;
    border-left: 1px solid #ccc;
    border-right: 1px solid #ccc;
}
</style>

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item active">Notices</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-bell mr-1"></i> Notice Management
        <button type="button" class="btn btn-primary btn-sm float-right" data-toggle="modal" data-target="#addNoticeModal">
            <i class="fas fa-plus"></i> Add New Notice
        </button>
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
        
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Position</th>
                        <th>Title</th>
                        <th>Content</th>
                        <th>Media</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($notices) > 0): ?>
                    <?php foreach ($notices as $notice): ?>
                    <tr>
                        <td>
                            <span class="badge badge-<?php echo $notice['type'] === 'scrolling' ? 'info' : 'warning'; ?>">
                                <?php echo ucfirst($notice['type']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($notice['type'] === 'scrolling'): ?>
                                <span class="position-badge"><?php echo $notice['position'] ?? 1; ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="notice-content"><?php echo $notice['title'] ? htmlspecialchars($notice['title']) : '(No title)'; ?></td>
                        <td class="notice-content">
                            <?php 
                            $content = strip_tags($notice['content']);
                            echo mb_substr($content, 0, 60, 'UTF-8') . (mb_strlen($content, 'UTF-8') > 60 ? '...' : ''); 
                            ?>
                        </td>
                        <td>
                            <?php if (!empty($notice['image_path'])): ?>
                                <?php 
                                $imagePath = SITE_URL . '/assets/uploads/notices/' . $notice['image_path'];
                                $extension = strtolower(pathinfo($notice['image_path'], PATHINFO_EXTENSION));
                                ?>
                                <?php if (in_array($extension, ['mp4', 'webm', 'ogg'])): ?>
                                    <video class="img-thumbnail" style="max-width: 50px; max-height: 50px;" muted>
                                        <source src="<?php echo $imagePath; ?>" type="video/<?php echo $extension; ?>">
                                    </video>
                                <?php else: ?>
                                    <img src="<?php echo $imagePath; ?>" alt="Notice Media" class="img-thumbnail" style="max-width: 50px; max-height: 50px;">
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">No media</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $notice['status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($notice['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d', strtotime($notice['created_at'])); ?></td>
                        <td>
                            <button type="button" class="btn btn-info btn-sm view-notice" 
                                data-id="<?php echo $notice['id']; ?>"
                                data-type="<?php echo $notice['type']; ?>"
                                data-title="<?php echo htmlspecialchars($notice['title']); ?>"
                                data-content="<?php echo htmlspecialchars($notice['content']); ?>"
                                data-image="<?php echo $notice['image_path']; ?>"
                                data-position="<?php echo $notice['position'] ?? 1; ?>"
                                data-status="<?php echo $notice['status']; ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-primary btn-sm edit-notice" 
                                data-id="<?php echo $notice['id']; ?>"
                                data-type="<?php echo $notice['type']; ?>"
                                data-title="<?php echo htmlspecialchars($notice['title']); ?>"
                                data-content="<?php echo htmlspecialchars($notice['content']); ?>"
                                data-image="<?php echo $notice['image_path']; ?>"
                                data-position="<?php echo $notice['position'] ?? 1; ?>"
                                data-status="<?php echo $notice['status']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="delete.php?id=<?php echo $notice['id']; ?>" class="btn btn-danger btn-sm delete-confirm">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No notices found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Notice Modal -->
<div class="modal fade" id="addNoticeModal" tabindex="-1" role="dialog" aria-labelledby="addNoticeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addNoticeModalLabel">Add New Notice</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="add.php" method="post" enctype="multipart/form-data" id="addNoticeForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="type" class="required">Notice Type</label>
                                <select class="form-control" id="type" name="type" required onchange="toggleNoticeOptions()">
                                    <option value="scrolling">Scrolling Notice</option>
                                    <option value="popup">Popup Notice</option>
                                </select>
                                <small class="form-text text-muted">Choose scrolling for moving text or popup for rich content with media</small>
                            </div>
                            
                            <div class="form-group" id="position-group">
                                <label for="position">Display Position</label>
                                <select class="form-control" id="position" name="position">
                                    <option value="1">Position 1 (First)</option>
                                    <option value="2">Position 2</option>
                                    <option value="3">Position 3</option>
                                    <option value="4">Position 4</option>
                                    <option value="5">Position 5</option>
                                </select>
                                <small class="form-text text-muted">For scrolling notices, they will display in order based on position</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="title">Title</label>
                                <input type="text" class="form-control notice-content" id="title" name="title" placeholder="Enter notice title">
                                <small class="form-text text-muted">Optional for scrolling notices, recommended for popup notices</small>
                            </div>
                            
                            <div class="form-group" id="content-group">
                                <label for="content" class="required">Content</label>
                                
                                <!-- Single content field for both types -->
                                <textarea id="content" name="content" style="display: none;"></textarea>
                                
                                <div id="scrolling-content">
                                    <textarea class="form-control scrolling-notice" id="scrolling_content_input" rows="4" placeholder="Enter short message for scrolling display"></textarea>
                                    <small class="form-text text-muted">Keep it short for scrolling notices. Use bold text for emphasis.</small>
                                </div>
                                <div id="popup-content" style="display: none;">
                                    <div class="quill-container">
                                        <div id="popup_content_editor"></div>
                                    </div>
                                    <small class="form-text text-muted">Rich content with formatting, images supported. Click image icon to upload.</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4" id="media-selection" style="display: none;">
                            <div class="form-group">
                                <label>Select Media from Library</label>
                                <div class="media-selector">
                                    <?php if (count($mediaFiles) > 0): ?>
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
                                    <?php else: ?>
                                        <p class="text-muted">No media files available</p>
                                    <?php endif; ?>
                                </div>
                                
                                <hr>
                                
                                <label for="notice_image">Or Upload New Media</label>
                                <input type="file" class="form-control-file" id="notice_image" name="notice_image" accept="image/*,video/*">
                                <small class="form-text text-muted">Upload image or video. Max size: 10MB</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Notice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Notice Modal -->
<div class="modal fade" id="editNoticeModal" tabindex="-1" role="dialog" aria-labelledby="editNoticeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editNoticeModalLabel">Edit Notice</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="update.php" method="post" enctype="multipart/form-data" id="editNoticeForm">
                <input type="hidden" id="edit_id" name="id">
                <input type="hidden" id="selected_media_path" name="selected_media_path">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="edit_type" class="required">Notice Type</label>
                                <select class="form-control" id="edit_type" name="type" required onchange="toggleEditNoticeOptions()">
                                    <option value="scrolling">Scrolling Notice</option>
                                    <option value="popup">Popup Notice</option>
                                </select>
                            </div>
                            
                            <div class="form-group" id="edit-position-group">
                                <label for="edit_position">Display Position</label>
                                <select class="form-control" id="edit_position" name="position">
                                    <option value="1">Position 1 (First)</option>
                                    <option value="2">Position 2</option>
                                    <option value="3">Position 3</option>
                                    <option value="4">Position 4</option>
                                    <option value="5">Position 5</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_title">Title</label>
                                <input type="text" class="form-control notice-content" id="edit_title" name="title" placeholder="Enter notice title">
                            </div>
                            
                            <div class="form-group" id="edit-content-group">
                                <label for="edit_content" class="required">Content</label>
                                
                                <!-- Single content field for both types -->
                                <textarea id="edit_content" name="content" style="display: none;"></textarea>
                                
                                <div id="edit-scrolling-content">
                                    <textarea class="form-control scrolling-notice" id="edit_scrolling_content_input" rows="4" placeholder="Enter short message for scrolling"></textarea>
                                </div>
                                <div id="edit-popup-content" style="display: none;">
                                    <div class="quill-container">
                                        <div id="edit_popup_content_editor"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_status">Status</label>
                                <select class="form-control" id="edit_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4" id="edit-media-selection" style="display: none;">
                            <div class="form-group">
                                <div id="current-media" style="display: none; margin-bottom: 15px;">
                                    <label>Current Media:</label>
                                    <div id="current-media-preview"></div>
                                </div>
                                
                                <label>Select Media from Library</label>
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
                                
                                <hr>
                                
                                <label for="edit_notice_image">Or Upload New Media</label>
                                <input type="file" class="form-control-file" id="edit_notice_image" name="notice_image" accept="image/*,video/*">
                                <small class="form-text text-muted">Upload new media to replace current. Max size: 10MB</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Notice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Notice Modal -->
<div class="modal fade" id="previewNoticeModal" tabindex="-1" role="dialog" aria-labelledby="previewNoticeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewNoticeModalLabel">Notice Preview</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="preview-content" class="preview-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
// Global Quill instances
let addQuill = null;
let editQuill = null;

// Initialize Quill Editor
function initQuillEditor(containerId) {
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

    const quill = new Quill(containerId, {
        theme: 'snow',
        modules: {
            toolbar: {
                container: toolbarOptions,
                handlers: {
                    image: function() {
                        selectLocalImage(this.quill);
                    }
                }
            }
        }
    });

    return quill;
}

// Handle image upload
function selectLocalImage(quill) {
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/*');
    input.click();

    input.onchange = () => {
        const file = input.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('file', file);

            // Show loading
            const range = quill.getSelection();
            quill.insertText(range.index, 'Uploading image...', 'user');

            fetch('upload_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Remove loading text
                quill.deleteText(range.index, 'Uploading image...'.length);
                
                if (data.success) {
                    // Insert image
                    quill.insertEmbed(range.index, 'image', data.location);
                } else {
                    alert('Upload failed: ' + data.message);
                }
            })
            .catch(error => {
                // Remove loading text
                quill.deleteText(range.index, 'Uploading image...'.length);
                alert('Upload failed: ' + error.message);
            });
        }
    };
}

function toggleNoticeOptions() {
    const type = document.getElementById('type').value;
    const scrollingContent = document.getElementById('scrolling-content');
    const popupContent = document.getElementById('popup-content');
    const mediaSelection = document.getElementById('media-selection');
    const positionGroup = document.getElementById('position-group');
    
    if (type === 'scrolling') {
        scrollingContent.style.display = 'block';
        popupContent.style.display = 'none';
        mediaSelection.style.display = 'none';
        positionGroup.style.display = 'block';
        
        // Destroy Quill instance if exists
        if (addQuill) {
            addQuill = null;
        }
    } else {
        scrollingContent.style.display = 'none';
        popupContent.style.display = 'block';
        mediaSelection.style.display = 'block';
        positionGroup.style.display = 'none';
        
        // Initialize Quill editor after a short delay
        setTimeout(function() {
            if (!addQuill) {
                addQuill = initQuillEditor('#popup_content_editor');
            }
        }, 100);
    }
}

function toggleEditNoticeOptions() {
    const type = document.getElementById('edit_type').value;
    const scrollingContent = document.getElementById('edit-scrolling-content');
    const popupContent = document.getElementById('edit-popup-content');
    const mediaSelection = document.getElementById('edit-media-selection');
    const positionGroup = document.getElementById('edit-position-group');
    
    if (type === 'scrolling') {
        scrollingContent.style.display = 'block';
        popupContent.style.display = 'none';
        mediaSelection.style.display = 'none';
        positionGroup.style.display = 'block';
        
        // Destroy Quill instance if exists
        if (editQuill) {
            editQuill = null;
        }
    } else {
        scrollingContent.style.display = 'none';
        popupContent.style.display = 'block';
        mediaSelection.style.display = 'block';
        positionGroup.style.display = 'none';
        
        // Initialize Quill editor after a short delay
        setTimeout(function() {
            if (!editQuill) {
                editQuill = initQuillEditor('#edit_popup_content_editor');
            }
        }, 100);
    }
}

$(document).ready(function() {
    // Initialize default state
    toggleNoticeOptions();
    
    // Update content field when scrolling textarea changes
    $('#scrolling_content_input').on('input', function() {
        $('#content').val($(this).val());
    });
    
    $('#edit_scrolling_content_input').on('input', function() {
        $('#edit_content').val($(this).val());
    });
    
    // Media selection functionality
    $(document).on('click', '.media-item', function() {
        $('.media-item').removeClass('selected');
        $(this).addClass('selected');
        
        const mediaPath = $(this).data('path');
        $('#selected_media_path').val(mediaPath);
    });
    
    // Form submission handling
    $('#addNoticeForm').on('submit', function(e) {
        const type = $('#type').val();
        
        if (type === 'scrolling') {
            const scrollingContent = $('#scrolling_content_input').val().trim();
            if (!scrollingContent) {
                e.preventDefault();
                alert('Content is required');
                return false;
            }
            $('#content').val(scrollingContent);
        } else {
            if (addQuill) {
                const quillContent = addQuill.root.innerHTML.trim();
                if (quillContent === '<p><br></p>' || !quillContent) {
                    e.preventDefault();
                    alert('Content is required');
                    return false;
                }
                $('#content').val(quillContent);
            }
        }
    });
    
    $('#editNoticeForm').on('submit', function(e) {
        const type = $('#edit_type').val();
        
        if (type === 'scrolling') {
            const scrollingContent = $('#edit_scrolling_content_input').val().trim();
            if (!scrollingContent) {
                e.preventDefault();
                alert('Content is required');
                return false;
            }
            $('#edit_content').val(scrollingContent);
        } else {
            if (editQuill) {
                const quillContent = editQuill.root.innerHTML.trim();
                if (quillContent === '<p><br></p>' || !quillContent) {
                    e.preventDefault();
                    alert('Content is required');
                    return false;
                }
                $('#edit_content').val(quillContent);
            }
        }
    });
    
    // Edit notice functionality
    $('.edit-notice').click(function() {
        const id = $(this).data('id');
        const type = $(this).data('type');
        const title = $(this).data('title');
        const content = $(this).data('content');
        const image = $(this).data('image');
        const position = $(this).data('position');
        const status = $(this).data('status');
        
        $('#edit_id').val(id);
        $('#edit_type').val(type);
        $('#edit_title').val(title);
        $('#edit_position').val(position);
        $('#edit_status').val(status);
        
        if (type === 'scrolling') {
            $('#edit_scrolling_content_input').val(content);
            $('#edit_content').val(content);
        } else {
            // For popup content, set the content after Quill is initialized
            setTimeout(function() {
                if (editQuill) {
                    editQuill.root.innerHTML = content;
                    $('#edit_content').val(content);
                }
            }, 200);
        }
        
        if (image) {
            $('#current-media').show();
            const imagePath = '<?php echo SITE_URL; ?>/assets/uploads/notices/' + image;
            const extension = image.split('.').pop().toLowerCase();
            
            if (['mp4', 'webm', 'ogg'].includes(extension)) {
                $('#current-media-preview').html('<video class="notice-video" style="max-width: 200px;" controls><source src="' + imagePath + '" type="video/' + extension + '"></video>');
            } else {
                $('#current-media-preview').html('<img src="' + imagePath + '" class="notice-image" style="max-width: 200px;" alt="Current Media">');
            }
        } else {
            $('#current-media').hide();
        }
        
        toggleEditNoticeOptions();
        $('#editNoticeModal').modal('show');
    });
    
    // Preview functionality
    $('.view-notice').click(function() {
        const type = $(this).data('type');
        const title = $(this).data('title');
        const content = $(this).data('content');
        const image = $(this).data('image');
        const position = $(this).data('position');
        
        let previewHtml = '';
        
        if (title) {
            previewHtml += '<h4 class="notice-content mb-3">' + $('<div>').text(title).html() + '</h4>';
        }
        
        if (type === 'scrolling') {
            previewHtml += '<div class="mb-2"><strong>Position:</strong> ' + position + '</div>';
            previewHtml += '<div class="scrolling-preview"><div class="scrolling-text">' + $('<div>').text(content).html() + '</div></div>';
            previewHtml += '<div class="mt-3"><small class="text-muted">This is how it will appear as scrolling notice on the website.</small></div>';
        } else {
            if (image) {
                const imagePath = '<?php echo SITE_URL; ?>/assets/uploads/notices/' + image;
                const extension = image.split('.').pop().toLowerCase();
                
                if (['mp4', 'webm', 'ogg'].includes(extension)) {
                    previewHtml += '<video class="notice-video" controls><source src="' + imagePath + '" type="video/' + extension + '"></video>';
                } else {
                    previewHtml += '<img src="' + imagePath + '" class="notice-image" alt="Notice Media">';
                }
            }
            previewHtml += '<div class="popup-notice">' + content + '</div>';
        }
        
        $('#preview-content').html(previewHtml);
        $('#previewNoticeModal').modal('show');
    });
    
    // Delete confirmation
    $('.delete-confirm').click(function(e) {
        if (!confirm('Are you sure you want to delete this notice? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
    
    // Modal cleanup
    $('#addNoticeModal').on('hidden.bs.modal', function() {
        if (addQuill) {
            addQuill = null;
        }
        // Reset form
        $('#addNoticeForm')[0].reset();
        $('#content').val('');
        toggleNoticeOptions();
    });
    
    $('#editNoticeModal').on('hidden.bs.modal', function() {
        if (editQuill) {
            editQuill = null;
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>