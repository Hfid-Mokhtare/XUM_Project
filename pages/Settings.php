<?php
/**
 * @file Settings.php
 * @brief User settings and profile management page.
 *
 * This page provides users with the ability to view and edit their profile information
 * and change their password. For users with 'admin' permissions, it also includes
 * a comprehensive panel for managing all application users, including adding,
 * editing, and deleting user accounts. The user list is loaded dynamically via AJAX.
 */

require_once __DIR__ . '/../config.php';

// --- Session and Authentication ---
// Redirect to the login page if the user is not authenticated.
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}

// Get the current user's data from the session.
$currentUser = $_SESSION['user'];

?>

<!-- =================================== -->
<!-- User Profile Section                -->
<!-- =================================== -->
<div class="container mt-4">
    <div class="row gy-4">
        <div class="col-lg-3 mb-3">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header">
                    <h4 class="mb-0"><?php display_icon('person-circle', 'me-2'); ?>User Profile</h4>
                </div>
                <div class="card-body">
                    <!-- Display current user's full name and username -->
                    <div class="mb-3">
                        <label class="form-label fw-bold"><strong>Full Name:</strong></label>
                        <p class="form-control-plaintext bg-light p-2 rounded"><?php echo htmlspecialchars($currentUser['FullName']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><strong>Username:</strong></label>
                        <p class="form-control-plaintext bg-light p-2 rounded"><?php echo htmlspecialchars($currentUser['UserName']); ?></p>
                    </div>
                    <!-- Buttons to trigger profile edit and password change modals -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <?php display_icon('person-gear', 'me-2'); ?> Edit Profile
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <?php display_icon('key', 'me-2'); ?> Change Password
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================== -->
        <!-- Edit Profile Modal                -->
        <!-- =================================== -->
        <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editProfileModalLabel"><?php display_icon('pencil-square', 'me-2'); ?>Edit Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="edit-profile-form">
                            <div id="edit-profile-error" class="alert alert-danger d-none"></div>
                            <div class="mb-3">
                                <label for="profileFullName" class="form-label fw-bold">Full Name</label>
                                <input type="text" class="form-control" id="profileFullName" name="fullName" value="<?php echo htmlspecialchars($currentUser['FullName']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="profileUserName" class="form-label fw-bold">Username</label>
                                <input type="text" class="form-control" id="profileUserName" name="userName" value="<?php echo htmlspecialchars($currentUser['UserName']); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================== -->
        <!-- Change Password Modal             -->
        <!-- =================================== -->
        <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changePasswordModalLabel"><?php display_icon('key-fill', 'me-2'); ?>Change Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="change-password-form">
                            <div id="change-password-error" class="alert alert-danger d-none"></div>
                            <div id="change-password-success" class="alert alert-success d-none"></div>
                            <div class="mb-3">
                                <label for="currentPassword" class="form-label fw-bold">Current Password</label>
                                <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                            </div>
                            <div class="mb-3">
                                <label for="newPassword" class="form-label fw-bold">New Password</label>
                                <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirmNewPassword" class="form-label fw-bold">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirmNewPassword" name="confirmNewPassword" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================= -->
        <!-- Application Users Section (Admin only)    -->
        <!-- ============================================= -->
        <div class="col-lg-9">
            <?php if ($currentUser['Permission'] === 'admin'): ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><?php display_icon('people-fill', 'me-2'); ?>Application Users</h4>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <?php display_icon('plus-circle', 'me-2'); ?> Add New User
                        </button>
                    </div>
                    <!-- Success message display area -->
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_GET['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <!-- Container where the dynamic user list is loaded via AJAX -->
                        <div id="users-list-container">
                            <p class="text-center">Loading users...</p>
                        </div>
                    </div>
                </div>

                <!-- Add User Modal -->
                <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addUserModalLabel"><?php display_icon('person-plus-fill', 'me-2'); ?>Add New User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="add-user-form">
                                    <div id="add-user-error" class="alert alert-danger d-none"></div>
                                    <div class="mb-3">
                                        <label for="fullName" class="form-label fw-bold">Full Name</label>
                                        <input type="text" class="form-control" id="fullName" name="fullName" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="userName" class="form-label fw-bold">Username</label>
                                        <input type="text" class="form-control" id="userName" name="userName" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label fw-bold">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="permission" class="form-label fw-bold">Permission</label>
                                        <select class="form-select" id="permission" name="permission" required>
                                            <option value="user" selected>User</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                        Create User
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit User Modal -->
                <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="edit-user-form">
                                    <input type="hidden" id="editUserId" name="userId">
                                    <div id="edit-user-error" class="alert alert-danger d-none"></div>
                                    <div class="mb-3">
                                        <label for="editFullName" class="form-label fw-bold">Full Name</label>
                                        <input type="text" class="form-control" id="editFullName" name="fullName" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="editUserName" class="form-label fw-bold">Username</label>
                                        <input type="text" class="form-control" id="editUserName" name="userName" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="editPermission" class="form-label fw-bold">Permission</label>
                                        <select class="form-select" id="editPermission" name="permission" required>
                                            <option value="user">User</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                        Save Changes
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete User Modal -->
                <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteUserModalLabel"><?php display_icon('exclamation-triangle-fill', 'me-2'); ?>Confirm Deletion</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                                <input type="hidden" id="deleteUserId">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" id="confirm-delete-btn" class="btn btn-danger">
                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    <?php display_icon('trash-fill', 'me-2'); ?>Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Message shown to non-admin users -->
                <div class="alert alert-info" role="alert">
                    You do not have permission to manage application users.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Load Bootstrap JS for modal functionality -->
<script src="assets/js/bootstrap.bundle.min.js"></script>

<?php if ($currentUser['Permission'] === 'admin'): ?>
    <script>
        /**
         * This script handles all client-side interactions for the Settings page,
         * including profile updates, password changes, and (for admins) user management.
         */
        document.addEventListener('DOMContentLoaded', function() {
            // --- Element and Modal Initialization ---
            const userListContainer = document.getElementById('users-list-container');
            const addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));
            const addUserForm = document.getElementById('add-user-form');
            const addUserError = document.getElementById('add-user-error');

            const editUserModal = new bootstrap.Modal(document.getElementById('editUserModal'));
            const editUserForm = document.getElementById('edit-user-form');

            const deleteUserModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));

            const editProfileModal = new bootstrap.Modal(document.getElementById('editProfileModal'));
            const changePasswordModal = new bootstrap.Modal(document.getElementById('changePasswordModal'));

            // --- AJAX Form Submission for Profile Edit ---
            document.getElementById('edit-profile-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const form = e.target;
                const button = form.querySelector('button[type="submit"]');
                const spinner = button.querySelector('.spinner-border');
                const formData = new FormData(form);
                const errorDiv = document.getElementById('edit-profile-error');

                button.disabled = true;
                spinner.classList.remove('d-none');

                fetch('actions/update_profile_action.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            editProfileModal.hide();
                            window.location.reload(); // Reload to reflect session changes
                        } else {
                            errorDiv.textContent = data.message || 'An error occurred.';
                            errorDiv.classList.remove('d-none');
                        }
                    }).catch(err => console.error(err))
                    .finally(() => {
                        button.disabled = false;
                        spinner.classList.add('d-none');
                    });
            });

            // --- AJAX Form Submission for Password Change ---
            document.getElementById('change-password-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const form = e.target;
                const button = form.querySelector('button[type="submit"]');
                const spinner = button.querySelector('.spinner-border');
                const formData = new FormData(form);
                const errorDiv = document.getElementById('change-password-error');
                const successDiv = document.getElementById('change-password-success');

                button.disabled = true;
                spinner.classList.remove('d-none');
                errorDiv.classList.add('d-none');
                successDiv.classList.add('d-none');

                fetch('actions/change_password_action.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            successDiv.textContent = data.message;
                            successDiv.classList.remove('d-none');
                            form.reset();
                            setTimeout(() => changePasswordModal.hide(), 2000); // Hide modal after 2 seconds
                        } else {
                            errorDiv.textContent = data.message || 'An error occurred.';
                            errorDiv.classList.remove('d-none');
                        }
                    }).catch(err => console.error(err))
                    .finally(() => {
                        button.disabled = false;
                        spinner.classList.add('d-none');
                    });
            });

            /**
             * Loads the user list HTML from a partial file and injects it into the container.
             * Preserves the search term and focus across reloads.
             * @param {string} url - The URL to fetch the user list from.
             */
            function loadUsers(url = 'pages/application_users_partial.php') {
                const searchInput = userListContainer.querySelector('input[name="search"]');
                const searchTerm = searchInput ? searchInput.value : '';
                const isSearchFocused = (document.activeElement === searchInput);

                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(response => response.text())
                    .then(html => {
                        userListContainer.innerHTML = html;

                        // Restore search term and focus to maintain user context
                        const newSearchInput = userListContainer.querySelector('input[name="search"]');
                        if (newSearchInput) {
                            newSearchInput.value = searchTerm;
                            if (isSearchFocused) {
                                newSearchInput.focus();
                                newSearchInput.setSelectionRange(searchTerm.length, searchTerm.length);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error loading users:', error);
                        userListContainer.innerHTML = '<p class="text-danger">Failed to load users.</p>';
                    });
            }

            // --- AJAX Form Submission for Adding a New User ---
            addUserForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const button = addUserForm.querySelector('button[type="submit"]');
                const spinner = button.querySelector('.spinner-border');
                addUserError.classList.add('d-none');
                const formData = new FormData(addUserForm);

                button.disabled = true;
                spinner.classList.remove('d-none');

                fetch('actions/new_user_action.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            addUserModal.hide();
                            addUserForm.reset();
                            loadUsers(); // Refresh the user list
                        } else {
                            addUserError.textContent = data.message || 'An error occurred.';
                            addUserError.classList.remove('d-none');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        addUserError.textContent = 'A network error occurred.';
                        addUserError.classList.remove('d-none');
                    })
                    .finally(() => {
                        button.disabled = false;
                        spinner.classList.add('d-none');
                    });
            });

            // --- Event Delegation for Edit/Delete Buttons ---
            // Populates modals with user data when an edit or delete button is clicked.
            userListContainer.addEventListener('click', function(e) {
                const editBtn = e.target.closest('.edit-user-btn');
                if (editBtn) {
                    document.getElementById('editUserId').value = editBtn.dataset.userId;
                    document.getElementById('editFullName').value = editBtn.dataset.fullName;
                    document.getElementById('editUserName').value = editBtn.dataset.userName;
                    document.getElementById('editPermission').value = editBtn.dataset.permission;
                }

                const deleteBtn = e.target.closest('.delete-user-btn');
                if (deleteBtn) {
                    document.getElementById('deleteUserId').value = deleteBtn.dataset.userId;
                }
            });

            // --- AJAX Form Submission for Editing a User ---
            editUserForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const button = editUserForm.querySelector('button[type="submit"]');
                const spinner = button.querySelector('.spinner-border');
                const formData = new FormData(editUserForm);

                button.disabled = true;
                spinner.classList.remove('d-none');
                fetch('actions/edit_user_action.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            editUserModal.hide();
                            loadUsers(); // Refresh the user list
                        } else {
                            document.getElementById('edit-user-error').textContent = data.message || 'An error occurred.';
                            document.getElementById('edit-user-error').classList.remove('d-none');
                        }
                    }).catch(err => console.error(err))
                    .finally(() => {
                        button.disabled = false;
                        spinner.classList.add('d-none');
                    });
            });

            // --- AJAX Request for Deleting a User ---
            document.getElementById('confirm-delete-btn').addEventListener('click', function() {
                const button = this;
                const spinner = button.querySelector('.spinner-border');
                const userId = document.getElementById('deleteUserId').value;

                button.disabled = true;
                spinner.classList.remove('d-none');
                fetch('actions/delete_user_action.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'userId=' + encodeURIComponent(userId)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            deleteUserModal.hide();
                            loadUsers(); // Refresh the user list
                        } else {
                            alert('Error deleting user: ' + data.message);
                        }
                    }).catch(err => console.error(err))
                    .finally(() => {
                        button.disabled = false;
                        spinner.classList.add('d-none');
                    });
            });

            // --- Event Listeners for User List Filtering and Pagination ---
            // Handles clicks on pagination links.
            userListContainer.addEventListener('click', function(e) {
                if (e.target.matches('.pagination .page-link')) {
                    e.preventDefault();
                    loadUsers(e.target.href);
                }
            });

            // Debounces search input to avoid excessive AJAX calls.
            let debounceTimer;
            userListContainer.addEventListener('keyup', function(e) {
                if (e.target.matches('input[name="search"]')) {
                    clearTimeout(debounceTimer);
                    const form = e.target.closest('form');
                    debounceTimer = setTimeout(() => {
                        const params = new URLSearchParams(new FormData(form));
                        params.set('p', '1'); // Reset to first page on new search
                        loadUsers(`pages/application_users_partial.php?${params.toString()}`);
                    }, 300); // 300ms delay
                }
            });

            // Prevents the search form from submitting traditionally.
            userListContainer.addEventListener('submit', function(e) {
                if (e.target.matches('#user-search-form')) {
                    e.preventDefault();
                }
            });

            // Handles changes in filter dropdowns.
            userListContainer.addEventListener('change', function(e) {
                if (e.target.matches('select[name="permission"]') || e.target.matches('select[name="date_filter"]')) {
                    const form = e.target.closest('form');
                    const params = new URLSearchParams(new FormData(form));
                    params.set('p', '1'); // Reset to first page
                    loadUsers(`pages/application_users_partial.php?${params.toString()}`);
                }
            });

            // Initial load of the user list when the page is ready.
            loadUsers();
        });
    </script>
<?php endif; ?>