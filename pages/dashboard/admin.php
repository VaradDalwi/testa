<?php
// Initialize statistics array
$stats = [
    'total_downloads' => 0,
    'total_earnings' => 0,
    'total_papers' => 0,
    'pending_papers' => 0,
    'total_instructors' => 0,
    'total_students' => 0,
    'pending_withdrawals' => 0,
    'pending_instructors' => 0
];

// Get total downloads and earnings
$query = "SELECT COUNT(*) as total_downloads, SUM(amount) as total_earnings 
          FROM purchases WHERE status = 'completed'";
$result = mysqli_query($conn, $query);
if ($row = mysqli_fetch_assoc($result)) {
    $stats['total_downloads'] = $row['total_downloads'] ?? 0;
    $stats['total_earnings'] = $row['total_earnings'] ?? 0;
}

// Get total and pending papers
$query = "SELECT 
            COUNT(*) as total_papers,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_papers
          FROM papers";
$result = mysqli_query($conn, $query);
if ($row = mysqli_fetch_assoc($result)) {
    $stats['total_papers'] = $row['total_papers'];
    $stats['pending_papers'] = $row['pending_papers'];
}

// Get pending instructors
$query = "SELECT COUNT(*) as count FROM users WHERE role = 'instructor' AND is_active = 0";
$result = mysqli_query($conn, $query);
if ($row = mysqli_fetch_assoc($result)) {
    $stats['pending_instructors'] = $row['count'];
}

// Get total instructors and students
$query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['role'] === 'instructor') {
        $stats['total_instructors'] = $row['count'];
    } elseif ($row['role'] === 'student') {
        $stats['total_students'] = $row['count'];
    }
}

// Get pending withdrawals
$query = "SELECT COUNT(*) as count FROM withdrawals WHERE status = 'pending'";
$result = mysqli_query($conn, $query);
if ($row = mysqli_fetch_assoc($result)) {
    $stats['pending_withdrawals'] = $row['count'];
}
?>

<!-- Admin Dashboard -->
<div class="container px-4 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Admin Dashboard</h1>
                    <p class="mb-0 text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                </div>
                <button id="refreshStats" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <!-- Revenue Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-2 opacity-75">Total Revenue</h6>
                            <h3 class="mb-0"><?php echo format_currency($stats['total_earnings']); ?></h3>
                        </div>
                        <div class="rounded-circle bg-white bg-opacity-25 p-3">
                            <i class="bi bi-currency-dollar text-white fs-3"></i>
                        </div>
                    </div>
                    <div class="mt-3 small">
                        <span class="text-white text-opacity-75">
                            <?php echo number_format($stats['total_downloads']); ?> total downloads
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Papers Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 bg-success text-white h-100" style="cursor: pointer;" onclick="window.location.href='<?php echo BASE_URL; ?>/index.php?page=manage-papers'">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-2 opacity-75">Total Papers</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['total_papers']); ?></h3>
                        </div>
                        <div class="rounded-circle bg-white bg-opacity-25 p-3">
                            <i class="bi bi-file-text text-white fs-3"></i>
                        </div>
                    </div>
                    <div class="mt-3 small">
                        <?php if ($stats['pending_papers'] > 0): ?>
                            <span class="text-white text-opacity-75">
                                <i class="bi bi-clock-history"></i>
                                <?php echo number_format($stats['pending_papers']); ?> pending approvals
                            </span>
                        <?php else: ?>
                            <span class="text-white text-opacity-75">No pending papers</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 bg-info text-white h-100" style="cursor: pointer;" onclick="window.location.href='<?php echo BASE_URL; ?>/index.php?page=manage-users'">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-2 opacity-75">Total Users</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['total_students'] + $stats['total_instructors']); ?></h3>
                        </div>
                        <div class="rounded-circle bg-white bg-opacity-25 p-3">
                            <i class="bi bi-people text-white fs-3"></i>
                        </div>
                    </div>
                    <div class="mt-3 small d-flex justify-content-between text-white text-opacity-75">
                        <span><?php echo number_format($stats['total_students']); ?> students</span>
                        <span><?php echo number_format($stats['total_instructors']); ?> instructors</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Withdrawal Requests Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 bg-danger text-white h-100" style="cursor: pointer;" onclick="window.location.href='<?php echo BASE_URL; ?>/index.php?page=manage-withdrawals'">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-2 opacity-75">Withdrawal Requests</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['pending_withdrawals']); ?></h3>
                        </div>
                        <div class="rounded-circle bg-white bg-opacity-25 p-3">
                            <i class="bi bi-cash-coin text-white fs-3"></i>
                        </div>
                    </div>
                    <div class="mt-3 small">
                        <?php if ($stats['pending_withdrawals'] > 0): ?>
                            <span class="text-white text-opacity-75">
                                <i class="bi bi-clock-history"></i>
                                <?php echo number_format($stats['pending_withdrawals']); ?> pending requests
                            </span>
                        <?php else: ?>
                            <span class="text-white text-opacity-75">No pending withdrawals</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mt-5">
    <h2 class="mb-4">Pending Activation Requests</h2>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Request Type</th>
                            <th>User ID</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Only fetch requests with status 'pending'
                        $query = "SELECT * FROM requests WHERE status = 'pending' ORDER BY created_at DESC";
                        $result = mysqli_query($conn, $query);
                        
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['request_type']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
                                echo "<td>";
                                // Add colored badge (will always be 'pending' in this case)
                                echo "<span class='badge bg-warning'>" . htmlspecialchars($row['status']) . "</span>";
                                echo "</td>";
                                echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center'>No pending requests found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Refresh stats button
    const refreshBtn = document.getElementById('refreshStats');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            location.reload();
        });
    }

    // Your other functions remain unchanged
    function filterActivity(type) {
        alert('Filtering by ' + type + ' will be implemented in the next update');
    }

    function handleRequest(requestId, action) {
        if (confirm('Are you sure you want to ' + action + ' this request?')) {
            $.ajax({
                url: '<?php echo BASE_URL; ?>/ajax/handle_request.php',
                method: 'POST',
                data: { 
                    request_id: requestId,
                    action: action
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while processing the request. Please try again.');
                }
            });
        }
    }
});
</script>

<style>
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-5px);
}

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