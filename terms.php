<?php 

/**
 * ExchangeBridge - Terms & Confitions Page
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

require_once __DIR__ . '/includes/app.php';
require_once 'config/config.php';
require_once 'config/verification.php';
require_once 'config/license.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';

// Get terms and conditions page content
$page = getPageBySlug('terms');

// Include header
include 'templates/header.php';
?>

<!-- Main Content -->
<main class="flex-grow container mx-auto p-4 md:p-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-card overflow-hidden mb-6 section-bg">
        <div class="bg-primary text-white p-4 border-b border-gray-200 dark:border-gray-600">
            <h1 class="text-xl font-semibold text-center">
                <?php echo htmlspecialchars($page['title'] ?? 'Terms & Conditions'); ?>
            </h1>
        </div>
        
        <div class="p-6 section-content">
            <div class="prose dark:prose-invert max-w-none">
                <?php 
                if (!empty($page['content'])) {
                    echo $page['content'];
                } else {
                    echo '<p class="text-center text-gray-500 dark:text-gray-400">Terms & Conditions content has not been added yet.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</main>

<?php include 'templates/footer.php'; ?>