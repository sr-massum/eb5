<?php 

/**
 * ExchangeBridge - Admin Panel Edit Tutorial
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

// Get tutorial ID
$tutorialId = intval($_GET['id'] ?? 0);
if (!$tutorialId) {
    header("Location: index.php");
    exit;
}

$db = Database::getInstance();
$tutorial = $db->getRow("SELECT * FROM tutorials_ads WHERE id = ?", [$tutorialId]);

if (!$tutorial) {
    $_SESSION['error_message'] = "Tutorial not found.";
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority = intval($_POST['priority']);
    $displayOnHomepage = isset($_POST['display_on_homepage']) ? 1 : 0;
    $status = $_POST['status'];
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($errors)) {
        $updateData = [
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'display_on_homepage' => $displayOnHomepage,
            'status' => $status
        ];
        
        // Handle different update types based on tutorial type
        if ($tutorial['type'] === 'adsense' && isset($_POST['adsense_code'])) {
            $adsenseCode = trim($_POST['adsense_code']);
            if (!empty($adsenseCode)) {
                $updateData['adsense_code'] = $adsenseCode;
            }
        } elseif (in_array($tutorial['type'], ['youtube_embed', 'facebook_embed', 'google_drive_embed']) && isset($_POST['embed_code'])) {
            $embedCode = trim($_POST['embed_code']);
            if (!empty($embedCode)) {
                $updateData['embed_code'] = $embedCode;
            }
        }
        
        $result = $db->update('tutorials_ads', $updateData, 'id = ?', [$tutorialId]);
        
        if ($result) {
            $_SESSION['success_message'] = "Tutorial updated successfully!";
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Failed to update tutorial";
        }
    }
}

// Include header
include '../includes/header.php';
?>

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item">
        <a href="index.php">Tutorials</a>
    </li>
    <li class="breadcrumb-item active">Edit Tutorial</li>
</ol>

<!-- Page Content -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-edit mr-1"></i> Edit <?php echo ucfirst(str_replace('_', ' ', $tutorial['type'])); ?>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" name="title" id="title" class="form-control" 
                               value="<?php echo htmlspecialchars($tutorial['title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"><?php echo htmlspecialchars($tutorial['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <?php if ($tutorial['type'] === 'adsense'): ?>
                    <div class="form-group">
                        <label for="adsense_code">AdSense Code</label>
                        <textarea name="adsense_code" id="adsense_code" class="form-control" rows="8"><?php echo htmlspecialchars($tutorial['adsense_code'] ?? ''); ?></textarea>
                    </div>
                    <?php elseif (in_array($tutorial['type'], ['youtube_embed', 'facebook_embed', 'google_drive_embed'])): ?>
                    <div class="form-group">
                        <label for="embed_code">Embed URL</label>
                        <input type="url" name="embed_code" id="embed_code" class="form-control" 
                               value="<?php echo htmlspecialchars($tutorial['embed_code'] ?? ''); ?>">
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="priority">Priority</label>
                            <input type="number" name="priority" id="priority" class="form-control" 
                                   value="<?php echo $tutorial['priority']; ?>" min="0">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="active" <?php echo $tutorial['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $tutorial['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="display_on_homepage" id="display_on_homepage" 
                                       class="form-check-input" <?php echo $tutorial['display_on_homepage'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="display_on_homepage">
                                    Display on Homepage
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Update Tutorial
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Tutorials
                    </a>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Tutorial Info</h6>
            </div>
            <div class="card-body">
                <p><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $tutorial['type'])); ?></p>
                <p><strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($tutorial['created_at'])); ?></p>
                <p><strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($tutorial['updated_at'])); ?></p>
                
                <?php if ($tutorial['file_path']): ?>
                <p><strong>File:</strong> <?php echo htmlspecialchars($tutorial['file_path']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($tutorial['type'] === 'video_upload' || $tutorial['type'] === 'image_upload'): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Preview</h6>
            </div>
            <div class="card-body">
                <?php if ($tutorial['type'] === 'video_upload' && $tutorial['file_path']): ?>
                <video controls style="width: 100%; max-height: 200px;">
                    <source src="../../assets/uploads/media/<?php echo htmlspecialchars($tutorial['file_path']); ?>" type="video/mp4">
                </video>
                <?php elseif ($tutorial['type'] === 'image_upload' && $tutorial['file_path']): ?>
                <img src="../../assets/uploads/media/<?php echo htmlspecialchars($tutorial['file_path']); ?>" 
                     style="width: 100%; max-height: 200px; object-fit: contain;" alt="Preview">
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
