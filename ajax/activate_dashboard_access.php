<?php
// activate_dashboard_access.php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone number is required']);
    exit;
}

$conn->begin_transaction();

try {
    // Create dashboard access request
    $insert_query = "INSERT INTO requests (request_type, user_id, status) 
                     VALUES ('dashboard_access', ?, 'pending')";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("i", $instructor_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create access request: " . $stmt->error);
    }

    // Process payment (simplified example)
    // In a real app, integrate with payment gateway here
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Dashboard access request submitted']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>