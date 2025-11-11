<?php 

/**
 * ExchangeBridge - Admin Panel Pages
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

// Get all pages
$db = Database::getInstance();
$pages = $db->getRows("SELECT * FROM pages ORDER BY menu_order ASC, title ASC");

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

<!-- TinyMCE Script -->
<script src="https://cdn.tiny.cloud/1/hhiyirqkh3fnrmgjs7nq6tpk6nqb62m3vww7smgrz7kjfv6v/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item active">Pages</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-file-alt mr-1"></i> Pages Management
        <button type="button" class="btn btn-primary btn-sm float-right" data-toggle="modal" data-target="#addPageModal">
            <i class="fas fa-plus"></i> Add New Page
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

        <!-- Quick Create Default Pages -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Quick Create Default Pages</h6>
            </div>
            <div class="card-body">
                <p class="text-muted">Create standard website pages with default content that you can customize later.</p>
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-outline-primary btn-block create-default-page" data-type="about">
                            <i class="fas fa-info-circle"></i> About Us
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-outline-primary btn-block create-default-page" data-type="contact">
                            <i class="fas fa-envelope"></i> Contact Us
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-outline-primary btn-block create-default-page" data-type="privacy">
                            <i class="fas fa-shield-alt"></i> Privacy Policy
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-outline-primary btn-block create-default-page" data-type="terms">
                            <i class="fas fa-file-contract"></i> Terms of Service
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Menu</th>
                        <th>Order</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pages) > 0): ?>
                    <?php foreach ($pages as $page): ?>
                    <tr>
                        <td>
                            <div class="font-weight-bold"><?php echo htmlspecialchars($page['title']); ?></div>
                            <?php if (!empty($page['meta_title'])): ?>
                                <small class="text-muted">SEO: <?php echo htmlspecialchars($page['meta_title']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code>/<?php echo htmlspecialchars($page['slug']); ?></code>
                            <a href="<?php echo SITE_URL; ?>/<?php echo $page['slug']; ?>" target="_blank" class="ml-2">
                                <i class="fas fa-external-link-alt text-muted"></i>
                            </a>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $page['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($page['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($page['show_in_menu']): ?>
                                <span class="badge badge-primary"><i class="fas fa-check"></i> Visible</span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><i class="fas fa-times"></i> Hidden</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $page['menu_order']; ?></td>
                        <td><?php echo date('M j, Y', strtotime($page['created_at'])); ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary edit-page-btn" 
                                    data-page-id="<?php echo $page['id']; ?>"
                                    data-page-title="<?php echo htmlspecialchars($page['title'], ENT_QUOTES); ?>"
                                    data-page-slug="<?php echo htmlspecialchars($page['slug'], ENT_QUOTES); ?>"
                                    data-page-content="<?php echo htmlspecialchars($page['content'], ENT_QUOTES); ?>"
                                    data-page-meta-title="<?php echo htmlspecialchars($page['meta_title'], ENT_QUOTES); ?>"
                                    data-page-meta-description="<?php echo htmlspecialchars($page['meta_description'], ENT_QUOTES); ?>"
                                    data-page-meta-keywords="<?php echo htmlspecialchars($page['meta_keywords'], ENT_QUOTES); ?>"
                                    data-page-status="<?php echo $page['status']; ?>"
                                    data-page-show-menu="<?php echo $page['show_in_menu'] ? '1' : '0'; ?>"
                                    data-page-menu-order="<?php echo $page['menu_order']; ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="<?php echo SITE_URL; ?>/<?php echo $page['slug']; ?>" target="_blank" 
                               class="btn btn-sm btn-outline-info">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="delete.php?id=<?php echo $page['id']; ?>" class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Are you sure you want to delete this page?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No pages found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Page Modal -->
<div class="modal fade" id="addPageModal" tabindex="-1" role="dialog" aria-labelledby="addPageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPageModalLabel">Add New Page</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="save.php" method="POST" id="pageForm">
                <div class="modal-body">
                    <input type="hidden" name="page_id" id="page_id">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="title">Page Title *</label>
                                <input type="text" name="title" id="title" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="slug">URL Slug *</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><?php echo SITE_URL; ?>/</span>
                                    </div>
                                    <input type="text" name="slug" id="slug" class="form-control" required>
                                </div>
                                <small class="form-text text-muted">Leave empty to auto-generate from title</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="content">Page Content</label>
                                <textarea name="content" id="content" class="form-control" rows="15"></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="show_in_menu" id="show_in_menu" class="custom-control-input" value="1">
                                    <label class="custom-control-label" for="show_in_menu">
                                        Show in Navigation Menu
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="menu_order">Menu Order</label>
                                <input type="number" name="menu_order" id="menu_order" class="form-control" value="0" min="0">
                            </div>
                            
                            <hr>
                            
                            <h6>SEO Settings</h6>
                            
                            <div class="form-group">
                                <label for="meta_title">Meta Title</label>
                                <input type="text" name="meta_title" id="meta_title" class="form-control" maxlength="60">
                                <small class="form-text text-muted">Recommended: 50-60 characters</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_description">Meta Description</label>
                                <textarea name="meta_description" id="meta_description" class="form-control" rows="3" maxlength="160"></textarea>
                                <small class="form-text text-muted">Recommended: 150-160 characters</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_keywords">Meta Keywords</label>
                                <input type="text" name="meta_keywords" id="meta_keywords" class="form-control">
                                <small class="form-text text-muted">Comma-separated keywords</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Page</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let tinymceInitialized = false;

// Initialize TinyMCE after page load
$(document).ready(function() {
    
    // Initialize TinyMCE
    tinymce.init({
        selector: '#content',
        height: 400,
        menubar: false,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | formatselect | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | code | help',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        setup: function(editor) {
            editor.on('init', function() {
                tinymceInitialized = true;
                console.log('TinyMCE initialized');
            });
            editor.on('change', function() {
                editor.save();
            });
        }
    });
    
    // Auto-generate slug from title
    $('#title').on('input', function() {
        if (!$('#page_id').val()) { // Only for new pages
            const title = $(this).val();
            const slug = title.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-+|-+$/g, '');
            $('#slug').val(slug);
        }
    });
    
    // Edit page click handler
    $(document).on('click', '.edit-page-btn', function(e) {
        e.preventDefault();
        console.log('Edit button clicked');
        
        const $btn = $(this);
        
        // Extract page data from data attributes
        const pageData = {
            id: $btn.data('page-id'),
            title: $btn.data('page-title'),
            slug: $btn.data('page-slug'),
            content: $btn.data('page-content'),
            status: $btn.data('page-status'),
            showMenu: $btn.data('page-show-menu'),
            menuOrder: $btn.data('page-menu-order'),
            metaTitle: $btn.data('page-meta-title'),
            metaDescription: $btn.data('page-meta-description'),
            metaKeywords: $btn.data('page-meta-keywords')
        };
        
        console.log('Page data:', pageData);
        
        // Reset form first
        $('#pageForm')[0].reset();
        
        // Populate form fields
        $('#page_id').val(pageData.id || '');
        $('#title').val(pageData.title || '');
        $('#slug').val(pageData.slug || '');
        $('#status').val(pageData.status || 'active');
        $('#show_in_menu').prop('checked', pageData.showMenu == 1);
        $('#menu_order').val(pageData.menuOrder || 0);
        $('#meta_title').val(pageData.metaTitle || '');
        $('#meta_description').val(pageData.metaDescription || '');
        $('#meta_keywords').val(pageData.metaKeywords || '');
        
        // Set modal title
        $('#addPageModalLabel').text('Edit Page');
        
        // Show modal
        $('#addPageModal').modal('show');
        
        // Set TinyMCE content after a short delay
        setTimeout(function() {
            if (tinymceInitialized && tinymce.get('content')) {
                tinymce.get('content').setContent(pageData.content || '');
                console.log('TinyMCE content set');
            } else {
                $('#content').val(pageData.content || '');
                console.log('Fallback: textarea content set');
            }
        }, 300);
    });
    
    // Reset modal when closed
    $('#addPageModal').on('hidden.bs.modal', function() {
        $('#pageForm')[0].reset();
        $('#page_id').val('');
        $('#addPageModalLabel').text('Add New Page');
        
        // Clear TinyMCE content
        if (tinymceInitialized && tinymce.get('content')) {
            tinymce.get('content').setContent('');
        }
    });
    
    // Create default pages
    $('.create-default-page').click(function(e) {
        e.preventDefault();
        const type = $(this).data('type');
        
        const templates = {
            about: {
                title: 'About Us',
                slug: 'about',
                content: `<h2>About Our Exchange Service</h2>
<p>Welcome to our trusted digital currency exchange platform. We provide fast, secure, and reliable exchange services for various digital currencies and payment methods.</p>
<h3>Our Mission</h3>
<p>To provide seamless and secure digital currency exchange services that bridge the gap between traditional and digital financial systems.</p>
<h3>Why Choose Us?</h3>
<ul>
<li>Fast transaction processing</li>
<li>Secure and encrypted transactions</li>
<li>24/7 customer support</li>
<li>Competitive exchange rates</li>
<li>Multiple payment methods</li>
</ul>`,
                meta_title: 'About Us - Your Trusted Exchange Platform'
            },
            contact: {
                title: 'Contact Us',
                slug: 'contact',
                content: `<h2>Get in Touch</h2>
<p>We're here to help! Contact us through any of the following methods:</p>
<div class="row">
<div class="col-md-6">
<h3>Contact Information</h3>
<p><strong>WhatsApp:</strong> +880XXXXXXXXX</p>
<p><strong>Email:</strong> support@example.com</p>
<p><strong>Phone:</strong> +880XXXXXXXXX</p>
</div>
<div class="col-md-6">
<h3>Business Hours</h3>
<p><strong>Monday - Friday:</strong> 9:00 AM - 6:00 PM</p>
<p><strong>Saturday:</strong> 10:00 AM - 4:00 PM</p>
<p><strong>Sunday:</strong> Closed</p>
</div>
</div>`,
                meta_title: 'Contact Us - Get Support'
            },
            privacy: {
                title: 'Privacy Policy',
                slug: 'privacy-policy',
                content: `<h2>Privacy Policy</h2>
<p><strong>Last updated:</strong> ${new Date().toLocaleDateString()}</p>
<h3>Information We Collect</h3>
<p>We collect information you provide directly to us, such as when you create an account, make a transaction, or contact us for support.</p>
<h3>How We Use Your Information</h3>
<ul>
<li>Process your transactions</li>
<li>Verify your identity</li>
<li>Provide customer support</li>
<li>Comply with legal requirements</li>
</ul>`,
                meta_title: 'Privacy Policy - How We Protect Your Data'
            },
            terms: {
                title: 'Terms of Service',
                slug: 'terms-of-service',
                content: `<h2>Terms of Service</h2>
<p><strong>Last updated:</strong> ${new Date().toLocaleDateString()}</p>
<h3>Acceptance of Terms</h3>
<p>By using our exchange service, you agree to be bound by these Terms of Service.</p>
<h3>Service Description</h3>
<p>We provide digital currency exchange services. All exchanges are subject to verification and compliance procedures.</p>`,
                meta_title: 'Terms of Service - Legal Agreement'
            }
        };
        
        const template = templates[type];
        if (template) {
            // Reset form first
            $('#pageForm')[0].reset();
            $('#page_id').val('');
            
            // Set template data
            $('#title').val(template.title);
            $('#slug').val(template.slug);
            $('#status').val('active');
            $('#show_in_menu').prop('checked', true);
            $('#meta_title').val(template.meta_title);
            
            // Set modal title
            $('#addPageModalLabel').text('Add New Page');
            
            // Show modal
            $('#addPageModal').modal('show');
            
            // Set TinyMCE content
            setTimeout(function() {
                if (tinymceInitialized && tinymce.get('content')) {
                    tinymce.get('content').setContent(template.content);
                } else {
                    $('#content').val(template.content);
                }
            }, 300);
        }
    });
    
    // Before form submission, ensure TinyMCE content is saved
    $('#pageForm').on('submit', function() {
        if (tinymceInitialized && tinymce.get('content')) {
            tinymce.get('content').save();
        }
        return true;
    });
});
</script>

<?php include '../includes/footer.php'; ?>