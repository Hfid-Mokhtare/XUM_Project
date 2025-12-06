<?php

/**
 * connection.php
 *
 * This script handles the database connection and provides utility functions.
 * It establishes a connection to the SQL Server database using PDO and defines
 * a helper function for displaying SVG icons.
 */

// --- Database Connection ---
// Load database credentials from the untracked 'config.php' file.
require_once 'config.php';

try {
    // Establish the database connection using the PDO (PHP Data Objects) extension.
    // Using PDO is recommended as it provides a consistent interface for different databases
    // and supports prepared statements to help prevent SQL injection.
    $conn = new PDO("sqlsrv:server=$serverName;Database=$databaseName", $username, $password);

    // Set the PDO error mode to exception.
    // This means that if a database error occurs, PDO will throw a PDOException,
    // which can be caught in a catch block for proper error handling.
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If the connection fails, catch the exception and terminate the script.
    // In a production environment, you should log this error to a file instead of
    // displaying it directly to the user, as it can expose sensitive information.
    error_log("Database Connection Failed: " . $e->getMessage());
    die("Database connection could not be established. Please contact support.");
}

/**
 * Displays an SVG icon from the local assets folder.
 *
 * This is a utility function to easily embed SVG icons into the HTML.
 * It reads the SVG file content and injects it directly into the output.
 *
 * @param string $icon_name The name of the icon file (without the .svg extension).
 * @param string $class Optional CSS classes to add to the root <svg> tag for styling.
 */
function display_icon($icon_name, $class = '')
{
    // Construct the full, reliable path to the icon file using the PROJECT_ROOT constant.
    $icon_path = PROJECT_ROOT . "/assets/icons/{$icon_name}.svg";

    if (file_exists($icon_path)) {
        // Read the content of the SVG file.
        $svg = file_get_contents($icon_path);

        // If a CSS class is provided, add it to the <svg> tag.
        if ($class) {
            $svg = preg_replace('/<svg/', '<svg class="' . htmlspecialchars($class) . '"', $svg, 1);
        }
        echo $svg; // Output the SVG content.
    } else {
        // If the icon file doesn't exist, output an HTML comment for debugging purposes.
        echo "<!-- Icon '{$icon_name}' not found. -->";
    }
}
