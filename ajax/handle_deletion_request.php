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

// Get request parameters
$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : ''; // 'approve' or 'reject'
$admin_comment = isset($_POST['admin_comment']) ? trim($_POST['admin_comment']) : '';

// Log the received parameters
error_log("Deletion request parameters - ID: $request_id, Action: $action, Reason: $admin_comment");

if (!$request_id || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if ($action === 'reject' && empty($admin_comment)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a reason for rejection']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get request details
    $request_query = "SELECT * FROM requests WHERE id = ? AND status = 'pending'";
    $stmt = $conn->prepare($request_query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();

    if (!$request) {
        throw new Exception("Request not found or already processed");
    }

    // Update request status
    $update_request = "UPDATE requests SET status = ?, admin_comment = ? WHERE id = ?";
    $stmt = $conn->prepare($update_request);
    $new_status = $action === 'approve' ? 'approved' : 'rejected';
    $stmt->bind_param("ssi", $new_status, $admin_comment, $request_id);
    $stmt->execute();

    // Handle different request types
    switch ($request['request_type']) {
        case 'paper_deletion':
            if ($action === 'approve') {
                $delete_paper = "DELETE FROM papers WHERE id = ?";
                $stmt = $conn->prepare($delete_paper);
                $stmt->bind_param("i", $request['paper_id']);
                $stmt->execute();
            } else {
                $update_paper = "UPDATE papers SET status = 'approved' WHERE id = ?";
                $stmt = $conn->prepare($update_paper);
                $stmt->bind_param("i", $request['paper_id']);
                $stmt->execute();
            }
            break;

        case 'instructor_activation':
            $update_user = "UPDATE users SET is_active = ? WHERE id = ?";
            $stmt = $conn->prepare($update_user);
            $is_active = $action === 'approve' ? 1 : 0;
            $stmt->bind_param("ii", $is_active, $request['user_id']);
            $stmt->execute();
            break;

        case 'dashboard_access':
            // Handle dashboard access request
            break;

        case 'paper_approval':
            // Handle paper approval request
            break;
    }

    // Commit transaction
    $conn->commit();
    error_log("Transaction completed successfully");
    echo json_encode(['success' => true, 'message' => "Request {$action}d successfully"]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Error handling deletion request: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 