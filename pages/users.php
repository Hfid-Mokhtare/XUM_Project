<?php
require_once __DIR__ . '/../config.php';

$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$selectedMenu = isset($_GET['menu']) ? $_GET['menu'] : '';

$sql = "SELECT [User], [Description], [Menu] FROM dbo.tbl_xpert_users";
$conditions = [];
$params = [];

// Add condition for the search term if it exists
if (!empty($searchTerm)) {
    $conditions[] = "[User] LIKE ?";
    $params[] = '%' . $searchTerm . '%';
}

// Add condition for the menu filter if it exists
if (!empty($selectedMenu)) {
    $conditions[] = "[Menu] = ?";
    $params[] = $selectedMenu;
}

// Append the WHERE clause if there are any conditions
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

// --- Pagination Variables ---
$results_per_page = 15; // Number of users to display per page
$current_page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}
$offset = ($current_page - 1) * $results_per_page;

// --- Get Total Number of Records for Pagination ---
// A separate COUNT query is needed with the same filters to calculate the total number of pages.
$count_sql = "SELECT COUNT(*) FROM dbo.tbl_xpert_users";
if (count($conditions) > 0) {
    $count_sql .= " WHERE " . implode(' AND ', $conditions);
}

try {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute($params); // Use the same parameters as the main query
    $total_users = $count_stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error counting users: " . $e->getMessage());
    $total_users = 0;
}

$total_pages = $total_users > 0 ? ceil($total_users / $results_per_page) : 1;

// --- Modify the main query for pagination ---
// The original $sql already has the WHERE clause.
// We add ORDER BY and the OFFSET-FETCH clause for SQL Server.
$sql .= " ORDER BY [User] ASC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

// Add the pagination parameters to the existing params array.
// We must ensure they are treated as integers for the query.
$params_with_pagination = $params;
$params_with_pagination[] = $offset;
$params_with_pagination[] = $results_per_page;

// --- Fetch users from the database ---
try {
    $stmt = $conn->prepare($sql);

    // Bind parameters one by one to ensure correct types, especially for OFFSET and FETCH.
    $param_idx = 1;
    foreach ($params as $param) {
        $stmt->bindValue($param_idx++, $param);
    }
    $stmt->bindValue($param_idx++, $offset, PDO::PARAM_INT);
    $stmt->bindValue($param_idx, $results_per_page, PDO::PARAM_INT);

    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = []; // Default to an empty array on error
}
?>

<head>
    <style>
        .table-responsive {
            /* 1. Set a fixed height and enable scrolling */
            max-height: 450px;
            /* Adjust as needed */
            overflow-y: auto;
        }

        /* For larger screens, make the table taller */
        @media (min-width: 1200px) {
            .table-responsive {
                max-height: 75vh; /* 75% of the viewport height */
            }
        }

        .table-responsive thead th {
            /* 2. Make the header cells stick to the top */
            position: -webkit-sticky;
            /* For Safari */
            position: sticky;
            top: 0;

            /* 3. Give it a background color so scrolling content doesn't show through */
            background-color: #ffffff;
            /* Or match your card's background */

            /* 4. Ensure it stays above the scrolling content */
            z-index: 1;

            /* Optional: Add a subtle border to separate it from the content */
            box-shadow: inset 0 -2px 0 #dee2e6;
            /* Bootstrap's default table border color */
        }
    </style>
</head>



<div id="user-management-container"> <!-- Wrapper for AJAX updates -->
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center">
            <h2 class="mb-0 me-3"><?php display_icon('people-fill', 'me-2'); ?>Users List</h2>
            <!-- Export Button -->
            <?php if ($total_users > 0): ?>
                <a id="export-button" href="actions/export_users_action.php?search=<?php echo urlencode($searchTerm); ?>&menu=<?php echo urlencode($selectedMenu); ?>" class="btn btn-outline-success rounded-pill shadow-sm">
                    <?php display_icon('file-earmark-excel', 'me-2'); ?> Export
                </a>
            <?php endif; ?>
        </div>

        <!-- Search and Filter Form -->
            <form action="index.php" method="GET" class="d-flex align-items-center mb-0">
                <input type="hidden" name="page" value="users">

                <!-- Search Input -->
                <input
                    class="form-control me-2"
                    type="search"
                    name="search"
                    placeholder="Search by username..."
                    aria-label="Search"
                    value="<?php echo htmlspecialchars($searchTerm); ?>"
                    id="user-search-input"
                    autocomplete="off">

                <!-- Menu Filter Dropdown -->
                <?php
                try {
                    $menuStmt = $conn->query("SELECT DISTINCT [Menu] FROM dbo.tbl_xpert_users WHERE [Menu] IS NOT NULL ORDER BY [Menu] ASC");
                    $menus = $menuStmt->fetchAll(PDO::FETCH_COLUMN);
                } catch (PDOException $e) {
                    $menus = []; // Default to empty array on error
                    error_log("Error fetching menus: " . $e->getMessage());
                }
                ?>
                <select class="form-select" name="menu" style="width: auto;">
                    <option value="">All Menus</option>
                    <?php foreach ($menus as $menu): ?>
                        <option value="<?php echo htmlspecialchars($menu); ?>" <?php if ($selectedMenu === $menu) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($menu); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>
<div id="user-table-container">
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th><?php display_icon('person-badge', 'me-2'); ?>Username</th>
                            <th><?php display_icon('card-text', 'me-2'); ?>Description</th>
                            <th><?php display_icon('list-stars', 'me-2'); ?>Menu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['User']); ?></td>
                                    <td><?php echo htmlspecialchars($user['Description']); ?></td>
                                    <td><?php echo htmlspecialchars($user['Menu']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    <?php display_icon('exclamation-circle', 'me-2'); ?>
                                    No users found matching your criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    <?php
                    $start_user = $offset + 1;
                    $end_user = min($offset + $results_per_page, $total_users);
                    if ($total_users > 0) {
                        echo "Showing {$start_user} to {$end_user} of {$total_users} users";
                    }
                    ?>
                </div>
                <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination mb-0">
                        <!-- Previous Page Link -->
                        <li class="page-item <?php if ($current_page <= 1) echo 'disabled'; ?>">
                            <a class="page-link" href="?page=users&p=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($searchTerm); ?>&menu=<?php echo urlencode($selectedMenu); ?>"><?php display_icon('chevron-left'); ?> Previous</a>
                        </li>

                        <?php
                        // Pagination Logic: show a few pages around the current one
                        $window = 2; // Number of pages to show on each side of the current page
                        for ($i = 1; $i <= $total_pages; $i++):
                            if ($i == 1 || $i == $total_pages || ($i >= $current_page - $window && $i <= $current_page + $window)):
                        ?>
                            <li class="page-item <?php if ($i == $current_page) echo 'active'; ?>">
                                <a class="page-link" href="?page=users&p=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>&menu=<?php echo urlencode($selectedMenu); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php
                            elseif ($i == $current_page - $window - 1 || $i == $current_page + $window + 1):
                        ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php
                            endif;
                        endfor;
                        ?>

                        <!-- Next Page Link -->
                        <li class="page-item <?php if ($current_page >= $total_pages) echo 'disabled'; ?>">
                            <a class="page-link" href="?page=users&p=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($searchTerm); ?>&menu=<?php echo urlencode($selectedMenu); ?>">Next <?php display_icon('chevron-right'); ?></a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div> <!-- This closes user-table-container -->
</div> <!-- This closes the container-fluid div -->
</div> <!-- This closes the user-management-container -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userManagementContainer = document.getElementById('user-management-container');
    const searchInput = document.getElementById('user-search-input');
    const menuSelect = document.querySelector('select[name="menu"]');
    const form = userManagementContainer.querySelector('form');
    let timeout = null;

    // --- Function to update the export link --- 
    function updateExportLink() {
        const exportButton = document.getElementById('export-button');
        if (exportButton) {
            const params = new URLSearchParams(new FormData(form));
            params.delete('page'); // Remove the 'page' param as it's not needed for export
            exportButton.href = `actions/export_users_action.php?${params.toString()}`;
        }
    }

    // --- Function to fetch and update only the table content ---
    function fetchUsers(url) {
        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTableContent = doc.getElementById('user-table-container');
            const currentTableContainer = document.getElementById('user-table-container');
            
            if (newTableContent && currentTableContainer) {
                currentTableContainer.innerHTML = newTableContent.innerHTML;
                reinitializePaginationListeners(); // Re-attach listeners to the new pagination links
            }
        })
        .catch(error => console.error('Error fetching users:', error));
    }

    // --- Function to re-initialize listeners for pagination ---
    function reinitializePaginationListeners() {
        const paginationLinks = document.querySelectorAll('#user-table-container .pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                if (!link.closest('.page-item').classList.contains('disabled')) {
                    fetchUsers(link.href);
                }
            });
        });
    }

    // --- Initialize listeners that don't get replaced ---
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const params = new URLSearchParams(new FormData(form));
                params.set('p', '1'); // Reset to first page on new search
                fetchUsers(`index.php?${params.toString()}`);
                updateExportLink();
            }, 300); // 300ms delay
        });
    }

    if (menuSelect) {
        menuSelect.addEventListener('change', function() {
            const params = new URLSearchParams(new FormData(form));
            params.set('p', '1');
            fetchUsers(`index.php?${params.toString()}`);
            updateExportLink(); // Update export link on filter change
        });
    }

    if (form) {
        form.addEventListener('submit', e => e.preventDefault());
    }

    // Initial call for pagination links
    reinitializePaginationListeners();
});
</script>