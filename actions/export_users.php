
<?php
/**
 * @file export_users.php
 * @brief Handles the export of filtered user data to a CSV file.
 *
 * This script retrieves search and filter parameters from the URL, constructs a SQL query
 * to fetch the relevant user data from the database, and then generates a CSV file for download.
 */

// Include the database connection file.
require_once __DIR__ . '/../connection.php';

// --- Data Filtering ---
// Retrieve search and filter parameters from the GET request.
$searchTerm = $_GET['search'] ?? '';
$selectedMenu = $_GET['menu'] ?? '';

// --- Dynamic SQL Query Construction ---
// Base SQL query to select user data.
$sql = "SELECT [User], [Description], [Menu] FROM dbo.tbl_xpert_users";
$conditions = [];
$params = [];

// Append conditions to the query based on the provided filters.
if (!empty($searchTerm)) {
    $conditions[] = "[User] LIKE ?";
    $params[] = '%' . $searchTerm . '%';
}
if (!empty($selectedMenu)) {
    $conditions[] = "[Menu] = ?";
    $params[] = $selectedMenu;
}

// Combine conditions using 'AND' if any are present.
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

// Add sorting to the query.
$sql .= " ORDER BY [User] ASC";

// --- Data Fetching ---
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Terminate script with an error message if the query fails.
    die("Error fetching data for export: " . $e->getMessage());
}

// --- CSV File Generation ---
// Set HTTP headers to prompt the user to download the file.
$filename = "users_export_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open a file pointer to the PHP output stream.
$output = fopen('php://output', 'w');

// Write the column headers to the CSV file.
fputcsv($output, ['Username', 'Description', 'Menu']);

// Loop through the fetched data and write each row to the CSV file.
if (count($users) > 0) {
    foreach ($users as $user) {
        fputcsv($output, $user);
    }
}

// Close the file pointer and terminate the script.
fclose($output);
exit();
