<?php
/**
 * @file export_keys.php
 * @brief Handles the export of filtered key data to a CSV file.
 *
 * This script retrieves search and filter parameters from the URL, constructs a SQL query
 * to fetch the relevant data from the database, and then generates a CSV file for download.
 */

// Include the database connection file.
require_once __DIR__ . '/../connection.php';

// --- Data Filtering ---
// Retrieve search and filter parameters from the GET request, matching those on the keys page.
$searchTerm = $_GET['search'] ?? '';
$selectedSequence = $_GET['sequence'] ?? '';
$selectedProgramme = $_GET['programme'] ?? '';

// --- Dynamic SQL Query Construction ---
// Base SQL query to select key data.
$sql = "SELECT [BBBENU], [BBPROG], [BBLFN3], [BBPA02], [BBPA06], [BBPA07], [BBPA08], [BBPA09], [BBPA10], [BBPA11] FROM dbo.tbl_xpert_keys";
$conditions = [];
$params = [];

// Append conditions to the query based on the provided filters.
if (!empty($searchTerm)) {
    $conditions[] = "[BBBENU] LIKE ?";
    $params[] = '%' . $searchTerm . '%';
}
if (!empty($selectedSequence)) {
    $conditions[] = "[BBLFN3] = ?";
    $params[] = $selectedSequence;
}
if (!empty($selectedProgramme)) {
    $conditions[] = "[BBPROG] = ?";
    $params[] = $selectedProgramme;
}

// Combine conditions using 'AND' if any are present.
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

// Add sorting to the query.
$sql .= " ORDER BY [BBBENU] ASC, [BBPROG] ASC, [BBLFN3] ASC";

// --- Data Fetching ---
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Terminate script with an error message if the query fails.
    die("Error fetching data for export: " . $e->getMessage());
}

// --- CSV File Generation ---
// Set HTTP headers to prompt the user to download the file.
$filename = "keys_export_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"" . $filename . "\"");

// Open a file pointer to the PHP output stream, allowing us to write directly to the response body.
$output = fopen('php://output', 'w');

// Write the column headers to the CSV file.
fputcsv($output, ['Username', 'Programme', 'Sequence', 'PA02', 'PA06', 'PA07', 'PA08', 'PA09', 'PA10', 'PA11']);

// Loop through the fetched data and write each row to the CSV file.
if (count($keys) > 0) {
    foreach ($keys as $key) {
        fputcsv($output, $key);
    }
}

// Close the file pointer and terminate the script.
fclose($output);
exit();
