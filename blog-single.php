<?php 

/**
 * ExchangeBridge - Blog Posts
 *
 * package     ExchangeBridge
 * author      Saieed Rahman
 * copyright   SidMan Solution 2025
 * version     1.0.0
 */

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

// Start session
session_start();

// Define access constant
define('ALLOW_ACCESS', true);

require_once __DIR__ . '/includes/app.php';
require_once 'config/config.php';
require_once 'config/verification.php';
require_once 'config/license.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';



// Get post slug
$slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';

if (empty($slug)) {
    header("Location: blog.php");
    exit;
}

// Get post data
$post = getBlogPostBySlug($slug);

if (!$post) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

// Include header
include 'templates/header.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');

* {
    font-family: 'Hind Siliguri', 'Poppins', 'Roboto', sans-serif;
}

.blog-content {
    font-family: 'Hind Siliguri', 'Poppins', 'Roboto', sans-serif;
    line-height: 1.8;
    font-size: 16px;
}

.blog-title {
    font-family: 'Hind Siliguri', 'Poppins', 'Roboto', sans-serif;
    font-weight: 600;
}

.blog-meta {
    font-family: 'Poppins', 'Roboto', sans-serif;
}

.blog-content h1, .blog-content h2, .blog-content h3, 
.blog-content h4, .blog-content h5, .blog-content h6 {
    font-family: 'Hind Siliguri', 'Poppins', 'Roboto', sans-serif;
    margin-top: 1.5rem;
    margin-bottom: 1rem;
}

.blog-content p {
    margin-bottom: 1rem;
    text-align: justify;
}

.blog-content ul, .blog-content ol {
    margin-bottom: 1rem;
    padding-left: 1.5rem;
}

.blog-content blockquote {
    border-left: 4px solid #5D5CDE;
    padding-left: 1rem;
    margin: 1rem 0;
    font-style: italic;
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.25rem;
}
</style>

<!-- Main Content -->
<main class="flex-grow container mx-auto p-4 md:p-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-card overflow-hidden mb-6 section-bg">
        <!-- Featured Image -->
        <?php if (!empty($post['featured_image'])): ?>
        <div class="w-full h-64 md:h-96 overflow-hidden">
            <img src="assets/uploads/blog/<?php echo htmlspecialchars($post['featured_image']); ?>" 
                 alt="<?php echo htmlspecialchars($post['title']); ?>" 
                 class="w-full h-full object-cover">
        </div>
        <?php endif; ?>
        
        <div class="p-6 section-content">
            <!-- Title -->
            <h1 class="text-3xl md:text-4xl font-bold mb-4 blog-title">
                <?php echo htmlspecialchars($post['title']); ?>
            </h1>
            
            <!-- Meta Information -->
            <div class="flex flex-wrap items-center text-sm text-gray-600 dark:text-gray-400 mb-6 blog-meta">
                <div class="flex items-center mr-6 mb-2">
                    <i class="fas fa-user-circle mr-2"></i>
                    <span>By <?php echo htmlspecialchars($post['author_name']); ?></span>
                </div>
                <div class="flex items-center mr-6 mb-2">
                    <i class="far fa-calendar-alt mr-2"></i>
                    <span><?php echo date('F d, Y', strtotime($post['created_at'])); ?></span>
                </div>
                <div class="flex items-center mb-2">
                    <i class="far fa-clock mr-2"></i>
                    <span><?php echo ceil(str_word_count(strip_tags($post['content'])) / 200); ?> min read</span>
                </div>
            </div>
            
            <!-- Content -->
            <div class="blog-content prose prose-lg max-w-none">
                <?php echo $post['content']; ?>
            </div>
            
            <!-- Navigation -->
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-600">
                <div class="flex justify-between items-center">
                    <a href="blog.php" class="inline-flex items-center text-primary hover:text-primary-dark transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Blog
                    </a>
                    
                    <div class="flex space-x-4">
                        <!-- Social Share Buttons -->
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . '/blog-single.php?slug=' . $post['slug']); ?>&text=<?php echo urlencode($post['title']); ?>" 
                           target="_blank" class="text-blue-500 hover:text-blue-600 transition-colors">
                            <i class="fab fa-twitter text-lg"></i>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . '/blog-single.php?slug=' . $post['slug']); ?>" 
                           target="_blank" class="text-blue-600 hover:text-blue-700 transition-colors">
                            <i class="fab fa-facebook text-lg"></i>
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(SITE_URL . '/blog-single.php?slug=' . $post['slug']); ?>" 
                           target="_blank" class="text-blue-800 hover:text-blue-900 transition-colors">
                            <i class="fab fa-linkedin text-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'templates/footer.php'; ?>