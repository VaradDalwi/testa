<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use __DIR__ for reliable path resolution
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header('Location: ' . BASE_URL . '/index.php?page=login');
    exit;
}

// Set fixed price
$fixed_price = 10.00; // Fixed price of 10 KSH

// Validate form inputs
$title = trim($_POST['title'] ?? '');
$course = trim($_POST['course'] ?? '');
$unit = trim($_POST['unit'] ?? '');

if (empty($title) || empty($course) || empty($unit)) {
    $_SESSION['error'] = "Please fill in all required fields";
    header("Location: " . BASE_URL . "/index.php?page=upload-paper");
    exit;
}

// Handle file upload
$upload_dir = __DIR__ . '/../../uploads/papers/';
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        $_SESSION['error'] = "Failed to create upload directory";
        header("Location: " . BASE_URL . "/index.php?page=upload-paper");
        exit;
    }
}

$file_name = '';
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    
    // Validate file type (only allow PDF)
    $allowed_types = ['application/pdf', 'application/x-pdf'];
    $file_mime = mime_content_type($_FILES['file']['tmp_name']);
    
    if (!in_array($file_mime, $allowed_types)) {
        $_SESSION['error'] = "Only PDF files are allowed";
        header("Location: " . BASE_URL . "/index.php?page=upload-paper");
        exit;
    }

    // Generate unique filename with full path
    $file_name = 'uploads/papers/' . uniqid('paper_') . '.pdf';
    $file_path = __DIR__ . '/../../' . $file_name;

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
        $_SESSION['error'] = "Failed to upload file";
        header("Location: " . BASE_URL . "/index.php?page=upload-paper");
        exit;
    }
} else {
    $_SESSION['error'] = "No file was uploaded or there was an upload error";
    header("Location: " . BASE_URL . "/index.php?page=upload-paper");
    exit;
}

// Insert paper into the `papers` table
$query = "INSERT INTO papers 
          (instructor_id, title, course, unit, file_path, price, status) 
          VALUES (?, ?, ?, ?, ?, ?, 'pending')";
$stmt = $conn->prepare($query);
if (!$stmt) {
    $_SESSION['error'] = "Database error: " . $conn->error;
    header("Location: " . BASE_URL . "/index.php?page=upload-paper");
    exit;
}

$stmt->bind_param("issssd", 
    $_SESSION['user_id'], 
    $title, 
    $course, 
    $unit, 
    $file_name,
    $fixed_price
);

if ($stmt->execute()) {
    $paper_id = $stmt->insert_id; // Get the ID of the inserted paper

    // Insert into the `requests` table (using `user_id` instead of `instructor_id`)
    $request_query = "INSERT INTO requests 
                      (paper_id, user_id, request_type, status) 
                      VALUES (?, ?, 'paper_approval', 'pending')";
    $request_stmt = $conn->prepare($request_query);
    if (!$request_stmt) {
        $_SESSION['error'] = "Database error: " . $conn->error;
        header("Location: " . BASE_URL . "/index.php?page=upload-paper");
        exit;
    }

    $request_stmt->bind_param("ii", $paper_id, $_SESSION['user_id']);
    if ($request_stmt->execute()) {
        $_SESSION['success'] = "Paper uploaded successfully! It will be reviewed by admin.";
    } else {
        $_SESSION['error'] = "Failed to create request: " . $conn->error;
    }
    $request_stmt->close();
} else {
    // Delete the uploaded file if database insert fails
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    $_SESSION['error'] = "Failed to save paper details: " . $conn->error;
}

$stmt->close();
header("Location: " . BASE_URL . "/index.php?page=upload-paper");
exit;
?> 