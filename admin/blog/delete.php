<?php 

/**
 * ExchangeBridge - Admin Panel Delete Blog
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

$user = Auth::getUser();
$db = Database::getInstance();

if (isset($_GET['id'])) {
    try {
        $post_id = (int)$_GET['id'];
        
        // Check if post exists
        $post = $db->getRow("SELECT * FROM blog_posts WHERE id = ?", [$post_id]);
        if (!$post) {
            throw new Exception('Blog post not found.');
        }

        // Delete associated image file if exists
        if ($post['featured_image']) {
            $image_path = '../../assets/uploads/blog/' . $post['featured_image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        // Delete blog post from database
        $result = $db->query("DELETE FROM blog_posts WHERE id = ?", [$post_id]);

        if ($result) {
            $_SESSION['success_message'] = 'Blog post deleted successfully!';
        } else {
            throw new Exception('Failed to delete blog post.');
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = 'Invalid request. Post ID is required.';
}

// Redirect back to blog management
header("Location: index.php");
exit;
?>