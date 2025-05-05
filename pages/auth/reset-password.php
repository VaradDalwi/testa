<?php
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $_SESSION['message'] = 'Invalid reset link';
    $_SESSION['message_type'] = 'danger';
    header('Location: ' . BASE_URL . '/index.php?page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($new_password !== $confirm_password) {
        $_SESSION['message'] = 'Passwords do not match';
        $_SESSION['message_type'] = 'danger';
    } else {
        $result = resetPassword($token, $new_password);
        
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = $result['success'] ? 'success' : 'danger';
        
        if ($result['success']) {
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }
    }
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-body p-4">
                <h3 class="text-center mb-4">Reset Password</h3>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="<?php echo BASE_URL; ?>/index.php?page=login">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div> 