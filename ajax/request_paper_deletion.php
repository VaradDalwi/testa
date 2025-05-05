<?php
// Include config files first
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth file after session is started
require_once __DIR__ . '/../includes/auth.php';

// Set header to return JSON
header('Content-Type: application/json');

// Ensure user is logged in and is an instructor
if (!isLoggedIn() || $_SESSION['role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get paper ID from POST request
$paper_id = isset($_POST['paper_id']) ? (int)$_POST['paper_id'] : 0;

if (!$paper_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid paper ID']);
    exit;
}

// Verify that the paper belongs to the instructor
$instructor_id = $_SESSION['user_id'];
$verify_query = "SELECT id FROM papers WHERE id = ? AND instructor_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $paper_id, $instructor_id);
$stmt->execute();
$verify_result = $stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this paper']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Create deletion request
    $insert_query = "INSERT INTO requests (request_type, user_id, paper_id, status) 
                     VALUES ('paper_deletion', ?, ?, 'pending')";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ii", $instructor_id, $paper_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create deletion request: " . $stmt->error);
    }

    // Update paper status to 'pending' (optional)
    $update_paper = "UPDATE papers SET status = 'pending' WHERE id = ?";
    $stmt = $conn->prepare($update_paper);
    $stmt->bind_param("i", $paper_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Deletion request submitted']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Paper deletion error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to submit deletion request: ' . $e->getMessage()]);
} 