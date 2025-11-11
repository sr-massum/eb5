<?php 

/**
 * ExchangeBridge - Admin Panel Login Form
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


// Check if user is already logged in, redirect to dashboard
if (Auth::isLoggedIn()) {
    header("Location: index.php");
    exit;
}

// Initialize variables
$error = '';
$username = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    }
    // Validate username
    elseif (empty($username)) {
        $error = 'Username or email is required';
    }
    // Validate password
    elseif (empty($password)) {
        $error = 'Password is required';
    }
    // Attempt login
    else {
        if (Auth::login($username, $password)) {
            // Redirect to dashboard
            header("Location: index.php");
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 15px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #5D5CDE;
            color: white;
            text-align: center;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }
        .card-body {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: #5D5CDE;
            border-color: #5D5CDE;
            width: 100%;
            padding: 10px;
        }
        .btn-primary:hover {
            background-color: #4c4cbe;
            border-color: #4c4cbe;
        }
        .login-icon {
            font-size: 50px;
            margin-bottom: 10px;
        }
        .alert {
            margin-bottom: 20px;
        }
        .form-control {
            padding: 12px;
            height: auto;
        }
        .input-group-text {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-shield login-icon"></i>
                <h4 class="mb-0">Admin Login</h4>
                <small><?php echo SITE_NAME; ?> Control Panel</small>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <form method="post" action="login.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username or Email" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            </div>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt mr-2"></i> Login
                    </button>
                </form>
                
                <div class="text-center mt-3">
                    <a href="../index.php" class="text-muted">
                        <i class="fas fa-home mr-1"></i> Back to Website
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>