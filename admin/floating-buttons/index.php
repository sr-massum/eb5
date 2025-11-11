<?php 

/**
 * ExchangeBridge - Admin Panel Floating Button
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

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Get floating buttons (handle both old and new data structure)
$db = Database::getInstance();

// Check if button_type column exists
try {
    $checkColumn = $db->getRows("SHOW COLUMNS FROM floating_buttons LIKE 'button_type'");
    if (!empty($checkColumn)) {
        // New structure - filter by button_type
        $buttons = $db->getRows("SELECT * FROM floating_buttons WHERE (button_type = 'custom' OR button_type IS NULL OR button_type = '') ORDER BY order_index ASC, created_at DESC");
    } else {
        // Old structure - get all buttons
        $buttons = $db->getRows("SELECT * FROM floating_buttons ORDER BY order_index ASC, created_at DESC");
    }
} catch (Exception $e) {
    // Fallback to old structure
    $buttons = $db->getRows("SELECT * FROM floating_buttons ORDER BY order_index ASC, created_at DESC");
}

// Get media files for icon selection
$mediaFiles = $db->getRows("SELECT * FROM media WHERE file_type = 'image' ORDER BY created_at DESC LIMIT 20");

// Check for messages
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Include header
include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <!-- Breadcrumbs -->
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Floating Buttons</li>
    </ol>

    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-life-ring me-2"></i>Floating Buttons Management
        </h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#buttonModal">
            <i class="fas fa-plus me-1"></i> Add New Button
        </button>
    </div>

    <!-- System Notice -->
    <?php if (getSetting('enable_whatsapp_button', 'yes') === 'yes' || getSetting('enable_tawkto', 'yes') === 'yes'): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Note:</strong> This panel manages custom floating buttons only. 
        System buttons (WhatsApp & Tawk.to) are managed in <a href="../settings/" class="alert-link">Settings</a>.
        <br><small class="mt-1 d-block"><strong>For Tawk.to buttons:</strong> Just paste your widget ID like <code>6869a98370e7fd1919383828/1ivebsat9</code> in the URL field</small>
    </div>
    <?php endif; ?>

    <!-- Alerts -->
    <?php if (!empty($successMessage)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $successMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo $errorMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Debug Info (remove this after testing) -->
    <div class="alert alert-warning">
        <strong>Debug:</strong> Found <?php echo count($buttons); ?> buttons in database.
        <?php if (!empty($buttons)): ?>
            <br>First button: <?php echo htmlspecialchars($buttons[0]['title'] ?? 'No title'); ?>
        <?php endif; ?>
    </div>

    <!-- Quick Templates -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-bolt me-1"></i> Quick Add Templates
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-auto">
                    <button class="btn btn-outline-success btn-sm quick-btn" data-template="whatsapp">
                        <i class="fab fa-whatsapp me-1"></i> WhatsApp
                    </button>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-primary btn-sm quick-btn" data-template="phone">
                        <i class="fas fa-phone me-1"></i> Phone
                    </button>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-danger btn-sm quick-btn" data-template="email">
                        <i class="fas fa-envelope me-1"></i> Email
                    </button>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-info btn-sm quick-btn" data-template="telegram">
                        <i class="fab fa-telegram me-1"></i> Telegram
                    </button>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-secondary btn-sm quick-btn" data-template="back-to-top">
                        <i class="fas fa-arrow-up me-1"></i> Back to Top
                    </button>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-warning btn-sm quick-btn" data-template="tawkto">
                        <i class="fas fa-comments me-1"></i> Tawk.to Chat
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Buttons List -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list me-1"></i> Your Floating Buttons (<?php echo count($buttons); ?>)
        </div>
        <div class="card-body">
            <?php if (count($buttons) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="60">Preview</th>
                            <th>Title</th>
                            <th>URL</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($buttons as $button): ?>
                        <tr>
                            <td>
                                <div class="btn-preview" style="background-color: <?php echo $button['color']; ?>;">
                                    <?php if (!empty($button['custom_icon']) && file_exists('../../' . $button['custom_icon'])): ?>
                                        <img src="<?php echo SITE_URL . '/' . $button['custom_icon']; ?>" alt="Icon">
                                    <?php else: ?>
                                        <i class="<?php echo $button['icon']; ?>"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($button['title']); ?></strong>
                                <br><small class="text-muted">Order: <?php echo $button['order_index']; ?></small>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars(substr($button['url'], 0, 40)) . (strlen($button['url']) > 40 ? '...' : ''); ?></code>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?php echo ucfirst(str_replace('-', ' ', $button['position'])); ?></span>
                                <br>
                                <small>
                                    <?php if ($button['show_on_mobile']): ?><i class="fas fa-mobile-alt text-success" title="Mobile"></i><?php endif; ?>
                                    <?php if ($button['show_on_desktop']): ?><i class="fas fa-desktop text-info" title="Desktop"></i><?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $button['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($button['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary edit-btn" 
                                        data-id="<?php echo $button['id']; ?>"
                                        data-title="<?php echo htmlspecialchars($button['title']); ?>"
                                        data-icon="<?php echo $button['icon']; ?>"
                                        data-custom-icon="<?php echo $button['custom_icon']; ?>"
                                        data-color="<?php echo $button['color']; ?>"
                                        data-url="<?php echo htmlspecialchars($button['url']); ?>"
                                        data-target="<?php echo $button['target']; ?>"
                                        data-position="<?php echo $button['position']; ?>"
                                        data-order="<?php echo $button['order_index']; ?>"
                                        data-status="<?php echo $button['status']; ?>"
                                        data-mobile="<?php echo $button['show_on_mobile']; ?>"
                                        data-desktop="<?php echo $button['show_on_desktop']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="delete.php?id=<?php echo $button['id']; ?>" class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Delete this button?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-life-ring fa-3x text-muted mb-3"></i>
                <h5>No floating buttons created yet</h5>
                <p class="text-muted">Use the quick templates above or create a custom button.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Button Modal -->
<div class="modal fade" id="buttonModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Floating Button</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="save.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="button_id" id="button_id">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Button Title *</label>
                                <input type="text" name="title" id="title" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Icon</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i id="icon-preview" class="fas fa-life-ring"></i>
                                    </span>
                                    <input type="text" name="icon" id="icon" class="form-control" placeholder="fab fa-whatsapp">
                                </div>
                                <div class="form-text">
                                    Browse icons at <a href="https://fontawesome.com/icons" target="_blank">FontAwesome</a>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Custom Icon (Optional)</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <input type="file" name="icon_upload" id="icon_upload" class="form-control" accept="image/*">
                                        <input type="hidden" name="custom_icon" id="custom_icon">
                                    </div>
                                    <div class="col-md-6">
                                        <?php if (count($mediaFiles) > 0): ?>
                                        <div class="media-selector" style="max-height: 100px; overflow-y: auto;">
                                            <?php foreach ($mediaFiles as $media): ?>
                                            <img src="<?php echo SITE_URL . '/' . $media['file_path']; ?>" 
                                                 class="media-thumb" 
                                                 data-path="<?php echo $media['file_path']; ?>"
                                                 style="width: 30px; height: 30px; object-fit: cover; margin: 2px; cursor: pointer; border-radius: 4px; border: 2px solid transparent;"
                                                 title="<?php echo htmlspecialchars($media['original_name']); ?>">
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Color *</label>
                                        <input type="color" name="color" id="color" class="form-control" value="#25D366">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Position</label>
                                        <select name="position" id="position" class="form-select">
                                            <option value="right">Right Side</option>
                                            <option value="left">Left Side</option>
                                            <option value="bottom-right">Bottom Right</option>
                                            <option value="bottom-left">Bottom Left</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">URL/Action *</label>
                                <input type="text" name="url" id="url" class="form-control" required placeholder="https://example.com">
                                <div class="form-text">
                                    <strong>Examples:</strong><br>
                                    • Website: <code>https://example.com</code><br>
                                    • Phone: <code>tel:+1234567890</code><br>
                                    • Email: <code>mailto:support@example.com</code><br>
                                    • WhatsApp: <code>https://wa.me/1234567890</code><br>
                                    • Tawk.to Chat: <code>6869a98370e7fd1919383828/1ivebsat9</code> (Just paste your widget ID)<br>
                                    • Back to Top: <code>javascript:window.scrollTo({top: 0, behavior: "smooth"})</code>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Target</label>
                                        <select name="target" id="target" class="form-select">
                                            <option value="_blank">New Window</option>
                                            <option value="_self">Same Window</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Order</label>
                                        <input type="number" name="order_index" id="order_index" class="form-control" value="0" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" id="status" class="form-select">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Visibility</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="show_on_mobile" id="show_on_mobile" checked>
                                    <label class="form-check-label" for="show_on_mobile">Mobile</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="show_on_desktop" id="show_on_desktop" checked>
                                    <label class="form-check-label" for="show_on_desktop">Desktop</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="text-center">
                                <h6>Preview</h6>
                                <div style="background: #f8f9fa; height: 200px; border-radius: 8px; position: relative; border: 1px dashed #dee2e6;">
                                    <div id="preview-btn" style="position: absolute; bottom: 20px; right: 20px; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; background: #25D366; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                        <i id="preview-icon" class="fas fa-life-ring"></i>
                                        <img id="preview-img" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover; display: none;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Button</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.btn-preview {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-preview i {
    font-size: 16px;
}

.btn-preview img {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
}

.media-thumb:hover {
    border-color: #0d6efd !important;
    transform: scale(1.1);
}

.media-thumb.selected {
    border-color: #198754 !important;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quick templates
    document.querySelectorAll('.quick-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const template = this.dataset.template;
            loadTemplate(template);
            new bootstrap.Modal(document.getElementById('buttonModal')).show();
        });
    });
    
    // Edit buttons
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = this.dataset;
            populateForm(data);
            document.querySelector('.modal-title').textContent = 'Edit Floating Button';
            new bootstrap.Modal(document.getElementById('buttonModal')).show();
        });
    });
    
    // Icon preview
    document.getElementById('icon').addEventListener('input', function() {
        updatePreview();
    });
    
    // Color preview
    document.getElementById('color').addEventListener('input', function() {
        updatePreview();
    });
    
    // Position preview
    document.getElementById('position').addEventListener('change', function() {
        updatePositionPreview();
    });
    
    // Media selection
    document.querySelectorAll('.media-thumb').forEach(img => {
        img.addEventListener('click', function() {
            document.querySelectorAll('.media-thumb').forEach(i => i.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('custom_icon').value = this.dataset.path;
            updatePreview();
        });
    });
    
    // Reset modal
    document.getElementById('buttonModal').addEventListener('hidden.bs.modal', function() {
        resetForm();
    });
    
    function loadTemplate(template) {
        const templates = {
            whatsapp: {
                title: 'WhatsApp Support',
                icon: 'fab fa-whatsapp',
                color: '#25D366',
                url: 'https://wa.me/<?php echo preg_replace("/[^0-9]/", "", getSetting("contact_whatsapp", "1234567890")); ?>',
                position: 'bottom-left'
            },
            phone: {
                title: 'Call Us',
                icon: 'fas fa-phone',
                color: '#007bff',
                url: 'tel:<?php echo getSetting("contact_phone", "+1234567890"); ?>',
                position: 'left'
            },
            email: {
                title: 'Email Us',
                icon: 'fas fa-envelope',
                color: '#dc3545',
                url: 'mailto:<?php echo getSetting("contact_email", "support@example.com"); ?>',
                position: 'right'
            },
            telegram: {
                title: 'Telegram',
                icon: 'fab fa-telegram',
                color: '#0088cc',
                url: 'https://t.me/yourchannel',
                position: 'bottom-right'
            },
            'back-to-top': {
                title: 'Back to Top',
                icon: 'fas fa-arrow-up',
                color: '#6c757d',
                url: 'javascript:window.scrollTo({top: 0, behavior: "smooth"})',
                position: 'bottom-right'
            },
            tawkto: {
                title: 'Live Chat Support',
                icon: 'fas fa-comments',
                color: '#1e90ff',
                url: '6869a98370e7fd1919383828/1ivebsat9',
                position: 'bottom-right'
            }
        };
        
        if (templates[template]) {
            populateForm(templates[template]);
        }
    }
    
    function populateForm(data) {
        document.getElementById('button_id').value = data.id || '';
        document.getElementById('title').value = data.title || '';
        document.getElementById('icon').value = data.icon || '';
        document.getElementById('color').value = data.color || '#25D366';
        document.getElementById('url').value = data.url || '';
        document.getElementById('target').value = data.target || '_blank';
        document.getElementById('position').value = data.position || 'bottom-right';
        document.getElementById('order_index').value = data.order || 0;
        document.getElementById('status').value = data.status || 'active';
        document.getElementById('show_on_mobile').checked = data.mobile !== '0';
        document.getElementById('show_on_desktop').checked = data.desktop !== '0';
        
        if (data.customIcon) {
            document.getElementById('custom_icon').value = data.customIcon;
            document.querySelectorAll('.media-thumb').forEach(img => {
                img.classList.toggle('selected', img.dataset.path === data.customIcon);
            });
        }
        
        updatePreview();
        updatePositionPreview();
    }
    
    function resetForm() {
        document.querySelector('form').reset();
        document.getElementById('button_id').value = '';
        document.getElementById('color').value = '#25D366';
        document.getElementById('custom_icon').value = '';
        document.querySelectorAll('.media-thumb').forEach(i => i.classList.remove('selected'));
        document.querySelector('.modal-title').textContent = 'Add Floating Button';
        updatePreview();
        updatePositionPreview();
    }
    
    function updatePreview() {
        const icon = document.getElementById('icon').value || 'fas fa-life-ring';
        const color = document.getElementById('color').value;
        const customIcon = document.getElementById('custom_icon').value;
        
        document.getElementById('icon-preview').className = icon;
        document.getElementById('preview-btn').style.backgroundColor = color;
        
        if (customIcon) {
            document.getElementById('preview-icon').style.display = 'none';
            document.getElementById('preview-img').style.display = 'block';
            document.getElementById('preview-img').src = '<?php echo SITE_URL; ?>/' + customIcon;
        } else {
            document.getElementById('preview-icon').style.display = 'block';
            document.getElementById('preview-img').style.display = 'none';
            document.getElementById('preview-icon').className = icon;
        }
    }
    
    function updatePositionPreview() {
        const position = document.getElementById('position').value;
        const btn = document.getElementById('preview-btn');
        
        // Reset position
        btn.style.top = 'auto';
        btn.style.bottom = 'auto';
        btn.style.left = 'auto';
        btn.style.right = 'auto';
        
        switch(position) {
            case 'left':
                btn.style.top = '50%';
                btn.style.left = '20px';
                btn.style.transform = 'translateY(-50%)';
                break;
            case 'right':
                btn.style.top = '50%';
                btn.style.right = '20px';
                btn.style.transform = 'translateY(-50%)';
                break;
            case 'bottom-left':
                btn.style.bottom = '20px';
                btn.style.left = '20px';
                btn.style.transform = 'none';
                break;
            default: // bottom-right
                btn.style.bottom = '20px';
                btn.style.right = '20px';
                btn.style.transform = 'none';
                break;
        }
    }
    
    // Initial preview
    updatePreview();
    updatePositionPreview();
});
</script>

<?php include '../includes/footer.php'; ?>