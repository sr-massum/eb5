<?php 

/**
 * ExchangeBridge - Admin Panel Update Blog
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['post_id']) || empty($_POST['title']) || empty($_POST['content']) || empty($_POST['slug'])) {
            throw new Exception('Post ID, title, content, and slug are required fields.');
        }

        $post_id = (int)$_POST['post_id'];
        
        // Check if post exists and user has permission to edit
        $existing_post = $db->getRow("SELECT * FROM blog_posts WHERE id = ?", [$post_id]);
        if (!$existing_post) {
            throw new Exception('Blog post not found.');
        }

        // Sanitize input data
        $title = trim($_POST['title']);
        $slug = trim($_POST['slug']);
        $excerpt = trim($_POST['excerpt'] ?? '');
        $content = $_POST['content']; // Don't trim content as it may contain formatting
        $status = $_POST['status'] ?? 'draft';
        $meta_title = trim($_POST['meta_title'] ?? '');
        $meta_description = trim($_POST['meta_description'] ?? '');
        $meta_keywords = trim($_POST['meta_keywords'] ?? '');

        // Validate slug uniqueness (excluding current post)
        $existingPost = $db->getRow("SELECT id FROM blog_posts WHERE slug = ? AND id != ?", [$slug, $post_id]);
        if ($existingPost) {
            throw new Exception('Slug already exists. Please choose a different slug.');
        }

        // Handle file upload
        $featured_image = $existing_post['featured_image']; // Keep existing image by default
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../assets/uploads/blog/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_info = pathinfo($_FILES['featured_image']['name']);
            $file_extension = strtolower($file_info['extension']);
            
            // Validate file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP files are allowed.');
            }

            // Generate unique filename
            $new_featured_image = time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_featured_image;

            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
                // Remove old image if it exists
                if ($existing_post['featured_image'] && file_exists($upload_dir . $existing_post['featured_image'])) {
                    unlink($upload_dir . $existing_post['featured_image']);
                }
                $featured_image = $new_featured_image;
            } else {
                throw new Exception('Failed to upload new image.');
            }
        }

        // Update blog post
        $sql = "UPDATE blog_posts SET 
                title = ?, slug = ?, excerpt = ?, content = ?, featured_image = ?, 
                status = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, 
                updated_at = NOW() 
                WHERE id = ?";
        
        $params = [
            $title,
            $slug,
            $excerpt,
            $content,
            $featured_image,
            $status,
            $meta_title,
            $meta_description,
            $meta_keywords,
            $post_id
        ];

        $result = $db->query($sql, $params);

        if ($result) {
            $_SESSION['success_message'] = 'Blog post updated successfully!';
        } else {
            throw new Exception('Failed to update blog post.');
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Redirect back to blog management
header("Location: index.php");
exit;
?>