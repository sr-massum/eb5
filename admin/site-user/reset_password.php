<?php 

/**
 * ExchangeBridge - Admin Panel User Password Reset
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
    $_SESSION['error_message'] = 'You do not have permission to reset passwords';
    header("Location: index.php");
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    
    // Validate form data
    if ($id <= 0) {
        $_SESSION['error_message'] = 'Invalid user ID';
    } else {
        // Check if user exists
        $db = Database::getInstance();
        $user = $db->getRow("SELECT * FROM users WHERE id = ?", [$id]);
        
        if (!$user) {
            $_SESSION['error_message'] = 'User not found';
        } else {
            // Generate random password if not provided
            if (empty($newPassword)) {
                $newPassword = bin2hex(random_bytes(4)); // 8 characters
            }
            
            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update user password
            $result = $db->update('users', [
                'password' => $hashedPassword
            ], 'id = ?', [$id]);
            
            if ($result) {
                $_SESSION['success_message'] = 'Password reset successfully. New password: ' . $newPassword;
            } else {
                $_SESSION['error_message'] = 'Failed to reset password';
            }
        }
    }
}

// Redirect back to users list
header("Location: index.php");
exit;