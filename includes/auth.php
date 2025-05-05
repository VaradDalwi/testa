<?php
require_once __DIR__ . '/../config/database.php';

function login($email, $password) {
    global $conn;
    
    $email = mysqli_real_escape_string($conn, $email);
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            if (!$user['is_active']) {
                if ($user['role'] === 'instructor') {
                    return [
                        'success' => false, 
                        'message' => 'Your instructor account is not yet active. Please contact the administrator.'
                    ];
                }
                return [
                    'success' => false, 
                    'message' => 'Your account is not active. Please contact support.'
                ];
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            return ['success' => true];
        }
    }
    
    return ['success' => false, 'message' => 'Invalid email or password'];
}

function signup($email, $password, $full_name, $phone_number, $role) {
    global $conn;
    
    $conn->begin_transaction();
    
    try {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Email already exists");
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Set is_active based on role
        $is_active = ($role === 'instructor') ? 0 : 1;
        
        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, phone_number, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $email, $hashed_password, $full_name, $phone_number, $role, $is_active);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create user: " . $stmt->error);
        }
        
        $user_id = $conn->insert_id;
        
        // If instructor, create activation request
        if ($role === 'instructor') {
            $stmt = $conn->prepare("INSERT INTO requests (request_type, user_id, status) VALUES ('instructor_activation', ?, 'pending')");
            $stmt->bind_param("i", $user_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create activation request: " . $stmt->error);
            }
        }
        
        $conn->commit();
        return ['success' => true, 'message' => 'Registration successful'];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function forgotPassword($email) {
    global $conn;
    
    $email = mysqli_real_escape_string($conn, $email);
    $query = "SELECT id FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $query = "UPDATE users SET reset_token = '$token', reset_expires = '$expires' WHERE id = " . $user['id'];
        if (mysqli_query($conn, $query)) {
            // Send email with reset link
            $reset_link = BASE_URL . "/index.php?page=reset-password&token=$token";
            // TODO: Implement email sending
            return ['success' => true, 'message' => 'Password reset link has been sent to your email'];
        }
    }
    
    return ['success' => false, 'message' => 'Email not found'];
}

function resetPassword($token, $new_password) {
    global $conn;
    
    $token = mysqli_real_escape_string($conn, $token);
    $query = "SELECT id FROM users WHERE reset_token = '$token' AND reset_expires > NOW()";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $query = "UPDATE users SET password = '$hashed_password', reset_token = NULL, reset_expires = NULL 
                 WHERE id = " . $user['id'];
        
        if (mysqli_query($conn, $query)) {
            return ['success' => true, 'message' => 'Password has been reset successfully'];
        }
    }
    
    return ['success' => false, 'message' => 'Invalid or expired reset token'];
}

function logout() {
    session_destroy();
    header('Location: ' . BASE_URL);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}
?> 