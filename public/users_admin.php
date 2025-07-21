<?php
// public/users_admin.php
require_once __DIR__ . '/../src/db/db.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get database connection
$db = Database::getInstance()->getConnection();

// Handle AJAX updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'update_user') {
        $user_id = (int)$_POST['user_id'];
        $field = $_POST['field'];
        $value = $_POST['value'];
        
        // Validate field
        $allowed_fields = ['first_name', 'last_name', 'display_name', 'email', 'phone', 'is_active', 'role'];
        if (!in_array($field, $allowed_fields)) {
            echo json_encode(['success' => false, 'message' => 'Invalid field']);
            exit;
        }
        
        // Auto-generate display_name if first_name or last_name is updated
        $auto_display_name = '';
        if ($field === 'first_name' || $field === 'last_name') {
            // Get current user data
            $stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($field === 'first_name') {
                $auto_display_name = trim($value . ' ' . $user['last_name']);
            } else {
                $auto_display_name = trim($user['first_name'] . ' ' . $value);
            }
        }
        
        try {
            // Update the field
            $stmt = $db->prepare("UPDATE users SET $field = ? WHERE id = ?");
            $stmt->execute([$value, $user_id]);
            
            // If we auto-generated display_name, update it too
            if ($auto_display_name) {
                $stmt = $db->prepare("UPDATE users SET display_name = ? WHERE id = ?");
                $stmt->execute([$auto_display_name, $user_id]);
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'User updated successfully',
                'auto_display_name' => $auto_display_name
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'reset_password') {
        $user_id = (int)$_POST['user_id'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate passwords
        if (empty($new_password) || empty($confirm_password)) {
            echo json_encode(['success' => false, 'message' => 'Both password fields are required']);
            exit;
        }
        
        if ($new_password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            exit;
        }
        
        if (strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
            exit;
        }
        
        try {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update the password
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Password reset successfully'
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Fetch all users
try {
    $stmt = $db->query("SELECT id, email, first_name, last_name, display_name, phone, is_active, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Administration | AppTrack</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .editable-input {
            border: none;
            background: transparent;
            width: 100%;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        .editable-input:focus {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        .editable-input:hover {
            background: #f8f9fa;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #198754;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .role-select {
            border: none;
            background: transparent;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.2s;
            font-size: 0.875rem;
            width: 100%;
        }
        .role-select:focus, .role-select:hover {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        
        /* Table styling to match dashboard */
        .table {
            border: none;
            margin-bottom: 0;
        }
        .table thead th {
            background-color: transparent;
            border-bottom: none;
            color: #495057;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 16px 12px;
            border-top: none;
        }
        .table tbody td {
            padding: 18px 12px;
            vertical-align: middle;
            border-top: 6px solid transparent;
            background-color: transparent;
        }
        .table tbody tr:hover {
            background-color: #E3F1FF;
        }
        .table tbody tr:hover td {
            background-color: transparent;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
            font-weight: 500;
        }
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        /* Column width adjustments and no word wrap */
        .table td, .table th {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Specific column widths */
        .table th:nth-child(1), .table td:nth-child(1) { /* ID */
            width: 5%;
            text-align: center;
        }
        .table th:nth-child(2), .table td:nth-child(2) { /* First Name */
            width: 10%;
        }
        .table th:nth-child(3), .table td:nth-child(3) { /* Last Name */
            width: 10%;
        }
        .table th:nth-child(4), .table td:nth-child(4) { /* Display Name */
            width: 12%;
        }
        .table th:nth-child(5), .table td:nth-child(5) { /* Email */
            width: 20%;
        }
        .table th:nth-child(6), .table td:nth-child(6) { /* Phone */
            width: 10%;
        }
        .table th:nth-child(7), .table td:nth-child(7) { /* Status */
            width: 12%;
        }
        .table th:nth-child(8), .table td:nth-child(8) { /* Role */
            width: 8%;
        }
        .table th:nth-child(9), .table td:nth-child(9) { /* Created */
            width: 8%;
        }
        .table th:nth-child(10), .table td:nth-child(10) { /* Actions */
            width: 5%;
            text-align: center;
        }
        
        /* Card styling to match dashboard */
        .main-card {
            border: none;
            border-radius: 4px;
            box-shadow: none;
        }
        .main-card .card-header {
            background-color: transparent;
            border-bottom: none;
            border-radius: 4px 4px 0 0 !important;
            padding: 20px 24px;
        }
        .main-card .card-body {
            padding: 0;
        }
        
        /* Updated badge styling */
        .id-badge {
            background-color: #6c757d;
            color: white;
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
            font-weight: 500;
        }
        
        /* Time badges like dashboard */
        .time-badge {
            background-color: #e7f3ff;
            color: #0066cc;
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
            font-weight: 500;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 sticky-top" style="z-index: 1030; margin-bottom:0; padding-top:0; padding-bottom:0;">
        <div class="container-fluid px-0">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../assets/logo.png" alt="AppTrack" style="height: 40px;">
            </a>
            <div class="flex-grow-1 d-flex justify-content-center">
                <h4 class="mb-0 text-muted">
                    <i class="bi bi-people-fill me-2"></i>
                    User Administration
                </h4>
            </div>
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="btn btn-outline-secondary" href="dashboard.php">
                        <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card main-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <i class="bi bi-people-fill text-primary me-2"></i>
                                User Administration
                            </h4>
                            <span class="badge bg-secondary">
                                Total Users: <?= count($users) ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger m-3 mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>First Name</th>
                                        <th>Last Name</th>
                                        <th>Display Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Role</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr data-user-id="<?= $user['id'] ?>">
                                            <td class="text-center">
                                                <span class="id-badge"><?= $user['id'] ?></span>
                                            </td>
                                            <td>
                                                <input type="text" 
                                                       class="editable-input" 
                                                       data-field="first_name"
                                                       value="<?= htmlspecialchars($user['first_name'] ?? '') ?>"
                                                       placeholder="First name">
                                            </td>
                                            <td>
                                                <input type="text" 
                                                       class="editable-input" 
                                                       data-field="last_name"
                                                       value="<?= htmlspecialchars($user['last_name'] ?? '') ?>"
                                                       placeholder="Last name">
                                            </td>
                                            <td>
                                                <input type="text" 
                                                       class="editable-input display-name-field" 
                                                       data-field="display_name"
                                                       value="<?= htmlspecialchars($user['display_name'] ?? '') ?>"
                                                       placeholder="Display name">
                                            </td>
                                            <td>
                                                <input type="email" 
                                                       class="editable-input" 
                                                       data-field="email"
                                                       value="<?= htmlspecialchars($user['email']) ?>"
                                                       placeholder="Email address">
                                            </td>
                                            <td>
                                                <input type="text" 
                                                       class="editable-input" 
                                                       data-field="phone"
                                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                                       placeholder="Phone number">
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <label class="switch me-2">
                                                        <input type="checkbox" 
                                                               class="status-switch" 
                                                               data-field="is_active"
                                                               <?= $user['is_active'] ? 'checked' : '' ?>>
                                                        <span class="slider"></span>
                                                    </label>
                                                    <span class="status-badge badge <?= $user['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <select class="role-select" data-field="role">
                                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                    <option value="editor" <?= $user['role'] === 'editor' ? 'selected' : '' ?>>Editor</option>
                                                    <option value="viewer" <?= $user['role'] === 'viewer' ? 'selected' : '' ?>>Viewer</option>
                                                </select>
                                            </td>
                                            <td>
                                                <span class="time-badge">
                                                    <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" 
                                                        class="btn btn-outline-warning btn-sm reset-password-btn" 
                                                        data-user-id="<?= $user['id'] ?>"
                                                        data-user-name="<?= htmlspecialchars($user['display_name'] ?: $user['email']) ?>"
                                                        title="Reset Password">
                                                    <i class="bi bi-key"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Toast -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="toastMessage" class="toast" role="alert">
            <div class="toast-header">
                <strong class="me-auto">User Administration</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>

    <!-- Password Reset Modal -->
    <div class="modal fade" id="passwordResetModal" tabindex="-1" aria-labelledby="passwordResetModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="passwordResetModalLabel">
                        <i class="bi bi-key me-2"></i>Reset Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <p class="text-muted mb-0">Changing password for</p>
                        <h6 class="mb-0" id="resetUserDisplayText"></h6>
                    </div>
                    
                    <form id="passwordResetForm">
                        <input type="hidden" id="resetUserId" name="user_id">
                        
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required minlength="6">
                            <div class="form-text">Password must be at least 6 characters long.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required minlength="6">
                        </div>
                        
                        <div id="passwordError" class="alert alert-danger d-none"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirmResetPassword">
                        <i class="bi bi-key me-1"></i>Reset Password
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize toast
        const toast = new bootstrap.Toast(document.getElementById('toastMessage'));
        
        // Function to show toast message
        function showToast(message, isSuccess = true) {
            const toastEl = document.getElementById('toastMessage');
            const toastBody = toastEl.querySelector('.toast-body');
            const toastHeader = toastEl.querySelector('.toast-header');
            
            toastBody.textContent = message;
            toastHeader.className = `toast-header ${isSuccess ? 'bg-success text-white' : 'bg-danger text-white'}`;
            toast.show();
        }
        
        // Function to update user field
        function updateUser(userId, field, value) {
            const row = document.querySelector(`tr[data-user-id="${userId}"]`);
            row.classList.add('loading');
            
            fetch('users_admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_user&user_id=${userId}&field=${field}&value=${encodeURIComponent(value)}`
            })
            .then(response => response.json())
            .then(data => {
                row.classList.remove('loading');
                
                if (data.success) {
                    showToast(data.message, true);
                    
                    // If display_name was auto-generated, update the field
                    if (data.auto_display_name) {
                        const displayNameField = row.querySelector('[data-field="display_name"]');
                        displayNameField.value = data.auto_display_name;
                    }
                    
                    // Update status badge if is_active was changed
                    if (field === 'is_active') {
                        const statusBadge = row.querySelector('.status-badge');
                        if (value === '1') {
                            statusBadge.className = 'status-badge badge bg-success ms-2';
                            statusBadge.textContent = 'Active';
                        } else {
                            statusBadge.className = 'status-badge badge bg-secondary ms-2';
                            statusBadge.textContent = 'Inactive';
                        }
                    }
                } else {
                    showToast(data.message, false);
                }
            })
            .catch(error => {
                row.classList.remove('loading');
                showToast('Network error occurred', false);
                console.error('Error:', error);
            });
        }
        
        // Handle text input changes
        document.addEventListener('blur', function(e) {
            if (e.target.classList.contains('editable-input')) {
                const userId = e.target.closest('tr').dataset.userId;
                const field = e.target.dataset.field;
                const value = e.target.value;
                
                updateUser(userId, field, value);
            }
        }, true);
        
        // Handle Enter key on text inputs
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.classList.contains('editable-input')) {
                e.target.blur();
            }
        });
        
        // Handle switch changes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('status-switch')) {
                const userId = e.target.closest('tr').dataset.userId;
                const field = e.target.dataset.field;
                const value = e.target.checked ? '1' : '0';
                
                updateUser(userId, field, value);
            }
        });
        
        // Handle role select changes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('role-select')) {
                const userId = e.target.closest('tr').dataset.userId;
                const field = e.target.dataset.field;
                const value = e.target.value;
                
                updateUser(userId, field, value);
            }
        });
        
        // Auto-generate display name when first_name or last_name changes
        document.addEventListener('input', function(e) {
            if (e.target.dataset.field === 'first_name' || e.target.dataset.field === 'last_name') {
                const row = e.target.closest('tr');
                const firstNameField = row.querySelector('[data-field="first_name"]');
                const lastNameField = row.querySelector('[data-field="last_name"]');
                const displayNameField = row.querySelector('[data-field="display_name"]');
                
                // Only auto-generate if display_name field is empty or matches the pattern "firstname lastname"
                const currentDisplayName = displayNameField.value.trim();
                const expectedDisplayName = `${firstNameField.value.trim()} ${lastNameField.value.trim()}`.trim();
                
                if (!currentDisplayName || currentDisplayName === expectedDisplayName.replace(/\s+/g, ' ')) {
                    displayNameField.value = expectedDisplayName;
                }
            }
        });
        
        // Password Reset Modal functionality
        const passwordResetModal = new bootstrap.Modal(document.getElementById('passwordResetModal'));
        const passwordResetForm = document.getElementById('passwordResetForm');
        const passwordError = document.getElementById('passwordError');
        
        // Handle password reset button clicks
        document.addEventListener('click', function(e) {
            if (e.target.closest('.reset-password-btn')) {
                const button = e.target.closest('.reset-password-btn');
                const userId = button.dataset.userId;
                const userName = button.dataset.userName;
                
                // Set modal fields
                document.getElementById('resetUserId').value = userId;
                document.getElementById('resetUserDisplayText').textContent = userName;
                
                // Clear form
                passwordResetForm.reset();
                passwordError.classList.add('d-none');
                
                // Show modal
                passwordResetModal.show();
            }
        });
        
        // Handle password confirmation
        document.getElementById('confirmResetPassword').addEventListener('click', function() {
            const formData = new FormData(passwordResetForm);
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');
            
            // Clear previous errors
            passwordError.classList.add('d-none');
            
            // Validate passwords
            if (!newPassword || !confirmPassword) {
                passwordError.textContent = 'Both password fields are required';
                passwordError.classList.remove('d-none');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                passwordError.textContent = 'Passwords do not match';
                passwordError.classList.remove('d-none');
                return;
            }
            
            if (newPassword.length < 6) {
                passwordError.textContent = 'Password must be at least 6 characters long';
                passwordError.classList.remove('d-none');
                return;
            }
            
            // Disable button during request
            const button = this;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Resetting...';
            
            // Send reset request
            fetch('users_admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=reset_password&user_id=${formData.get('user_id')}&new_password=${encodeURIComponent(newPassword)}&confirm_password=${encodeURIComponent(confirmPassword)}`
            })
            .then(response => response.json())
            .then(data => {
                button.disabled = false;
                button.innerHTML = '<i class="bi bi-key me-1"></i>Reset Password';
                
                if (data.success) {
                    passwordResetModal.hide();
                    showToast(data.message, true);
                } else {
                    passwordError.textContent = data.message;
                    passwordError.classList.remove('d-none');
                }
            })
            .catch(error => {
                button.disabled = false;
                button.innerHTML = '<i class="bi bi-key me-1"></i>Reset Password';
                passwordError.textContent = 'Network error occurred';
                passwordError.classList.remove('d-none');
                console.error('Error:', error);
            });
        });
        
        // Real-time password validation
        document.addEventListener('input', function(e) {
            if (e.target.id === 'newPassword' || e.target.id === 'confirmPassword') {
                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                
                if (newPassword && confirmPassword && newPassword !== confirmPassword) {
                    passwordError.textContent = 'Passwords do not match';
                    passwordError.classList.remove('d-none');
                } else {
                    passwordError.classList.add('d-none');
                }
            }
        });
    </script>
</body>
</html>
