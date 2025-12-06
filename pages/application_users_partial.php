<?php
/**
 * Application Users Partial
 *
 * This script is loaded via AJAX into the Settings page to display a list of application users.
 * It handles searching, filtering by permission and date, and pagination.
 * It is not intended to be accessed directly.
 *
 * @package    XUM
 * @subpackage Pages
 */

// --- AJAX Request Validation ---
// Ensures that the script is only accessed via an XMLHttpRequest (AJAX).
// This prevents direct URL access to the partial file.
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    // If not an AJAX request, terminate the script with an error message.
    die('Direct access is not allowed.');
}

// --- Session and Configuration ---
// Start a session if one doesn't already exist to access session variables (e.g., user permissions).
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the configuration file which contains database connection and other settings.
require_once __DIR__ . '/../config.php';

// --- Dynamic SQL Query for Filtering and Searching ---

// Retrieve search and filter values from the GET request, providing default empty values.
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$selectedPermission = isset($_GET['permission']) ? $_GET['permission'] : '';
$selectedDateFilter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';

// The base SQL query to select users.
$sql = "SELECT [ID], [FullName], [UserName], [Permission], [DateAssigned] FROM dbo.tbl_xpert_application_users";
$conditions = []; // Holds WHERE clause conditions.
$params = [];     // Holds parameters for the prepared statement to prevent SQL injection.

// Add a search condition for the username if a search term is provided.
if (!empty($searchTerm)) {
    $conditions[] = "[UserName] LIKE ?";
    $params[] = '%' . $searchTerm . '%'; // Use wildcards for a partial match.
}

// Add a filter condition for user permission if one is selected.
if (!empty($selectedPermission)) {
    $conditions[] = "[Permission] = ?";
    $params[] = $selectedPermission;
}

// Add a date range condition based on the selected date filter.
if (!empty($selectedDateFilter)) {
    switch ($selectedDateFilter) {
        case 'today':
            $conditions[] = "[DateAssigned] >= CAST(GETDATE() AS DATE)";
            break;
        case 'last7':
            $conditions[] = "[DateAssigned] >= DATEADD(day, -7, GETDATE())";
            break;
        case 'last30':
            $conditions[] = "[DateAssigned] >= DATEADD(day, -30, GETDATE())";
            break;
    }
}

// Combine all conditions into a single WHERE clause if any filters are active.
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

// --- Pagination Logic ---

// Create a COUNT query to get the total number of records matching the filters.
$countSql = str_replace("SELECT [ID], [FullName], [UserName], [Permission], [DateAssigned]", "SELECT COUNT(*)", $sql);
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params); // Execute with the same filter parameters.
$total_records = $countStmt->fetchColumn();

// Define pagination settings.
$records_per_page = 15;
$total_pages = ceil($total_records / $records_per_page); // Calculate total pages.

// Determine the current page from the GET request, with validation.
$current_page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1; // Ensure current page is not less than 1.
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages; // Ensure current page does not exceed total pages.
}

// Calculate the offset for the SQL query.
$offset = ($current_page - 1) * $records_per_page;
if ($offset < 0) $offset = 0; // Ensure offset is not negative.

// --- Final Query Execution ---

// Append the ORDER BY and pagination clauses to the main SQL query.
// This uses OFFSET and FETCH for modern, efficient pagination in SQL Server.
$sql .= " ORDER BY [UserName] ASC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

$stmt = $conn->prepare($sql);

// Bind the filter parameters first.
$paramIndex = 1;
foreach ($params as $param) {
    $stmt->bindValue($paramIndex, $param);
    $paramIndex++;
}

// Bind the pagination parameters (offset and records per page).
// These must be bound as integers for SQL Server.
$stmt->bindValue($paramIndex, (int)$offset, PDO::PARAM_INT);
$stmt->bindValue($paramIndex + 1, (int)$records_per_page, PDO::PARAM_INT);

// Execute the final query and fetch all matching user records.
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- Search and Filter Form -->
<!-- This form submits back to this same script. The JavaScript on the Settings page intercepts the submission -->
<!-- and reloads the user list container with the form parameters appended to the URL. -->
<form id="user-search-form" action="pages/application_users_partial.php" method="GET" class="d-flex mb-3">
    <!-- Search input for username -->
    <input type="text" class="form-control me-2" name="search" placeholder="Search by Username..." value="<?php echo htmlspecialchars($searchTerm); ?>">

    <!-- Dropdown to filter by permission -->
    <select class="form-select me-2" name="permission" style="width: auto;">
        <option value="">All Permissions</option>
        <option value="admin" <?php if ($selectedPermission === 'admin') echo 'selected'; ?>>Admin</option>
        <option value="user" <?php if ($selectedPermission === 'user') echo 'selected'; ?>>User</option>
    </select>

    <!-- Dropdown to filter by date assigned -->
    <select class="form-select me-2" name="date_filter" style="width: auto;">
        <option value="">All Time</option>
        <option value="today" <?php if ($selectedDateFilter === 'today') echo 'selected'; ?>>Today</option>
        <option value="last7" <?php if ($selectedDateFilter === 'last7') echo 'selected'; ?>>Last 7 Days</option>
        <option value="last30" <?php if ($selectedDateFilter === 'last30') echo 'selected'; ?>>Last 30 Days</option>
    </select>
</form>

<!-- Users Table -->
<!-- The table is responsive and has a maximum height with vertical scrolling. -->
<div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
    <table class="table table-striped table-hover">
        <!-- The table header is sticky, so it stays visible while scrolling. -->
        <thead class="table-dark sticky-top">
            <tr>
                <th><?php display_icon('person-lines-fill', 'me-2'); ?>Full Name</th>
                <th><?php display_icon('person-badge', 'me-2'); ?>Username</th>
                <th><?php display_icon('shield-check', 'me-2'); ?>Permission</th>
                <th><?php display_icon('calendar-plus', 'me-2'); ?>Date Assigned</th>
                <th><?php display_icon('gear', 'me-2'); ?>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($users) > 0): // Check if there are any users to display ?>
                <?php foreach ($users as $user): // Loop through each user and create a table row ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['FullName']); ?></td>
                        <td><?php echo htmlspecialchars($user['UserName']); ?></td>
                        <td>
                            <!-- Display a colored badge based on the user's permission level. -->
                            <span class="badge <?php echo $user['Permission'] === 'admin' ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo htmlspecialchars(ucfirst($user['Permission'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                                // Format the DateAssigned field for display.
                                if (!empty($user['DateAssigned'])) {
                                    try {
                                        $date = new DateTime($user['DateAssigned']);
                                        echo $date->format('Y-m-d H:i:s');
                                    } catch (Exception $e) {
                                        // If the date format is invalid, display the raw value as a fallback.
                                        echo htmlspecialchars($user['DateAssigned']);
                                    }
                                } else {
                                    echo 'N/A';
                                }
                            ?>
                        </td>
                        <td>
                            <!-- Edit User Button -->
                            <!-- This button triggers the 'Edit User' modal. -->
                            <!-- Data attributes are used to pass the user's current data to the modal. -->
                            <button class="btn btn-sm btn-outline-primary me-1 edit-user-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editUserModal"
                                    data-user-id="<?php echo $user['ID']; ?>"
                                    data-full-name="<?php echo htmlspecialchars($user['FullName']); ?>"
                                    data-user-name="<?php echo htmlspecialchars($user['UserName']); ?>"
                                    data-permission="<?php echo htmlspecialchars($user['Permission']); ?>">
                                <?php display_icon('pencil-square', 'me-1'); ?> Edit
                            </button>
                            <!-- Delete User Button -->
                            <!-- This button triggers the 'Delete User' modal. -->
                            <!-- The user ID is passed via a data attribute. -->
                            <button class="btn btn-sm btn-outline-danger delete-user-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteUserModal"
                                    data-user-id="<?php echo $user['ID']; ?>">
                                <?php display_icon('trash', 'me-1'); ?> Delete
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: // If no users are found, display a message. ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4"><?php display_icon('info-circle', 'me-2'); ?>No users found matching your criteria.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination Controls -->
<!-- The pagination component is only displayed if there is more than one page of results. -->
<?php if ($total_pages > 1): ?>
<nav class="d-flex justify-content-center mt-3">
    <ul class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): // Loop through all pages and create a page link ?>
            <li class="page-item <?php if ($i == $current_page) echo 'active'; // Highlight the current page ?>">
                <!-- The link includes all current search and filter parameters to maintain state across pages. -->
                <a class="page-link" href="?p=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>&permission=<?php echo urlencode($selectedPermission); ?>&date_filter=<?php echo urlencode($selectedDateFilter); ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
