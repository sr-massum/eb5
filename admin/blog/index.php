<?php 

/**
 * ExchangeBridge - Admin Panel Blog
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
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// Check if user is logged in, if not redirect to login page
if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

$user = Auth::getUser();
$db = Database::getInstance();

// Set MySQL connection to UTF-8
$db->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

// Get blog posts with author info
$posts = $db->getRows("
    SELECT p.*, u.username as author_name 
    FROM blog_posts p 
    JOIN users u ON p.author_id = u.id 
    ORDER BY p.created_at DESC
");

// Get statistics
$totalPosts = count($posts);
$publishedPosts = count(array_filter($posts, function($post) { return $post['status'] === 'published'; }));
$draftPosts = count(array_filter($posts, function($post) { return $post['status'] === 'draft'; }));
$postsThisMonth = count(array_filter($posts, function($post) { 
    return date('Y-m', strtotime($post['created_at'])) === date('Y-m'); 
}));

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
/* Enhanced font support styles */
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@100;200;300;400;500;600;700;800;900&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');

/* Force UTF-8 encoding for all text */
* {
    -webkit-font-feature-settings: "kern" 1;
    font-feature-settings: "kern" 1;
    text-rendering: optimizeLegibility;
}

/* Primary font family */
.blog-content {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', 'Roboto', Arial, sans-serif !important;
    line-height: 1.8 !important;
    direction: ltr;
    unicode-bidi: embed;
    font-weight: 400;
    font-size: 16px;
}

/* Mixed language support */
.multilingual-text {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', 'Roboto', Arial, sans-serif !important;
    line-height: 1.8 !important;
    font-weight: 400;
}

/* Table content font support */
.table td, .table th {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif !important;
    line-height: 1.8 !important;
    vertical-align: middle;
    font-weight: 400;
}

/* Statistics cards */
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.post-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
}

.post-title {
    font-weight: 600;
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif !important;
}

.post-excerpt {
    color: #6c757d;
    font-size: 0.9rem;
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', Arial, sans-serif !important;
}
</style>

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item active">Blog Management</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-newspaper mr-1"></i> Blog Posts Management
        <a href="add.php" class="btn btn-primary btn-sm float-right">
            <i class="fas fa-plus"></i> Add New Post
        </a>
    </div>
    <div class="card-body">
        <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Posts</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalPosts; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-newspaper fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Published</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $publishedPosts; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Drafts</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $draftPosts; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-edit fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">This Month</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $postsThisMonth; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($posts) > 0): ?>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td>
                            <?php if ($post['featured_image']): ?>
                                <img src="../../assets/uploads/blog/<?php echo htmlspecialchars($post['featured_image'], ENT_QUOTES, 'UTF-8'); ?>" 
                                     alt="Featured Image" class="post-image">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center bg-light post-image">
                                    <i class="fas fa-image text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="post-title blog-content"><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="post-excerpt blog-content">
                                <?php 
                                $excerpt = $post['excerpt'] ?: strip_tags($post['content']);
                                echo htmlspecialchars(mb_substr($excerpt, 0, 100, 'UTF-8'), ENT_QUOTES, 'UTF-8') . (mb_strlen($excerpt, 'UTF-8') > 100 ? '...' : '');
                                ?>
                            </div>
                            <small class="text-muted">Slug: <?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?></small>
                        </td>
                        <td class="blog-content"><?php echo htmlspecialchars($post['author_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $post['status'] === 'published' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($post['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y H:i', strtotime($post['created_at'])); ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="../../blog-single.php?slug=<?php echo urlencode($post['slug']); ?>" 
                               class="btn btn-sm btn-outline-info" target="_blank" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-danger delete-confirm" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-newspaper fa-3x mb-3"></i>
                                <p class="mb-0">No blog posts found</p>
                                <a href="add.php" class="btn btn-primary btn-sm mt-2">Create your first blog post</a>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
$(document).ready(function() {
    // Set document encoding
    document.charset = "UTF-8";
    
    // Delete confirmation
    $('.delete-confirm').click(function(e) {
        if (!confirm('Are you sure you want to delete this blog post? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>