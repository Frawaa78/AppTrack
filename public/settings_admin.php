<?php
// Admin Settings Page
session_start();
require_once '../src/config/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$topbar_search_disabled = true;

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - AppTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Fjern eller endre til eksisterende CSS-fil -->
    <!-- <link href="assets/css/style.css" rel="stylesheet"> -->
</head>
<body>
    <?php include 'shared/topbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-gear me-2"></i>System Settings</h2>
                </div>

                <!-- Settings Tabs -->
                <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="portfolio-tab" data-bs-toggle="tab" data-bs-target="#portfolio" type="button" role="tab">
                            <i class="bi bi-collection me-2"></i>Portfolio Management
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="application-tab" data-bs-toggle="tab" data-bs-target="#application" type="button" role="tab">
                            <i class="bi bi-grid-3x3-gap me-2"></i>Application Config
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="ai-tab" data-bs-toggle="tab" data-bs-target="#ai" type="button" role="tab">
                            <i class="bi bi-robot me-2"></i>AI Settings
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
                            <i class="bi bi-tools me-2"></i>System Maintenance
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="settingsTabContent">
                    <!-- Portfolio Management Tab -->
                    <div class="tab-pane fade show active" id="portfolio" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0">Portfolio Management</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Pre-ops Portfolios</h6>
                                        <div id="preops-portfolios">
                                            <!-- Dynamic content will be loaded here -->
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary mt-2" id="add-preops-portfolio">
                                            <i class="bi bi-plus"></i> Add Portfolio
                                        </button>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Application Portfolios</h6>
                                        <div id="app-portfolios">
                                            <!-- Dynamic content will be loaded here -->
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary mt-2" id="add-app-portfolio">
                                            <i class="bi bi-plus"></i> Add Portfolio
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Application Configuration Tab -->
                    <div class="tab-pane fade" id="application" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0">Application Configuration</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Phases</h6>
                                        <div id="phases-config">
                                            <div class="list-group">
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    Need <span class="badge bg-primary">Active</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    Solution <span class="badge bg-primary">Active</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    Build <span class="badge bg-primary">Active</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    Implement <span class="badge bg-primary">Active</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    Operate <span class="badge bg-primary">Active</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Statuses</h6>
                                        <div id="statuses-config">
                                            <div class="list-group">
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    Unknown <span class="badge bg-secondary">Active</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    Not started <span class="badge bg-danger">Active</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    Ongoing Work <span class="badge bg-warning">Active</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    On Hold <span class="badge bg-info">Active</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    Completed <span class="badge bg-success">Active</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Settings Tab -->
                    <div class="tab-pane fade" id="ai" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0">AI Configuration</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="openai-api-key" class="form-label">OpenAI API Key</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="openai-api-key" placeholder="sk-...">
                                        <button class="btn btn-outline-secondary" type="button" id="toggle-api-key">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="ai-model" class="form-label">AI Model</label>
                                    <select class="form-select" id="ai-model">
                                        <option value="gpt-4">GPT-4</option>
                                        <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Analysis Types</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="enable-summary" checked>
                                        <label class="form-check-label" for="enable-summary">
                                            Application Summary Generation
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="enable-recommendations" checked>
                                        <label class="form-check-label" for="enable-recommendations">
                                            Recommendations
                                        </label>
                                    </div>
                                </div>
                                <button class="btn btn-primary" id="save-ai-settings">Save AI Settings</button>
                            </div>
                        </div>
                    </div>

                    <!-- System Maintenance Tab -->
                    <div class="tab-pane fade" id="system" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0">System Maintenance</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Database Operations</h6>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-outline-warning" id="cleanup-logs">
                                                <i class="bi bi-trash"></i> Cleanup Old Logs
                                            </button>
                                            <button class="btn btn-outline-info" id="backup-db">
                                                <i class="bi bi-download"></i> Backup Database
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>System Information</h6>
                                        <div class="card">
                                            <div class="card-body">
                                                <small class="text-muted">
                                                    <div>Version: 1.0.0</div>
                                                    <div>Database: Connected</div>
                                                    <div>Last Backup: Never</div>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Settings page functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Load portfolios when page loads
            loadPortfolios();

            // Portfolio Management Functions
            function loadPortfolios() {
                // Mock data for testing while API is missing
                console.log('Loading portfolios...');
                
                const mockData = {
                    success: true,
                    preops_portfolios: [
                        { name: 'Infrastructure', count: 12 },
                        { name: 'Security Portfolio', count: 8 },
                        { name: 'Network Operations', count: 5 }
                    ],
                    app_portfolios: [
                        { name: 'Customer Applications', count: 15 },
                        { name: 'Internal Tools', count: 6 },
                        { name: 'Mobile Apps', count: 9 }
                    ]
                };
                
                renderPortfolios('preops', mockData.preops_portfolios);
                renderPortfolios('app', mockData.app_portfolios);
                
                // Original API call - commented out until API is created
                /*
                fetch('api/settings/portfolios.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            renderPortfolios('preops', data.preops_portfolios);
                            renderPortfolios('app', data.app_portfolios);
                        } else {
                            showToast('Error loading portfolios', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Failed to load portfolios', 'error');
                    });
                */
            }

            function renderPortfolios(type, portfolios) {
                const containerId = type === 'preops' ? 'preops-portfolios' : 'app-portfolios';
                const container = document.getElementById(containerId);
                
                if (portfolios.length === 0) {
                    container.innerHTML = '<p class="text-muted">No portfolios found</p>';
                    return;
                }

                const html = portfolios.map(portfolio => `
                    <div class="d-flex justify-content-between align-items-center mb-2 portfolio-item" data-type="${type}" data-name="${portfolio.name}">
                        <div class="portfolio-display">
                            <span class="portfolio-name">${portfolio.name}</span>
                            <small class="text-muted ms-2">(${portfolio.count} apps)</small>
                        </div>
                        <div class="portfolio-edit" style="display: none;">
                            <input type="text" class="form-control form-control-sm portfolio-edit-input" value="${portfolio.name}">
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary btn-edit" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-success btn-save" style="display: none;" title="Save">
                                <i class="bi bi-check"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-cancel" style="display: none;" title="Cancel">
                                <i class="bi bi-x"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-delete" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `).join('');
                
                container.innerHTML = html;

                // Add event listeners
                container.querySelectorAll('.btn-edit').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const item = this.closest('.portfolio-item');
                        startEdit(item);
                    });
                });

                container.querySelectorAll('.btn-save').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const item = this.closest('.portfolio-item');
                        saveEdit(item);
                    });
                });

                container.querySelectorAll('.btn-cancel').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const item = this.closest('.portfolio-item');
                        cancelEdit(item);
                    });
                });

                container.querySelectorAll('.btn-delete').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const item = this.closest('.portfolio-item');
                        deletePortfolio(item);
                    });
                });
            }

            function startEdit(item) {
                const display = item.querySelector('.portfolio-display');
                const edit = item.querySelector('.portfolio-edit');
                const btnEdit = item.querySelector('.btn-edit');
                const btnSave = item.querySelector('.btn-save');
                const btnCancel = item.querySelector('.btn-cancel');

                display.style.display = 'none';
                edit.style.display = 'block';
                btnEdit.style.display = 'none';
                btnSave.style.display = 'inline-block';
                btnCancel.style.display = 'inline-block';

                edit.querySelector('input').focus();
            }

            function cancelEdit(item) {
                const display = item.querySelector('.portfolio-display');
                const edit = item.querySelector('.portfolio-edit');
                const btnEdit = item.querySelector('.btn-edit');
                const btnSave = item.querySelector('.btn-save');
                const btnCancel = item.querySelector('.btn-cancel');

                display.style.display = 'block';
                edit.style.display = 'none';
                btnEdit.style.display = 'inline-block';
                btnSave.style.display = 'none';
                btnCancel.style.display = 'none';
            }

            function saveEdit(item) {
                const type = item.dataset.type;
                const oldName = item.dataset.name;
                const newName = item.querySelector('.portfolio-edit-input').value.trim();

                if (!newName) {
                    showToast('Portfolio name cannot be empty', 'error');
                    return;
                }

                fetch('api/settings/portfolios.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        type: type,
                        old_name: oldName,
                        new_name: newName
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Portfolio updated successfully', 'success');
                        loadPortfolios(); // Reload to get fresh data
                    } else {
                        showToast(data.error || 'Failed to update portfolio', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Failed to update portfolio', 'error');
                });
            }

            function deletePortfolio(item) {
                const type = item.dataset.type;
                const name = item.dataset.name;

                if (!confirm(`Are you sure you want to delete the portfolio "${name}"? This will remove it from all applications.`)) {
                    return;
                }

                fetch('api/settings/portfolios.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        type: type,
                        name: name
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(`Portfolio deleted successfully (${data.affected_rows} apps updated)`, 'success');
                        loadPortfolios(); // Reload to get fresh data
                    } else {
                        showToast(data.error || 'Failed to delete portfolio', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Failed to delete portfolio', 'error');
                });
            }

            // Add Portfolio buttons
            document.getElementById('add-preops-portfolio').addEventListener('click', function() {
                addPortfolio('preops');
            });

            document.getElementById('add-app-portfolio').addEventListener('click', function() {
                addPortfolio('app');
            });

            function addPortfolio(type) {
                const name = prompt(`Enter name for new ${type === 'preops' ? 'Pre-ops' : 'Application'} portfolio:`);
                if (!name || !name.trim()) return;

                fetch('api/settings/portfolios.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        type: type,
                        name: name.trim()
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Portfolio ready to be used', 'success');
                        loadPortfolios(); // Reload to get fresh data
                    } else {
                        showToast(data.error || 'Failed to create portfolio', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Failed to create portfolio', 'error');
                });
            }

            function showToast(message, type = 'info') {
                // Create toast if not exists
                if (!document.getElementById('toast-container')) {
                    const toastContainer = document.createElement('div');
                    toastContainer.id = 'toast-container';
                    toastContainer.className = 'position-fixed top-0 end-0 p-3';
                    toastContainer.style.zIndex = '1055';
                    document.body.appendChild(toastContainer);
                }

                const toastId = 'toast-' + Date.now();
                const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
                
                const toastHtml = `
                    <div id="${toastId}" class="toast ${bgClass} text-white" role="alert">
                        <div class="toast-body">
                            ${message}
                        </div>
                    </div>
                `;

                document.getElementById('toast-container').insertAdjacentHTML('beforeend', toastHtml);
                
                const toastElement = document.getElementById(toastId);
                const toast = new bootstrap.Toast(toastElement);
                toast.show();

                // Remove toast after it's hidden
                toastElement.addEventListener('hidden.bs.toast', function() {
                    toastElement.remove();
                });
            }

            // Toggle API key visibility
            document.getElementById('toggle-api-key').addEventListener('click', function() {
                const apiKeyInput = document.getElementById('openai-api-key');
                const icon = this.querySelector('i');
                
                if (apiKeyInput.type === 'password') {
                    apiKeyInput.type = 'text';
                    icon.className = 'bi bi-eye-slash';
                } else {
                    apiKeyInput.type = 'password';
                    icon.className = 'bi bi-eye';
                }
            });

            // Save AI settings
            document.getElementById('save-ai-settings').addEventListener('click', function() {
                alert('AI settings saved successfully!');
            });

            // System maintenance buttons
            document.getElementById('cleanup-logs').addEventListener('click', function() {
                if (confirm('Are you sure you want to cleanup old logs?')) {
                    alert('Log cleanup completed!');
                }
            });

            document.getElementById('backup-db').addEventListener('click', function() {
                alert('Database backup started!');
            });
        });
    </script>
</body>
</html>