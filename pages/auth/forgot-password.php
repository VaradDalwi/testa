<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    $result = forgotPassword($email);
    
    $_SESSION['message'] = $result['message'];
    $_SESSION['message_type'] = $result['success'] ? 'success' : 'danger';
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-body p-4">
                <h3 class="text-center mb-4">Forgot Password</h3>
                <p class="text-center text-muted mb-4">
                    Enter your email address and we'll send you a link to reset your password.
                </p>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Send Reset Link</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="<?php echo BASE_URL; ?>/index.php?page=login">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div> 