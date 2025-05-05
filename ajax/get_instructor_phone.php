<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

// Check instructor privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$instructor_id = isset($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : 0;

if (!$instructor_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid instructor ID']);
    exit;
}

// Fetch the instructor's phone number
$query = "SELECT phone_number FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['success' => true, 'phone' => $row['phone_number']]);
} else {
    echo json_encode(['success' => false, 'message' => 'No phone number found']);
}
?>
