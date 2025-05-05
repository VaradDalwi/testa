<?php
ob_start(); // Must be the very first line
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and include database if not already done
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';

// Check if instructor is active
$instructor_id = $_SESSION['user_id'] ?? null;
if (!$instructor_id || $_SESSION['role'] !== 'instructor') {
    header('Location: ' . BASE_URL . '/index.php?page=login');
    exit;
}

$query = "SELECT is_active FROM users WHERE id = $instructor_id";
$result = mysqli_query($conn, $query);
$is_active = mysqli_fetch_assoc($result)['is_active'] ?? false;

if (!$is_active) {
    ob_end_clean(); // Discard all buffered output
    header('Location: ' . BASE_URL . '/index.php?page=upload-paper');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/upload-paper-submit.php';
    // The submit script will handle the redirect back to this page
    exit;
}

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}

ob_end_flush(); // Flush the output buffer and turn off output buffering
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">Upload Paper</h2>
            <p class="text-muted">Upload a new examination paper</p>
        </div>
    </div>

    <!-- Display success/error messages -->
    <?php if (isset($_SESSION['upload_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['upload_message_type']; ?> mb-4">
            <?php 
            echo $_SESSION['upload_message']; 
            unset($_SESSION['upload_message']);
            unset($_SESSION['upload_message_type']);
            ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="<?php echo BASE_URL; ?>/index.php?page=upload-paper" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="title" class="form-label">Paper Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="course" class="form-label">Course</label>
                                <input type="text" class="form-control" id="course" name="course" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="unit" class="form-label">Unit</label>
                                <input type="text" class="form-control" id="unit" name="unit" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="file" class="form-label">Paper File (PDF)</label>
                            <input type="file" class="form-control" id="file" name="file" accept=".pdf" required>
                            <div class="form-text">Only PDF files are allowed. Maximum file size: 10MB</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="text" class="form-control" value="10 KSH" readonly>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Upload Paper</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Upload Guidelines</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            Only PDF files are allowed
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            Maximum file size: 10MB
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            Papers will be reviewed by admin
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            Fixed price: KSH 10.00 per paper
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // File size validation
    $('#file').change(function() {
        const file = this.files[0];
        if (file) {
            if (file.size > 10 * 1024 * 1024) { // 10MB
                alert('File size exceeds 10MB limit');
                this.value = '';
            }
            if (file.type !== 'application/pdf') {
                alert('Only PDF files are allowed');
                this.value = '';
            }
        }
    });
});
</script> 