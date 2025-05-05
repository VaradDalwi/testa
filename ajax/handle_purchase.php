<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get POST data
$paper_id = isset($_POST['paper_id']) ? (int)$_POST['paper_id'] : 0;
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

// Validate inputs
if (!$paper_id || !$phone || !preg_match('/^2547\d{8}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // 1. Get paper details
    $paper_query = "SELECT p.*, u.id as instructor_id 
                   FROM papers p 
                   JOIN users u ON p.instructor_id = u.id 
                   WHERE p.id = ? AND p.status = 'approved'";
    $stmt = $conn->prepare($paper_query);
    $stmt->bind_param("i", $paper_id);
    $stmt->execute();
    $paper = $stmt->get_result()->fetch_assoc();

    if (!$paper) {
        throw new Exception("Paper not found or not approved");
    }

    // 2. Check if student already purchased this paper
    $check_query = "SELECT id FROM purchases 
                   WHERE student_id = ? AND paper_id = ? AND status = 'completed'";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $_SESSION['user_id'], $paper_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("You have already purchased this paper");
    }

    // 3. Create purchase record
    $insert_query = "INSERT INTO purchases 
                    (student_id, paper_id, amount, phone, status) 
                    VALUES (?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iids", $_SESSION['user_id'], $paper_id, $paper['price'], $phone);
    $stmt->execute();
    $purchase_id = $conn->insert_id;

    // 4. Process payment (simulated - integrate with your payment gateway)
    // In a real implementation, this would call M-Pesa API or other payment processor
    $payment_success = true; // Simulate successful payment
    
    if (!$payment_success) {
        throw new Exception("Payment processing failed");
    }

    // 5. Update purchase status to completed
    $update_query = "UPDATE purchases SET status = 'completed' WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $purchase_id);
    $stmt->execute();

    // 6. Record instructor earnings (70% of paper price)
    $instructor_share = $paper['price'] * 0.7;
    $earnings_query = "INSERT INTO instructor_earnings 
                      (instructor_id, paper_id, amount) 
                      VALUES (?, ?, ?)";
    $stmt = $conn->prepare($earnings_query);
    $stmt->bind_param("iid", $paper['instructor_id'], $paper_id, $instructor_share);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Purchase completed successfully',
        'paper_id' => $paper_id,
        'download_url' => BASE_URL . '/download.php?id=' . $paper_id
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
