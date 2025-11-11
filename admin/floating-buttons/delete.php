<?php 

/**
 * ExchangeBridge - Admin Panel Delete Floating Button
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

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'Invalid button ID provided.';
    header("Location: index.php");
    exit;
}

$buttonId = (int)$_GET['id'];

try {
    $db = Database::getInstance();
    
    // First, check if the button exists and get its details
    $button = $db->getRow("SELECT * FROM floating_buttons WHERE id = ?", [$buttonId]);
    
    if (!$button) {
        $_SESSION['error_message'] = 'Button not found.';
        header("Location: index.php");
        exit;
    }
    
    // If button has a custom icon file, delete it from filesystem
    if (!empty($button['custom_icon']) && file_exists('../../' . $button['custom_icon'])) {
        @unlink('../../' . $button['custom_icon']);
    }
    
    // Delete the button from database
    $result = $db->query("DELETE FROM floating_buttons WHERE id = ?", [$buttonId]);
    
    if ($result) {
        $_SESSION['success_message'] = 'Floating button "' . htmlspecialchars($button['title']) . '" has been deleted successfully.';
    } else {
        $_SESSION['error_message'] = 'Failed to delete the floating button. Please try again.';
    }
    
} catch (Exception $e) {
    // Log the error (you might want to use a proper logging system)
    error_log("Error deleting floating button: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while deleting the button. Please try again.';
}

// Redirect back to the main page
header("Location: index.php");
exit;
?>