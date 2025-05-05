<?php
// Get pending withdrawal requests
$withdrawals_query = "SELECT wr.*, u.full_name 
    FROM withdrawal_requests wr
    JOIN users u ON wr.instructor_id = u.id
    WHERE wr.status = 'pending'
    ORDER BY wr.request_date DESC";
$withdrawals = $conn->query($withdrawals_query);
?>

<div class="container px-4 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">Manage Withdrawal Requests</h1>
            <p class="mb-0 text-muted">View and process pending withdrawal requests</p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Instructor</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Details</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($withdrawal = $withdrawals->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($withdrawal['full_name']); ?></td>
                            <td>KSH <?php echo number_format($withdrawal['amount'], 2); ?></td>
                            <td><?php echo ucfirst($withdrawal['payment_method']); ?></td>
                            <td><?php echo htmlspecialchars($withdrawal['payment_details']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($withdrawal['request_date'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-success" onclick="processWithdrawal(<?php echo $withdrawal['id']; ?>, 'approved')">Approve</button>
                                <button class="btn btn-sm btn-danger" onclick="processWithdrawal(<?php echo $withdrawal['id']; ?>, 'rejected')">Reject</button>
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
function processWithdrawal(id, action) {
    if (confirm("Are you sure you want to " + action + " this withdrawal?")) {
        $.ajax({
            url: "<?php echo BASE_URL; ?>/ajax/handle_withdrawal_approval.php",
            method: "POST",
            data: { 
                withdrawal_id: id, 
                action: action,
                notes: prompt("Enter any notes:")
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    alert("Withdrawal request processed successfully");
                    location.reload();
                } else {
                    alert("Error: " + response.message);
                }
            }
        });
    }
}
</script> 