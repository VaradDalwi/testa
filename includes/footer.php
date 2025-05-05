    </div> <!-- End of container -->

    <?php if (!isset($hideFooter) || !$hideFooter): ?>
    <!-- Footer -->
    <footer class="mt-5" style="background: linear-gradient(135deg, #2000ff 0%, #2a5298 100%); color: white; padding: 60px 0 30px;">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h4 class="mb-4">TESTA</h4>
                    <p class="mb-3">Your trusted platform for accessing quality examination papers and study materials.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h4 class="mb-4">Quick Links</h4>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>" class="text-white text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>/index.php?page=login" class="text-white text-decoration-none">Login</a></li>
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>/index.php?page=signup" class="text-white text-decoration-none">Sign Up</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h4 class="mb-4">Contact</h4>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="bi bi-envelope me-2"></i> support@testa.com</li>
                        <li class="mb-2"><i class="bi bi-phone me-2"></i> +254 700 000000</li>
                    </ul>
                </div>
            </div>
            <hr class="mt-4 mb-4" style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> TESTA. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html> 