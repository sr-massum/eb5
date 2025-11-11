<?php 

/**
 * ExchangeBridge - Admin Panel Add New Floating Button
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
require_once '../../includes/auth.php';
require_once '../../includes/security.php';

if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $db = Database::getInstance();
    $id = (int)$_GET['id'];
    
    // Get button details for file cleanup
    $button = $db->getRow("SELECT * FROM floating_buttons WHERE id = ?", [$id]);
    
    if ($button) {
        // Delete custom icon file if exists
        if (!empty($button['custom_icon']) && file_exists('../../' . $button['custom_icon'])) {
            unlink('../../' . $button['custom_icon']);
        }
        
        // Delete from database
        $result = $db->delete('floating_buttons', ['id' => $id]);
        
        if ($result) {
            $_SESSION['success_message'] = 'Floating button deleted successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to delete floating button.';
        }
    } else {
        $_SESSION['error_message'] = 'Floating button not found.';
    }
} else {
    $_SESSION['error_message'] = 'Invalid request.';
}

header("Location: index.php");
exit;
?>