<?php 

/**
 * ExchangeBridge - Admin Panel Scrolling Notice
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

// Get notice data and verify it's a scrolling notice
$notice = $db->getRow("SELECT * FROM notices WHERE id = ? AND type = 'scrolling'", [$id]);

if (!$notice) {
    $_SESSION['error_message'] = 'Scrolling notice not found';
    header("Location: index.php");
    exit;
}

// Delete the notice
$deleted = $db->delete('notices', 'id = ?', [$id]);

if ($deleted) {
    $_SESSION['success_message'] = 'Scrolling notice deleted successfully';
} else {
    $_SESSION['error_message'] = 'Failed to delete scrolling notice';
}

// Redirect back to scrolling notices list
header("Location: index.php");
exit;
?>