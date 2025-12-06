<?php
/**
 * @file update_profile_action.php
 * @brief Handles the server-side logic for updating a user's own profile information.
 *
 * This script allows a logged-in user to update their full name and username.
 * It validates the input, checks for duplicate usernames, and updates both the
 * database and the user's current session data.
 */

session_start();
require_once '../connection.php';

header('Content-Type: application/json');

// --- Security and Validation ---
// Ensure the user is logged in before allowing profile updates.
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to perform this action.']);
    exit();
}

// Restrict access to POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

// Retrieve user ID from the session and profile data from the POST request.
$userId = $_SESSION['user']['ID'];
$fullName = $_POST['fullName'] ?? null;
$userName = $_POST['userName'] ?? null;

// Validate that the required fields are not empty.
if (empty($fullName) || empty($userName)) {
    echo json_encode(['status' => 'error', 'message' => 'Full name and username are required.']);
    exit();
}

// --- Profile Update Logic ---
try {
    // Check if the new username is already taken by another user.
    $stmt = $conn->prepare("SELECT ID FROM dbo.tbl_xpert_application_users WHERE UserName = ? AND ID != ?");
    $stmt->execute([$userName, $userId]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'This username is already taken. Please choose another.']);
        exit();
    }

    // Prepare and execute the statement to update the user's information in the database.
    $updateStmt = $conn->prepare("UPDATE dbo.tbl_xpert_application_users SET FullName = ?, UserName = ? WHERE ID = ?");
    if ($updateStmt->execute([$fullName, $userName, $userId])) {
        // If the update is successful, also update the session data to reflect the changes immediately.
        $_SESSION['user']['FullName'] = $fullName;
        $_SESSION['user']['UserName'] = $userName;
        echo json_encode(['status' => 'success']);
    } else {
        // Return an error if the database update fails.
        echo json_encode(['status' => 'error', 'message' => 'Failed to update profile.']);
    }
} catch (PDOException $e) {
    // Handle potential database errors.
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>
