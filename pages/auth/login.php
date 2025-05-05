<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = login($email, $password);
    
    if ($result['success']) {
        header('Location: ' . BASE_URL . '/index.php?page=dashboard');
        exit;
    } else {
        // Store the specific error message in session
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = 'danger';
    }
}
?>

<!-- Remove default container -->
</div>

<!-- Custom auth layout -->
<div class="auth-wrapper">
    <div class="auth-container">
        <div class="auth-card">
            <h3 class="text-center mb-4">Login</h3>
            
            <!-- Display error message if exists -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?> mb-4">
                    <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <a href="<?php echo BASE_URL; ?>/index.php?page=forgot-password">Forgot Password?</a>
            </div>
            
            <hr>
            
            <div class="text-center">
                <p>Don't have an account? <a href="<?php echo BASE_URL; ?>/index.php?page=signup">Sign Up</a></p>
            </div>
        </div>
    </div>
</div>

<!-- Prevent footer from loading -->
<?php $hideFooter = true; ?>

<style>
.auth-wrapper {
    min-height: calc(100vh - 76px); /* Subtract navbar height */
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #2000ff 0%, #2a5298 100%);
    padding: 0;
    margin: 0;
}

.auth-container {
    width: 100%;
    max-width: 800px;
    padding: 30px;
}

.auth-card {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}

/* Style for the alert message */
.alert {
    margin-bottom: 1rem;
    padding: 0.75rem 1.25rem;
    border: 1px solid transparent;
    border-radius: 0.25rem;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}
</style> 