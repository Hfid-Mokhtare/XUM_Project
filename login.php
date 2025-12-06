<?php
/**
 * @file login.php
 * @brief Handles user login and serves the login page.
 *
 * This script checks if any users exist in the database. If not, it redirects to the
 * new user creation page to set up the first administrative account. Otherwise, it displays
 * a login form for users to enter their credentials.
 */

require_once 'config.php';

// --- Initial User Check ---
try {
    // Query the database to count the number of existing users.
    $stmt = $conn->query("SELECT COUNT(*) as user_count FROM dbo.tbl_xpert_application_users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no users are found, redirect to the new user page to create the first admin.
    if ($result && $result['user_count'] == 0) {
        header('Location: new_user.php');
        exit();
    }
} catch (PDOException $e) {
    // Handle database connection or query errors gracefully.
    // In a production environment, this might redirect to a dedicated error page.
    die("Database connection or query failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- Optional: Bootstrap Icons -->

    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: #f8f9fa;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="text-center mb-4">
            <img src="assets/images/Logo.png" alt="Company Logo" style="max-width: 180px;">
            <h4 class="mt-3 text-muted">Xpert Users</h4>
        </div>
        <div class="card login-card shadow-sm mx-auto">
        <div class="card-header text-center">
            <h2><?php display_icon('box-arrow-in-right', 'me-2'); ?>Login</h2>
        </div>
        <div class="card-body p-4">
            <?php
            /**
             * --- Feedback Messages ---
             * Displays success or error messages passed as URL parameters.
             * For example, after a failed login attempt or a session timeout.
             */
            if (isset($_GET['success'])):
            ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && !empty($_GET['error'])):
            ?>
                <div class="alert alert-danger mt-3" role="alert">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            <!-- Login Form -->
            <!-- /**
             * Handles user login form submission.
             * Validates user input and checks credentials against the database.
             */ -->
            <form action="actions/login_action.php" method="POST">
                <!-- Username Input -->
                <div class="form-group mb-3">
                    <label for="userName" class="form-label fw-semibold">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><?php display_icon('person-fill'); ?></span>
                        <input type="text" class="form-control" id="userName" name="userName" required>
                    </div>
                </div>
                <!-- Password Input -->
                <div class="form-group mb-4">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><?php display_icon('lock-fill'); ?></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                <!-- Submit Button -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center">
                        <?php display_icon('box-arrow-in-right', 'me-2'); ?>Login
                    </button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center">
             <a href="new_user.php" class="d-inline-flex align-items-center">
                <?php display_icon('person-plus-fill', 'me-2'); ?>Create an account
            </a>
        </div>
    </div>
</div>
</body>

</html>