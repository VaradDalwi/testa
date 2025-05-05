<?php
// Get search parameter and page number
$search = $_GET['search'] ?? '';
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 20;

// Calculate the offset
$offset = ($current_page - 1) * $items_per_page;

// Build query for all papers with instructor information (excluding units and courses)
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

// Assume $student_id is the logged-in student's ID (only if logged in)
$student_id = $isLoggedIn ? $_SESSION['user_id'] : null;

// Prepare the statement once outside the loop (only if logged in)
$check_purchase_stmt = null;
if ($isLoggedIn && $userRole === 'student') {
    $check_purchase_query = "SELECT id FROM purchases WHERE student_id = ? AND paper_id = ? AND status = 'completed'";
    $check_purchase_stmt = mysqli_prepare($conn, $check_purchase_query);
}
?>

<!-- Hero Section -->
<div class="hero-section text-center py-5 mb-5" style="background: linear-gradient(135deg, #2000ff 0%, #2a5298 100%); min-height: 400px; display: flex; align-items: center; justify-content: center;">
    <div class="container text-white">
        <h1 class="display-2 mb-4 fw-bold">Welcome to TESTA</h1>
        <p class="lead mb-4" style="font-size: 1.4rem;">Your secure platform for accessing high-quality examination papers and study materials.</p>
        <a href="<?php echo $isLoggedIn ? BASE_URL . '/index.php?page=dashboard' : BASE_URL . '/index.php?page=signup'; ?>" class="btn btn-light btn-lg px-4 py-2">Get Started</a>
    </div>
</div>

<!-- Browse Papers Section -->
<div class="container mb-5">
    <h2 class="text-center mb-4">Browse All Papers</h2>
    
    <!-- Search Form -->
    <div class="row justify-content-center mb-5">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="position-relative">
                        <i class="bi bi-search position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
                        <input type="text" 
                               class="form-control form-control-lg ps-5" 
                               id="searchInput"
                               placeholder="Search by paper title or instructor..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               aria-label="Search papers">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Papers Grid -->
    <div id="searchResults">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="row">
                <?php while ($paper = mysqli_fetch_assoc($result)): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($paper['title']); ?></h5>
                                <p class="card-text">
                                    <strong>Instructor:</strong> <?php echo htmlspecialchars($paper['instructor_name']); ?>
                                </p>
                                <p class="card-text">
                                    <strong>Course:</strong> <?php echo htmlspecialchars($paper['course']); ?>
                                </p>
                                <p class="card-text">
                                    <strong>Unit:</strong> <?php echo htmlspecialchars($paper['unit']); ?>
                                </p>
                                <p class="card-text text-primary fw-bold"><?php echo format_currency($paper['price']); ?></p>
                            </div>
                            <div class="card-footer bg-transparent border-top-0">
                                <?php if ($isLoggedIn && $userRole === 'student'): ?>
                                    <?php
                                    if ($check_purchase_stmt) {
                                        mysqli_stmt_bind_param($check_purchase_stmt, "ii", $student_id, $paper['id']);
                                        mysqli_stmt_execute($check_purchase_stmt);
                                        mysqli_stmt_store_result($check_purchase_stmt);
                                        $has_purchased = mysqli_stmt_num_rows($check_purchase_stmt) > 0;
                                    } else {
                                        $has_purchased = false;
                                    }
                                    ?>
                                    <?php if ($has_purchased): ?>
                                        <button class="btn btn-primary w-100 view-btn" data-paper-id="<?php echo $paper['id']; ?>" onclick="viewPaperDetails(<?php echo $paper['id']; ?>)">
                                            View Paper
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-primary w-100 purchase-btn" data-paper-id="<?php echo $paper['id']; ?>" data-price="<?php echo $paper['price']; ?>">
                                            Purchase Paper
                                        </button>
                                    <?php endif; ?>
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
    </div>
</div>

<!-- Purchase Modal -->
<div class="modal fade" id="purchaseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Purchase Paper</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="purchaseForm">
                    <input type="hidden" id="paperId" name="paper_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Paper Title</label>
                        <div class="form-control-plaintext" id="paperTitle"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount to Pay</label>
                        <input type="text" class="form-control" id="amount" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required readonly value="<?php echo htmlspecialchars($_SESSION['phone_number'] ?? ''); ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmPurchase">Pay with MPESA</button>
            </div>
        </div>
    </div>
</div>

<!-- Paper Details Modal (Same as in student.php) -->
<div class="modal fade" id="paperDetailsModal" tabindex="-1" aria-labelledby="paperDetailsModalLabel" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    let searchTimeout;
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    let currentPage = <?php echo $current_page; ?>;

    // Function to perform the search
    function performSearch(query, page = 1) {
        console.log('Performing search for:', query, 'Page:', page);
        
        fetch(`<?php echo BASE_URL; ?>/ajax/search_papers.php?search=${encodeURIComponent(query)}&page=${page}`)
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(html => {
                console.log('Received response');
                searchResults.innerHTML = html;
                
                // Reattach event listeners
                attachEventListeners();
            })
            .catch(error => {
                console.error('Search error:', error);
                searchResults.innerHTML = '<div class="alert alert-danger text-center">An error occurred while searching. Please try again.</div>';
            });
    }

    // Function to attach event listeners
    function attachEventListeners() {
        // Purchase buttons
        document.querySelectorAll('.purchase-btn').forEach(button => {
            button.addEventListener('click', function() {
                const paperId = this.dataset.paperId;
                const price = this.dataset.price;
                const card = this.closest('.card');
                const paperTitle = card.querySelector('.card-title').textContent;
                
                // Get all card text elements
                const cardTexts = card.querySelectorAll('.card-text');
                let course = '';
                
                // Find course information
                cardTexts.forEach(text => {
                    const content = text.textContent;
                    if (content.includes('Course:')) {
                        course = content.split('Course:')[1].trim();
                    }
                });
                
                document.getElementById('paperId').value = paperId;
                document.getElementById('amount').value = 'KSH ' + parseFloat(price).toFixed(2);
                document.getElementById('paperTitle').textContent = paperTitle;
                new bootstrap.Modal(document.getElementById('purchaseModal')).show();
            });
        });

        // Pagination links
        document.querySelectorAll('.pagination .page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                currentPage = page;
                performSearch(searchInput.value.trim(), page);
            });
        });

        // View Paper button
        document.querySelectorAll('.view-btn').forEach(button => {
            button.addEventListener('click', function() {
                const paperId = this.dataset.paperId;
                viewPaperDetails(paperId);
            });
        });
    }

    // Handle search input with debouncing
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        console.log('Input event triggered:', query);
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        // Reset to first page on new search
        currentPage = 1;
        
        // Set new timeout
        searchTimeout = setTimeout(function() {
            if (query.length === 0 || query.length >= 2) {
                performSearch(query, currentPage);
            }
        }, 300);
    });

    // Initial attachment of event listeners
    attachEventListeners();

    // Handle purchase confirmation
    document.getElementById('confirmPurchase').addEventListener('click', function() {
        const paperId = document.getElementById('paperId').value;
        const phone = document.getElementById('phone').value;
        
        // TODO: Implement MPESA payment integration
        alert('MPESA payment integration will be implemented here');
    });

    // When purchase button is clicked, fetch student's phone number
    $('.purchase-btn').click(function() {
        const paperId = $(this).data('paper-id');
        const price = $(this).data('price');
        
        // Set paper details in modal
        $('#paperId').val(paperId);
        $('#paperTitle').text($(this).closest('.card').find('.card-title').text());
        $('#amount').val('KSH ' + price.toLocaleString());
        
        // Fetch student's phone number if not already set
        if (!$('#phone').val()) {
            $.ajax({
                url: '<?php echo BASE_URL; ?>/ajax/get_student_phone.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.phone) {
                        $('#phone').val(response.phone);
                    }
                }
            });
        }
        
        // Show modal
        const purchaseModal = new bootstrap.Modal(document.getElementById('purchaseModal'));
        purchaseModal.show();

        // Add cleanup for backdrop when modal is closed
        document.getElementById('purchaseModal').addEventListener('hidden.bs.modal', function() {
            document.body.classList.remove('modal-open');
            document.body.style.paddingRight = '';
            document.body.style.overflow = '';
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
        });
    });
});

// Global function to handle paper preview
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
                const modalElement = document.getElementById('paperDetailsModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

                // Clear any existing alerts
                $('#pdfViewerContainer').find('.alert').remove();

                if (paper.file_path) {
                    // Disable right-click and text selection
                    pdfViewer.attr('oncontextmenu', 'return false;')
                             .css('user-select', 'none')
                             .css('-webkit-user-select', 'none')
                             .css('-moz-user-select', 'none')
                             .css('-ms-user-select', 'none');

                    // Load the PDF with restrictions
                    pdfViewer.attr('src', paper.file_path + '#toolbar=0&navpanes=0&scrollbar=0');
                    pdfViewer.show();
                } else {
                    // Show error message if no PDF is available
                    $('#pdfViewerContainer').html('<div class="alert alert-info">No PDF file available</div>');
                    pdfViewer.hide();
                }

                // Show the modal
                modal.show();

                // Cleanup when modal is closed
                modalElement.addEventListener('hidden.bs.modal', function () {
                    // Remove the modal-open class and reset padding
                    document.body.classList.remove('modal-open');
                    document.body.style.paddingRight = '';

                    // Remove the backdrop
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());

                    // Reset the PDF viewer
                    pdfViewer.attr('src', '');
                });
            } else {
                alert('Error: ' + (response.message || 'Unknown error occurred'));
            }
        },
        error: function() {
            alert('An error occurred while fetching paper details. Please try again.');
        }
    });
};
</script>

<?php
// Close the statement after the loop (only if it was initialized)
if ($check_purchase_stmt !== null) {
    mysqli_stmt_close($check_purchase_stmt);
}
?> 