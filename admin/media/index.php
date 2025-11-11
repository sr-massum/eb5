<?php 

/**
 * ExchangeBridge - Admin Panel Media
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

$user = Auth::getUser();
$pageTitle = 'Media Library';

// Helper functions
function getFileType($mimeType) {
    if (strpos($mimeType, 'image/') === 0) return 'image';
    if (strpos($mimeType, 'video/') === 0) return 'video';
    if (strpos($mimeType, 'audio/') === 0) return 'audio';
    if ($mimeType === 'application/pdf') return 'pdf';
    if (in_array($mimeType, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) return 'document';
    return 'other';
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

function getMimeTypeFromExtension($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mimeTypes = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif',
        'pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'mp4' => 'video/mp4', 'avi' => 'video/avi', 'mov' => 'video/quicktime',
        'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg'
    ];
    return isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';
}

// Function to scan and import existing files
function scanAndImportExistingFiles() {
    $db = Database::getInstance();
    global $user;
    
    $uploadDirs = [
        'blog' => '../../assets/uploads/blog/',
        'currencies' => '../../assets/uploads/currencies/',
        'media' => '../../assets/uploads/media/',
        'notices' => '../../assets/uploads/notices/'
    ];
    
    $importedCount = 0;
    
    foreach ($uploadDirs as $category => $dir) {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), array('.', '..'));
            
            foreach ($files as $filename) {
                $filePath = $dir . $filename;
                if (is_file($filePath)) {
                    // Check if file already exists in database
                    $existingFile = $db->getRow("SELECT id FROM media WHERE file_path = ?", ["assets/uploads/$category/" . $filename]);
                    
                    if (!$existingFile) {
                        $fileSize = filesize($filePath);
                        $mimeType = getMimeTypeFromExtension($filename);
                        $fileType = getFileType($mimeType);
                        
                        // Insert into database
                        $mediaId = $db->insert('media', [
                            'filename' => $filename,
                            'original_name' => $filename,
                            'file_path' => "assets/uploads/$category/" . $filename,
                            'file_size' => $fileSize,
                            'mime_type' => $mimeType,
                            'file_type' => $fileType,
                            'uploaded_by' => $user['id'],
                            'created_at' => date('Y-m-d H:i:s', filemtime($filePath)),
                            'updated_at' => date('Y-m-d H:i:s', filemtime($filePath))
                        ]);
                        
                        if ($mediaId) {
                            $importedCount++;
                        }
                    }
                }
            }
        }
    }
    
    return $importedCount;
}

// Handle scan existing files
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'scan_existing') {
    $importedCount = scanAndImportExistingFiles();
    $scanMessage = "Scanned and imported $importedCount new files from existing uploads.";
}

// Handle AJAX requests for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] === 'get_media') {
    header('Content-Type: application/json');
    $mediaId = (int)$_GET['id'];
    $db = Database::getInstance();
    
    $media = $db->getRow("SELECT * FROM media WHERE id = ?", [$mediaId]);
    
    if ($media) {
        echo json_encode(['success' => true, 'media' => $media]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Media not found']);
    }
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (isset($_FILES['files'])) {
        $uploadResults = [];
        $uploadDir = '../../assets/uploads/media/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $files = $_FILES['files'];
        $fileCount = is_array($files['name']) ? count($files['name']) : 1;
        
        // Handle single file upload
        if (!is_array($files['name'])) {
            $files = array(
                'name' => array($files['name']),
                'tmp_name' => array($files['tmp_name']),
                'size' => array($files['size']),
                'type' => array($files['type']),
                'error' => array($files['error'])
            );
            $fileCount = 1;
        }
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $originalName = $files['name'][$i];
                $tempName = $files['tmp_name'][$i];
                $fileSize = $files['size'][$i];
                $mimeType = $files['type'][$i];
                
                // Generate unique filename
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $filename = uniqid() . '_' . time() . '.' . $extension;
                $filePath = $uploadDir . $filename;
                
                // Determine file type
                $fileType = getFileType($mimeType);
                
                if (move_uploaded_file($tempName, $filePath)) {
                    // Save to database
                    $db = Database::getInstance();
                    $mediaId = $db->insert('media', [
                        'filename' => $filename,
                        'original_name' => $originalName,
                        'file_path' => 'assets/uploads/media/' . $filename,
                        'file_size' => $fileSize,
                        'mime_type' => $mimeType,
                        'file_type' => $fileType,
                        'uploaded_by' => $user['id'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    if ($mediaId) {
                        $uploadResults[] = ['success' => true, 'filename' => $originalName];
                    }
                } else {
                    $uploadResults[] = ['success' => false, 'filename' => $originalName, 'error' => 'Failed to move file'];
                }
            }
        }
        
        $successCount = count(array_filter($uploadResults, function($r) { return $r['success']; }));
        $uploadMessage = $successCount . ' file(s) uploaded successfully';
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $mediaId = (int)$_POST['media_id'];
    $db = Database::getInstance();
    
    // Get file info before deleting
    $media = $db->getRow("SELECT * FROM media WHERE id = ?", [$mediaId]);
    if ($media) {
        // Delete physical file
        $fullPath = '../../' . $media['file_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        
        // Delete from database
        $db->delete('media', 'id = ?', [$mediaId]);
        $deleteMessage = "File deleted successfully";
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $mediaId = (int)$_POST['media_id'];
    $originalName = sanitizeInput($_POST['original_name']);
    $caption = sanitizeInput($_POST['caption']);
    $altText = sanitizeInput($_POST['alt_text']);
    
    $db = Database::getInstance();
    $result = $db->update('media', [
        'original_name' => $originalName,
        'caption' => $caption,
        'alt_text' => $altText,
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$mediaId]);
    
    if ($result) {
        $updateMessage = "Media updated successfully";
    }
}

// Get media files
$db = Database::getInstance();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$fileType = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';

// Build query
$whereConditions = [];
$params = [];

if ($search) {
    $whereConditions[] = "(original_name LIKE ? OR caption LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($fileType) {
    $whereConditions[] = "file_type = ?";
    $params[] = $fileType;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$totalMedia = $db->getValue("SELECT COUNT(*) FROM media $whereClause", $params);

// Get media files
$mediaFiles = $db->getRows("
    SELECT m.*, u.username as uploaded_by_name 
    FROM media m 
    LEFT JOIN users u ON m.uploaded_by = u.id 
    $whereClause 
    ORDER BY m.created_at DESC 
    LIMIT $perPage OFFSET $offset
", $params);

$totalPages = ceil($totalMedia / $perPage);

include '../includes/header.php';
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-3">Media Library</h1>
        <div>
            <button type="button" class="btn btn-info mr-2" onclick="scanExistingFiles()">
                <i class="fas fa-sync mr-2"></i>Scan Existing Files
            </button>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#uploadModal">
                <i class="fas fa-upload mr-2"></i>Upload Files
            </button>
        </div>
    </div>
</div>

<?php if (isset($scanMessage)): ?>
    <div class="alert alert-info alert-dismissible fade show">
        <?php echo $scanMessage; ?>
        <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<?php if (isset($uploadMessage)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $uploadMessage; ?>
        <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<?php if (isset($deleteMessage)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $deleteMessage; ?>
        <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<?php if (isset($updateMessage)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $updateMessage; ?>
        <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-md-4">
                <label for="search">Search</label>
                <input type="text" name="search" id="search" class="form-control" 
                       value="<?php echo htmlspecialchars($search); ?>" placeholder="Search files...">
            </div>
            <div class="col-md-3">
                <label for="type">File Type</label>
                <select name="type" id="type" class="form-control">
                    <option value="">All Types</option>
                    <option value="image" <?php echo $fileType === 'image' ? 'selected' : ''; ?>>Images</option>
                    <option value="video" <?php echo $fileType === 'video' ? 'selected' : ''; ?>>Videos</option>
                    <option value="audio" <?php echo $fileType === 'audio' ? 'selected' : ''; ?>>Audio</option>
                    <option value="pdf" <?php echo $fileType === 'pdf' ? 'selected' : ''; ?>>PDF</option>
                    <option value="document" <?php echo $fileType === 'document' ? 'selected' : ''; ?>>Documents</option>
                    <option value="other" <?php echo $fileType === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary">Filter</button>
            </div>
            <div class="col-md-3 text-right">
                <small class="text-muted">Total: <?php echo $totalMedia; ?> files</small>
            </div>
        </form>
    </div>
</div>

<!-- Media Grid -->
<div class="row" id="mediaGrid">
    <?php if (empty($mediaFiles)): ?>
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-photo-video fa-4x text-muted mb-3"></i>
                <h4>No media files found</h4>
                <p class="text-muted">Upload your first file or scan existing files to get started</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($mediaFiles as $media): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card media-item" data-id="<?php echo $media['id']; ?>">
                    <div class="card-body p-0">
                        <!-- File Preview -->
                        <div class="media-preview" style="height: 200px; background: #f8f9fa; position: relative; overflow: hidden;">
                            <?php if ($media['file_type'] === 'image'): ?>
                                <img src="<?php echo SITE_URL . '/' . $media['file_path']; ?>" 
                                     alt="<?php echo htmlspecialchars($media['alt_text'] ?: $media['original_name']); ?>"
                                     class="img-fluid w-100 h-100" style="object-fit: cover;">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center h-100">
                                    <?php
                                    $iconClass = 'fa-file';
                                    switch($media['file_type']) {
                                        case 'video': $iconClass = 'fa-file-video'; break;
                                        case 'audio': $iconClass = 'fa-file-audio'; break;
                                        case 'pdf': $iconClass = 'fa-file-pdf'; break;
                                        case 'document': $iconClass = 'fa-file-word'; break;
                                    }
                                    ?>
                                    <i class="fas <?php echo $iconClass; ?> fa-4x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Action Buttons -->
                            <div class="position-absolute" style="top: 5px; right: 5px;">
                                <div class="btn-group-vertical">
                                    <button type="button" class="btn btn-sm btn-primary" onclick="editMedia(<?php echo $media['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-info" onclick="copyUrl('<?php echo SITE_URL . '/' . $media['file_path']; ?>')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteMedia(<?php echo $media['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- File Category Badge -->
                            <div class="position-absolute" style="bottom: 5px; left: 5px;">
                                <?php 
                                $categoryBadge = '';
                                if (strpos($media['file_path'], 'blog/') !== false) $categoryBadge = 'Blog';
                                elseif (strpos($media['file_path'], 'currencies/') !== false) $categoryBadge = 'Currency';
                                elseif (strpos($media['file_path'], 'notices/') !== false) $categoryBadge = 'Notice';
                                else $categoryBadge = 'Media';
                                ?>
                                <span class="badge badge-secondary"><?php echo $categoryBadge; ?></span>
                            </div>
                        </div>
                        
                        <!-- File Info -->
                        <div class="p-3">
                            <h6 class="card-title mb-1 text-truncate" title="<?php echo htmlspecialchars($media['original_name']); ?>">
                                <?php echo htmlspecialchars($media['original_name']); ?>
                            </h6>
                            <small class="text-muted d-block mb-1"><?php echo formatFileSize($media['file_size']); ?></small>
                            <small class="text-muted"><?php echo date('M j, Y', strtotime($media['created_at'])); ?></small>
                            <?php if ($media['caption']): ?>
                                <p class="small text-muted mt-2 mb-0"><?php echo htmlspecialchars($media['caption']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <nav aria-label="Media pagination">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($fileType); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Files</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="upload">
                    
                    <div class="form-group">
                        <label>Select Files</label>
                        <input type="file" name="files[]" class="form-control-file" multiple required>
                        <small class="form-text text-muted">
                            You can select multiple files. Supported formats: Images, Videos, Audio, PDF, Documents
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Files</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Media</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" id="editForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="media_id" id="editMediaId">
                    
                    <div class="form-group">
                        <label for="editOriginalName">File Name</label>
                        <input type="text" name="original_name" id="editOriginalName" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editCaption">Caption</label>
                        <textarea name="caption" id="editCaption" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="editAltText">Alt Text</label>
                        <input type="text" name="alt_text" id="editAltText" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>File URL</label>
                        <div class="input-group">
                            <input type="text" id="editFileUrl" class="form-control" readonly>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="copyUrl(document.getElementById('editFileUrl').value)">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Scan existing files function
function scanExistingFiles() {
    if (confirm('This will scan all upload directories and add missing files to the media library. Continue?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="scan_existing">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Edit media function
function editMedia(mediaId) {
    // AJAX to get media data using the same file
    fetch('?ajax=get_media&id=' + mediaId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const media = data.media;
                document.getElementById('editMediaId').value = media.id;
                document.getElementById('editOriginalName').value = media.original_name;
                document.getElementById('editCaption').value = media.caption || '';
                document.getElementById('editAltText').value = media.alt_text || '';
                document.getElementById('editFileUrl').value = '<?php echo SITE_URL; ?>/' + media.file_path;
                
                $('#editModal').modal('show');
            } else {
                alert('Error: ' + (data.message || 'Could not load media data'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading media data');
        });
}

// Delete media function
function deleteMedia(mediaId) {
    if (confirm('Are you sure you want to delete this file? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="media_id" value="${mediaId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Copy URL function
function copyUrl(url) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            alert('URL copied to clipboard!');
        });
    } else {
        // Fallback
        const textArea = document.createElement('textarea');
        textArea.value = url;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('URL copied to clipboard!');
    }
}
</script>

<style>
.media-item {
    transition: transform 0.2s, box-shadow 0.2s;
}

.media-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.media-preview {
    border-radius: 0.375rem 0.375rem 0 0;
}

.btn-group-vertical .btn {
    margin-bottom: 2px;
}

.badge {
    font-size: 0.7em;
}
</style>

<?php include '../includes/footer.php'; ?>