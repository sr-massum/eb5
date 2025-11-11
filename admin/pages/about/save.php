<?php 

/**
 * ExchangeBridge - Admin Panel About Page
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
require_once '../../../config/config.php';
require_once '../../../config/verification.php';
require_once '../../../config/license.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/app.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/security.php';


// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance();
    
    // Get form data
    $action = sanitizeInput($_POST['action']);
    $page_id = isset($_POST['page_id']) ? (int)$_POST['page_id'] : 0;
    $title = sanitizeInput($_POST['title']);
    $content = $_POST['content']; // Don't sanitize HTML content
    $meta_title = sanitizeInput($_POST['meta_title']);
    $meta_description = sanitizeInput($_POST['meta_description']);
    $meta_keywords = sanitizeInput($_POST['meta_keywords']);
    $status = sanitizeInput($_POST['status']);
    $show_in_menu = isset($_POST['show_in_menu']) ? 1 : 0;
    $menu_order = (int)$_POST['menu_order'];
    
    // Fixed slug for about page
    $slug = 'about';
    
    // Validate required fields
    if (empty($title) || empty($content)) {
        $_SESSION['error_message'] = 'Title and content are required.';
        header("Location: index.php");
        exit;
    }
    
    try {
        if ($action === 'update' && $page_id > 0) {
            // Update existing about page
            $sql = "UPDATE pages SET 
                    title = ?, 
                    content = ?, 
                    meta_title = ?, 
                    meta_description = ?, 
                    meta_keywords = ?, 
                    status = ?, 
                    show_in_menu = ?, 
                    menu_order = ?, 
                    updated_at = NOW() 
                    WHERE id = ? AND slug = 'about'";
            
            $params = [$title, $content, $meta_title, $meta_description, 
                      $meta_keywords, $status, $show_in_menu, $menu_order, $page_id];
            
            $result = $db->query($sql, $params);
            
            if ($result) {
                $_SESSION['success_message'] = 'About page updated successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to update About page.';
            }
            
        } elseif ($action === 'create') {
            // Check if about page already exists
            $existing = $db->getRow("SELECT id FROM pages WHERE slug = 'about'");
            if ($existing) {
                $_SESSION['error_message'] = 'About page already exists!';
                header("Location: index.php");
                exit;
            }
            
            // Create new about page
            $sql = "INSERT INTO pages (title, slug, content, meta_title, meta_description, 
                    meta_keywords, status, show_in_menu, menu_order, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $params = [$title, $slug, $content, $meta_title, $meta_description, 
                      $meta_keywords, $status, $show_in_menu, $menu_order];
            
            $result = $db->query($sql, $params);
            
            if ($result) {
                $_SESSION['success_message'] = 'About page created successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to create About page.';
            }
        } else {
            $_SESSION['error_message'] = 'Invalid action or missing page ID.';
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