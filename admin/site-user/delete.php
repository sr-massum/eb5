<?php 

/**
 * ExchangeBridge - Admin Panel User Delete 
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

// Check if user has admin role
if (!Auth::isAdmin()) {
    $_SESSION['error_message'] = 'You do not have permission to delete users';
    header("Location: index.php");
    exit;
}

// Get user ID from URL
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Prevent deleting yourself
if ($userId === $_SESSION['user_id']) {
    $_SESSION['error_message'] = 'You cannot delete your own account';
    header("Location: index.php");
    exit;
}

// Delete user
$result = Auth::deleteUser($userId);

if ($result['success']) {
    $_SESSION['success_message'] = 'User deleted successfully';
} else {
    $_SESSION['error_message'] = $result['message'];
}

// Redirect back to users list
header("Location: index.php");
exit;