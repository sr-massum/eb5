<?php 

/**
 * ExchangeBridge - Admin Panel Popup Notice
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

// Get notice ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['error_message'] = 'Invalid notice ID';
    header("Location: index.php");
    exit;
}

$db = Database::getInstance();

// Get notice data and verify it's a popup notice
$notice = $db->getRow("SELECT * FROM notices WHERE id = ? AND type = 'popup'", [$id]);

if (!$notice) {
    $_SESSION['error_message'] = 'Popup notice not found';
    header("Location: index.php");
    exit;
}

// Delete associated media file if exists
if (!empty($notice['image_path'])) {
    $mediaPath = '../../../assets/uploads/notices/' . $notice['image_path'];
    if (file_exists($mediaPath)) {
        unlink($mediaPath);
    }
}

// Delete the notice from database
$deleted = $db->delete('notices', 'id = ?', [$id]);

if ($deleted) {
    $_SESSION['success_message'] = 'Popup notice and associated media deleted successfully';
} else {
    $_SESSION['error_message'] = 'Failed to delete popup notice';
}

// Redirect back to popup notices list
header("Location: index.php");
exit;
?>