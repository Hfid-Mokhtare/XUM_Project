<?php
/**
 * @file register_action.php
 * @brief Handles the registration of the first user or subsequent users.
 *
 * This script processes the new user registration form. It validates the input,
 * hashes the password, and creates a new user account. The first user registered
 * is automatically granted administrator privileges.
 */

session_start();
require_once '../connection.php';

// --- Security and Validation ---
// Restrict access to POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../new_user.php?error=Invalid request');
    exit();
}

// Retrieve user data from the POST request.
$fullName = $_POST['fullName'] ?? null;
$userName = $_POST['userName'] ?? null;
$password = $_POST['password'] ?? null;

// Validate that all required fields are provided.
if (empty($fullName) || empty($userName) || empty($password)) {
    header('Location: ../new_user.php?error=Please fill in all required fields.');
    exit();
}

// --- User Registration Logic ---
// Hash the password for secure storage.
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$dateAssigned = date('Y-m-d H:i:s');

try {
    // Determine the user's permission level.
    $countStmt = $conn->query("SELECT COUNT(*) as user_count FROM dbo.tbl_xpert_application_users");
    $result = $countStmt->fetch(PDO::FETCH_ASSOC);

    if ($result && $result['user_count'] == 0) {
        // If this is the first user, assign 'admin' rights.
        $permission = 'admin';
    } else {
        // Otherwise, assign the default 'user' role.
        $permission = 'user';
    }

    // Check if the chosen username is already in use.
    $checkUserStmt = $conn->prepare("SELECT ID FROM dbo.tbl_xpert_application_users WHERE UserName = ?");
    $checkUserStmt->execute([$userName]);
    if ($checkUserStmt->fetch()) {
        header('Location: ../new_user.php?error=' . urlencode('This username is already taken. Please choose another.'));
        exit();
    }

    // Prepare and execute the SQL statement to insert the new user.
    $sql = "INSERT INTO dbo.tbl_xpert_application_users (FullName, UserName, Password, Permission, DateAssigned) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$fullName, $userName, $hashedPassword, $permission, $dateAssigned])) {
        // On success, redirect to the login page with a success message.
        $successMessage = 'Account created successfully. Please log in.';
        if ($permission === 'admin') {
            $successMessage = 'Admin account created successfully. Please log in.';
        }
        header('Location: ../login.php?success=' . urlencode($successMessage));
        exit();
    } else {
        // On failure, redirect back to the registration page with an error.
        header('Location: ../new_user.php?error=Failed to create account.');
        exit();
    }
} catch (PDOException $e) {
    // Handle any other database errors.
    header('Location: ../new_user.php?error=' . urlencode('A database error occurred. Please try again later.'));
    exit();
}
?>
