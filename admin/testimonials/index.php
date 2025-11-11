<?php 

/**
 * ExchangeBridge - Admin Panel Testimonial
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

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['testimonial_id'])) {
        $testimonialId = intval($_POST['testimonial_id']);
        $action = $_POST['action'];
        
        $db = Database::getInstance();
        
        if ($action === 'approve') {
            $result = $db->update('testimonials', ['status' => 'active'], 'id = ?', [$testimonialId]);
            if ($result) {
                $_SESSION['success_message'] = 'Testimonial approved successfully';
            } else {
                $_SESSION['error_message'] = 'Failed to approve testimonial';
            }
        } elseif ($action === 'reject') {
            $result = $db->delete('testimonials', 'id = ?', [$testimonialId]);
            if ($result) {
                $_SESSION['success_message'] = 'Testimonial rejected and deleted';
            } else {
                $_SESSION['error_message'] = 'Failed to reject testimonial';
            }
        }
    }
}

// Get all testimonials
$db = Database::getInstance();
$testimonials = $db->getRows(
    "SELECT t.*, 
     fc.name as from_currency_name, fc.display_name as from_display_name, 
     tc.name as to_currency_name, tc.display_name as to_display_name
     FROM testimonials t
     LEFT JOIN currencies fc ON t.from_currency = fc.code
     LEFT JOIN currencies tc ON t.to_currency = tc.code
     ORDER BY 
     CASE 
         WHEN t.status = 'pending' THEN 1 
         WHEN t.status = 'active' THEN 2 
         ELSE 3 
     END, t.created_at DESC"
);

// Get counts for different statuses
$pendingCount = $db->getValue("SELECT COUNT(*) FROM testimonials WHERE status = 'pending'");
$activeCount = $db->getValue("SELECT COUNT(*) FROM testimonials WHERE status = 'active'");
$totalCount = count($testimonials);

// Get all currencies for the form
$currencies = getAllCurrencies();

// Check for success message
$successMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Check for error message
$errorMessage = '';
if (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Include header
include '../includes/header.php';
?>

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item active">Testimonials</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-comments mr-1"></i> Testimonials Management
        <div class="float-right">
            <?php if ($pendingCount > 0): ?>
            <span class="badge badge-warning mr-2">
                <i class="fas fa-clock"></i> <?php echo $pendingCount; ?> Pending
            </span>
            <?php endif; ?>
            <span class="badge badge-success mr-2">
                <i class="fas fa-check"></i> <?php echo $activeCount; ?> Active
            </span>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addTestimonialModal">
                <i class="fas fa-plus"></i> Add New Testimonial
            </button>
        </div>
    </div>
    <div class="card-body">
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
        
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Rating</th>
                        <th>Exchange</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($testimonials) > 0): ?>
                    <?php foreach ($testimonials as $testimonial): ?>
                    <tr class="<?php echo $testimonial['status'] === 'pending' ? 'table-warning' : ''; ?>">
                        <td><?php echo htmlspecialchars($testimonial['name']); ?></td>
                        <td><?php echo htmlspecialchars($testimonial['email']); ?></td>
                        <td>
                            <?php for ($i = 0; $i < $testimonial['rating']; $i++): ?>
                            <i class="fas fa-star text-warning"></i>
                            <?php endfor; ?>
                            <?php for ($i = $testimonial['rating']; $i < 5; $i++): ?>
                            <i class="far fa-star text-warning"></i>
                            <?php endfor; ?>
                        </td>
                        <td>
                            <?php if ($testimonial['from_currency'] && $testimonial['to_currency']): ?>
                            <?php echo htmlspecialchars($testimonial['from_currency_name']); ?> → 
                            <?php echo htmlspecialchars($testimonial['to_currency_name']); ?>
                            <?php else: ?>
                            <span class="text-muted">Not specified</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars(substr($testimonial['message'], 0, 50) . (strlen($testimonial['message']) > 50 ? '...' : '')); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($testimonial['created_at'])); ?></td>
                        <td>
                            <?php if ($testimonial['status'] === 'pending'): ?>
                            <span class="badge badge-warning">
                                <i class="fas fa-clock"></i> Pending
                            </span>
                            <?php elseif ($testimonial['status'] === 'active'): ?>
                            <span class="badge badge-success">
                                <i class="fas fa-check"></i> Active
                            </span>
                            <?php else: ?>
                            <span class="badge badge-danger">
                                <i class="fas fa-times"></i> Inactive
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($testimonial['status'] === 'pending'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-success btn-sm" title="Approve">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-danger btn-sm" title="Reject & Delete" 
                                        onclick="return confirm('Are you sure you want to reject and delete this testimonial?')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-primary btn-sm edit-testimonial" 
                                data-id="<?php echo $testimonial['id']; ?>"
                                data-name="<?php echo htmlspecialchars($testimonial['name']); ?>"
                                data-email="<?php echo htmlspecialchars($testimonial['email']); ?>"
                                data-rating="<?php echo $testimonial['rating']; ?>"
                                data-message="<?php echo htmlspecialchars($testimonial['message']); ?>"
                                data-from="<?php echo $testimonial['from_currency']; ?>"
                                data-to="<?php echo $testimonial['to_currency']; ?>"
                                data-status="<?php echo $testimonial['status']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="delete.php?id=<?php echo $testimonial['id']; ?>" class="btn btn-danger btn-sm delete-confirm">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No testimonials found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Testimonial Modal -->
<div class="modal fade" id="addTestimonialModal" tabindex="-1" role="dialog" aria-labelledby="addTestimonialModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTestimonialModalLabel">Add New Testimonial</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="add.php" method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name" class="required">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="rating" class="required">Rating</label>
                        <select class="form-control" id="rating" name="rating" required>
                            <option value="5">5 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="2">2 Stars</option>
                            <option value="1">1 Star</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="from_currency">From Currency</label>
                        <select class="form-control" id="from_currency" name="from_currency">
                            <option value="">Select Currency</option>
                            <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo $currency['code']; ?>"><?php echo htmlspecialchars($currency['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="to_currency">To Currency</label>
                        <select class="form-control" id="to_currency" name="to_currency">
                            <option value="">Select Currency</option>
                            <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo $currency['code']; ?>"><?php echo htmlspecialchars($currency['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message" class="required">Testimonial Message</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Testimonial</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Testimonial Modal -->
<div class="modal fade" id="editTestimonialModal" tabindex="-1" role="dialog" aria-labelledby="editTestimonialModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTestimonialModalLabel">Edit Testimonial</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="update.php" method="post">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name" class="required">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_rating" class="required">Rating</label>
                        <select class="form-control" id="edit_rating" name="rating" required>
                            <option value="5">5 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="2">2 Stars</option>
                            <option value="1">1 Star</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_from_currency">From Currency</label>
                        <select class="form-control" id="edit_from_currency" name="from_currency">
                            <option value="">Select Currency</option>
                            <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo $currency['code']; ?>"><?php echo htmlspecialchars($currency['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_to_currency">To Currency</label>
                        <select class="form-control" id="edit_to_currency" name="to_currency">
                            <option value="">Select Currency</option>
                            <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo $currency['code']; ?>"><?php echo htmlspecialchars($currency['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_message" class="required">Testimonial Message</label>
                        <textarea class="form-control" id="edit_message" name="message" rows="5" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select class="form-control" id="edit_status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Testimonial</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize edit modal
    $('.edit-testimonial').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const email = $(this).data('email');
        const rating = $(this).data('rating');
        const message = $(this).data('message');
        const fromCurrency = $(this).data('from');
        const toCurrency = $(this).data('to');
        const status = $(this).data('status');
        
        $('#edit_id').val(id);
        $('#edit_name').val(name);
        $('#edit_email').val(email);
        $('#edit_rating').val(rating);
        $('#edit_message').val(message);
        $('#edit_from_currency').val(fromCurrency);
        $('#edit_to_currency').val(toCurrency);
        $('#edit_status').val(status);
        
        $('#editTestimonialModal').modal('show');
    });
    
    // Delete confirmation
    $('.delete-confirm').click(function(e) {
        if (!confirm('Are you sure you want to delete this testimonial?')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>