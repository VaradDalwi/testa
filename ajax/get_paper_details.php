<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to view paper details']);
    exit;
}

// Get paper ID from POST request
$paper_id = isset($_POST['paper_id']) ? (int)$_POST['paper_id'] : 0;

if (!$paper_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid paper ID']);
    exit;
}

// Get paper details with proper access control
$query = "SELECT p.*, 
          u.full_name as instructor_name,
          u.email as instructor_email
          FROM papers p
          LEFT JOIN users u ON p.instructor_id = u.id";

if ($_SESSION['role'] === 'admin') {
    // Admin can view any paper
    $query .= " WHERE p.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $paper_id);
} elseif ($_SESSION['role'] === 'instructor') {
    // Instructor can view their own papers
    $query .= " WHERE p.id = ? AND p.instructor_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $paper_id, $_SESSION['user_id']);
} elseif ($_SESSION['role'] === 'student') {
    // Student can view purchased papers
    $query .= " JOIN purchases pu ON p.id = pu.paper_id 
                WHERE p.id = ? AND pu.student_id = ? AND pu.status = 'completed'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $paper_id, $_SESSION['user_id']);
} else {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Paper not found or unauthorized access']);
    exit;
}

$paper = $result->fetch_assoc();

// Format the file path
if (!empty($paper['file_path'])) {
    if ($_SESSION['role'] === 'student') {
        // Generate a temporary access token
        $token = bin2hex(random_bytes(16));
        $_SESSION['pdf_tokens'][$paper_id] = $token;
        $paper['file_path'] = '../protected_pdf.php?file=' . urlencode($paper['file_path']) . '&token=' . $token;
    } else {
        // Add /testa/ to the path
        $paper['file_path'] = '../testa/' . ltrim($paper['file_path'], '/');
    }
}

// Format the created date
$paper['created_at'] = date('M d, Y H:i', strtotime($paper['created_at']));

echo json_encode(['success' => true, 'paper' => $paper]);
?> 