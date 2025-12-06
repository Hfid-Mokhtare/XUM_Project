<?php
/**
 * @file change_password_action.php
 * @brief Handles the server-side logic for changing a user's password.
 *
 * This script validates the user's session, checks the submitted password fields,
 * verifies the current password, and updates the database with the new hashed password.
 * It returns JSON responses to be handled by the client-side script.
 */

session_start();
require_once '../connection.php';

// Set the content type to JSON for AJAX responses.
header('Content-Type: application/json');

// --- Security and Validation ---
// Ensure the user is logged in before allowing a password change.
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to perform this action.']);
    exit();
}

// Restrict access to POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

// Retrieve user ID from the session and password data from the POST request.
$userId = $_SESSION['user']['ID'];
$currentPassword = $_POST['currentPassword'] ?? null;
$newPassword = $_POST['newPassword'] ?? null;
$confirmNewPassword = $_POST['confirmNewPassword'] ?? null;

// Validate that all password fields are filled.
if (empty($currentPassword) || empty($newPassword) || empty($confirmNewPassword)) {
    echo json_encode(['status' => 'error', 'message' => 'All password fields are required.']);
    exit();
}

// Check if the new password and its confirmation match.
if ($newPassword !== $confirmNewPassword) {
    echo json_encode(['status' => 'error', 'message' => 'New passwords do not match.']);
    exit();
}

// --- Password Update Logic ---
try {
    // Fetch the current user's hashed password from the database for verification.
    $stmt = $conn->prepare("SELECT Password FROM dbo.tbl_xpert_application_users WHERE ID = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify that the provided current password is correct.
    if (!$user || !password_verify($currentPassword, $user['Password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Your current password is incorrect.']);
        exit();
    }

    // Hash the new password for secure storage.
    $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Prepare and execute the statement to update the password in the database.
    $updateStmt = $conn->prepare("UPDATE dbo.tbl_xpert_application_users SET Password = ? WHERE ID = ?");

    if ($updateStmt->execute([$newHashedPassword, $userId])) {
        // Return a success message if the update is successful.
        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully.']);
    } else {
        // Return an error if the database update fails.
        echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
    }
} catch (PDOException $e) {
    // Handle potential database errors.
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>
