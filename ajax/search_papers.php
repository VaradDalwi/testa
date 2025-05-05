<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Session is already started in config.php, so no need to start again
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? '';

// Get search parameter and page number
$search = $_GET['search'] ?? '';
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 20;

// Calculate the offset
$offset = ($current_page - 1) * $items_per_page;

// Simplified query (removed joins to units and courses tables)
$query = "SELECT p.*, u.full_name as instructor_name
          FROM papers p 
          JOIN users u ON p.instructor_id = u.id
          WHERE p.status = 'approved'";

if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $query .= " AND (
        p.title LIKE '%$search%' OR 
        u.full_name LIKE '%$search%'
    )";
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM (" . $query . ") as subquery";
$count_result = mysqli_query($conn, $count_query);
$total_items = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_items / $items_per_page);

// Add pagination to the main query
$query .= " ORDER BY p.created_at DESC LIMIT $offset, $items_per_page";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0): ?>
    <div class="row">
        <?php while ($paper = mysqli_fetch_assoc($result)): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($paper['title']); ?></h5>
                        <p class="card-text">
                            <strong>Instructor:</strong> <?php echo htmlspecialchars($paper['instructor_name']); ?>
                        </p>
                        <p class="card-text text-primary fw-bold"><?php echo format_currency($paper['price']); ?></p>
                    </div>
                    <div class="card-footer bg-transparent border-top-0">
                        <?php if ($isLoggedIn && $userRole === 'student'): ?>
                            <button class="btn btn-primary w-100 purchase-btn" data-paper-id="<?php echo $paper['id']; ?>" data-price="<?php echo $paper['price']; ?>">
                                Purchase Paper
                            </button>
                        <?php elseif (!$isLoggedIn): ?>
                            <a href="<?php echo BASE_URL; ?>/index.php?page=login" class="btn btn-primary w-100">
                                Login to Purchase
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="d-flex justify-content-center mt-4">
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="<?php echo $current_page - 1; ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    if ($end_page - $start_page < 4) {
                        $start_page = max(1, $end_page - 4);
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                            <a class="page-link" href="#" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="<?php echo $current_page + 1; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="alert alert-info text-center">
        <?php if ($search): ?>
            No papers found matching "<?php echo htmlspecialchars($search); ?>". Try a different search term.
        <?php else: ?>
            No papers available at the moment.
        <?php endif; ?>
    </div>
<?php endif; ?> 