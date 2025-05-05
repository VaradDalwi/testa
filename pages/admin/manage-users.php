<?php
// Get all users with their details
$query = "SELECT u.*, 
          COUNT(DISTINCT p.id) as total_papers,
          COUNT(DISTINCT pr.id) as total_purchases,
          COALESCE(SUM(pr.amount), 0) as total_spent
          FROM users u
          LEFT JOIN papers p ON u.id = p.instructor_id
          LEFT JOIN purchases pr ON u.id = pr.student_id
          GROUP BY u.id
          ORDER BY u.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<div class="container px-4 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">Manage Users</h1>
            <p class="mb-0 text-muted">View and manage all users in the system</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Papers</th>
                            <th>Purchases</th>
                            <th>Total Spent</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></span>
                                    <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $user['role'] === 'instructor' ? 'primary' : 'info'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($user['total_papers']); ?></td>
                            <td><?php echo number_format($user['total_purchases']); ?></td>
                            <td>KSH <?php echo number_format($user['total_spent'], 2); ?></td>
                            <td><?php echo format_date($user['created_at']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <?php if ($user['role'] === 'instructor' && !$user['is_active']): ?>
                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                onclick="activateUser(<?php echo $user['id']; ?>)">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($user['role'] !== 'admin'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteUser(<?php echo $user['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
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
function viewUserDetails(userId) {
    // Implementation for viewing user details
    alert('View user details functionality will be implemented in the next update');
}

function activateUser(userId) {
    if (confirm('Are you sure you want to activate this instructor?')) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>/ajax/activate_instructor.php',
            method: 'POST',
            data: { 
                user_id: userId,
                action: 'approve'
            },
            dataType: 'json',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Unknown error occurred'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert('Failed to activate instructor. Check console for details.');
            }
        });
    }
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>/ajax/delete_user.php',
            method: 'POST',
            data: { user_id: userId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                let errorMsg = 'Request failed: ';
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMsg += response.message || error;
                    } catch (e) {
                        errorMsg += xhr.responseText || error;
                    }
                } else {
                    errorMsg += error;
                }
                alert(errorMsg);
            }
        });
    }
}

$(document).ready(function() {
    // Handle approval
    $('.approve-btn').click(function() {
        const requestId = $(this).data('request-id');
        if (confirm('Are you sure you want to approve this instructor?')) {
            $.ajax({
                url: '<?= BASE_URL ?>/ajax/activate_instructor.php',
                method: 'POST',
                data: { 
                    request_id: requestId,
                    action: 'approve'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                }
            });
        }
    });

    // Handle rejection
    $('.reject-btn').click(function() {
        const requestId = $(this).data('request-id');
        const reason = prompt('Please enter reason for rejection:');
        if (reason !== null) {
            $.ajax({
                url: '<?= BASE_URL ?>/ajax/activate_instructor.php',
                method: 'POST',
                data: { 
                    request_id: requestId,
                    action: 'reject',
                    admin_comment: reason
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                }
            });
        }
    });
});
</script>

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
</style> 