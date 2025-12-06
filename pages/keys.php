<?php
/**
 * Keys Management Page
 *
 * This page displays a list of keys from the 'tbl_xpert_keys' table.
 * It supports searching, filtering by programme and sequence, and pagination.
 * The page uses JavaScript to perform these actions via AJAX for a smoother user experience.
 *
 * @package    XUM
 * @subpackage Pages
 */

// Include the configuration file for database connection and other settings.
require_once __DIR__ . '/../config.php';

// --- Dynamic SQL Query for Filtering and Searching ---

// Retrieve search and filter values from the GET request, with empty defaults.
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$selectedSequence = isset($_GET['sequence']) ? $_GET['sequence'] : '';
$selectedProgramme = isset($_GET['programme']) ? $_GET['programme'] : '';

// Base SQL query to select key data.
$sql = "SELECT [BBBENU], [BBPROG], [BBLFN3], [BBPA02], [BBPA06], [BBPA07], [BBPA08], [BBPA09], [BBPA10], [BBPA11] FROM dbo.tbl_xpert_keys";
$conditions = []; // Array to hold WHERE clause conditions.
$params = [];     // Array to hold parameters for the prepared statement, preventing SQL injection.

// Add a search condition for the username ('BBBENU') if a search term is provided.
if (!empty($searchTerm)) {
    $conditions[] = "[BBBENU] LIKE ?";
    $params[] = '%' . $searchTerm . '%'; // Use wildcards for partial matching.
}

// Add a filter condition for the sequence ('BBLFN3').
if (!empty($selectedSequence)) {
    $conditions[] = "[BBLFN3] = ?";
    $params[] = $selectedSequence;
}

// Add a filter condition for the programme ('BBPROG').
if (!empty($selectedProgramme)) {
    $conditions[] = "[BBPROG] = ?";
    $params[] = $selectedProgramme;
}

// Combine all conditions into a single WHERE clause if any filters are active.
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

// --- Pagination Logic ---

// Define pagination settings.
$results_per_page = 15;

// Determine the current page from the GET request, ensuring it's a valid integer.
$current_page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($current_page < 1) {
    $current_page = 1; // Prevent page number from being less than 1.
}

// Calculate the offset for the SQL query.
$offset = ($current_page - 1) * $results_per_page;

// --- Get Total Record Count for Pagination ---
// A separate query is run to count the total number of records that match the filters.
$count_sql = "SELECT COUNT(*) FROM dbo.tbl_xpert_keys";
if (count($conditions) > 0) {
    $count_sql .= " WHERE " . implode(' AND ', $conditions);
}

try {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute($params); // Execute with the same filter parameters.
    $total_keys = $count_stmt->fetchColumn();
} catch (PDOException $e) {
    // Log database errors instead of displaying them to the user.
    error_log("Error counting keys: " . $e->getMessage());
    $total_keys = 0; // Default to 0 on error.
}

// Calculate the total number of pages.
$total_pages = $total_keys > 0 ? ceil($total_keys / $results_per_page) : 1;

// --- Final Query Execution ---

// Append the ORDER BY and pagination clauses (OFFSET/FETCH) to the main SQL query.
$sql .= " ORDER BY [BBBENU] ASC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

// Add the pagination values (offset and results per page) to the parameter array.
$params_with_pagination = array_merge($params, [$offset, $results_per_page]);

// Fetch the paginated key data.
try {
    $stmt = $conn->prepare($sql);
    
    // Bind all parameters (filters and pagination) to the prepared statement.
    foreach ($params_with_pagination as $key => $value) {
        // The last two parameters (offset and fetch) must be bound as integers for SQL Server.
        $param_type = ($key >= count($params)) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key + 1, $value, $param_type);
    }
    
    $stmt->execute();
    $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log errors and return an empty array if the query fails.
    error_log("Error fetching keys: " . $e->getMessage());
    $keys = [];
}
?>

<head>
    <!-- Page-specific styles -->
    <style>
        /* Set a max-height for the table container to make it scrollable */
        .table-responsive {
            max-height: 450px;
            overflow-y: auto;
        }
        /* Increase max-height on larger screens */
        @media (min-width: 1200px) {
            .table-responsive {
                max-height: 75vh;
            }
        }
        /* Make the table header sticky so it stays visible during scroll */
        .table-responsive thead th {
            position: -webkit-sticky; /* For Safari */
            position: sticky;
            top: 0;
            background-color: #ffffff; /* Background to prevent text overlap */
            z-index: 1;
            box-shadow: inset 0 -2px 0 #dee2e6; /* Bottom border for the header */
        }
    </style>
</head>

<!-- This container wraps the entire content and is used by JavaScript to refresh the view via AJAX. -->
<div id="key-management-container">
<div class="container-fluid mt-4">
    <!-- Header section with title and export button -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center">
            <h2 class="mb-0 me-3"><?php display_icon('key-fill', 'me-2'); ?>Keys List</h2>
            <?php
            // Build the query string for the export link, preserving the current filters.
            $export_params = http_build_query([
                'search' => $searchTerm,
                'sequence' => $selectedSequence,
                'programme' => $selectedProgramme
            ]);
            ?>
            <!-- Export button that links to the export script with current filters. -->
            <a id="export-link" href="actions/export_keys.php?<?php echo $export_params; ?>" class="btn btn-outline-success rounded-pill shadow-sm">
                <?php display_icon('file-earmark-excel', 'me-2'); ?> Export
            </a>
        </div>
        
        <!-- Search and Filter Form -->
        <!-- This form's submission is handled by JavaScript for AJAX updates. -->
        <form action="index.php" method="GET" class="d-flex align-items-center">
            <input type="hidden" name="page" value="keys">

            <!-- Search input field -->
            <input
                class="form-control me-2"
                type="search"
                name="search"
                placeholder="Search by Username..."
                aria-label="Search"
                value="<?php echo htmlspecialchars($searchTerm); ?>"
                id="key-search-input"
                autocomplete="off">

            <!-- Programme Filter Dropdown -->
            <?php
            // Fetch distinct programme values from the database to populate the dropdown.
            try {
                $programmeStmt = $conn->query("SELECT DISTINCT [BBPROG] FROM dbo.tbl_xpert_keys WHERE [BBPROG] IS NOT NULL ORDER BY [BBPROG] ASC");
                $programmes = $programmeStmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (PDOException $e) {
                $programmes = []; // Default to empty on error.
            }
            ?>
            <select class="form-select me-2" name="programme" style="width: auto;">
                <option value="">All Programmes</option>
                <?php foreach ($programmes as $programme): ?>
                    <option value="<?php echo htmlspecialchars($programme); ?>" <?php if ($selectedProgramme === $programme) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($programme); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Sequence Filter Dropdown -->
            <?php
            // Fetch distinct sequence values to populate the dropdown.
            try {
                $sequenceStmt = $conn->query("SELECT DISTINCT [BBLFN3] FROM dbo.tbl_xpert_keys WHERE [BBLFN3] IS NOT NULL ORDER BY [BBLFN3] ASC");
                $sequences = $sequenceStmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (PDOException $e) {
                $sequences = []; // Default to empty on error.
            }
            ?>
            <select class="form-select me-2" name="sequence" style="width: auto;">
                <option value="">All Sequences</option>
                <?php foreach ($sequences as $sequence): ?>
                    <option value="<?php echo htmlspecialchars($sequence); ?>" <?php if ($selectedSequence === $sequence) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($sequence); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Card container for the table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Table of keys -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th><?php display_icon('person-badge', 'me-2'); ?>Username</th>
                            <th><?php display_icon('grid-1x2', 'me-2'); ?>Programme</th>
                            <th><?php display_icon('list-ol', 'me-2'); ?>Sequence</th>
                            <th>T</th>
                            <th>Key</th>
                            <th>I/O Wh</th>
                            <th>Wh1</th>
                            <th>Wh2</th>
                            <th>I/O Key</th>
                            <th>Key1</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($keys) > 0): // Check if any keys were found ?>
                            <?php foreach ($keys as $key): // Loop through and display each key ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($key['BBBENU']); ?></td>
                                    <td><?php echo htmlspecialchars($key['BBPROG']); ?></td>
                                    <td><?php echo htmlspecialchars($key['BBLFN3']); ?></td>
                                    <td><?php echo htmlspecialchars($key['BBPA02']); ?></td>
                                    <td><?php echo htmlspecialchars($key['BBPA06']); ?></td>
                                    <td><?php echo htmlspecialchars($key['BBPA07']); ?></td>
                                    <td><?php echo htmlspecialchars($key['BBPA08']); ?></td>
                                    <td><?php echo htmlspecialchars($key['BBPA09']); ?></td>
                                    <td><?php echo htmlspecialchars($key['BBPA10']); ?></td>
                                    <td><?php echo htmlspecialchars($key['BBPA11']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: // Display a message if no keys match the criteria ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4"><?php display_icon('exclamation-circle', 'me-2'); ?>No keys found matching your criteria.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination and Record Count -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <!-- Displaying the count of currently shown records -->
                <div class="text-muted">
                    <?php
                    $start_key = $offset + 1;
                    $end_key = min($offset + $results_per_page, $total_keys);
                    if ($total_keys > 0) {
                        echo "Showing {$start_key} to {$end_key} of {$total_keys} keys";
                    }
                    ?>
                </div>
                <?php if ($total_pages > 1): // Only show pagination if there's more than one page ?>
                <nav>
                    <ul class="pagination mb-0">
                        <!-- Previous Page Link -->
                        <li class="page-item <?php if ($current_page <= 1) echo 'disabled'; ?>">
                            <a class="page-link" href="?page=keys&p=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($searchTerm); ?>&sequence=<?php echo urlencode($selectedSequence); ?>&programme=<?php echo urlencode($selectedProgramme); ?>"><?php display_icon('chevron-left'); ?> Previous</a>
                        </li>

                        <?php
                        // This logic creates a pagination control with a limited number of page links visible at a time.
                        $window = 2; // Number of pages to show around the current page.
                        for ($i = 1; $i <= $total_pages; $i++):
                            // Always show the first and last page, and pages within the 'window' of the current page.
                            if ($i == 1 || $i == $total_pages || ($i >= $current_page - $window && $i <= $current_page + $window)):
                        ?>
                            <li class="page-item <?php if ($i == $current_page) echo 'active'; ?>">
                                <a class="page-link" href="?page=keys&p=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>&sequence=<?php echo urlencode($selectedSequence); ?>&programme=<?php echo urlencode($selectedProgramme); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php
                            // Show '...' for gaps in the page numbers.
                            elseif ($i == $current_page - $window - 1 || $i == $current_page + $window + 1):
                        ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php
                            endif;
                        endfor;
                        ?>

                        <!-- Next Page Link -->
                        <li class="page-item <?php if ($current_page >= $total_pages) echo 'disabled'; ?>">
                            <a class="page-link" href="?page=keys&p=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($searchTerm); ?>&sequence=<?php echo urlencode($selectedSequence); ?>&programme=<?php echo urlencode($selectedProgramme); ?>">Next <?php display_icon('chevron-right'); ?></a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>

<script>
/**
 * This script handles the dynamic, AJAX-powered filtering, searching, and pagination
 * for the keys list. It prevents full page reloads for a smoother user experience.
 */
document.addEventListener('DOMContentLoaded', function() {
    // The main container that will be updated with new content from AJAX calls.
    const container = document.getElementById('key-management-container');
    let timeout = null; // Used for debouncing the search input.

    /**
     * Fetches the updated key list from the server.
     * @param {string} url - The URL to fetch, including query parameters for filters and pagination.
     */
    function fetchKeys(url) {
        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' } // Identify the request as AJAX.
        })
        .then(response => response.text())
        .then(html => {
            // Parse the HTML response to extract the new content.
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.getElementById('key-management-container');
            
            if (newContent) {
                // Replace the old content with the new content.
                container.innerHTML = newContent.innerHTML;

                // After updating, re-focus the search input and move the cursor to the end.
                const newSearchInput = document.getElementById('key-search-input');
                if (newSearchInput) {
                    newSearchInput.focus();
                    const len = newSearchInput.value.length;
                    newSearchInput.setSelectionRange(len, len);
                }
                // Re-initialize event listeners on the new content.
                initializeEventListeners();
            }
        })
        .catch(error => console.error('Error fetching keys:', error));
    }

    /**
     * Initializes all necessary event listeners for the search, filter, and pagination controls.
     * This function is called on page load and after every AJAX update.
     */
    function initializeEventListeners() {
        const searchInput = document.getElementById('key-search-input');
        if (searchInput) {
            // Add a debounced input event listener to the search field.
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    const form = searchInput.closest('form');
                    const params = new URLSearchParams(new FormData(form));
                    params.set('p', '1'); // Reset to the first page on a new search.
                    fetchKeys(form.action + '?' + params.toString());
                }, 300); // Wait 300ms after user stops typing.
            });
        }

        // Add event listeners for the filter dropdowns.
        const programmeSelect = document.querySelector('select[name="programme"]');
        if (programmeSelect) {
            programmeSelect.addEventListener('change', function() {
                const form = this.closest('form');
                const params = new URLSearchParams(new FormData(form));
                params.set('p', '1'); // Reset to the first page when a filter changes.
                fetchKeys(form.action + '?' + params.toString());
            });
        }

        const sequenceSelect = document.querySelector('select[name="sequence"]');
        if (sequenceSelect) {
            sequenceSelect.addEventListener('change', function() {
                const form = this.closest('form');
                const params = new URLSearchParams(new FormData(form));
                params.set('p', '1'); // Reset to the first page.
                fetchKeys(form.action + '?' + params.toString());
            });
        }

        // Add click event listeners to all pagination links.
        const paginationLinks = container.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent the default link behavior.
                const url = this.getAttribute('href');
                if (url) fetchKeys(url); // Fetch the content for the clicked page.
            });
        });

        // Prevent the form from submitting in the traditional way.
        const form = container.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
            });
        }
    }

    // Initial call to set up the event listeners when the page first loads.
    initializeEventListeners();
});
</script>