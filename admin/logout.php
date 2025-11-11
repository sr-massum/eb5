<?php 

/**
 * ExchangeBridge - Admin Panel LogOut
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
require_once '../config/config.php';
require_once '../config/verification.php';
require_once '../config/license.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/app.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';


// Logout the user
Auth::logout();

// Redirect to login page
header("Location: login.php");
exit;