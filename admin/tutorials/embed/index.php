<?php 

/**
 * ExchangeBridge - Admin Panel Video Embed
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
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $embedType = $_POST['embed_type'];
    $embedUrl = trim($_POST['embed_url']);
    $priority = intval($_POST['priority']);
    $displayOnHomepage = isset($_POST['display_on_homepage']) ? 1 : 0;
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($embedUrl)) {
        $errors[] = "Embed URL is required";
    }
    
    // Validate URL based on type
    if (!empty($embedUrl)) {
        switch ($embedType) {
            case 'youtube_embed':
                if (!preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $embedUrl)) {
                    $errors[] = "Invalid YouTube URL format";
                }
                break;
            case 'facebook_embed':
                if (!preg_match('/facebook\.com.*\/videos\//', $embedUrl)) {
                    $errors[] = "Invalid Facebook video URL format";
                }
                break;
            case 'google_drive_embed':
                if (!preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9-_]+)/', $embedUrl)) {
                    $errors[] = "Invalid Google Drive URL format";
                }
                break;
        }
    }
    
    if (empty($errors)) {
        $db = Database::getInstance();
        $result = $db->insert('tutorials_ads', [
            'title' => $title,
            'description' => $description,
            'type' => $embedType,
            'embed_code' => $embedUrl,
            'priority' => $priority,
            'display_on_homepage' => $displayOnHomepage,
            'created_by' => Auth::getUser()['id']
        ]);
        
        if ($result) {
            $_SESSION['success_message'] = "Video embed added successfully!";
            header("Location: ../index.php");
            exit;
        } else {
            $errors[] = "Failed to save to database";
        }
    }
}

// Include header
include '../../includes/header.php';
?>

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item">
        <a href="../index.php">Tutorials</a>
    </li>
    <li class="breadcrumb-item active">Embed Video</li>
</ol>

<!-- Page Content -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-code mr-1"></i> Embed Video from External Sources
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
                        <label for="embed_type">Video Source</label>
                        <select name="embed_type" id="embed_type" class="form-control" onchange="updateEmbedHelp()">
                            <option value="youtube_embed">YouTube</option>
                            <option value="facebook_embed">Facebook</option>
                            <option value="google_drive_embed">Google Drive</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" name="title" id="title" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="embed_url">Video URL *</label>
                        <input type="url" name="embed_url" id="embed_url" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['embed_url'] ?? ''); ?>" 
                               placeholder="https://www.youtube.com/watch?v=VIDEO_ID" required>
                        <small class="form-text text-muted" id="embed-help">
                            Enter the full YouTube video URL (e.g., https://www.youtube.com/watch?v=dQw4w9WgXcQ)
                        </small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="priority">Priority</label>
                            <input type="number" name="priority" id="priority" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['priority'] ?? '0'); ?>" min="0">
                            <small class="form-text text-muted">Lower numbers appear first</small>
                        </div>
                        <div class="form-group col-md-6">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="display_on_homepage" id="display_on_homepage" 
                                       class="form-check-input" <?php echo isset($_POST['display_on_homepage']) ? 'checked' : 'checked'; ?>>
                                <label class="form-check-label" for="display_on_homepage">
                                    Display on Homepage
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-code mr-1"></i> Add Embed
                    </button>
                    <a href="../index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Tutorials
                    </a>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">URL Format Examples</h6>
            </div>
            <div class="card-body">
                <h6>YouTube URLs:</h6>
                <ul class="small">
                    <li>https://www.youtube.com/watch?v=VIDEO_ID</li>
                    <li>https://youtu.be/VIDEO_ID</li>
                    <li>https://www.youtube.com/embed/VIDEO_ID</li>
                </ul>
                
                <h6 class="mt-3">Facebook URLs:</h6>
                <ul class="small">
                    <li>https://www.facebook.com/username/videos/123456789/</li>
                    <li>https://fb.watch/VIDEO_ID/</li>
                </ul>
                
                <h6 class="mt-3">Google Drive URLs:</h6>
                <ul class="small">
                    <li>https://drive.google.com/file/d/FILE_ID/view</li>
                    <li>Make sure the file is set to "Anyone with the link can view"</li>
                </ul>
                
                <div class="alert alert-info mt-3">
                    <small><strong>Note:</strong> Make sure the videos are publicly accessible and not restricted by privacy settings.</small>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Preview</h6>
            </div>
            <div class="card-body">
                <div id="preview-container" style="background: #f8f9fa; height: 150px; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                    <span class="text-muted">Enter a URL to see preview</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateEmbedHelp() {
    const embedType = document.getElementById('embed_type').value;
    const embedHelp = document.getElementById('embed-help');
    const embedUrl = document.getElementById('embed_url');
    
    switch (embedType) {
        case 'youtube_embed':
            embedHelp.textContent = 'Enter the full YouTube video URL (e.g., https://www.youtube.com/watch?v=dQw4w9WgXcQ)';
            embedUrl.placeholder = 'https://www.youtube.com/watch?v=VIDEO_ID';
            break;
        case 'facebook_embed':
            embedHelp.textContent = 'Enter the Facebook video URL (e.g., https://www.facebook.com/username/videos/123456789/)';
            embedUrl.placeholder = 'https://www.facebook.com/username/videos/123456789/';
            break;
        case 'google_drive_embed':
            embedHelp.textContent = 'Enter the Google Drive file URL (make sure it\'s publicly accessible)';
            embedUrl.placeholder = 'https://drive.google.com/file/d/FILE_ID/view';
            break;
    }
    
    updatePreview();
}

function updatePreview() {
    const embedType = document.getElementById('embed_type').value;
    const embedUrl = document.getElementById('embed_url').value;
    const previewContainer = document.getElementById('preview-container');
    
    if (!embedUrl) {
        previewContainer.innerHTML = '<span class="text-muted">Enter a URL to see preview</span>';
        return;
    }
    
    let embedCode = '';
    
    switch (embedType) {
        case 'youtube_embed':
            const youtubeMatch = embedUrl.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/);
            if (youtubeMatch) {
                embedCode = `<iframe width="100%" height="100%" src="https://www.youtube.com/embed/${youtubeMatch[1]}" frameborder="0" allowfullscreen></iframe>`;
            }
            break;
        case 'facebook_embed':
            embedCode = `<iframe width="100%" height="100%" src="https://www.facebook.com/plugins/video.php?href=${encodeURIComponent(embedUrl)}&show_text=false" frameborder="0" allowfullscreen></iframe>`;
            break;
        case 'google_drive_embed':
            const driveMatch = embedUrl.match(/\/d\/([a-zA-Z0-9-_]+)/);
            if (driveMatch) {
                embedCode = `<iframe width="100%" height="100%" src="https://drive.google.com/file/d/${driveMatch[1]}/preview" frameborder="0"></iframe>`;
            }
            break;
    }
    
    if (embedCode) {
        previewContainer.innerHTML = embedCode;
    } else {
        previewContainer.innerHTML = '<span class="text-danger">Invalid URL format</span>';
    }
}

// Event listeners
document.getElementById('embed_url').addEventListener('input', updatePreview);
document.addEventListener('DOMContentLoaded', function() {
    updateEmbedHelp();
    updatePreview();
});
</script>

<?php include '../../includes/footer.php'; ?>
