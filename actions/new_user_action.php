<?php
/**
 * @file new_user_action.php
 * @brief Handles the server-side logic for creating a new user.
 *
 * This script is restricted to administrators. It validates the submitted user data,
 * hashes the password, and inserts the new user into the database. It also handles
 * the special case of creating the first user, who is automatically assigned admin rights.
 */

session_start();
header('Content-Type: application/json');
require_once '../connection.php';

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// --- Security and Validation ---
// Ensure that only logged-in administrators can create new users.
if (!isset($_SESSION['user']) || $_SESSION['user']['Permission'] !== 'admin') {
    $response['message'] = 'Permission denied.';
    echo json_encode($response);
    exit();
}

// Process the request only if it is a POST request.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize user data from the POST request.
    $fullName = $_POST['fullName'] ?? null;
    $userName = $_POST['userName'] ?? null;
    $password = $_POST['password'] ?? null;
    $permission = $_POST['permission'] ?? 'user'; // Default permission is 'user'.

    // Validate that all required fields are filled.
    if (empty($fullName) || empty($userName) || empty($password)) {
        $response['message'] = 'Please fill in all required fields.';
        echo json_encode($response);
        exit();
    }

    // --- User Creation Logic ---
    // Hash the password for secure storage.
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $dateAssigned = date('Y-m-d H:i:s');

    try {
        // Special handling for the first user: automatically make them an admin.
        $checkStmt = $conn->query("SELECT COUNT(*) FROM dbo.tbl_xpert_application_users");
        if ($checkStmt->fetchColumn() == 0) {
            $permission = 'admin';
        }

        // Prepare and execute the SQL statement to insert the new user.
        $sql = "INSERT INTO dbo.tbl_xpert_application_users (FullName, UserName, Password, Permission, DateAssigned) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute([$fullName, $userName, $hashedPassword, $permission, $dateAssigned])) {
            $response['status'] = 'success';
            $response['message'] = 'User created successfully.';
        } else {
            $response['message'] = 'Failed to create user.';
        }
    } catch (PDOException $e) {
        // Handle database errors, specifically for duplicate usernames.
        if ($e->getCode() == 23000 || strpos($e->getMessage(), 'UNIQUE KEY') !== false) {
            $response['message'] = 'This username is already taken.';
        } else {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Return the final response as a JSON object.
echo json_encode($response);
exit();
?>