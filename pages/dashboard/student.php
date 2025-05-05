<?php
$student_id = $_SESSION['user_id'];

// Get purchased papers - simplified query without joining to units and courses tables
$query = "SELECT p.*, 
          pa.title, 
          pa.file_path,
          pa.price,
          pa.status as paper_status,
          pa.course as course_name,
          pa.unit as unit_name,
          u.full_name as instructor_name,
          u.email as instructor_email
          FROM purchases p
          JOIN papers pa ON p.paper_id = pa.id
          JOIN users u ON pa.instructor_id = u.id
          WHERE p.student_id = $student_id AND p.status = 'completed'
          ORDER BY p.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">Student Dashboard</h2>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">My Papers</h5>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Course</th>
                                        <th>Unit</th>
                                        <th>Instructor</th>
                                        <th>Purchase Date</th>
                                        <th>Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($purchase = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($purchase['title']); ?></td>
                                            <td><?php echo htmlspecialchars($purchase['course_name']); ?></td>
                                            <td><?php echo htmlspecialchars($purchase['unit_name']); ?></td>
                                            <td><?php echo htmlspecialchars($purchase['instructor_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($purchase['created_at'])); ?></td>
                                            <td>KSH <?php echo number_format($purchase['price'], 2); ?></td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-primary btn-sm" 
                                                        onclick="viewPaperDetails(<?php echo $purchase['paper_id']; ?>)">
                                                    View Paper
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            You haven't purchased any papers yet. <a href="<?php echo BASE_URL; ?>">Browse papers</a> to get started.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const paperDetailsModal = new bootstrap.Modal(document.getElementById('paperDetailsModal'));
    
    window.viewPaperDetails = function(paperId) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>/ajax/get_paper_details.php',
            method: 'POST',
            data: { paper_id: paperId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const paper = response.paper;
                    const pdfViewer = $('#pdfViewer');
                    
                    if (paper.file_path) {
                        // Disable right-click and text selection
                        pdfViewer.attr('oncontextmenu', 'return false;');
                        pdfViewer.css('user-select', 'none');
                        pdfViewer.css('-webkit-user-select', 'none');
                        pdfViewer.css('-moz-user-select', 'none');
                        pdfViewer.css('-ms-user-select', 'none');
                        
                        // Load the PDF with additional restrictions
                        pdfViewer.attr('src', paper.file_path + '#toolbar=0&navpanes=0&scrollbar=0');
                        pdfViewer.show();
                        $('#pdfViewerContainer').find('.alert').remove();
                    } else {
                        pdfViewer.hide();
                        $('#pdfViewerContainer').html('<div class="alert alert-info">No PDF file available</div>');
                    }
                    
                    paperDetailsModal.show();
                } else {
                    alert('Error: ' + (response.message || 'Unknown error occurred'));
                }
            },
            error: function() {
                alert('An error occurred while fetching paper details. Please try again.');
            }
        });
    };
});
</script>

<!-- Paper Details Modal -->
<div class="modal fade" id="paperDetailsModal" tabindex="-1" aria-labelledby="paperDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 90%;">
        <div class="modal-content" style="max-height: 90vh;">
            <div class="modal-header">
                <h5 class="modal-title" id="paperDetailsModalLabel">Paper Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="overflow-y: auto; max-height: calc(90vh - 10px);">
                <div class="pdf-viewer-container" id="pdfViewerContainer" style="height: 100%;">
                    <div class="alert alert-info">Loading PDF...</div>
                    <iframe id="pdfViewer" style="width: 100%; height: 100%; border: none; display: none;" sandbox="allow-scripts allow-same-origin"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.paper-details-form {
    padding: 1rem;
}

.paper-details-form .form-label {
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.paper-details-form .form-control-plaintext {
    padding: 0.375rem 0;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid #dee2e6;
}

.pdf-viewer-container {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    overflow: hidden;
    position: relative;
}

.modal-dialog {
    margin: 1.75rem auto;
}

.modal-content {
    border-radius: 0.5rem;
}

.modal-body {
    padding: 1rem;
}

@media (max-width: 768px) {
    .modal-dialog {
        margin: 0.5rem auto;
    }
    
    .modal-content {
        margin: 0.5rem;
    }
}
</style> 