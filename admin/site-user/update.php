<?php 

/**
 * ExchangeBridge - Admin Panel User Update  
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
    $_SESSION['error_message'] = 'You do not have permission to update users';
    header("Location: index.php");
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $role = isset($_POST['role']) ? sanitizeInput($_POST['role']) : 'editor';
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active';
    
    // Validate form data
    if ($id <= 0) {
        $_SESSION['error_message'] = 'Invalid user ID';
    } elseif (empty($username)) {
        $_SESSION['error_message'] = 'Username is required';
    } elseif (empty($email) || !isValidEmail($email)) {
        $_SESSION['error_message'] = 'Valid email address is required';
    } elseif (!in_array($role, ['admin', 'manager', 'editor'])) {
        $_SESSION['error_message'] = 'Invalid role selected';
    } elseif (!in_array($status, ['active', 'inactive'])) {
        $_SESSION['error_message'] = 'Invalid status selected';
    } else {
        // Check if user exists
        $db = Database::getInstance();
        $user = $db->getRow("SELECT * FROM users WHERE id = ?", [$id]);
        
        if (!$user) {
            $_SESSION['error_message'] = 'User not found';
        } else {
            // Check if updating the last admin
            if ($user['role'] === 'admin' && $role !== 'admin') {
                $adminCount = $db->getValue("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                
                if ($adminCount <= 1) {
                    $_SESSION['error_message'] = 'Cannot change the role of the last admin user';
                    header("Location: index.php");
                    exit;
                }
            }
            
            // Update user
            $result = Auth::updateUser($id, [
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'status' => $status
            ]);
            
            if ($result['success']) {
                $_SESSION['success_message'] = 'User updated successfully';
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
        }
    }
}

// Redirect back to users list
header("Location: index.php");
exit;