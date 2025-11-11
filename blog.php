<?php 

/**
 * ExchangeBridge - Blog Pages
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

// Function to get featured image URL
function getBlogFeaturedImageUrl($imageName) {
    if (empty($imageName)) {
        return '';
    }
    
    // If it's already a full URL, return as is
    if (strpos($imageName, 'http') === 0) {
        return $imageName;
    }
    
    return SITE_URL . '/assets/uploads/blog/' . $imageName;
}

// Get page number for pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

// Set posts per page
$postsPerPage = 6;

// Get total post count for pagination
$totalPosts = countBlogPosts();
$totalPages = ceil($totalPosts / $postsPerPage);

// Adjust page if it's out of bounds
if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

// Calculate offset for SQL query
$offset = ($page - 1) * $postsPerPage;

// Get blog posts with pagination
$posts = getBlogPosts($postsPerPage, $offset);

// Include header
include 'templates/header.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');

* {
    font-family: 'Hind Siliguri', 'Poppins', 'Roboto', sans-serif;
}

.multilingual-text {
    font-family: 'Hind Siliguri', 'Poppins', 'Roboto', sans-serif;
}

.blog-title {
    font-family: 'Hind Siliguri', 'Poppins', 'Roboto', sans-serif;
    font-weight: 600;
}

.blog-excerpt {
    font-family: 'Hind Siliguri', 'Poppins', 'Roboto', sans-serif;
    text-align: justify;
    line-height: 1.6;
}

/* Enhanced image styling for blog listing */
.blog-card-image {
    transition: transform 0.3s ease;
}

.blog-card:hover .blog-card-image {
    transform: scale(1.05);
}

.blog-card {
    transition: all 0.3s ease;
}

.blog-card:hover {
    transform: translateY(-2px);
}

/* Fallback image styling */
.image-fallback {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
}

/* Loading animation for images */
.blog-image-loading {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}
</style>

<!-- Main Content -->
<main class="flex-grow container mx-auto p-4 md:p-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-card overflow-hidden mb-6 section-bg">
        <div class="bg-primary text-white p-4 border-b border-gray-200 dark:border-gray-600">
            <h1 class="text-xl font-semibold text-center">Blog</h1>
        </div>
        
        <div class="p-6 section-content">
            <?php if (count($posts) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($posts as $post): ?>
                <div class="blog-card bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow">
                    <?php if (!empty($post['featured_image'])): ?>
                        <?php $imageUrl = getBlogFeaturedImageUrl($post['featured_image']); ?>
                        <div class="w-full h-48 overflow-hidden blog-image-loading">
                            <img src="<?php echo $imageUrl; ?>" 
                                 alt="<?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>" 
                                 class="blog-card-image w-full h-full object-cover"
                                 onload="this.parentElement.classList.remove('blog-image-loading')"
                                 onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'image-fallback w-full h-full\'><i class=\'fas fa-newspaper\'></i></div>';">
                        </div>
                    <?php else: ?>
                        <div class="w-full h-48 bg-gray-200 dark:bg-gray-600 flex items-center justify-center image-fallback">
                            <i class="fas fa-newspaper text-gray-400 dark:text-gray-500 text-5xl"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="p-4">
                        <h2 class="text-xl font-bold mb-2 blog-title hover:text-primary transition-colors">
                            <a href="blog-single.php?slug=<?php echo urlencode($post['slug']); ?>">
                                <?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </h2>
                        
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-3 flex items-center">
                            <i class="fas fa-user-circle mr-1"></i>
                            <span class="mr-3"><?php echo htmlspecialchars($post['author_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <i class="far fa-calendar-alt mr-1"></i>
                            <span><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                        </div>
                        
                        <p class="text-gray-600 dark:text-gray-400 mb-4 blog-excerpt">
                            <?php 
                            $excerpt = !empty($post['excerpt']) ? $post['excerpt'] : mb_substr(strip_tags($post['content']), 0, 150, 'UTF-8');
                            echo htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8') . (mb_strlen($excerpt, 'UTF-8') >= 150 ? '...' : '');
                            ?>
                        </p>
                        
                        <a href="blog-single.php?slug=<?php echo urlencode($post['slug']); ?>" class="inline-block bg-primary text-white px-4 py-2 rounded-full text-sm hover:bg-primary-dark transition-colors">
                            Read More <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="flex justify-center mt-8">
                <div class="inline-flex">
                    <a href="blog.php?page=1" class="bg-gray-200 dark:bg-gray-700 px-4 py-2 rounded-l-lg <?php echo $page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-300 dark:hover:bg-gray-600'; ?>">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    
                    <a href="blog.php?page=<?php echo max(1, $page - 1); ?>" class="bg-gray-200 dark:bg-gray-700 px-4 py-2 <?php echo $page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-300 dark:hover:bg-gray-600'; ?>">
                        <i class="fas fa-angle-left"></i>
                    </a>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                    <a href="blog.php?page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'bg-primary text-white' : 'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600'; ?> px-4 py-2">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <a href="blog.php?page=<?php echo min($totalPages, $page + 1); ?>" class="bg-gray-200 dark:bg-gray-700 px-4 py-2 <?php echo $page >= $totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-300 dark:hover:bg-gray-600'; ?>">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    
                    <a href="blog.php?page=<?php echo $totalPages; ?>" class="bg-gray-200 dark:bg-gray-700 px-4 py-2 rounded-r-lg <?php echo $page >= $totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-300 dark:hover:bg-gray-600'; ?>">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="text-center py-12">
                <i class="fas fa-newspaper text-gray-400 text-5xl mb-4"></i>
                <h2 class="text-2xl font-bold mb-2">No Blog Posts Yet</h2>
                <p class="text-gray-600 dark:text-gray-400">Check back soon for updates and news!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'templates/footer.php'; ?>