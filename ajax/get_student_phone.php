<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get student's phone number from database
$query = "SELECT phone_number FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'phone' => $user['phone_number']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Phone number not found'
    ]);
}
?>
