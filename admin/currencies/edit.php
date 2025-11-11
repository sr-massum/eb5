<?php 

/**
 * ExchangeBridge - Admin Panel Edit Currency
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

// Get currency ID from URL
$currencyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($currencyId === 0) {
    $_SESSION['error_message'] = 'Invalid currency ID';
    header("Location: index.php");
    exit;
}

// Get database instance
$db = Database::getInstance();

// Get currency details
$currency = $db->getRow("SELECT * FROM currencies WHERE id = ?", [$currencyId]);

if (!$currency) {
    $_SESSION['error_message'] = 'Currency not found';
    header("Location: index.php");
    exit;
}

// Initialize variables with existing data
$name = $currency['name'];
$code = $currency['code'];
$displayName = $currency['display_name'];
$iconClass = $currency['icon_class'];
$backgroundClass = $currency['background_class'];
$paymentAddress = $currency['payment_address'];
$addressLabel = $currency['address_label'];
$addressType = $currency['address_type'];
$status = $currency['status'];
$currentLogo = $currency['logo'];

$error = '';

// Get media files for logo selection
$mediaFiles = $db->getRows("SELECT * FROM media WHERE file_type = 'image' ORDER BY created_at DESC LIMIT 50");

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
    $code = isset($_POST['code']) ? sanitizeInput($_POST['code']) : '';
    $displayName = isset($_POST['display_name']) ? sanitizeInput($_POST['display_name']) : '';
    $iconClass = isset($_POST['icon_class']) ? sanitizeInput($_POST['icon_class']) : '';
    $backgroundClass = isset($_POST['background_class']) ? sanitizeInput($_POST['background_class']) : '';
    $paymentAddress = isset($_POST['payment_address']) ? sanitizeInput($_POST['payment_address']) : '';
    $addressLabel = isset($_POST['address_label']) ? sanitizeInput($_POST['address_label']) : '';
    $addressType = isset($_POST['address_type']) ? sanitizeInput($_POST['address_type']) : '';
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active';
    $selectedMedia = isset($_POST['selected_media']) ? sanitizeInput($_POST['selected_media']) : '';
    $removeLogo = isset($_POST['remove_logo']) ? true : false;
    
    // Validate form data
    if (empty($name)) {
        $error = 'Currency name is required';
    } else {
        // Check if currency name already exists (excluding current currency)
        $existingCurrency = $db->getRow("SELECT * FROM currencies WHERE name = ? AND id != ?", [$name, $currencyId]);
        
        if ($existingCurrency) {
            $error = 'Currency name already exists. Please use a different name.';
        } else {
            // Handle logo upload/selection
            $logo = $currentLogo; // Keep current logo by default
            
            // Check if user wants to remove logo
            if ($removeLogo) {
                // Delete current logo file if exists
                if (!empty($currentLogo) && file_exists('../../assets/uploads/currencies/' . $currentLogo)) {
                    unlink('../../assets/uploads/currencies/' . $currentLogo);
                }
                $logo = '';
            }
            // Check if media library file is selected
            elseif (!empty($selectedMedia)) {
                // Use selected media file
                $mediaFile = $db->getRow("SELECT * FROM media WHERE id = ?", [$selectedMedia]);
                if ($mediaFile) {
                    // Delete old logo if different
                    if (!empty($currentLogo) && file_exists('../../assets/uploads/currencies/' . $currentLogo)) {
                        unlink('../../assets/uploads/currencies/' . $currentLogo);
                    }
                    
                    // Copy the media file to currencies folder
                    $sourcePath = '../../' . $mediaFile['file_path'];
                    $targetDir = '../../assets/uploads/currencies/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }
                    
                    $fileExtension = pathinfo($mediaFile['file_path'], PATHINFO_EXTENSION);
                    $logo = uniqid() . '.' . $fileExtension;
                    $targetPath = $targetDir . $logo;
                    
                    if (copy($sourcePath, $targetPath)) {
                        // File copied successfully
                    } else {
                        $error = 'Failed to copy selected media file';
                    }
                }
            }
            // Check if new file is uploaded
            elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                // Delete old logo
                if (!empty($currentLogo) && file_exists('../../assets/uploads/currencies/' . $currentLogo)) {
                    unlink('../../assets/uploads/currencies/' . $currentLogo);
                }
                
                $uploadDir = '../../assets/uploads/currencies/';
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $result = uploadFile($_FILES['logo'], $uploadDir);
                
                if ($result['success']) {
                    $logo = $result['filename'];
                } else {
                    $error = $result['message'];
                }
            }
            
            if (empty($error)) {
                // Update currency data
                $updateResult = $db->update('currencies', [
                    'name' => $name,
                    'code' => $code,
                    'display_name' => $displayName,
                    'logo' => $logo,
                    'icon_class' => $iconClass,
                    'background_class' => $backgroundClass,
                    'payment_address' => $paymentAddress,
                    'address_label' => $addressLabel,
                    'address_type' => $addressType,
                    'status' => $status
                ], "id = $currencyId");
                
                if ($updateResult) {
                    // Set success message and redirect
                    $_SESSION['success_message'] = 'Currency updated successfully';
                    header("Location: index.php");
                    exit;
                } else {
                    $error = 'Failed to update currency';
                }
            }
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
        <a href="index.php">Currencies</a>
    </li>
    <li class="breadcrumb-item active">Edit Currency</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-edit mr-1"></i> Edit Currency: <?php echo htmlspecialchars($currency['name']); ?>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Note:</strong> You can have multiple currencies with the same code (e.g., PayPal USD, Payoneer USD). 
            The currency name must be unique and will be used for exchange order selection.
        </div>
        
        <form action="edit.php?id=<?php echo $currencyId; ?>" method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="name" class="required">Currency Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                        <small class="form-text text-muted">Example: PayPal USD, Payoneer USD, bKash BDT. Must be unique.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="code">Currency Code</label>
                        <input type="text" class="form-control" id="code" name="code" value="<?php echo htmlspecialchars($code); ?>">
                        <small class="form-text text-muted">Example: USD, BDT, BTC. Multiple currencies can have the same code.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="display_name">Display Name</label>
                        <input type="text" class="form-control" id="display_name" name="display_name" value="<?php echo htmlspecialchars($displayName); ?>">
                        <small class="form-text text-muted">Example: USD, BDT. This will be displayed next to amounts.</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Currency Logo</label>
                        
                        <!-- Current Logo Display -->
                        <?php if (!empty($currentLogo) && file_exists('../../assets/uploads/currencies/' . $currentLogo)): ?>
                        <div class="current-logo mb-3 p-3 border rounded">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo ASSETS_URL; ?>/uploads/currencies/<?php echo $currentLogo; ?>" 
                                     alt="Current Logo" 
                                     class="mr-3" 
                                     style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
                                <div class="flex-grow-1">
                                    <strong>Current Logo:</strong> <?php echo htmlspecialchars($currentLogo); ?>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="remove_logo" name="remove_logo">
                                    <label class="custom-control-label" for="remove_logo">Remove Logo</label>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Logo Selection Tabs -->
                        <ul class="nav nav-tabs" id="logoTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="upload-tab" data-toggle="tab" href="#upload" role="tab" aria-controls="upload" aria-selected="true">
                                    <i class="fas fa-upload mr-1"></i> Upload New
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="media-tab" data-toggle="tab" href="#media" role="tab" aria-controls="media" aria-selected="false">
                                    <i class="fas fa-images mr-1"></i> Select from Media Library
                                </a>
                            </li>
                        </ul>
                        
                        <div class="tab-content mt-3" id="logoTabContent">
                            <!-- Upload New Logo -->
                            <div class="tab-pane fade show active" id="upload" role="tabpanel" aria-labelledby="upload-tab">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="logo" name="logo" accept="image/*">
                                    <label class="custom-file-label" for="logo">Choose file</label>
                                </div>
                                <small class="form-text text-muted">Recommended size: 32x32 pixels. Will replace current logo.</small>
                            </div>
                            
                            <!-- Select from Media Library -->
                            <div class="tab-pane fade" id="media" role="tabpanel" aria-labelledby="media-tab">
                                <?php if (count($mediaFiles) > 0): ?>
                                <div class="media-gallery" style="max-height: 300px; overflow-y: auto;">
                                    <div class="row">
                                        <?php foreach ($mediaFiles as $media): ?>
                                        <div class="col-md-2 col-sm-3 col-4 mb-3">
                                            <div class="media-item" data-media-id="<?php echo $media['id']; ?>" style="cursor: pointer; border: 2px solid transparent; border-radius: 8px; padding: 5px;">
                                                <img src="<?php echo SITE_URL . '/' . $media['file_path']; ?>" 
                                                     alt="<?php echo htmlspecialchars($media['original_name']); ?>" 
                                                     class="img-fluid rounded" 
                                                     style="width: 100%; height: 60px; object-fit: cover;">
                                                <small class="d-block text-center mt-1" style="font-size: 10px;"><?php echo htmlspecialchars(substr($media['original_name'], 0, 15)); ?></small>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <input type="hidden" name="selected_media" id="selected_media">
                                <div id="selected-media-preview" class="mt-2" style="display: none;">
                                    <strong>Selected:</strong> <span id="selected-media-name"></span>
                                    <button type="button" class="btn btn-sm btn-outline-danger ml-2" onclick="clearMediaSelection()">
                                        <i class="fas fa-times"></i> Clear
                                    </button>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    No media files found. <a href="../media/" target="_blank">Upload some images to the media library</a> first.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="icon_class">Icon Class (Optional)</label>
                        <input type="text" class="form-control" id="icon_class" name="icon_class" value="<?php echo htmlspecialchars($iconClass); ?>">
                        <small class="form-text text-muted">Example: fas fa-money-bill-wave, fab fa-paypal. Used if no logo is uploaded.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="background_class">Background Class (Optional)</label>
                        <input type="text" class="form-control" id="background_class" name="background_class" value="<?php echo htmlspecialchars($backgroundClass); ?>">
                        <small class="form-text text-muted">Example: bg-blue-500 text-white. Used for the currency icon background.</small>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Preview</label>
                        <div class="border rounded p-3 text-center">
                            <div id="currency-preview" class="d-inline-block">
                                <?php if (!empty($currentLogo) && file_exists('../../assets/uploads/currencies/' . $currentLogo)): ?>
                                    <img src="<?php echo ASSETS_URL; ?>/uploads/currencies/<?php echo $currentLogo; ?>" 
                                         alt="Current Logo" 
                                         style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid #dee2e6;">
                                <?php else: ?>
                                    <div class="currency-logo d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #f8f9fa; border-radius: 50%;">
                                        <i class="<?php echo !empty($iconClass) ? $iconClass : 'fas fa-money-bill-wave'; ?>"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Currency Logo Preview</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="payment_address">Payment Address</label>
                <input type="text" class="form-control" id="payment_address" name="payment_address" value="<?php echo htmlspecialchars($paymentAddress); ?>">
                <small class="form-text text-muted">Example: a PayPal email, a crypto wallet address, or a mobile money number.</small>
            </div>
            
            <div class="form-group">
                <label for="address_label">Address Label</label>
                <input type="text" class="form-control" id="address_label" name="address_label" value="<?php echo htmlspecialchars($addressLabel); ?>">
                <small class="form-text text-muted">Example: PayPal Email, Wallet Address. This label will be shown in the exchange form.</small>
            </div>
            
            <div class="form-group">
                <label for="address_type">Address Type</label>
                <input type="text" class="form-control" id="address_type" name="address_type" value="<?php echo htmlspecialchars($addressType); ?>">
                <small class="form-text text-muted">Example: email, address, mobile number. Used in form explanations.</small>
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select class="form-control" id="status" name="status">
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Update Currency
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times mr-1"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.currency-logo {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
}

.media-item:hover {
    border-color: #007bff !important;
    background-color: #f8f9fa;
}

.media-item.selected {
    border-color: #28a745 !important;
    background-color: #d4edda;
}

.media-gallery {
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 10px;
}

.current-logo {
    background-color: #f8f9fa;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Custom file input label update
    document.querySelector('.custom-file-input').addEventListener('change', function(e) {
        var fileName = e.target.files[0].name;
        var nextSibling = e.target.nextElementSibling;
        nextSibling.innerText = fileName;
        
        // Clear media selection if file is uploaded
        clearMediaSelection();
        
        // Uncheck remove logo if file is uploaded
        document.getElementById('remove_logo').checked = false;
        
        // Preview uploaded image
        if (e.target.files && e.target.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                updatePreview(e.target.result);
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });
    
    // Media library selection
    document.querySelectorAll('.media-item').forEach(function(item) {
        item.addEventListener('click', function() {
            // Clear previous selections
            document.querySelectorAll('.media-item').forEach(function(i) {
                i.classList.remove('selected');
            });
            
            // Select current item
            this.classList.add('selected');
            
            var mediaId = this.dataset.mediaId;
            var mediaName = this.querySelector('small').textContent;
            var mediaSrc = this.querySelector('img').src;
            
            document.getElementById('selected_media').value = mediaId;
            document.getElementById('selected-media-name').textContent = mediaName;
            document.getElementById('selected-media-preview').style.display = 'block';
            
            // Clear file upload
            document.getElementById('logo').value = '';
            document.querySelector('.custom-file-label').textContent = 'Choose file';
            
            // Uncheck remove logo
            document.getElementById('remove_logo').checked = false;
            
            // Update preview
            updatePreview(mediaSrc);
        });
    });
    
    // Remove logo checkbox
    document.getElementById('remove_logo').addEventListener('change', function() {
        if (this.checked) {
            // Clear file upload and media selection
            clearMediaSelection();
            document.getElementById('logo').value = '';
            document.querySelector('.custom-file-label').textContent = 'Choose file';
            
            // Show default icon
            var preview = document.getElementById('currency-preview');
            var iconClass = document.getElementById('icon_class').value || 'fas fa-money-bill-wave';
            preview.innerHTML = '<div class="currency-logo d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #f8f9fa; border-radius: 50%;"><i class="' + iconClass + '"></i></div>';
        }
    });
    
    // Tab switching - clear selections when switching
    document.getElementById('upload-tab').addEventListener('click', function() {
        clearMediaSelection();
    });
    
    document.getElementById('media-tab').addEventListener('click', function() {
        // Clear file upload
        document.getElementById('logo').value = '';
        document.querySelector('.custom-file-label').textContent = 'Choose file';
    });
    
    // Icon class change
    document.getElementById('icon_class').addEventListener('input', function() {
        // Only update if no logo is selected and remove logo is checked
        if (document.getElementById('remove_logo').checked || 
            (!document.getElementById('selected_media').value && !document.getElementById('logo').value)) {
            var iconClass = this.value || 'fas fa-money-bill-wave';
            var preview = document.getElementById('currency-preview');
            preview.innerHTML = '<div class="currency-logo d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #f8f9fa; border-radius: 50%;"><i class="' + iconClass + '"></i></div>';
        }
    });
});

function clearMediaSelection() {
    document.querySelectorAll('.media-item').forEach(function(i) {
        i.classList.remove('selected');
    });
    document.getElementById('selected_media').value = '';
    document.getElementById('selected-media-preview').style.display = 'none';
}

function updatePreview(imageSrc) {
    var preview = document.getElementById('currency-preview');
    preview.innerHTML = '<img src="' + imageSrc + '" alt="Preview" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid #dee2e6;">';
}
</script>

<?php include '../includes/footer.php'; ?>