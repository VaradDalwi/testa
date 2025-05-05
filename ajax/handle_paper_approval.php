<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

// Debug: Log session and POST data
error_log("Session data: " . print_r($_SESSION, true));
error_log("POST data: " . print_r($_POST, true));

// Check admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$paper_id = isset($_POST['paper_id']) ? (int)$_POST['paper_id'] : 0;
$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : null;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$rejection_reason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : '';

// Debug: Log validated inputs
error_log("Validated inputs - paper_id: $paper_id, request_id: $request_id, action: $action");

// Basic validation
if (!$paper_id || !in_array($action, ['approve', 'reject'])) {
    error_log("Validation failed: paper_id=$paper_id, action=$action");
    echo json_encode(['success' => false, 'message' => 'Invalid request. Missing or invalid parameters.']);
    exit;
}

if ($action === 'reject' && empty($rejection_reason)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a rejection reason']);
    exit;
}

$conn->begin_transaction();

try {
    if ($action === 'approve') {
        // Update paper status regardless of request_id
        $update_paper = "UPDATE papers SET status = 'approved' WHERE id = ?";
        $stmt_paper = $conn->prepare($update_paper);
        $stmt_paper->bind_param("i", $paper_id);
        $stmt_paper->execute();
        
        // If we have a request_id, update it too
        if ($request_id) {
            $update_request = "UPDATE requests SET status = 'approved' WHERE id = ?";
            $stmt_request = $conn->prepare($update_request);
            $stmt_request->bind_param("i", $request_id);
            $stmt_request->execute();
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Paper approved successfully']);
        
    } elseif ($action === 'reject') {
        // Handle rejection (existing code)
        // Update paper status
        $update_paper = "UPDATE papers SET status = 'rejected', reject_reason = ? WHERE id = ?";
        $stmt_paper = $conn->prepare($update_paper);
        $stmt_paper->bind_param("si", $rejection_reason, $paper_id);
        $stmt_paper->execute();
        
        // If we have a request_id, update it too
        if ($request_id) {
            $update_request = "UPDATE requests SET status = 'rejected', admin_comment = ? WHERE id = ?";
            $stmt_request = $conn->prepare($update_request);
            $stmt_request->bind_param("si", $rejection_reason, $request_id);
            $stmt_request->execute();
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Paper rejected successfully']);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>