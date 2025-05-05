<?php
// Get all papers with their details (including request_type)
$query = "SELECT p.*, 
          u.full_name as instructor_name,
          u.email as instructor_email,
          COUNT(DISTINCT pr.id) as downloads,
          r.id as request_id,
          r.request_type
          FROM papers p
          LEFT JOIN users u ON p.instructor_id = u.id
          LEFT JOIN purchases pr ON p.id = pr.paper_id
          LEFT JOIN requests r ON p.id = r.paper_id AND r.request_type = 'paper_approval'
          GROUP BY p.id
          ORDER BY p.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<div class="container px-4 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">Manage Papers</h1>
            <p class="mb-0 text-muted">View and manage all papers in the system</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Paper</th>
                            <th>Instructor</th>
                            <th>Price</th>
                            <th>Downloads</th>
                            <th>Status</th>
                            <th>Request Type</th>
                            <th>Reject Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($paper = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold"><?php echo htmlspecialchars($paper['title']); ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold"><?php echo htmlspecialchars($paper['instructor_name']); ?></span>
                                    <small class="text-muted"><?php echo htmlspecialchars($paper['instructor_email']); ?></small>
                                </div>
                            </td>
                            <td>KSH <?php echo number_format($paper['price'], 2); ?></td>
                            <td><?php echo number_format($paper['downloads'] ?? 0); ?></td>
                            <td>
                                <?php if ($paper['status'] === 'pending'): ?>
                                    <span class="badge bg-warning">Pending Approval</span>
                                <?php elseif ($paper['status'] === 'rejected'): ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $paper['request_type'] ?? 'N/A'; ?></td>
                            <td>
                                <?php if ($paper['status'] === 'rejected'): ?>
                                    <?php echo htmlspecialchars($paper['reject_reason'] ?? 'No reason provided'); ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewPaperDetails(<?php echo $paper['id']; ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php if ($paper['status'] === 'pending'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                onclick="approvePaper(<?php echo $paper['id']; ?>, <?php echo isset($paper['request_id']) ? $paper['request_id'] : 'null'; ?>)">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="rejectPaper(<?php echo $paper['id']; ?>, <?php echo isset($paper['request_id']) ? $paper['request_id'] : 'null'; ?>)">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deletePaper(<?php echo $paper['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const paperDetailsModal = new bootstrap.Modal(document.getElementById('paperDetailsModal'));
    const modalElement = document.getElementById('paperDetailsModal');
    
    let lastFocusedElement;

    window.viewPaperDetails = function(paperId) {
        console.log('Fetching details for paper ID:', paperId);
        
        lastFocusedElement = document.activeElement;
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>/ajax/get_paper_details.php',
            method: 'POST',
            data: { paper_id: paperId },
            dataType: 'json',
            success: function(response) {
                console.log('Server response:', response);
                
                if (response.success) {
                    const paper = response.paper;
                    console.log('Paper details:', paper);

                    $('#paperTitle').text(paper.title || 'No title');
                    $('#paperRejectReason').text(paper.reject_reason || 'No reject reason provided');
                    $('#paperInstructor').text(paper.instructor_name || 'Unknown');
                    $('#paperEmail').text(paper.instructor_email || 'No email');
                    $('#paperPrice').text('KSH ' + (parseFloat(paper.price) || 0).toFixed(2));
                    $('#paperCreated').text(paper.created_at || 'Unknown date');
                    $('#paperStatus').text(
                        paper.status === 'approved' ? 'Active' : 
                        (paper.status === 'rejected' ? 'Rejected' : 'Pending')
                    );
                    $('#paperStatus').removeClass('bg-success bg-warning bg-danger')
                        .addClass(
                            paper.status === 'approved' ? 'bg-success' : 
                            (paper.status === 'rejected' ? 'bg-danger' : 'bg-warning')
                        );

                    const pdfViewer = $('#pdfViewer');
                    if (paper.file_path) {
                        console.log('PDF path:', paper.file_path);
                        pdfViewer.attr('src', paper.file_path);
                        pdfViewer.show();
                        $('#pdfViewerContainer').find('.alert').remove();
                    } else {
                        pdfViewer.hide();
                        if (!$('#pdfViewerContainer').find('.alert').length) {
                            $('#pdfViewerContainer').html('<div class="alert alert-info">No PDF file available</div>');
                        }
                    }

                    // Only show the modal here
                    paperDetailsModal.show();
                } else {
                    console.error('Server error:', response.message);
                    alert('Error: ' + (response.message || 'Unknown error occurred'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert('An error occurred while fetching paper details. Please try again.');
            }
        });
    };

    // When modal is shown, THEN remove aria-hidden and inert
    $('#paperDetailsModal').on('shown.bs.modal', function () {
        modalElement.removeAttribute('inert');
        modalElement.removeAttribute('aria-hidden');
    });

    // When modal is hidden, add inert again
    $('#paperDetailsModal').on('hidden.bs.modal', function () {
        if (modalElement.contains(document.activeElement)) {
            document.activeElement.blur();
        }

        modalElement.setAttribute('inert', '');
        
        if (lastFocusedElement) {
            lastFocusedElement.focus();
        }
    });
});

function approvePaper(paperId, requestId) {
    if (!paperId) {
        alert('Missing paper ID');
        return;
    }
    
    // Prepare data with proper validation
    const data = {
        paper_id: paperId,
        action: 'approve'
    };
    
    // Only add request_id if it exists and is valid
    if (requestId && requestId !== 'null' && !isNaN(requestId)) {
        data.request_id = requestId;
    }

    $.ajax({
        url: '<?php echo BASE_URL; ?>/ajax/handle_paper_approval.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Paper approved successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            alert('AJAX error: ' + error);
            console.error('Error details:', { 
                status: status, 
                error: error, 
                response: xhr.responseText 
            });
        }
    });
}

function rejectPaper(paperId, requestId) {
    $('#paperIdToReject').val(paperId);
    $('#requestIdToReject').val(requestId);
    $('#rejectPaperModal').modal('show');
}

function submitRejection() {
    const paperId = $('#paperIdToReject').val();
    const requestId = $('#requestIdToReject').val();
    const rejectionReason = $('#rejectionReason').val();
    
    if (!rejectionReason.trim()) {
        alert('Please provide a reason for rejection');
        return;
    }
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>/ajax/handle_paper_approval.php',
        method: 'POST',
        data: { 
            paper_id: paperId,
            request_id: requestId,
            action: 'reject',
            rejection_reason: rejectionReason
        },
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr) {
            let errorMsg = 'Configuration Error: ';
            
            if (xhr.responseText.includes('config.php')) {
                errorMsg += 'Server configuration issue detected. ';
                errorMsg += 'Please contact administrator about config.php file paths.';
            } else {
                errorMsg += 'Request failed. Check console for details.';
            }
            
            alert(errorMsg);
            console.error('Full error:', {
                status: xhr.status,
                response: xhr.responseText,
                readyState: xhr.readyState
            });
        }
    });
}

function deletePaper(paperId) {
    if (confirm('Are you sure you want to permanently delete this paper? This action cannot be undone.')) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>/ajax/delete_paper.php',
            method: 'POST',
            data: { paper_id: paperId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while deleting the paper. Please try again.');
                console.error('Delete paper error:', error);
            }
        });
    }
}
</script>

<!-- Paper Details Modal -->
<div class="modal fade" id="paperDetailsModal" tabindex="-1" aria-labelledby="paperDetailsModalLabel">
    <div class="modal-dialog modal-xl" style="max-width: 90%;">
        <div class="modal-content" style="max-height: 90vh;">
            <div class="modal-header">
                <h5 class="modal-title" id="paperDetailsModalLabel">Paper Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="overflow-y: auto; max-height: calc(90vh - 10px);">
                <div class="row">
                    <div class="col-md-6">
                        <form class="paper-details-form">
                            <div class="mb-3">
                                <label class="form-label text-muted">Title</label>
                                <div class="form-control-plaintext" id="paperTitle">Loading...</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Instructor</label>
                                <div class="form-control-plaintext" id="paperInstructor">Loading...</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Email</label>
                                <div class="form-control-plaintext" id="paperEmail">Loading...</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Price</label>
                                <div class="form-control-plaintext" id="paperPrice">Loading...</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Created</label>
                                <div class="form-control-plaintext" id="paperCreated">Loading...</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Status</label>
                                <div class="form-control-plaintext">
                                    <span class="badge" id="paperStatus">Loading...</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Reject Reason</label>
                                <div class="form-control-plaintext" id="paperRejectReason">Loading...</div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <div class="pdf-viewer-container" id="pdfViewerContainer" style="height: 400px;">
                            <div class="alert alert-info">Loading PDF...</div>
                            <iframe id="pdfViewer" style="width: 100%; height: 100%; border: none; display: none;"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Reason Modal -->
<div class="modal fade" id="rejectPaperModal" tabindex="-1" aria-labelledby="rejectPaperModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectPaperModalLabel">Reject Paper</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="rejectPaperForm">
                    <input type="hidden" id="paperIdToReject" name="paper_id">
                    <input type="hidden" id="requestIdToReject" name="request_id">
                    <div class="mb-3">
                        <label for="rejectionReason" class="form-label">Reason for Rejection</label>
                        <textarea class="form-control" id="rejectionReason" name="rejection_reason" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="submitRejection()">Reject Paper</button>
            </div>
        </div>
    </div>
</div>

<style>
.table th {
    font-weight: 600;
    font-size: 0.875rem;
}

.badge {
    padding: 0.5em 0.75em;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
}

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