<?php
/**
 * @file delete_user_action.php
 * @brief Handles the server-side logic for deleting a user account.
 *
 * This script is restricted to administrators. It validates the request, ensures admins
 * cannot delete their own accounts, and removes the specified user from the database.
 * It returns JSON responses to the client.
 */

session_start();
require_once '../connection.php';

header('Content-Type: application/json');

// --- Security and Validation ---
// Ensure that only logged-in administrators can access this functionality.
if (!isset($_SESSION['user']) || $_SESSION['user']['Permission'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Permission denied.']);
    exit();
}

// Restrict access to POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

// Retrieve the ID of the user to be deleted from the POST data.
$userId = $_POST['userId'] ?? null;

// Validate that a user ID was provided.
if (empty($userId)) {
    echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
    exit();
}

// Prevent an administrator from deleting their own account.
if ($userId == $_SESSION['user']['ID']) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account.']);
    exit();
}

// --- User Deletion Logic ---
try {
    // Prepare and execute the SQL statement to delete the user.
    $sql = "DELETE FROM dbo.tbl_xpert_application_users WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$userId])) {
        // Check if any rows were affected to confirm the deletion.
        if ($stmt->rowCount() > 0) {
            // Return a success status if the user was deleted.
            echo json_encode(['status' => 'success']);
        } else {
            // Return an error if the user was not found (e.g., already deleted).
            echo json_encode(['status' => 'error', 'message' => 'User not found or already deleted.']);
        }
    } else {
        // Return an error if the database query failed.
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete user.']);
    }
} catch (PDOException $e) {
    // Handle potential database errors.
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>
