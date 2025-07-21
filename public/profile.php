<?php
// public/profile.php
require_once __DIR__ . '/../src/db/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get database connection
$db = Database::getInstance()->getConnection();

// Handle AJAX updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'update_profile') {
        $user_id = $_SESSION['user_id'];
        $field = $_POST['field'];
        $value = $_POST['value'];
        
        // Validate field
        $allowed_fields = ['first_name', 'last_name', 'display_name', 'email', 'phone'];
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
            
            // Update session if email was changed
            if ($field === 'email') {
                $_SESSION['user_email'] = $value;
            }
            
            echo json_encode([
                'success' => true, 
                'message' => ucfirst(str_replace('_', ' ', $field)) . ' updated successfully',
                'auto_display_name' => $auto_display_name
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'change_password') {
        $user_id = $_SESSION['user_id'];
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate passwords
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            echo json_encode(['success' => false, 'message' => 'All password fields are required']);
            exit;
        }
        
        if ($new_password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
            exit;
        }
        
        if (strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
            exit;
        }
        
        try {
            // Verify current password
            $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($current_password, $user['password_hash'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit;
            }
            
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update the password
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Password changed successfully'
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Fetch current user data
try {
    $stmt = $db->prepare("SELECT id, email, first_name, last_name, display_name, phone, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | AppTrack</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid #0d6efd;
        }
        
        .profile-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #0d6efd, #0056b3);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .form-floating > .form-control {
            padding-top: 1.625rem;
            padding-bottom: 0.625rem;
            height: auto;
        }
        
        .form-floating > label {
            padding: 1rem 0.75rem;
        }
        
        .btn-save {
            background: #28a745;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-save:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        
        .password-section {
            border-top: 1px solid #dee2e6;
            margin-top: 2rem;
            padding-top: 2rem;
        }
        
        .role-badge {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section h5 {
            color: #495057;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .auto-generated-note {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'shared/topbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <!-- Back Button -->
                <div class="mb-3">
                    <button type="button" class="btn btn-outline-secondary" onclick="goBack()">
                        <i class="bi bi-arrow-left me-2"></i>Back
                    </button>
                </div>
                
                <div class="profile-card">
                    <!-- Profile Header -->
                    <div class="profile-header">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['display_name'] ?: $user['email']); ?>&background=ffffff&color=0d6efd&size=80" 
                             alt="Profile Avatar" 
                             class="profile-avatar mb-3">
                        <h4 class="mb-1"><?= htmlspecialchars($user['display_name'] ?: $user['email']) ?></h4>
                        <p class="mb-2"><?= htmlspecialchars($user['email']) ?></p>
                        <span class="role-badge <?= $user['role'] === 'admin' ? 'bg-danger' : ($user['role'] === 'editor' ? 'bg-warning text-dark' : 'bg-info') ?>">
                            <?= ucfirst($user['role']) ?>
                        </span>
                        <p class="mt-3 mb-0 opacity-75">
                            <small>Member since <?= date('M j, Y', strtotime($user['created_at'])) ?></small>
                        </p>
                    </div>
                    
                    <!-- Profile Form -->
                    <div class="card-body p-4">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Personal Information Section -->
                        <div class="form-section">
                            <h5><i class="bi bi-person me-2"></i>Personal Information</h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" 
                                               class="form-control profile-input" 
                                               id="firstName"
                                               data-field="first_name"
                                               value="<?= htmlspecialchars($user['first_name'] ?? '') ?>"
                                               placeholder="First Name">
                                        <label for="firstName">First Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" 
                                               class="form-control profile-input" 
                                               id="lastName"
                                               data-field="last_name"
                                               value="<?= htmlspecialchars($user['last_name'] ?? '') ?>"
                                               placeholder="Last Name">
                                        <label for="lastName">Last Name</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="text" 
                                           class="form-control profile-input" 
                                           id="displayName"
                                           data-field="display_name"
                                           value="<?= htmlspecialchars($user['display_name'] ?? '') ?>"
                                           placeholder="Display Name">
                                    <label for="displayName">Display Name</label>
                                </div>
                                <div class="auto-generated-note">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Display name is automatically generated from first and last name, but you can customize it.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contact Information Section -->
                        <div class="form-section">
                            <h5><i class="bi bi-envelope me-2"></i>Contact Information</h5>
                            
                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="email" 
                                           class="form-control profile-input" 
                                           id="email"
                                           data-field="email"
                                           value="<?= htmlspecialchars($user['email']) ?>"
                                           placeholder="Email Address">
                                    <label for="email">Email Address</label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="tel" 
                                           class="form-control profile-input" 
                                           id="phone"
                                           data-field="phone"
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                           placeholder="Phone Number">
                                    <label for="phone">Phone Number</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Password Section -->
                        <div class="password-section">
                            <h5><i class="bi bi-key me-2"></i>Change Password</h5>
                            
                            <form id="passwordForm">
                                <div class="mb-3">
                                    <div class="form-floating">
                                        <input type="password" 
                                               class="form-control" 
                                               id="currentPassword"
                                               name="current_password"
                                               placeholder="Current Password">
                                        <label for="currentPassword">Current Password</label>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-floating">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="newPassword"
                                                   name="new_password"
                                                   minlength="6"
                                                   placeholder="New Password">
                                            <label for="newPassword">New Password</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-floating">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="confirmPassword"
                                                   name="confirm_password"
                                                   minlength="6"
                                                   placeholder="Confirm Password">
                                            <label for="confirmPassword">Confirm Password</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="passwordError" class="alert alert-danger d-none"></div>
                                
                                <button type="button" class="btn btn-warning" id="changePasswordBtn">
                                    <i class="bi bi-key me-2"></i>Change Password
                                </button>
                            </form>
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
                <strong class="me-auto">Profile</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body"></div>
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
        
        // Function to update profile field
        function updateProfile(field, value) {
            const body = document.body;
            body.classList.add('loading');
            
            fetch('profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_profile&field=${field}&value=${encodeURIComponent(value)}`
            })
            .then(response => response.json())
            .then(data => {
                body.classList.remove('loading');
                
                if (data.success) {
                    showToast(data.message, true);
                    
                    // If display_name was auto-generated, update the field
                    if (data.auto_display_name) {
                        const displayNameField = document.getElementById('displayName');
                        displayNameField.value = data.auto_display_name;
                    }
                } else {
                    showToast(data.message, false);
                }
            })
            .catch(error => {
                body.classList.remove('loading');
                showToast('Network error occurred', false);
                console.error('Error:', error);
            });
        }
        
        // Handle profile input changes
        document.addEventListener('blur', function(e) {
            if (e.target.classList.contains('profile-input')) {
                const field = e.target.dataset.field;
                const value = e.target.value;
                
                updateProfile(field, value);
            }
        }, true);
        
        // Handle Enter key on profile inputs
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.classList.contains('profile-input')) {
                e.target.blur();
            }
        });
        
        // Auto-generate display name when first_name or last_name changes
        document.addEventListener('input', function(e) {
            if (e.target.dataset.field === 'first_name' || e.target.dataset.field === 'last_name') {
                const firstNameField = document.getElementById('firstName');
                const lastNameField = document.getElementById('lastName');
                const displayNameField = document.getElementById('displayName');
                
                // Only auto-generate if display_name field is empty or matches the pattern "firstname lastname"
                const currentDisplayName = displayNameField.value.trim();
                const expectedDisplayName = `${firstNameField.value.trim()} ${lastNameField.value.trim()}`.trim();
                
                if (!currentDisplayName || currentDisplayName === expectedDisplayName.replace(/\s+/g, ' ')) {
                    displayNameField.value = expectedDisplayName;
                }
            }
        });
        
        // Password change functionality
        const passwordForm = document.getElementById('passwordForm');
        const passwordError = document.getElementById('passwordError');
        const changePasswordBtn = document.getElementById('changePasswordBtn');
        
        // Handle password change
        changePasswordBtn.addEventListener('click', function() {
            const formData = new FormData(passwordForm);
            const currentPassword = formData.get('current_password');
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');
            
            // Clear previous errors
            passwordError.classList.add('d-none');
            
            // Validate passwords
            if (!currentPassword || !newPassword || !confirmPassword) {
                passwordError.textContent = 'All password fields are required';
                passwordError.classList.remove('d-none');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                passwordError.textContent = 'New passwords do not match';
                passwordError.classList.remove('d-none');
                return;
            }
            
            if (newPassword.length < 6) {
                passwordError.textContent = 'New password must be at least 6 characters long';
                passwordError.classList.remove('d-none');
                return;
            }
            
            // Disable button during request
            const button = this;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Changing...';
            
            // Send change request
            fetch('profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=change_password&current_password=${encodeURIComponent(currentPassword)}&new_password=${encodeURIComponent(newPassword)}&confirm_password=${encodeURIComponent(confirmPassword)}`
            })
            .then(response => response.json())
            .then(data => {
                button.disabled = false;
                button.innerHTML = '<i class="bi bi-key me-2"></i>Change Password';
                
                if (data.success) {
                    passwordForm.reset();
                    showToast(data.message, true);
                } else {
                    passwordError.textContent = data.message;
                    passwordError.classList.remove('d-none');
                }
            })
            .catch(error => {
                button.disabled = false;
                button.innerHTML = '<i class="bi bi-key me-2"></i>Change Password';
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
                    passwordError.textContent = 'New passwords do not match';
                    passwordError.classList.remove('d-none');
                } else {
                    passwordError.classList.add('d-none');
                }
            }
        });
        
        // Back button functionality
        function goBack() {
            // Try to go back in history, fallback to dashboard
            if (document.referrer && document.referrer !== window.location.href) {
                window.history.back();
            } else {
                window.location.href = 'dashboard.php';
            }
        }
    </script>
</body>
</html>
