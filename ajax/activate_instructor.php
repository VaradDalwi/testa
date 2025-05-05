<?php
// Set header to return JSON
header('Content-Type: application/json');

session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate the request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate required fields
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$user_id = $data['user_id'] ?? null;
$action = $data['action'] ?? null;

if (!$user_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Activate the instructor
    $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ? AND role = 'instructor'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("No instructor found with ID: $user_id");
    }

    // Update the request status (if applicable)
    $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE user_id = ? AND request_type = 'instructor_activation'");
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    $stmt->bind_param("si", $status, $user_id);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Instructor activated successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>