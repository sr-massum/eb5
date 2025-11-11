<?php 

/**
 * ExchangeBridge - Admin Panel User Profile
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


// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Get current user
$db = Database::getInstance();
$user = $db->getRow("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);

if (!$user) {
    header("Location: logout.php");
    exit;
}

// Initialize variables
$successMessage = '';
$errorMessage = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    
    // Validate form data
    if (empty($username)) {
        $errorMessage = 'Username is required';
    } elseif (empty($email) || !isValidEmail($email)) {
        $errorMessage = 'Valid email address is required';
    } else {
        // Update profile
        $result = Auth::updateUser($_SESSION['user_id'], [
            'username' => $username,
            'email' => $email
        ]);
        
        if ($result['success']) {
            $successMessage = 'Profile updated successfully';
            
            // Update session variables
            $_SESSION['user_username'] = $username;
            $_SESSION['user_email'] = $email;
            
            // Update user variable
            $user['username'] = $username;
            $user['email'] = $email;
        } else {
            $errorMessage = $result['message'];
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Validate form data
    if (empty($currentPassword)) {
        $errorMessage = 'Current password is required';
    } elseif (empty($newPassword)) {
        $errorMessage = 'New password is required';
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = 'New password and confirm password do not match';
    } else {
        // Change password
        $result = Auth::changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
        
        if ($result['success']) {
            $successMessage = 'Password changed successfully';
        } else {
            $errorMessage = $result['message'];
        }
    }
}

// Include header
include 'includes/header.php';
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Profile</h1>
    <a href="index.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Dashboard
    </a>
</div>

<?php if (!empty($successMessage)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $successMessage; ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $errorMessage; ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<div class="row">
    <!-- Profile Information -->
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Profile Information</h6>
            </div>
            <div class="card-body">
                <form action="profile.php" method="post">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Last Login</label>
                        <input type="text" class="form-control" value="<?php echo $user['last_login'] ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : 'Never'; ?>" readonly>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Change Password</h6>
            </div>
            <div class="card-body">
                <form action="profile.php" method="post">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key mr-1"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Account Info -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Account Information</h6>
            </div>
            <div class="card-body">
                <p>
                    <strong>Account ID:</strong> <?php echo $user['id']; ?>
                </p>
                <p>
                    <strong>Status:</strong> 
                    <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                        <?php echo ucfirst($user['status']); ?>
                    </span>
                </p>
                <p>
                    <strong>Created At:</strong> <?php echo date('Y-m-d H:i:s', strtotime($user['created_at'])); ?>
                </p>
                <p class="mb-0">
                    <strong>Last Updated:</strong> <?php echo date('Y-m-d H:i:s', strtotime($user['updated_at'] ?: $user['created_at'])); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>