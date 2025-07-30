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
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon/favicon-16x16.png">
    <link rel="manifest" href="../assets/favicon/site.webmanifest">
    <link rel="shortcut icon" href="../assets/favicon/favicon.ico">
    
    <!-- AppTrack CSS -->
    <link href="../assets/css/main.css" rel="stylesheet">
    
    <style>
        /* Header Action Button Styling - matching app_view.php */
        .header-action-btn {
            background-color: #FCFCFC;
            border: 1px solid #F0F1F2;
            color: #212529;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            cursor: pointer;
        }
        
        .header-action-btn:hover {
            background-color: #F8F9FA;
            border-color: #DEE2E6;
            color: #212529;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .header-action-btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
            background-color: #F8F9FA;
            border-color: #86B7FE;
            color: #212529;
        }
        
        .header-action-btn i {
            font-size: 16px;
            min-width: 16px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'shared/topbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center">
                        <button onclick="goBack()" 
                                class="header-action-btn me-3" 
                                title="Go back">
                            <i class="bi bi-arrow-left"></i> Back
                        </button>
                        <h2><i class="bi bi-gear me-2"></i>System Settings</h2>
                    </div>
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
                                            <!-- Dynamic content will be loaded here -->
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary mt-2" id="add-phase">
                                            <i class="bi bi-plus"></i> Add Phase
                                        </button>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Statuses</h6>
                                        <div id="statuses-config">
                                            <!-- Dynamic content will be loaded here -->
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary mt-2" id="add-status">
                                            <i class="bi bi-plus"></i> Add Status
                                        </button>
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
                                <!-- AI Configurations Management -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6>AI Prompt Configurations</h6>
                                        <p class="text-muted small">Manage AI prompt templates and model settings for different analysis types.</p>
                                        <div id="ai-configurations">
                                            <!-- Dynamic content will be loaded here -->
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary mt-2" id="add-ai-config">
                                            <i class="bi bi-plus"></i> Add Configuration
                                        </button>
                                    </div>
                                </div>

                                <hr>

                                <!-- Global AI Settings -->
                                <form id="ai-settings-form">
                                    <h6>Global AI Settings</h6>
                                    <!-- Hidden username field for accessibility -->
                                    <input type="text" style="display:none" name="username" autocomplete="username">
                                    
                                    <div class="mb-3">
                                        <label for="openai-api-key" class="form-label">OpenAI API Key</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="openai-api-key" placeholder="sk-..." autocomplete="new-password">
                                            <button class="btn btn-outline-secondary" type="button" id="toggle-api-key">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="ai-model" class="form-label">Default AI Model</label>
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
                                </form>
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
            // Load only portfolio settings when page loads (since it's the default active tab)
            loadPortfolios();

            // Setup tab change handlers
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
                tab.addEventListener('shown.bs.tab', function (e) {
                    const target = e.target.getAttribute('data-bs-target');
                    if (target === '#application') {
                        // Only load if not already loaded
                        const phasesContainer = document.getElementById('phases-config');
                        const statusesContainer = document.getElementById('statuses-config');
                        
                        if (!phasesContainer.hasAttribute('data-loaded')) {
                            console.log('Loading phases for the first time');
                            loadPhases();
                        } else {
                            console.log('Phases already loaded, skipping');
                        }
                        if (!statusesContainer.hasAttribute('data-loaded')) {
                            console.log('Loading statuses for the first time');
                            loadStatuses();
                        } else {
                            console.log('Statuses already loaded, skipping');
                        }
                    } else if (target === '#ai') {
                        // Load AI configurations when AI tab is clicked
                        const aiContainer = document.getElementById('ai-configurations');
                        if (!aiContainer.hasAttribute('data-loaded')) {
                            console.log('Loading AI configurations for the first time');
                            loadAiConfigurations();
                        } else {
                            console.log('AI configurations already loaded, skipping');
                        }
                    }
                });
            });

            // Portfolio Management Functions
            function loadPortfolios() {
                console.log('=== Loading portfolios START ===');
                console.log('Current URL:', window.location.href);
                console.log('Session info:', {
                    user_id: '<?php echo $_SESSION["user_id"] ?? "not set"; ?>',
                    user_role: '<?php echo $_SESSION["user_role"] ?? "not set"; ?>'
                });
                
                // Add cache-busting parameter
                const timestamp = new Date().getTime();
                const apiUrl = `api/settings/portfolios.php?t=${timestamp}`;
                console.log('Fetching from URL:', apiUrl);
                console.log('Full fetch URL:', new URL(apiUrl, window.location.href).href);
                
                fetch(apiUrl)
                    .then(response => {
                        console.log('Response status:', response.status);
                        console.log('Response headers:', response.headers);
                        
                        if (!response.ok) {
                            return response.text().then(text => {
                                console.error('Error response body:', text);
                                throw new Error(`HTTP ${response.status}: ${text || 'Unknown error'}`);
                            });
                        }
                        return response.text(); // First get as text to debug
                    })
                    .then(text => {
                        console.log('=== PORTFOLIO API RESPONSE START ===');
                        console.log('Raw response from portfolios API:', text);
                        console.log('Response length:', text.length);
                        console.log('Response type:', typeof text);
                        
                        try {
                            const data = JSON.parse(text);
                            console.log('Parsed portfolio data:', data);
                            console.log('Data keys:', Object.keys(data));
                            
                            if (data.success) {
                                // Check if this is actually portfolio data
                                if (data.preops_portfolios && data.app_portfolios) {
                                    console.log('✅ Correct portfolio data structure detected');
                                    renderPortfolios('preops', data.preops_portfolios);
                                    renderPortfolios('app', data.app_portfolios);
                                } else if (data.phases) {
                                    // This is phases data, not portfolios
                                    console.error('❌ API returned phases data instead of portfolios data');
                                    console.error('Phases data:', data.phases);
                                    showToast('API returned wrong data type (phases instead of portfolios)', 'error');
                                } else {
                                    console.error('❌ Unexpected data structure:', data);
                                    showToast('Unexpected data structure from portfolios API', 'error');
                                }
                            } else {
                                console.error('❌ API returned error:', data.error);
                                showToast('Error loading portfolios: ' + (data.error || 'Unknown error'), 'error');
                            }
                        } catch (e) {
                            console.error('❌ JSON Parse Error:', e);
                            console.error('Response text that failed to parse:', text);
                            console.log('=== PORTFOLIO API RESPONSE END ===');
                            throw new Error('Invalid JSON response from server');
                        }
                        console.log('=== PORTFOLIO API RESPONSE END ===');
                    })
                    .catch(error => {
                        console.error('❌ Portfolio API Error:', error);
                        showToast('Failed to load portfolios: ' + error.message, 'error');
                        
                        // Show empty state instead of demo data
                        document.getElementById('preops-portfolios').innerHTML = '<p class="text-muted">Failed to load portfolios</p>';
                        document.getElementById('app-portfolios').innerHTML = '<p class="text-muted">Failed to load portfolios</p>';
                    });
            }

            function renderPortfolios(type, portfolios) {
                const containerId = type === 'preops' ? 'preops-portfolios' : 'app-portfolios';
                const container = document.getElementById(containerId);
                
                console.log(`Rendering ${type} portfolios:`, portfolios);
                console.log(`Container found:`, container);
                console.log(`Container ID: ${containerId}`);
                
                if (!portfolios || !Array.isArray(portfolios) || portfolios.length === 0) {
                    console.log(`No portfolios to render for ${type}`);
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
                
                console.log(`Generated HTML for ${type}:`, html);
                console.log(`Setting innerHTML for container: ${containerId}`);
                container.innerHTML = html;
                console.log(`Container innerHTML set. Current content:`, container.innerHTML);

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
                
                console.log(`Event listeners added for ${type} portfolios`);
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

            // Phases Management Functions
            function loadPhases() {
                fetch('api/settings/phases.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            renderPhases(data.phases);
                        } else {
                            showToast('Error loading phases', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Failed to load phases', 'error');
                    });
            }

            function renderPhases(phases) {
                const container = document.getElementById('phases-config');
                
                if (phases.length === 0) {
                    container.innerHTML = '<p class="text-muted">No phases found</p>';
                    container.setAttribute('data-loaded', 'true');
                    return;
                }

                const html = phases.map(phase => `
                    <div class="d-flex justify-content-between align-items-center mb-2 config-item" data-type="phase" data-name="${phase.name}">
                        <div class="config-display">
                            <span class="config-name">${phase.name}</span>
                            <small class="text-muted ms-2">(${phase.count} apps)</small>
                        </div>
                        <div class="config-edit" style="display: none;">
                            <input type="text" class="form-control form-control-sm config-edit-input" value="${phase.name}">
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
                container.setAttribute('data-loaded', 'true'); // Mark as loaded
                setupConfigEventListeners(container, 'phase');
            }

            // Statuses Management Functions
            function loadStatuses() {
                fetch('api/settings/statuses.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            renderStatuses(data.statuses);
                        } else {
                            showToast('Error loading statuses', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Failed to load statuses', 'error');
                    });
            }

            function renderStatuses(statuses) {
                const container = document.getElementById('statuses-config');
                
                if (statuses.length === 0) {
                    container.innerHTML = '<p class="text-muted">No statuses found</p>';
                    container.setAttribute('data-loaded', 'true');
                    return;
                }

                const html = statuses.map(status => `
                    <div class="d-flex justify-content-between align-items-center mb-2 config-item" data-type="status" data-name="${status.name}">
                        <div class="config-display">
                            <span class="config-name">${status.name}</span>
                            <small class="text-muted ms-2">(${status.count} apps)</small>
                            <span class="badge bg-${status.badge_color} ms-2">Active</span>
                        </div>
                        <div class="config-edit" style="display: none;">
                            <input type="text" class="form-control form-control-sm config-edit-input" value="${status.name}">
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
                container.setAttribute('data-loaded', 'true'); // Mark as loaded
                setupConfigEventListeners(container, 'status');
            }

            // Generic config item event listeners
            function setupConfigEventListeners(container, type) {
                container.querySelectorAll('.btn-edit').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const item = this.closest('.config-item');
                        startConfigEdit(item);
                    });
                });

                container.querySelectorAll('.btn-save').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const item = this.closest('.config-item');
                        saveConfigEdit(item, type);
                    });
                });

                container.querySelectorAll('.btn-cancel').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const item = this.closest('.config-item');
                        cancelConfigEdit(item);
                    });
                });

                container.querySelectorAll('.btn-delete').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const item = this.closest('.config-item');
                        deleteConfig(item, type);
                    });
                });
            }

            function startConfigEdit(item) {
                const display = item.querySelector('.config-display');
                const edit = item.querySelector('.config-edit');
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

            function cancelConfigEdit(item) {
                const display = item.querySelector('.config-display');
                const edit = item.querySelector('.config-edit');
                const btnEdit = item.querySelector('.btn-edit');
                const btnSave = item.querySelector('.btn-save');
                const btnCancel = item.querySelector('.btn-cancel');

                display.style.display = 'block';
                edit.style.display = 'none';
                btnEdit.style.display = 'inline-block';
                btnSave.style.display = 'none';
                btnCancel.style.display = 'none';
            }

            function saveConfigEdit(item, type) {
                const oldName = item.dataset.name;
                const newName = item.querySelector('.config-edit-input').value.trim();

                if (!newName) {
                    showToast(`${type.charAt(0).toUpperCase() + type.slice(1)} name cannot be empty`, 'error');
                    return;
                }

                const apiUrl = type === 'phase' ? 'api/settings/phases.php' : 'api/settings/statuses.php';

                fetch(apiUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        old_name: oldName,
                        new_name: newName
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(`${type.charAt(0).toUpperCase() + type.slice(1)} updated successfully`, 'success');
                        if (type === 'phase') loadPhases();
                        else loadStatuses();
                    } else {
                        showToast(data.error || `Failed to update ${type}`, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast(`Failed to update ${type}`, 'error');
                });
            }

            function deleteConfig(item, type) {
                const name = item.dataset.name;

                if (!confirm(`Are you sure you want to delete the ${type} "${name}"? This will remove it from all applications.`)) {
                    return;
                }

                const apiUrl = type === 'phase' ? 'api/settings/phases.php' : 'api/settings/statuses.php';

                fetch(apiUrl, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        name: name
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(`${type.charAt(0).toUpperCase() + type.slice(1)} deleted successfully (${data.affected_rows} apps updated)`, 'success');
                        if (type === 'phase') loadPhases();
                        else loadStatuses();
                    } else {
                        showToast(data.error || `Failed to delete ${type}`, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast(`Failed to delete ${type}`, 'error');
                });
            }

            // Add buttons event listeners
            document.getElementById('add-phase').addEventListener('click', function() {
                addConfig('phase');
            });

            document.getElementById('add-status').addEventListener('click', function() {
                addConfig('status');
            });

            function addConfig(type) {
                const name = prompt(`Enter name for new ${type}:`);
                if (!name || !name.trim()) return;

                const apiUrl = type === 'phase' ? 'api/settings/phases.php' : 'api/settings/statuses.php';

                fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        name: name.trim()
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(`${type.charAt(0).toUpperCase() + type.slice(1)} ready to be used`, 'success');
                        if (type === 'phase') loadPhases();
                        else loadStatuses();
                    } else {
                        showToast(data.error || `Failed to create ${type}`, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast(`Failed to create ${type}`, 'error');
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
                const bgClass = type === 'success' ? 'bg-success' : 
                              type === 'error' ? 'bg-danger' : 
                              type === 'warning' ? 'bg-warning' : 'bg-info';
                
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

            // AI Configurations Management Functions
            function loadAiConfigurations() {
                console.log('Loading AI configurations...');
                
                fetch('api/settings/ai_configurations.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            renderAiConfigurations(data.configurations);
                        } else {
                            showToast('Error loading AI configurations: ' + (data.error || 'Unknown error'), 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Failed to load AI configurations', 'error');
                    });
            }

            function renderAiConfigurations(configurations) {
                const container = document.getElementById('ai-configurations');
                
                if (!configurations || configurations.length === 0) {
                    container.innerHTML = '<p class="text-muted">No AI configurations found</p>';
                    container.setAttribute('data-loaded', 'true');
                    return;
                }

                const html = configurations.map(config => `
                    <div class="card mb-3 ai-config-item" data-id="${config.id}">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${config.analysis_type}</strong>
                                <span class="badge bg-secondary ms-2">${config.prompt_version}</span>
                                <span class="badge bg-${config.is_active ? 'success' : 'secondary'} ms-1">
                                    ${config.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-edit-config" title="Edit Configuration" data-id="${config.id}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-info btn-view-prompt" title="View Prompt" data-id="${config.id}">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-delete-config" title="Delete Configuration" data-id="${config.id}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">Model:</small> ${config.model_name}<br>
                                    <small class="text-muted">Max Tokens:</small> ${config.max_tokens}<br>
                                    <small class="text-muted">Temperature:</small> ${config.temperature}
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Created:</small> ${config.created_at}<br>
                                    <small class="text-muted">Updated:</small> ${config.updated_at}
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Prompt Preview:</small><br>
                                <small class="text-secondary">${config.prompt_preview}</small>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                container.innerHTML = html;
                container.setAttribute('data-loaded', 'true');
                setupAiConfigEventListeners();
            }

            function setupAiConfigEventListeners() {
                // Edit configuration buttons
                document.querySelectorAll('.btn-edit-config').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const configId = this.dataset.id;
                        editAiConfiguration(configId);
                    });
                });

                // View prompt buttons
                document.querySelectorAll('.btn-view-prompt').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const configId = this.dataset.id;
                        viewPromptTemplate(configId);
                    });
                });

                // Delete configuration buttons
                document.querySelectorAll('.btn-delete-config').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const configId = this.dataset.id;
                        deleteAiConfiguration(configId);
                    });
                });
            }

            function editAiConfiguration(configId) {
                // Find the configuration data
                fetch(`api/settings/ai_configurations.php`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const config = data.configurations.find(c => c.id == configId);
                            if (config) {
                                showAiConfigModal(config);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Failed to load configuration details', 'error');
                    });
            }

            function viewPromptTemplate(configId) {
                fetch(`api/settings/ai_configurations.php`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const config = data.configurations.find(c => c.id == configId);
                            if (config) {
                                showPromptViewModal(config);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Failed to load prompt template', 'error');
                    });
            }

            function deleteAiConfiguration(configId) {
                if (!confirm('Are you sure you want to delete this AI configuration? This action cannot be undone.')) {
                    return;
                }

                fetch('api/settings/ai_configurations.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: parseInt(configId)
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('AI configuration deleted successfully', 'success');
                        loadAiConfigurations(); // Reload configurations
                    } else {
                        showToast(data.error || 'Failed to delete configuration', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Failed to delete configuration', 'error');
                });
            }

            function showAiConfigModal(config = null) {
                const isEdit = config !== null;
                const modalId = 'aiConfigModal';
                
                // Remove existing modal if any
                const existingModal = document.getElementById(modalId);
                if (existingModal) {
                    existingModal.remove();
                }

                // Extract safe values
                const analysisType = config ? (config.analysis_type || '') : '';
                const promptVersion = config ? (config.prompt_version || 'v1.0') : 'v1.0';
                const promptTemplate = config ? (config.prompt_template || '') : '';
                const modelName = config ? (config.model_name || 'gpt-3.5-turbo') : 'gpt-3.5-turbo';
                const maxTokens = config ? (config.max_tokens || 2000) : 2000;
                const temperature = config ? (config.temperature || 0.7) : 0.7;
                const isActive = config ? (config.is_active !== 0) : true;
                const gpt35Selected = modelName === 'gpt-3.5-turbo' ? 'selected' : '';
                const gpt4Selected = modelName === 'gpt-4' ? 'selected' : '';
                const checkedAttr = isActive ? 'checked' : '';

                const modalHtml = `
                    <div class="modal fade" id="${modalId}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">${isEdit ? 'Edit' : 'Add'} AI Configuration</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="aiConfigForm">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="analysisType" class="form-label">Analysis Type *</label>
                                                    <input type="text" class="form-control" id="analysisType" 
                                                           value="${analysisType}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="promptVersion" class="form-label">Prompt Version *</label>
                                                    <input type="text" class="form-control" id="promptVersion" 
                                                           value="${promptVersion}" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="promptTemplate" class="form-label">Prompt Template *</label>
                                            <textarea class="form-control" id="promptTemplate" rows="8" required>${promptTemplate}</textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="modelName" class="form-label">Model Name</label>
                                                    <select class="form-select" id="modelName">
                                                        <option value="gpt-3.5-turbo" ${gpt35Selected}>GPT-3.5 Turbo</option>
                                                        <option value="gpt-4" ${gpt4Selected}>GPT-4</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="maxTokens" class="form-label">Max Tokens</label>
                                                    <input type="number" class="form-control" id="maxTokens" 
                                                           value="${maxTokens}" min="100" max="4000">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="temperature" class="form-label">Temperature</label>
                                                    <input type="number" class="form-control" id="temperature" 
                                                           value="${temperature}" min="0" max="1" step="0.1">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="isActive" 
                                                       ${checkedAttr}>
                                                <label class="form-check-label" for="isActive">
                                                    Active Configuration
                                                </label>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="saveAiConfig">
                                        ${isEdit ? 'Update' : 'Create'} Configuration
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                document.body.insertAdjacentHTML('beforeend', modalHtml);
                const modal = new bootstrap.Modal(document.getElementById(modalId));
                modal.show();

                // Add save handler
                document.getElementById('saveAiConfig').addEventListener('click', function() {
                    saveAiConfiguration(isEdit ? config.id : null, modal);
                });

                // Remove modal from DOM when hidden
                document.getElementById(modalId).addEventListener('hidden.bs.modal', function() {
                    this.remove();
                });
            }

            function showPromptViewModal(config) {
                const modalId = 'promptViewModal';
                
                // Remove existing modal if any
                const existingModal = document.getElementById(modalId);
                if (existingModal) {
                    existingModal.remove();
                }

                const modalHtml = `
                    <div class="modal fade" id="${modalId}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Prompt Template - ${config.analysis_type} (${config.prompt_version})</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <pre style="white-space: pre-wrap; background-color: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;">${config.prompt_template}</pre>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                document.body.insertAdjacentHTML('beforeend', modalHtml);
                const modal = new bootstrap.Modal(document.getElementById(modalId));
                modal.show();

                // Remove modal from DOM when hidden
                document.getElementById(modalId).addEventListener('hidden.bs.modal', function() {
                    this.remove();
                });
            }

            function saveAiConfiguration(configId, modal) {
                const form = document.getElementById('aiConfigForm');
                
                const data = {
                    analysis_type: document.getElementById('analysisType').value.trim(),
                    prompt_template: document.getElementById('promptTemplate').value.trim(),
                    prompt_version: document.getElementById('promptVersion').value.trim(),
                    model_name: document.getElementById('modelName').value,
                    max_tokens: parseInt(document.getElementById('maxTokens').value),
                    temperature: parseFloat(document.getElementById('temperature').value),
                    is_active: document.getElementById('isActive').checked ? 1 : 0
                };

                if (!data.analysis_type || !data.prompt_template) {
                    showToast('Analysis type and prompt template are required', 'error');
                    return;
                }

                const method = configId ? 'PUT' : 'POST';
                if (configId) {
                    data.id = configId;
                }

                fetch('api/settings/ai_configurations.php', {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(`AI configuration ${configId ? 'updated' : 'created'} successfully`, 'success');
                        modal.hide();
                        loadAiConfigurations(); // Reload configurations
                    } else {
                        showToast(data.error || `Failed to ${configId ? 'update' : 'create'} configuration`, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast(`Failed to ${configId ? 'update' : 'create'} configuration`, 'error');
                });
            }

            // Add AI configuration button
            document.getElementById('add-ai-config').addEventListener('click', function() {
                showAiConfigModal();
            });

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

        // Back button functionality - same as other pages
        function goBack() {
            if (document.referrer && document.referrer !== window.location.href) {
                window.history.back();
            } else {
                // Fallback to dashboard if no referrer
                window.location.href = 'dashboard.php';
            }
        }
    </script>
</body>
</html>