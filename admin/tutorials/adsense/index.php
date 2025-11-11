<?php 

/**
 * ExchangeBridge - Admin Panel Google AdSense
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
    $adsenseCode = trim($_POST['adsense_code']);
    $priority = intval($_POST['priority']);
    $displayOnHomepage = isset($_POST['display_on_homepage']) ? 1 : 0;
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($adsenseCode)) {
        $errors[] = "AdSense code is required";
    }
    
    // Basic validation for AdSense code
    if (!empty($adsenseCode)) {
        if (!preg_match('/<script.*?src=.*?googlesyndication\.com.*?<\/script>/s', $adsenseCode) && 
            !preg_match('/data-ad-client/i', $adsenseCode)) {
            $errors[] = "Invalid AdSense code format. Please paste the complete ad unit code from Google AdSense.";
        }
    }
    
    if (empty($errors)) {
        $db = Database::getInstance();
        $result = $db->insert('tutorials_ads', [
            'title' => $title,
            'description' => $description,
            'type' => 'adsense',
            'adsense_code' => $adsenseCode,
            'priority' => $priority,
            'display_on_homepage' => $displayOnHomepage,
            'created_by' => Auth::getUser()['id']
        ]);
        
        if ($result) {
            $_SESSION['success_message'] = "AdSense ad added successfully!";
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
    <li class="breadcrumb-item active">Google AdSense</li>
</ol>

<!-- Page Content -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-ad mr-1"></i> Add Google AdSense Ad
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
                        <label for="title">Ad Title *</label>
                        <input type="text" name="title" id="title" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                               placeholder="e.g., Homepage Banner Ad" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="2" 
                                  placeholder="Brief description of this ad placement"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="adsense_code">AdSense Ad Code *</label>
                        <textarea name="adsense_code" id="adsense_code" class="form-control" rows="8" 
                                  placeholder="Paste your complete AdSense ad unit code here..." required><?php echo htmlspecialchars($_POST['adsense_code'] ?? ''); ?></textarea>
                        <small class="form-text text-muted">
                            Copy the complete ad unit code from your Google AdSense account, including the &lt;script&gt; tags.
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
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-ad mr-1"></i> Add AdSense Ad
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
                <h6 class="card-title mb-0">How to Get AdSense Code</h6>
            </div>
            <div class="card-body">
                <ol class="small">
                    <li>Log in to your <strong>Google AdSense</strong> account</li>
                    <li>Go to <strong>Ads</strong> → <strong>Ad units</strong></li>
                    <li>Create a new ad unit or select an existing one</li>
                    <li>Click <strong>"Get code"</strong></li>
                    <li>Copy the complete HTML code</li>
                    <li>Paste it in the "AdSense Ad Code" field above</li>
                </ol>
                
                <div class="alert alert-warning mt-3">
                    <small><strong>Important:</strong> Make sure your website is approved by Google AdSense before adding ads.</small>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Code Example</h6>
            </div>
            <div class="card-body">
                <code style="font-size: 10px; display: block; white-space: pre-wrap; background: #f8f9fa; padding: 10px; border-radius: 4px;">
&lt;script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXX"
     crossorigin="anonymous"&gt;&lt;/script&gt;
&lt;ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-XXXXXXXXX"
     data-ad-slot="XXXXXXXXX"
     data-ad-format="auto"
     data-full-width-responsive="true"&gt;&lt;/ins&gt;
&lt;script&gt;
     (adsbygoogle = window.adsbygoogle || []).push({});
&lt;/script&gt;
                </code>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Ad Placement Tips</h6>
            </div>
            <div class="card-body">
                <ul class="small">
                    <li>Place ads in high-visibility areas</li>
                    <li>Don't place too many ads on one page</li>
                    <li>Use responsive ad units for mobile</li>
                    <li>Follow Google AdSense policies</li>
                    <li>Monitor ad performance regularly</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Validate AdSense code format
document.getElementById('adsense_code').addEventListener('input', function() {
    const code = this.value;
    const isValid = code.includes('googlesyndication.com') || code.includes('data-ad-client');
    
    if (code && !isValid) {
        this.style.borderColor = '#dc3545';
    } else {
        this.style.borderColor = '';
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
