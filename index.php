<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

// Define base URL
define('BASE_URL', 'http://localhost/testa');

// Include necessary files
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Get current page
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Handle logout before any output
if ($page === 'logout') {
    // Clear all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy the session
    session_destroy();

    // Redirect to home page
    header('Location: ' . BASE_URL);
    exit;
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['role'] : null;

// Set hideContainer for auth pages, home page, and upload-paper
$authPages = ['login', 'signup', 'forgot-password', 'reset-password', 'home', 'upload-paper', 'dashboard', 'manage-users', 'manage-papers'];
if (in_array($page, $authPages)) {
    $hideContainer = true;
}

// Include header
include 'includes/header.php';

// Route to appropriate page
switch($page) {
    case 'login':
        include 'pages/auth/login.php';
        break;
    case 'signup':
        include 'pages/auth/signup.php';
        break;
    case 'forgot-password':
        include 'pages/auth/forgot-password.php';
        break;
    case 'reset-password':
        include 'pages/auth/reset-password.php';
        break;
    case 'dashboard':
        if (!$isLoggedIn) {
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }
        include 'pages/dashboard/' . $userRole . '.php';
        break;
    case 'upload-paper':
        if (!$isLoggedIn || $userRole !== 'instructor') {
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }
        include 'pages/instructor/upload-paper.php';
        break;
    case 'requests':
        if (!$isLoggedIn || $userRole !== 'admin') {
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }
        include 'pages/admin/requests.php';
        break;
    case 'manage-users':
        if (!$isLoggedIn || $userRole !== 'admin') {
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }
        include 'pages/admin/manage-users.php';
        break;
    case 'manage-papers':
        if (!$isLoggedIn || $userRole !== 'admin') {
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }
        include 'pages/admin/manage-papers.php';
        break;
    case 'manage-withdrawals':
            if (!$isLoggedIn || $userRole !== 'admin') {
                header('Location: ' . BASE_URL . '/index.php?page=login');
                exit;
            }
            include 'pages/admin/manage-withdrawals.php';
            break;
    default:
        include 'pages/home.php';
}

// Include footer
include 'includes/footer.php';
?> 