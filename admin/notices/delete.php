<?php 

/**
 * ExchangeBridge - Admin Panel Notices
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
    $_SESSION['error_message'] = 'Invalid notice ID';
    header("Location: index.php");
    exit;
}

$noticeId = (int)$_GET['id'];
$db = Database::getInstance();

// Get notice details before deleting
$notice = $db->getRow("SELECT * FROM notices WHERE id = ?", [$noticeId]);

if (!$notice) {
    $_SESSION['error_message'] = 'Notice not found';
    header("Location: index.php");
    exit;
}

// Delete the notice from database
$deleted = $db->delete('notices', 'id = ?', [$noticeId]);

if ($deleted) {
    // Delete associated media file if exists
    if (!empty($notice['image_path'])) {
        $mediaPath = '../../assets/uploads/notices/' . $notice['image_path'];
        if (file_exists($mediaPath)) {
            unlink($mediaPath);
        }
    }
    
    $_SESSION['success_message'] = 'Notice deleted successfully';
} else {
    $_SESSION['error_message'] = 'Failed to delete notice';
}

// Redirect back to notices list
header("Location: index.php");
exit;
?>