<?php 

/**
 * ExchangeBridge - Admin Panel User Add
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
    $_SESSION['error_message'] = 'You do not have permission to add users';
    header("Location: index.php");
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role = isset($_POST['role']) ? sanitizeInput($_POST['role']) : 'editor';
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active';
    
    // Validate form data
    if (empty($username)) {
        $_SESSION['error_message'] = 'Username is required';
    } elseif (empty($email) || !isValidEmail($email)) {
        $_SESSION['error_message'] = 'Valid email address is required';
    } elseif (empty($password)) {
        $_SESSION['error_message'] = 'Password is required';
    } elseif (!in_array($role, ['admin', 'manager', 'editor'])) {
        $_SESSION['error_message'] = 'Invalid role selected';
    } elseif (!in_array($status, ['active', 'inactive'])) {
        $_SESSION['error_message'] = 'Invalid status selected';
    } else {
        // Create user
        $result = Auth::createUser($username, $email, $password, $role);
        
        if ($result['success']) {
            $_SESSION['success_message'] = 'User created successfully';
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
    }
}

// Redirect back to users list
header("Location: index.php");
exit;