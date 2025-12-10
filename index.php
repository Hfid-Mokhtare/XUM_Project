<?php
/**
 * @file index.php
 * @brief The main dashboard and entry point of the application after user login.
 *
 * This file handles session management, including authentication checks and timeouts.
 * It serves as the primary router, loading different page content based on the 'page' URL parameter.
 * It also includes the main HTML structure, navigation, and client-side scripts for interactivity.
 */

// Include the central configuration file
require_once __DIR__ . '/config.php';

// Start the session to access session variables.
session_start();

// --- Authentication Check ---
// If the 'user' session variable is not set, the user is not logged in.
// Redirect them to the login page and terminate the script.
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// --- Session Timeout Logic ---
$timeout_duration = 900; // Set timeout duration to 15 minutes (900 seconds).

// Check if the session has been inactive for longer than the timeout duration.
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // If the session has timed out, unset and destroy the session data.
    session_unset();
    session_destroy();
    // Redirect to the login page with a timeout error message.
    header('Location: login.php?error=' . urlencode('Your session has timed out due to inactivity.'));
    exit();
}

// Update the 'last_activity' timestamp to the current time for every user interaction.
$_SESSION['last_activity'] = time();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <script src="assets/js/chart.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xpert Users Dashboard</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar {
            padding: 0.8rem 1rem;
        }

        .main-content {
            padding: 2rem;
        }

        .menu-shadow {
            transition: box-shadow 0.2s;
            border-radius: 0.5rem;
        }

        .menu-shadow:hover,
        .menu-shadow:focus-within,
        .menu-shadow.active {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.10);
            background: #f1f5f9;
        }

        .menu-shadow .nav-link {
            border-radius: 0.5rem;
        }

        body {
            /* Adjust padding to match the combined height of both fixed divs */
            padding-top: 100px;
            background-image: url('assets/images/background2.jpeg');
            background-attachment: fixed;
            background-size: cover;
        }

        .nav .nav-link.active {
            border-bottom: 3px solid #0d6efd;
            font-weight: 700;
            color: #0d6efd !important;
        }

        .navbar-nav .nav-link.btn:hover,
        .navbar-nav .nav-link.btn:active {
            background-color: #e9ecef;
            transform: translateY(-1px);
        }
    </style>
</head>

<body>
    <?php
    /**
     * --- Page Routing ---
     * Determines which page to display based on the 'page' URL parameter.
     * Defaults to the 'users' page if the parameter is not set.
     */
    $page = isset($_GET['page']) ? $_GET['page'] : 'users';
    ?>
    <div class="fixed-top">
        <nav class="navbar navbar-expand-lg navbar-light" style="background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div class="container-fluid">
                <a class="navbar-brand d-flex align-items-center" href="?page=users">
                    <span class="fs-5 fw-bold text-primary">Xpert Users</span>
                </a>
                <div class="mx-auto w-50">
                    <form id="global-search-form" class="d-flex position-relative align-items-center" role="search">
                        <span class="position-absolute ps-3 text-muted">
                            <?php display_icon('search'); ?>
                        </span>
                        <input id="global-search-input" class="form-control me-2 rounded-pill shadow-sm ps-5" type="search" placeholder="Search users, keys, or programmes..." aria-label="Search" style="background:#f1f5f9;">
                    </form>
                </div>
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link btn rounded-pill px-4 menu-shadow d-flex align-items-center" href="logout.php">
                            <?php display_icon('box-arrow-right', 'me-2'); ?>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="bg-white border-bottom py-2">
            <div class="container-fluid">
                <ul class="nav justify-content-center">
                    <li class="nav-item">
                                                <a class="nav-link text-primary fw-semibold <?php if ($page === 'users') echo 'active'; ?>" href="?page=users"><?php display_icon('people-fill', 'me-2'); ?>Users</a>
                    </li>
                    <li class="nav-item">
                                                <a class="nav-link text-primary fw-semibold <?php if ($page === 'keys') echo 'active'; ?>" href="?page=keys"><?php display_icon('key-fill', 'me-2'); ?>Keys Affectation</a>
                    </li>
                    <li class="nav-item">
                                                <a class="nav-link text-primary fw-semibold <?php if ($page === 'stats') echo 'active'; ?>" href="?page=stats"><?php display_icon('bar-chart-line-fill', 'me-2'); ?>Statistics</a>
                    </li>
                    <li class="nav-item">
                                                <a class="nav-link text-primary fw-semibold <?php if ($page === 'settings') echo 'active'; ?>" href="?page=settings"><?php display_icon('gear-fill', 'me-2'); ?>Settings</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="main-content">
        <?php
        /**
         * --- Dynamic Page Content Inclusion ---
         * A switch statement loads the content of the appropriate page from the '/pages' directory
         * based on the value of the $page variable.
         */
        switch ($page) {
            case 'users':
                include 'pages/users.php';
                break;
            case 'keys':
                include 'pages/keys.php';
                break;
            case 'stats':
                include 'pages/stats.php';
                break;
            case 'settings':
                include 'pages/settings.php';
                break;
            case 'new_user':
                include 'pages/new_user.php';
                break;
            default:
                // If the 'page' parameter is invalid, default to the users page.
                include 'pages/users.php';
                break;
        }
        ?>
    </div>

    <script>
        /**
         * Adds a visual 'active' state to menu items on click for better user feedback.
         */
        document.querySelectorAll('.menu-shadow').forEach(function(li) {
            li.addEventListener('click', function() {
                // Remove 'active' from all other menu items
                document.querySelectorAll('.menu-shadow').forEach(function(el) {
                    el.classList.remove('active');
                });
                // Add 'active' to the clicked item
                li.classList.add('active');
            });
        });
    </script>
    <script>
        /**
         * --- Client-Side Inactivity Timer ---
         * This script complements the server-side session timeout. It automatically redirects the user
         * to the login page after a period of inactivity (15 minutes).
         */
        (function() {
            const timeoutDuration = 900 * 1000; // 15 minutes in milliseconds
            let inactivityTimer;

            // Function to redirect to the login page.
            function logout() {
                window.location.href = 'login.php?error=' + encodeURIComponent('Your session has timed out due to inactivity.');
            }

            // Function to reset the inactivity timer.
            function resetTimer() {
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(logout, timeoutDuration);
            }

            // Event listeners to detect user activity and reset the timer.
            window.onload = resetTimer;
            document.onmousemove = resetTimer;
            document.onkeypress = resetTimer;
            document.onclick = resetTimer;
            document.onscroll = resetTimer;
            document.onfocus = resetTimer;
        })();
    </script>
<script>
    /**
     * --- Global Search Form Handler ---
     * Handles the submission of the global search form. It captures the search term,
     * determines the current page, and reloads the page with the search term as a URL parameter.
     */
    document.addEventListener('DOMContentLoaded', function() {
        const globalSearchForm = document.getElementById('global-search-form');
        const globalSearchInput = document.getElementById('global-search-input');

        globalSearchForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent the default form submission.
            
            const searchTerm = globalSearchInput.value.trim();
            
            // Get the current page from the URL to maintain context.
            const urlParams = new URLSearchParams(window.location.search);
            let currentPage = urlParams.get('page') || 'users'; // Default to 'users' page.

            // Restrict search functionality to 'users' and 'keys' pages for relevance.
            if (currentPage !== 'users' && currentPage !== 'keys') {
                currentPage = 'users'; // Default to a safe page if on an unsupported page.
            }

            // Construct the new URL with the current page and search term.
            const newUrl = `?page=${currentPage}&search=${encodeURIComponent(searchTerm)}`;
            
            // Redirect to the new URL to apply the search filter.
            window.location.href = newUrl;
        });
    });
</script>
</body>

</html>
