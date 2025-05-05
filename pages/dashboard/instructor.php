<?php
// Get instructor ID
$instructor_id = $_SESSION['user_id'];

// Get instructor stats
$stats = array(
    'total_papers' => 0,
    'pending_papers' => 0,
    'total_downloads' => 0,
    'total_earnings' => 0,
    'available_balance' => 0
);

// Get total papers
$query = "SELECT COUNT(*) as count FROM papers WHERE instructor_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_papers'] = $result->fetch_assoc()['count'];

// Get pending papers
$query = "SELECT COUNT(*) as count FROM papers WHERE instructor_id = ? AND status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['pending_papers'] = $result->fetch_assoc()['count'];

// Get total downloads and earnings
$query = "SELECT COUNT(*) as downloads, SUM(amount) as earnings 
          FROM purchases p 
          JOIN papers pa ON p.paper_id = pa.id 
          WHERE pa.instructor_id = ? AND p.status = 'completed'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['total_downloads'] = $row['downloads'];
$stats['total_earnings'] = $row['earnings'] ?? 0;

// Get total withdrawn amount
$query = "SELECT COALESCE(SUM(amount), 0) as total_withdrawn FROM withdrawals WHERE instructor_id = ? AND status = 'completed'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();
$total_withdrawn = $result->fetch_assoc()['total_withdrawn'];

// Calculate available balance
$stats['available_balance'] = $stats['total_earnings'] - $total_withdrawn;

// Check if instructor is active
$query = "SELECT is_active,dashboard_access FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$is_active = $row['is_active'];
$dashboard_access = $row['dashboard_access'];

// Display earnings summary
$earnings_query = "SELECT 
    SUM(CASE WHEN status = 'cleared' THEN amount ELSE 0 END) as available,
    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'withdrawn' THEN amount ELSE 0 END) as withdrawn
    FROM instructor_earnings 
    WHERE instructor_id = ?";
$stmt = $conn->prepare($earnings_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$earnings = $stmt->get_result()->fetch_assoc();

// Get papers for the current instructor with request type
$query = "SELECT p.*, r.request_type, 
          COUNT(pr.id) as downloads,
          COALESCE(SUM(pr.amount), 0) as earnings
          FROM papers p
          LEFT JOIN requests r ON p.id = r.paper_id AND r.user_id = ?
          LEFT JOIN purchases pr ON p.id = pr.paper_id AND pr.status = 'completed'
          WHERE p.instructor_id = ?
          GROUP BY p.id
          ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container py-4">

<?php if ($is_active && $dashboard_access == '0'): ?>
    <div class="alert alert-warning d-flex align-items-center justify-content-between p-3 rounded-3 border-0 shadow-sm">
        <div class="me-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Your dashboard access is pending activation. You need to pay <strong>KSH 1000</strong> to activate your dashboard access.
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#dashboardAccessModal">
            Pay Now
        </button>
    </div>
    <div class="modal fade" id="dashboardAccessModal" tabindex="-1" aria-labelledby="dashboardAccessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dashboardAccessModalLabel">Activate Dashboard Access</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please pay KSH 1000 to activate your dashboard access.</p>
                    <form id="dashboardAccessForm">
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="text" class="form-control" value="KSH 1000" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="instructorPhone" name="phone" placeholder="2547XXXXXXXX" required readonly>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitDashboardAccess()">Pay</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Fetch instructor phone when modal is shown
        $('#dashboardAccessModal').on('show.bs.modal', function() {
            $.ajax({
                url: '<?php echo BASE_URL; ?>/ajax/get_instructor_phone.php',
                method: 'POST',
                data: { 
                    instructor_id: <?php echo $_SESSION['user_id']; ?> 
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.phone) {
                        $('#instructorPhone').val(response.phone);
                    } else {
                        console.error('Error fetching phone:', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        });

        function submitDashboardAccess() {
            const form = document.getElementById('dashboardAccessForm');
            const formData = new FormData(form);
            const phone = formData.get('phone');
            
            // Validate phone number
            if (!phone || !/^2547\d{8}$/.test(phone)) {
                alert('Please enter a valid phone number starting with 2547 followed by 8 digits');
                return;
            }
            
            // Send AJAX request to process dashboard access payment
            $.ajax({
                url: '<?php echo BASE_URL; ?>/ajax/activate_dashboard_access.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $('#dashboardAccessModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while processing your dashboard access payment. Please try again.');
                }
            });
        }
    </script>
<?php else: ?>

    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="mb-0">Instructor Dashboard</h2>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="dropdown">
                <button class="btn btn-primary" type="button" id="actionMenu" data-bs-toggle="dropdown">
                    <i class="bi bi-gear"></i> Actions
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/index.php?page=upload-paper">
                        <i class="bi bi-upload"></i> Upload New Paper
                    </a></li>
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                        <i class="bi bi-cash"></i> Withdraw Money
                    </a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Total Downloads Card -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-download text-primary fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Downloads</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['total_downloads']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Earnings Card -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-currency-dollar text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Earnings</h6>
                            <h3 class="mb-0">KSH <?php echo number_format($stats['total_earnings'], 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Balance Card -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-wallet2 text-info fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Available Balance</h6>
                            <h3 class="mb-0">KSH <?php echo number_format($stats['available_balance'], 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Papers Card -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-file-earmark-text text-warning fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Papers</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['total_papers']); ?></h3>
                            <?php if ($stats['pending_papers'] > 0): ?>
                                <small class="text-warning">
                                    <i class="bi bi-clock"></i> <?php echo $stats['pending_papers']; ?> pending
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Papers Section -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Total Papers</h5>
        </div>
        <div class="card-body">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Paper</th>
                                <th>Downloads</th>
                                <th>Earnings</th>
                                <th>Status</th>
                                <th>Request Type</th>
                                <th>Admin Comment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($paper = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold"><?php echo htmlspecialchars($paper['title']); ?></span>
                                            <small class="text-muted"><?php echo substr(htmlspecialchars($paper['summary'] ?? ''), 0, 50) . '...'; ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($paper['downloads'] ?? 0); ?></td>
                                    <td>KSH <?php echo number_format($paper['earnings'] ?? 0, 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $paper['status'] === 'approved' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($paper['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $paper['request_type'] ?? 'N/A'; ?></td>
                                    <td>
                                        <?php if ($paper['status'] === 'rejected' && !empty($paper['reject_reason'])): ?>
                                            <span class="text-danger"><?php echo htmlspecialchars($paper['reject_reason']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewPaperDetails(<?php echo $paper['id']; ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    onclick="deletePaper(<?php echo $paper['id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-file-earmark-text text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">You haven't uploaded any papers yet.</p>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=upload-paper" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Upload Your First Paper
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Withdraw Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1" aria-labelledby="withdrawModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="withdrawModalLabel">Withdraw Earnings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="withdrawForm">
                    <div class="mb-3">
                        <label class="form-label">Available Balance</label>
                        <div class="form-control-plaintext" id="availableBalance">Loading...</div>
                    </div>
                    <div class="mb-3">
                        <label for="withdrawAmount" class="form-label">Amount to Withdraw (KSH)</label>
                        <input type="number" class="form-control" id="withdrawAmount" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <label for="phoneNumber" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phoneNumber" name="phone" required readonly>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitWithdraw">Submit</button>
            </div>
        </div>
    </div>
</div>

<script>
function deletePaper(paperId) {
    if (confirm('Are you sure you want to request deletion of this paper? This request will be sent to the admin for approval.')) {
        const button = event.target.closest('button');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

        // Send AJAX request to create deletion request
        fetch('<?php echo BASE_URL; ?>/ajax/request_paper_deletion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'paper_id=' + encodeURIComponent(paperId)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Show success message with Bootstrap toast
                const toast = new bootstrap.Toast(document.createElement('div'));
                toast.show();
                
                // Reload the page after a short delay
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                throw new Error(data.message || 'Failed to create deletion request');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Show error message with Bootstrap toast
            const toast = new bootstrap.Toast(document.createElement('div'));
            toast.show();
            
            // Reset button state
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-trash"></i>';
        });
    }
}

function submitWithdrawal() {
    const form = document.getElementById('withdrawForm');
    const formData = new FormData(form);
    const submitButton = document.getElementById('submitWithdraw');
    
    // Validate form
    const amount = formData.get('amount');
    const phone = formData.get('phone');
    
    if (!amount || amount < 100 || amount > <?php echo $stats['available_balance']; ?>) {
        alert('Please enter a valid amount');
        return;
    }
    
    if (!phone || !/^2547\d{8}$/.test(phone)) {
        alert('Please enter a valid phone number starting with 2547 followed by 8 digits');
        return;
    }
    
    // Send AJAX request to process withdrawal
    $.ajax({
        url: '<?php echo BASE_URL; ?>/ajax/withdraw.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#withdrawModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('An error occurred while processing your withdrawal. Please try again.');
        }
    });
}

// Add event listener for modal close to reset form
$('#withdrawModal').on('hidden.bs.modal', function () {
    const form = document.getElementById('withdrawForm');
    form.reset();
    const submitButton = document.getElementById('submitWithdraw');
    submitButton.disabled = false;
    submitButton.innerHTML = 'Withdraw';
});

$(document).ready(function() {
    // Initialize the modal
    const paperDetailsModal = new bootstrap.Modal(document.getElementById('paperDetailsModal'));
    
    window.viewPaperDetails = function(paperId) {
        console.log('Fetching details for paper ID:', paperId);
        
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
                    
                    // Update modal content
                    $('#paperTitle').text(paper.title || 'No title');
                    $('#paperSummary').text(paper.summary || 'No summary available');
                    $('#paperInstructor').text(paper.instructor_name || 'Unknown');
                    $('#paperEmail').text(paper.instructor_email || 'No email');
                    $('#paperPrice').text('KSH ' + (parseFloat(paper.price) || 0).toFixed(2));
                    $('#paperCreated').text(paper.created_at || 'Unknown date');
                    $('#paperStatus').text(paper.status === 'approved' ? 'Active' : 'Pending');
                    $('#paperStatus').removeClass('bg-success bg-warning').addClass(paper.status === 'approved' ? 'bg-success' : 'bg-warning');
                    
                    // Update PDF viewer
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
                    
                    // Show modal
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

    // Fetch available balance and phone number when the withdrawal modal is shown
    $('#withdrawModal').on('show.bs.modal', function() {
        $.ajax({
            url: '<?php echo BASE_URL; ?>/ajax/get_instructor_balance.php',
            method: 'POST',
            data: { instructor_id: <?php echo $_SESSION['user_id']; ?> },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#availableBalance').text('KSH ' + response.balance.toFixed(2));
                } else {
                    $('#availableBalance').text('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                $('#availableBalance').text('Error fetching balance');
                console.error('Error:', error);
            }
        });

        // Fetch instructor's phone number
        $.ajax({
            url: '<?php echo BASE_URL; ?>/ajax/get_instructor_phone.php',
            method: 'POST',
            data: { instructor_id: <?php echo $_SESSION['user_id']; ?> },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#phoneNumber').val(response.phone);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error fetching phone number');
                console.error('Error:', error);
            }
        });

        // Attach the click event listener to the submit button here
        const submitWithdrawButton = $('#submitWithdraw');
        if (submitWithdrawButton.length) {
            submitWithdrawButton.off('click').on('click', function() {
                const amount = parseFloat($('#withdrawAmount').val());
                const phone = $('#phoneNumber').val();

                if (!amount || amount <= 0) {
                    alert('Please enter a valid amount');
                    return;
                }

                if (!phone || !/^2547\d{8}$/.test(phone)) {
                    alert('Please enter a valid phone number starting with 2547 followed by 8 digits');
                    return;
                }

                $.ajax({
                    url: '<?php echo BASE_URL; ?>/ajax/handle_withdrawal.php',
                    method: 'POST',
                    data: {
                        instructor_id: <?php echo $_SESSION['user_id']; ?>,
                        amount: amount,
                        phone: phone
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Withdrawal request submitted successfully!');
                            $('#withdrawModal').modal('hide');
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred. Please try again.');
                        console.error('Error:', error);
                    }
                });
            });
        } else {
            console.error('Button with ID "submitWithdraw" not found.');
        }
    });
});
</script>

<!-- Paper Details Modal -->
<div class="modal fade" id="paperDetailsModal" tabindex="-1" aria-labelledby="paperDetailsModalLabel" aria-hidden="true">
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
                                <label class="form-label text-muted">Summary</label>
                                <div class="form-control-plaintext" id="paperSummary">Loading...</div>
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

.table td:nth-child(5) {  /* Target the Admin Comment column */
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style> 