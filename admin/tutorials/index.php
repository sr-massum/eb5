<?php 

/**
 * ExchangeBridge - Admin Panel Tutorials/Ads
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

// Handle deletion
if (isset($_POST['delete_tutorial'])) {
    $id = intval($_POST['tutorial_id']);
    $db = Database::getInstance();
    
    // Get tutorial details for file deletion
    $tutorial = $db->getRow("SELECT * FROM tutorials_ads WHERE id = ?", [$id]);
    
    if ($tutorial) {
        // Delete files if they exist
        if ($tutorial['file_path'] && file_exists("../../assets/uploads/media/" . $tutorial['file_path'])) {
            unlink("../../assets/uploads/media/" . $tutorial['file_path']);
        }
        if ($tutorial['thumbnail'] && file_exists("../../assets/uploads/media/" . $tutorial['thumbnail'])) {
            unlink("../../assets/uploads/media/" . $tutorial['thumbnail']);
        }
        
        // Delete from database
        $db->delete('tutorials_ads', 'id = ?', [$id]);
        $_SESSION['success_message'] = "Tutorial deleted successfully!";
    }
    header("Location: index.php");
    exit;
}

// Handle status toggle
if (isset($_POST['toggle_status'])) {
    $id = intval($_POST['tutorial_id']);
    $newStatus = $_POST['new_status'] === 'active' ? 'active' : 'inactive';
    
    $db = Database::getInstance();
    $db->update('tutorials_ads', ['status' => $newStatus], 'id = ?', [$id]);
    $_SESSION['success_message'] = "Status updated successfully!";
    header("Location: index.php");
    exit;
}

// Get all tutorials
$db = Database::getInstance();
$tutorials = $db->getRows("SELECT * FROM tutorials_ads ORDER BY priority ASC, created_at DESC");

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

// Include header
include '../includes/header.php';
?>

<style>
.tutorial-preview {
    max-width: 60px;
    max-height: 40px;
    object-fit: cover;
    border-radius: 4px;
}

.type-badge {
    font-size: 11px;
    padding: 2px 6px;
}

.priority-badge {
    background-color: #6c757d;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
}
</style>

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item active">Tutorials & Ads</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-play-circle mr-1"></i> Tutorials & Ads Management
        <div class="btn-group float-right" role="group">
            <a href="video/" class="btn btn-success btn-sm">
                <i class="fas fa-video mr-1"></i> Video Upload
            </a>
            <a href="embed/" class="btn btn-info btn-sm">
                <i class="fas fa-code mr-1"></i> Video Embed
            </a>
            <a href="adsense/" class="btn btn-warning btn-sm">
                <i class="fas fa-ad mr-1"></i> AdSense
            </a>
        </div>
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
        
        <?php if (empty($tutorials)): ?>
            <div class="text-center py-5">
                <i class="fas fa-video text-muted" style="font-size: 48px;"></i>
                <h4 class="mt-3 text-muted">No tutorials found</h4>
                <p class="text-muted">Start by adding your first tutorial or ad.</p>
                <a href="video/" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i> Add Tutorial
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Preview</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Homepage</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tutorials as $tutorial): ?>
                        <tr>
                            <td>
                                <div style="width: 60px; height: 40px; background: #f8f9fa; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                    <?php if ($tutorial['type'] === 'video_upload' && $tutorial['file_path']): ?>
                                        <video class="tutorial-preview">
                                            <source src="../../assets/uploads/media/<?php echo htmlspecialchars($tutorial['file_path']); ?>" type="video/mp4">
                                        </video>
                                    <?php elseif ($tutorial['type'] === 'image_upload' && $tutorial['file_path']): ?>
                                        <img src="../../assets/uploads/media/<?php echo htmlspecialchars($tutorial['file_path']); ?>" 
                                             class="tutorial-preview" alt="Preview">
                                    <?php elseif ($tutorial['thumbnail']): ?>
                                        <img src="../../assets/uploads/media/<?php echo htmlspecialchars($tutorial['thumbnail']); ?>" 
                                             class="tutorial-preview" alt="Thumbnail">
                                    <?php else: ?>
                                        <i class="fas fa-play-circle text-muted"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($tutorial['title']); ?></strong>
                                <?php if ($tutorial['description']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($tutorial['description'], 0, 50)) . (strlen($tutorial['description']) > 50 ? '...' : ''); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $typeLabels = [
                                    'video_upload' => '<span class="badge badge-primary type-badge">Video Upload</span>',
                                    'image_upload' => '<span class="badge badge-info type-badge">Image Upload</span>',
                                    'youtube_embed' => '<span class="badge badge-danger type-badge">YouTube</span>',
                                    'facebook_embed' => '<span class="badge badge-primary type-badge">Facebook</span>',
                                    'google_drive_embed' => '<span class="badge badge-success type-badge">Google Drive</span>',
                                    'adsense' => '<span class="badge badge-warning type-badge">AdSense</span>'
                                ];
                                echo $typeLabels[$tutorial['type']] ?? $tutorial['type'];
                                ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $tutorial['display_on_homepage'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $tutorial['display_on_homepage'] ? 'Yes' : 'No'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="priority-badge"><?php echo $tutorial['priority']; ?></span>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="tutorial_id" value="<?php echo $tutorial['id']; ?>">
                                    <input type="hidden" name="new_status" value="<?php echo $tutorial['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                    <button type="submit" name="toggle_status" class="btn btn-sm btn-<?php echo $tutorial['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($tutorial['status']); ?>
                                    </button>
                                </form>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($tutorial['created_at'])); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="edit.php?id=<?php echo $tutorial['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="confirmDelete(<?php echo $tutorial['id']; ?>, '<?php echo htmlspecialchars($tutorial['title'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete "<span id="deleteTutorialTitle"></span>"?</p>
                <p class="text-danger"><small>This action cannot be undone and will also delete associated files.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="tutorial_id" id="deleteTutorialId">
                    <button type="submit" name="delete_tutorial" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, title) {
    document.getElementById('deleteTutorialId').value = id;
    document.getElementById('deleteTutorialTitle').textContent = title;
    $('#deleteModal').modal('show');
}
</script>

<?php include '../includes/footer.php'; ?>
