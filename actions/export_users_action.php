<?php
/**
 * @file export_users_action.php
 * @brief Handles the server-side logic for exporting filtered user data to a CSV file.
 *
 * This script retrieves filter parameters from the URL, constructs a SQL query to fetch
 * the corresponding user data, and generates a CSV file for download.
 */

require_once __DIR__ . '/../config.php';

// --- Data Filtering ---
// Retrieve search and filter parameters from the GET request.
$searchTerm = $_GET['search'] ?? '';
$selectedMenu = $_GET['menu'] ?? '';

// --- Dynamic SQL Query Construction ---
// Base SQL query to select user data.
$sql = "SELECT [User], [Description], [Menu] FROM dbo.tbl_xpert_users";
$conditions = [];
$params = [];

// Add a condition for the search term if provided.
if (!empty($searchTerm)) {
    $conditions[] = "[User] LIKE ?";
    $params[] = '%' . $searchTerm . '%';
}

// Add a condition for the menu filter if provided.
if (!empty($selectedMenu)) {
    $conditions[] = "[Menu] = ?";
    $params[] = $selectedMenu;
}

// Append a WHERE clause to the SQL query if there are any conditions.
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

// Add sorting to the query.
$sql .= " ORDER BY [User] ASC";

try {
    // --- Data Fetching ---
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- CSV File Generation ---
    // Set HTTP headers to trigger a CSV file download.
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=users_export_' . date('Y-m-d') . '.csv');

    // Open a file pointer connected to the PHP output stream.
    $output = fopen('php://output', 'w');

    // Write the column headers to the CSV file.
    fputcsv($output, array('User', 'Description', 'Menu'));

    // Loop through the fetched data and write each row to the CSV file.
    if (count($users) > 0) {
        foreach ($users as $user) {
            fputcsv($output, $user);
        }
    }

    // Close the file pointer and terminate the script.
    fclose($output);
    exit();

} catch (PDOException $e) {
    // Handle potential database errors by logging them and showing a generic error message.
    error_log("Export Error: " . $e->getMessage());
    die("An error occurred during the export. Please check the logs.");
}
?>
