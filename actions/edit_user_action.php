<?php
/**
 * @file edit_user_action.php
 * @brief Handles the server-side logic for updating user details.
 *
 * This script is restricted to administrators. It validates the incoming data,
 * updates the user's full name and permission level, and includes a safeguard
 * to prevent the last admin from being demoted.
 */

session_start();
require_once '../connection.php';

header('Content-Type: application/json');

// --- Security and Validation ---
// Ensure that only logged-in administrators can perform this action.
if (!isset($_SESSION['user']) || $_SESSION['user']['Permission'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Permission denied.']);
    exit();
}

// Restrict access to POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

// Retrieve user data from the POST request.
$userId = $_POST['userId'] ?? null;
$fullName = $_POST['fullName'] ?? null;
$permission = $_POST['permission'] ?? null;

// Validate that all required fields are provided.
if (empty($userId) || empty($fullName) || empty($permission)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit();
}

// --- Safeguard for Last Administrator ---
// Prevent an admin from changing their own permission if they are the only admin.
if ($userId == $_SESSION['user']['ID'] && $permission !== 'admin') {
    try {
        // Count the number of administrators in the system.
        $stmt = $conn->prepare("SELECT COUNT(*) FROM dbo.tbl_xpert_application_users WHERE Permission = 'admin'");
        $stmt->execute();
        $adminCount = $stmt->fetchColumn();
        // If there is only one admin, block the demotion.
        if ($adminCount <= 1) {
            echo json_encode(['status' => 'error', 'message' => 'You cannot demote the only administrator.']);
            exit();
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error checking admin count.']);
        exit();
    }
}

// --- User Update Logic ---
try {
    // Prepare and execute the SQL statement to update the user's details.
    $sql = "UPDATE dbo.tbl_xpert_application_users SET FullName = ?, Permission = ? WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$fullName, $permission, $userId])) {
        // Return a success status on successful update.
        echo json_encode(['status' => 'success']);
    } else {
        // Return an error if the update query fails.
        echo json_encode(['status' => 'error', 'message' => 'Failed to update user.']);
    }
} catch (PDOException $e) {
    // Handle potential database errors.
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>
