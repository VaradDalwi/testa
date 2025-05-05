<?php
// delete_paper.php
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

// Get the paper ID from the POST data
$paper_id = isset($_POST['paper_id']) ? (int)$_POST['paper_id'] : 0;

if ($paper_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid paper ID']);
    exit;
}

// Delete the paper
try {
    // First delete associated purchases (if needed)
    $conn->begin_transaction();
    
    // Delete purchases first to maintain referential integrity
    $stmt = $conn->prepare("DELETE FROM purchases WHERE paper_id = ?");
    $stmt->bind_param("i", $paper_id);
    $stmt->execute();
    
    // Then delete the paper
    $stmt = $conn->prepare("DELETE FROM papers WHERE id = ?");
    $stmt->bind_param("i", $paper_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Paper deleted successfully']);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Paper not found or already deleted']);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>
