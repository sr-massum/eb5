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


// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance();
    
    // Get form data
    $page_id = isset($_POST['page_id']) ? (int)$_POST['page_id'] : 0;
    $title = sanitizeInput($_POST['title']);
    $slug = sanitizeInput($_POST['slug']);
    $content = $_POST['content']; // Don't sanitize HTML content
    $meta_title = sanitizeInput($_POST['meta_title']);
    $meta_description = sanitizeInput($_POST['meta_description']);
    $meta_keywords = sanitizeInput($_POST['meta_keywords']);
    $status = sanitizeInput($_POST['status']);
    $show_in_menu = isset($_POST['show_in_menu']) ? 1 : 0;
    $menu_order = (int)$_POST['menu_order'];
    
    // Auto-generate slug if empty
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    }
    
    // Validate required fields
    if (empty($title) || empty($slug)) {
        $_SESSION['error_message'] = 'Title and slug are required.';
        header("Location: index.php");
        exit;
    }
    
    // Check if slug already exists (for different page)
    $existing = $db->getRow("SELECT id FROM pages WHERE slug = ? AND id != ?", [$slug, $page_id]);
    if ($existing) {
        $_SESSION['error_message'] = 'A page with this slug already exists.';
        header("Location: index.php");
        exit;
    }
    
    try {
        if ($page_id > 0) {
            // Update existing page
            $sql = "UPDATE pages SET 
                    title = ?, 
                    slug = ?, 
                    content = ?, 
                    meta_title = ?, 
                    meta_description = ?, 
                    meta_keywords = ?, 
                    status = ?, 
                    show_in_menu = ?, 
                    menu_order = ?, 
                    updated_at = NOW() 
                    WHERE id = ?";
            
            $params = [$title, $slug, $content, $meta_title, $meta_description, 
                      $meta_keywords, $status, $show_in_menu, $menu_order, $page_id];
            
            $db->query($sql, $params);
            $_SESSION['success_message'] = 'Page updated successfully!';
        } else {
            // Create new page
            $sql = "INSERT INTO pages (title, slug, content, meta_title, meta_description, 
                    meta_keywords, status, show_in_menu, menu_order, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $params = [$title, $slug, $content, $meta_title, $meta_description, 
                      $meta_keywords, $status, $show_in_menu, $menu_order];
            
            $db->query($sql, $params);
            $_SESSION['success_message'] = 'Page created successfully!';
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error saving page: ' . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = 'Invalid request method.';
}

header("Location: index.php");
exit;
?>