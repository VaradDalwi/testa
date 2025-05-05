<?php
ob_start(); // Start output buffering at the very beginning
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TESTA - Examination Revision Platform</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- jQuery (must be loaded first) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        :root {
            --primary-color: #2000ff;
            --secondary-color: #2a5298;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding:0;
        }
        
        .navbar-brand {
            font-size: 4rem;
            font-weight: bold;
            color: var(--primary-color) !important;
        }
        
        .nav-link {
            color: #333 !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: color 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--primary-color) !important;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 100px 0;
        }
        
        .auth-buttons .btn {
            margin-left: 10px;
            padding: 8px 20px;
            border-radius: 5px;
        }
        
        .auth-buttons .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .auth-buttons .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
            font-weight: 500;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .dropdown-item {
            padding: 8px 20px;
            color: #333;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: var(--primary-color);
        }

        .dropdown-item i {
            margin-right: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">TESTA</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                                </div>
                                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($userRole === 'student'): ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/index.php?page=dashboard">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </a></li>
                                <?php endif; ?>
                                <?php if ($userRole === 'instructor'): ?>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/index.php?page=dashboard">
                                        <i class="bi bi-speedometer2"></i> Dashboard
                                    </a></li>
                                <?php endif; ?>
                                <?php if ($userRole === 'admin'): ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/index.php?page=dashboard">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/index.php?page=manage-papers">
                                    <i class="bi bi-file-text"></i> Manage Papers
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/index.php?page=manage-users">
                                    <i class="bi bi-people"></i> Manage Users
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/index.php?page=manage-withdrawals">
                                    <i class="bi bi-cash-coin me-2"></i>
                                    Manage Withdrawals
                                </a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/index.php?page=logout">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/index.php?page=login">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/index.php?page=signup">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if (!isset($hideContainer) || !$hideContainer): ?>
    <div class="container py-4">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="container mt-3">
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                    <?php 
                        echo $_SESSION['message'];
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?> 