<?php
/**
 * @file login_action.php
 * @brief Processes the user login form submission.
 *
 * This script handles the backend logic for user authentication. It verifies credentials
 * against the database, creates a session for authenticated users, and redirects
 * accordingly.
 */

session_start();

require_once '../connection.php';

// Ensure the script is accessed via a POST request, typical for form submissions.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve username and password from the POST data.
    $userName = $_POST['userName'];
    $password = $_POST['password'];

    try {
        // Prepare and execute a query to find the user by their username.
        $sql = "SELECT * FROM dbo.tbl_xpert_application_users WHERE userName = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userName]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify if the user exists and if the provided password matches the hashed password in the database.
        if ($user && password_verify($password, $user['Password'])) {

            // --- Successful Login ---
            // Store essential user data in the session.
            $_SESSION['user'] = [
                'ID' => $user['ID'],
                'FullName' => $user['FullName'],
                'UserName' => $user['UserName'],
                'Permission' => $user['Permission']
            ];

            // Set the initial timestamp for session timeout management.
            $_SESSION['last_activity'] = time();

            // Redirect to the main dashboard.
            header('Location: ../index.php');
            exit();
        } else {
            // --- Failed Login ---
            // If authentication fails, redirect back to the login page with an error message.
            header('Location: ../login.php?error=' . urlencode('Invalid username or password. Please try again.'));
            exit();
        }
    } catch (PDOException $e) {
        // Handle any database-related errors during the login process.
        die("Database error during login: " . $e->getMessage());
    }
} else {
    // If the script is accessed directly or with a method other than POST, redirect to the login page.
    header('Location: ../login.php');
    exit();
}
