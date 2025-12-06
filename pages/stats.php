<?php
/**
 * stats.php
 *
 * This page serves as the main dashboard, displaying key statistics about the application.
 * It fetches data from various tables to provide an overview of user counts, key assignments,
 * and breakdowns by different categories. The data is then visualized using Chart.js.
 *
 * PHP Dependencies: config.php for database connection.
 * JS Dependencies: Chart.js for rendering charts.
 */

require_once __DIR__ . '/../config.php';

// --- Initialize variables to hold the statistics ---
$app_user_count = 0;
$xpert_user_count = 0;
$key_count = 0;
$permissions_breakdown = [];
$programme_breakdown = [];
$menu_breakdown = [];

try {
    // --- Fetch data from the database ---

    // 1. Get the total count of application users.
    $stmt = $conn->query("SELECT COUNT(*) FROM dbo.tbl_xpert_application_users");
    $app_user_count = $stmt->fetchColumn();

    // 2. Get the total count of Xpert users.
    $stmt = $conn->query("SELECT COUNT(*) FROM dbo.tbl_xpert_users");
    $xpert_user_count = $stmt->fetchColumn();

    // 3. Get the total count of assigned keys.
    $stmt = $conn->query("SELECT COUNT(*) FROM dbo.tbl_xpert_keys");
    $key_count = $stmt->fetchColumn();

    // 4. Get the breakdown of application users by their permission level.
    $stmt = $conn->query("SELECT [Permission], COUNT(*) as count FROM dbo.tbl_xpert_application_users GROUP BY [Permission]");
    $permissions_breakdown = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Fetches into an associative array [Permission => count].

    // 5. Get the top 10 programmes with the most assigned keys.
    $stmt = $conn->query("SELECT TOP 10 [BBPROG] as Programme, COUNT(*) as count FROM dbo.tbl_xpert_keys WHERE [BBPROG] IS NOT NULL AND [BBPROG] <> '' GROUP BY [BBPROG] ORDER BY count DESC");
    $programme_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Get the top 10 menus with the most users.
    $stmt = $conn->query("SELECT TOP 10 [Menu], COUNT(*) as count FROM dbo.tbl_xpert_users WHERE [Menu] IS NOT NULL AND [Menu] <> '' GROUP BY [Menu] ORDER BY count DESC");
    $menu_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If a database error occurs, store the message to display to the user.
    $error_message = "Database error: " . $e->getMessage();
}

// --- Prepare data for Chart.js by encoding it into JSON format ---
$permission_labels = json_encode(array_keys($permissions_breakdown));
$permission_data = json_encode(array_values($permissions_breakdown));

$programme_labels = json_encode(array_column($programme_breakdown, 'Programme'));
$programme_data = json_encode(array_column($programme_breakdown, 'count'));

$menu_labels = json_encode(array_column($menu_breakdown, 'Menu'));
$menu_data = json_encode(array_column($menu_breakdown, 'count'));

?>

<style>
    .stats-card {
        border: none;
        border-radius: 0.75rem; /* Slightly smaller radius */
        color: white;
        padding: 1.25rem; /* Reduced padding */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        min-height: 130px; /* Specific min-height */
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    .stats-card .icon {
        font-size: 2.5rem; /* Smaller icon */
        opacity: 0.7;
    }
    .stats-card .stat-title {
        font-size: 0.9rem; /* Smaller title */
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    .stats-card .stat-number {
        font-size: 2rem; /* Smaller number */
        font-weight: 700;
    }
    .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
    .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); }
    .bg-gradient-info { background: linear-gradient(45deg, #36b9cc, #2a96a5); }

    .chart-container {
        background: #fff;
        padding: 1.5rem; /* Reduced padding */
        border-radius: 0.75rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        height: 350px; /* Fixed height for chart containers */
        position: relative;
    }
</style>

<!-- Main container for the dashboard content -->
<div class="container-fluid py-4">
    <h1 class="mb-4">Application Dashboard</h1>

    <?php if (isset($error_message)): // Display an error message if the database query failed ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php else: // Otherwise, display the dashboard content ?>
        
        <!-- Top-level metric cards for quick overview -->
        <div class="row mb-5">
            <!-- Application Users Card -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="stats-card bg-gradient-primary">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="stat-title text-uppercase">Application Users</div>
                            <div class="stat-number"><?php echo number_format($app_user_count); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people-fill icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Xpert Users Card -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="stats-card bg-gradient-success">
                     <div class="row align-items-center">
                        <div class="col">
                            <div class="stat-title text-uppercase">Xpert Users</div>
                            <div class="stat-number"><?php echo number_format($xpert_user_count); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-check-fill icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Assigned Keys Card -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="stats-card bg-gradient-info">
                     <div class="row align-items-center">
                        <div class="col">
                            <div class="stat-title text-uppercase">Assigned Keys</div>
                            <div class="stat-number"><?php echo number_format($key_count); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-key-fill icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row">
            <!-- Users by Permission Chart -->
            <div class="col-lg-4 mb-4">
                <div class="chart-container">
                     <h5 class="mb-3 fw-bold text-secondary">Users by Permission</h5>
                    <canvas id="permissionChart"></canvas>
                </div>
            </div>
            <!-- Top Menus Chart -->
            <div class="col-lg-8 mb-4">
                <div class="chart-container">
                    <h5 class="mb-3 fw-bold text-secondary">Top 10 Menus by User Count</h5>
                    <canvas id="menuChart"></canvas>
                </div>
            </div>
        </div>
        <div class="row">
             <!-- Top Programmes Chart -->
             <div class="col-lg-12 mb-4">
                <div class="chart-container">
                     <h5 class="mb-3 fw-bold text-secondary">Top 10 Programmes by Key Count</h5>
                    <canvas id="programmeChart"></canvas>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
/**
 * This script initializes the charts on the dashboard using Chart.js.
 * It waits for the DOM to be fully loaded, then creates three charts using the
 * data that was fetched and JSON-encoded by the PHP script.
 */
document.addEventListener('DOMContentLoaded', function() {
    // First, check if Chart.js is loaded to avoid errors.
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded');
        return;
    }

    /**
     * A helper function to provide a consistent color palette for the charts.
     * @param {number} num - The number of colors to return.
     * @returns {string[]} An array of color hex codes.
     */
    const getChartColors = (num) => {
        const baseColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69'];
        return baseColors.slice(0, num);
    };

    // 1. Initialize the Permissions Donut Chart
    const permissionCtx = document.getElementById('permissionChart')?.getContext('2d');
    if (permissionCtx) {
        new Chart(permissionCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo $permission_labels; ?>, // Labels from PHP
                datasets: [{
                    data: <?php echo $permission_data; ?>, // Data from PHP
                    backgroundColor: getChartColors(<?php echo count($permissions_breakdown); ?>),
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }, // Display legend at the bottom
                cutout: '70%' // Makes it a donut chart
            }
        });
    }

    // 2. Initialize the Menus Bar Chart
    const menuCtx = document.getElementById('menuChart')?.getContext('2d');
    if (menuCtx) {
        new Chart(menuCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $menu_labels; ?>, // Labels from PHP
                datasets: [{
                    label: 'User Count',
                    data: <?php echo $menu_data; ?>, // Data from PHP
                    backgroundColor: '#4e73df'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }, // Hide legend for bar charts
                scales: { y: { beginAtZero: true } } // Start Y-axis at 0
            }
        });
    }

    // 3. Initialize the Programmes Bar Chart
    const programmeCtx = document.getElementById('programmeChart')?.getContext('2d');
    if (programmeCtx) {
        new Chart(programmeCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $programme_labels; ?>, // Labels from PHP
                datasets: [{
                    label: 'Key Count',
                    data: <?php echo $programme_data; ?>, // Data from PHP
                    backgroundColor: '#1cc88a'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }
});
</script>
