<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $role = $_POST['role'] ?? '';
    
    if ($password !== $confirm_password) {
        $_SESSION['message'] = 'Passwords do not match';
        $_SESSION['message_type'] = 'danger';
    } else {
        $result = signup($email, $password, $full_name, $phone_number, $role);
        
        if ($result['success']) {
            if ($role === 'instructor') {
                $_SESSION['message'] = 'Registration successful! Your account is pending admin approval.';
            } else {
                $_SESSION['message'] = 'Registration successful! Please login.';
            }
            $_SESSION['message_type'] = 'success';
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        } else {
            $_SESSION['message'] = $result['message'];
            $_SESSION['message_type'] = 'danger';
        }
    }
}
?>

<!-- Custom auth layout -->
<div class="auth-wrapper">
    <div class="auth-container">
        <div class="auth-card">
            <h2 class="text-center mb-4">Create Account</h2>
            
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone_number" name="phone_number" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="role" class="form-label">I want to</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Select role</option>
                            <option value="student">Student</option>
                            <option value="instructor">Instructor</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="terms" required>
                    <label class="form-check-label" for="terms">
                        I agree to the <a href="#">Terms and Conditions</a>
                    </label>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <p>Already have an account? <a href="<?php echo BASE_URL; ?>/index.php?page=login">Login</a></p>
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

.auth-card h3 {
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
}

.auth-card .form-label {
    font-size: 0.9rem;
    margin-bottom: 0.3rem;
}

.auth-card .mb-3 {
    margin-bottom: 0.8rem !important;
}

.auth-card .form-control,
.auth-card .form-select {
    padding: 0.4rem 0.75rem;
    font-size: 0.9rem;
}

.auth-card .btn-primary {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.auth-card .text-center {
    margin-top: 1rem;
}

body {
    overflow: hidden;
}
</style> 