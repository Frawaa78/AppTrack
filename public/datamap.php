<?php
require_once '../src/config/config.php';
require_once '../src/db/db.php';
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ensure user data is loaded in session
if (!isset($_SESSION['user_display_name']) || !isset($_SESSION['user_email'])) {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        $stmt = $pdo->prepare("SELECT email, display_name, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_display_name'] = $user['display_name'];
            $_SESSION['user_role'] = $user['role'];
        }
    } catch (Exception $e) {
        // If we can't load user data, continue with what we have
    }
}

// Get application ID from URL parameter
$application_id = isset($_GET['app_id']) ? intval($_GET['app_id']) : null;

if (!$application_id) {
    // Redirect with more helpful error
    header('Location: /public/dashboard.php?error=DataMap editor requires an application ID. Please select an application first.');
    exit;
}

// Get application details for context
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("SELECT short_description as name, business_need as description, status FROM applications WHERE id = ?");
    $stmt->execute([$application_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        header('Location: /public/dashboard.php?error=Application not found. Please check the application ID.');
        exit;
    }
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DataMap - <?php echo htmlspecialchars($application['name']); ?> | AppTrack</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon/favicon-16x16.png">
    <link rel="manifest" href="../assets/favicon/site.webmanifest">
    <link rel="shortcut icon" href="../assets/favicon/favicon.ico">
    <!-- FontAwesome Pro -->
    <script src="https://kit.fontawesome.com/d67c79608d.js" crossorigin="anonymous"></script>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- AppTrack CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    
    <!-- Drawflow CSS and Custom Theme -->
    <link href="/assets/vendor/drawflow.min.css" rel="stylesheet">
    <link href="/assets/css/components/drawflow-theme.css" rel="stylesheet">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }
        
        .content-wrapper {
            height: calc(100vh - 60px); /* Account for topbar */
            max-width: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 0;
            align-items: stretch;
        }
        
        .sidebar {
            width: 200px;
            flex-shrink: 0;
            background: #f8f9fa;
            border-right: 1px solid #ddd;
            margin-top: 0;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .app-header {
            background: #fff;
            border-bottom: 1px solid #ddd;
            padding: 10px 20px;
            margin: 0;
            box-shadow: none;
            flex-shrink: 0;
        }
        
        .app-header button {
            transition: all 0.2s;
        }
        
        .app-header button:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .app-title {
            margin: 0;
            color: #333;
            font-size: 20px;
        }
        
        .app-meta {
            color: #666;
            font-size: 14px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            margin-left: 10px;
        }
        
        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.development {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-badge.archived {
            background: #f8d7da;
            color: #721c24;
        }
        
        .editor-section {
            background: #fff;
            border-radius: 0;
            overflow: hidden;
            box-shadow: none;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .toolbar {
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            padding: 8px 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        
        .toolbar-left {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .toolbar-right {
            display: flex;
            align-items: center;
        }
        
        .sidebar-section {
            background: transparent;
            border-radius: 0;
            margin-bottom: 0;
            box-shadow: none;
            border-bottom: 1px solid #ddd;
        }
        
        .sidebar-header {
            padding: 15px;
            background: #fff;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            font-size: 14px;
            color: #333;
            border-radius: 0;
        }
        
        .sidebar-content {
            padding: 10px;
        }
        
        .node-item {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            margin-bottom: 8px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 12.6px;
        }
        
        .node-item:hover {
            background: #e9ecef;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .node-item:last-child {
            margin-bottom: 0;
        }
        
        .node-icon {
            margin-right: 8px;
            font-size: 16px;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .action-btn {
            padding: 10px 15px;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
            text-align: left;
        }
        
        .action-btn:hover {
            background: #f8f9fa;
            transform: translateY(-1px);
        }
        
        .action-btn.primary {
            background: #4EA5D9;
            color: white;
            border-color: #4EA5D9;
        }
        
        .action-btn.primary:hover {
            background: #3d8bb3;
        }
        
        .action-btn.danger {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }
        
        .action-btn.danger:hover {
            background: #c82333;
        }
        
        .toolbar-group {
            display: flex;
            gap: 8px;
            margin-right: 20px;
        }
        
        .toolbar-group:last-child {
            margin-right: 0;
        }
        
        .toolbar-label {
            font-weight: 500;
            color: #666;
            margin-right: 8px;
            font-size: 14px;
        }
        
        .toolbar button {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .toolbar button:hover {
            background: #f8f9fa;
            transform: translateY(-1px);
        }
        
        .toolbar button.primary {
            background: #4EA5D9;
            color: white;
            border-color: #4EA5D9;
        }
        
        .toolbar button.primary:hover {
            background: #3d8bb3;
        }
        
        .editor-container {
            width: 100%;
            flex: 1;
            position: relative;
            min-height: 0; /* Important for flex child to shrink */
        }
        
        #drawflow {
            width: 100%;
            height: 100%;
            position: relative;
        }
        
        .status-panel {
            margin-top: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .status-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            font-weight: 500;
            color: #333;
        }
        
        .status-content {
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 200px;
            overflow-y: auto;
            background: #f8f9fa;
            line-height: 1.4;
        }
        
        .breadcrumb {
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .breadcrumb a {
            color: #4EA5D9;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .auto-save-indicator {
            display: inline-flex;
            align-items: center;
            font-size: 12px;
            color: #666;
            padding: 6px 10px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .auto-save-indicator.saving {
            color: #ffc107;
        }
        
        .auto-save-indicator.saved {
            color: #28a745;
        }
        
        .auto-save-indicator.error {
            color: #dc3545;
        }
        
        /* Node textarea styling */
        .node-description {
            font-family: inherit;
            transition: height 0.1s ease;
            overflow: hidden;
            text-align: left !important;
        }
        
        .node-description:focus {
            background: rgba(255, 255, 255, 0.9) !important;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
            border-radius: 2px;
        }
        
        /* Node title styling */
        .node-title {
            text-align: left !important;
        }
        
        .node-title i {
            margin-right: 6px;
        }
        
        /* Fix for topbar dropdown */
        .dropdown-menu {
            z-index: 9999 !important;
        }
        
        .navbar {
            z-index: 1050 !important;
        }
        
        /* Header Action Button Styling - for Back button */
        .header-action-btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            color: #6c757d;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .header-action-btn:hover {
            background: #f8f9fa;
            color: #495057;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-action-btn i {
            margin-right: 6px;
            font-size: 16px;
        }
        
        .header-action-btn.me-3 {
            margin-right: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'shared/topbar.php'; ?>
    
    <div class="content-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Node Elements -->
            <div class="sidebar-section">
                <div class="sidebar-header">Add Elements</div>
                <div class="sidebar-content" id="nodeTemplates">
                    <!-- Node templates will be loaded dynamically -->
                    <div style="text-align: center; padding: 20px; color: #666;">
                        <i class="fas fa-spinner fa-spin"></i> Loading elements...
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Application Header -->
            <div class="app-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <a href="javascript:history.back()" class="header-action-btn me-3" title="Go back to previous page">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                        <h1 class="app-title" style="margin: 0;">
                            DataMap: <?php echo htmlspecialchars($application['name']); ?>
                            <span class="status-badge <?php echo strtolower($application['status']); ?>">
                                <?php echo htmlspecialchars($application['status']); ?>
                            </span>
                        </h1>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button onclick="saveDiagram()" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save
                        </button>
                    </div>
                </div>
            </div>
        
        <!-- Flow Editor -->
        <div class="editor-section">
            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-group">
                        <button onclick="zoomIn()" title="Zoom In">
                            <i class="fas fa-search-plus"></i>
                        </button>
                        <button onclick="zoomOut()" title="Zoom Out">
                            <i class="fas fa-search-minus"></i>
                        </button>
                        <button onclick="zoomReset()" title="Reset Zoom">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </button>
                    </div>
                    <div class="toolbar-group">
                        <button onclick="clearDiagram()" title="Clear Diagram" style="color: #dc3545;">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button onclick="exportDiagram()" title="Export Diagram">
                            <i class="fas fa-download"></i>
                        </button>
                        <button onclick="updateAllConnectionPositions()" title="Fix Connection Lines" style="color: #28a745;">
                            <i class="fas fa-wrench"></i>
                        </button>
                    </div>
                </div>
                
                <div class="toolbar-right">
                    <div class="auto-save-indicator" id="saveStatus">
                        <i class="fas fa-check-circle"></i> Auto-save enabled
                    </div>
                </div>
            </div>
            
            <div class="editor-container">
                <div id="drawflow"></div>
            </div>
        </div>
        
        </div> <!-- Close main-content -->
    </div>

    <!-- Drawflow JS -->
    <script src="/assets/vendor/drawflow.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let editor;
        let nodeCounter = 1;
        let isAutoSaving = false;
        const applicationId = <?php echo $application_id; ?>;
        
        // Initialize
        window.addEventListener('DOMContentLoaded', function() {
            log('🔄 Initializing DataMap Editor...');
            
            const container = document.getElementById('drawflow');
            editor = new Drawflow(container);
            editor.reroute = true;
            editor.curvature = 0.5;
            editor.start();
            
            // Force transparent background to override inline CSS
            setTimeout(() => {
                const drawflowContainer = document.getElementById('drawflow');
                if (drawflowContainer) {
                    drawflowContainer.style.background = 'transparent';
                    drawflowContainer.style.backgroundColor = 'transparent';
                    log('🎨 Forced drawflow background to transparent');
                }
            }, 100);
            
            log('✅ DataMap Editor initialized');
            loadNodeTemplates();
            loadDiagram();
            
            // Set up auto-save on changes
            editor.on('nodeCreated', (id) => {
                autoSave();
                // Set up drag handle for the newly created node
                setTimeout(() => setupDragHandles(), 50);
            });
            editor.on('nodeRemoved', () => autoSave());
            editor.on('nodeMoved', () => {
                // Update comment connections when nodes are moved
                setTimeout(() => {
                    updateCommentConnections();
                }, 50);
                autoSave();
            });
            editor.on('connectionCreated', () => autoSave());
            editor.on('connectionRemoved', () => autoSave());
            
            // Fix connection positions on window resize
            window.addEventListener('resize', function() {
                setTimeout(() => {
                    updateAllConnectionPositions();
                    updateCommentConnections(); // Also update comment connections
                }, 100);
            });
            
            // Set up real-time comment connection updates during node dragging
            const observer = new MutationObserver((mutations) => {
                let needsUpdate = false;
                mutations.forEach(mutation => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        const target = mutation.target;
                        // Check if this is a drawflow node being moved
                        if (target.classList.contains('drawflow-node') || target.closest('.drawflow-node')) {
                            needsUpdate = true;
                        }
                    }
                });
                
                if (needsUpdate) {
                    // Use requestAnimationFrame for optimal performance and smoothness
                    if (window.commentConnectionAnimationFrame) {
                        cancelAnimationFrame(window.commentConnectionAnimationFrame);
                    }
                    window.commentConnectionAnimationFrame = requestAnimationFrame(() => {
                        updateCommentConnections();
                    });
                }
            });
            
            // Start observing the drawflow container for changes
            const drawflowContainer = document.querySelector('.drawflow');
            if (drawflowContainer) {
                observer.observe(drawflowContainer, {
                    attributes: true,
                    subtree: true,
                    attributeFilter: ['style']
                });
            }
            
            // Set up context menu for nodes
            setupContextMenu();
            
            // Set up drag handles for nodes
            setupDragHandles();
        });
        
        // Set up context menu functionality
        function setupContextMenu() {
            // Hide any existing context menu on document click
            document.addEventListener('click', hideContextMenu);
            
            // Disable default browser context menu on drawflow nodes and connections
            document.addEventListener('contextmenu', function(e) {
                // Check if right-clicking on a node
                if (e.target.closest('.drawflow-node')) {
                    e.preventDefault();
                    showContextMenu(e, e.target.closest('.drawflow-node'));
                }
                // Check if right-clicking on a connection line
                else if (e.target.closest('.connection')) {
                    e.preventDefault();
                    showConnectionContextMenu(e, e.target.closest('.connection'));
                }
            });
        }
        
        // Set up drag handles functionality
        function setupDragHandles() {
            // Get all nodes with drag handles
            const dragHandles = document.querySelectorAll('.node-drag-handle');
            
            dragHandles.forEach(handle => {
                // Skip if already has event listener
                if (handle.hasAttribute('data-drag-initialized')) {
                    return;
                }
                
                const nodeElement = handle.closest('.drawflow-node');
                if (!nodeElement) return;
                
                // Find interactive elements that should not trigger dragging
                const titleElement = nodeElement.querySelector('.node-title');
                const descriptionElement = nodeElement.querySelector('.node-description');
                const inputElements = nodeElement.querySelectorAll('.input');
                const outputElements = nodeElement.querySelectorAll('.output');
                
                // Add specific handlers for text elements to ensure they work properly
                if (titleElement) {
                    titleElement.addEventListener('mousedown', function(e) {
                        e.stopImmediatePropagation();
                    });
                    titleElement.addEventListener('click', function(e) {
                        e.stopImmediatePropagation();
                    });
                }
                
                if (descriptionElement) {
                    descriptionElement.addEventListener('mousedown', function(e) {
                        e.stopImmediatePropagation();
                    });
                    descriptionElement.addEventListener('click', function(e) {
                        e.stopImmediatePropagation();
                    });
                }
                
                // Ensure input/output circles still work for connections
                inputElements.forEach(element => {
                    element.addEventListener('mousedown', function(e) {
                        // Allow input connection behavior but prevent node dragging
                        e.stopPropagation(); 
                    });
                });
                
                // DON'T add event listeners to outputs - let Drawflow handle them completely
                // outputElements.forEach(element => {
                //     element.addEventListener('mousedown', function(e) {
                //         e.stopPropagation(); 
                //     });
                // });
                
                // Override Drawflow's default mousedown behavior
                nodeElement.addEventListener('mousedown', function(e) {
                    // Allow output connections to work normally
                    if (e.target.closest('.output')) {
                        // This is an output - let Drawflow handle it completely
                        return true;
                    }
                    
                    // Allow input connections to work normally  
                    if (e.target.closest('.input')) {
                        // This is an input - let Drawflow handle it completely
                        return true;
                    }
                    
                    // Only allow dragging if clicking on the drag handle
                    if (e.target.closest('.node-drag-handle')) {
                        // This is a valid drag - let Drawflow handle it normally
                        return true;
                    } else {
                        // This is not a drag handle or connection point - prevent Drawflow from starting drag
                        e.stopPropagation();
                        return false;
                    }
                }, true);
                
                // Mark as initialized
                handle.setAttribute('data-drag-initialized', 'true');
            });
        }
        
        // Show custom context menu
        function showContextMenu(event, nodeElement) {
            hideContextMenu(); // Hide any existing menu
            
            const nodeId = nodeElement.id.replace('node-', '');
            const nodeData = editor.drawflow.drawflow.Home.data[nodeId];
            const isCommentNode = nodeData && nodeData.class && nodeData.class.includes('comment-node');
            
            const menu = document.createElement('div');
            menu.className = 'custom-context-menu';
            menu.id = 'contextMenu';
            
            // Menu items based on node type
            let menuItems = [];
            
            if (isCommentNode) {
                menuItems = [
                    { icon: 'fas fa-link', text: 'Connect to', action: null, submenu: () => getConnectSubmenu(nodeId) },
                    { divider: true },
                    { icon: 'fas fa-trash', text: 'Delete', action: () => deleteNode(nodeId), danger: true }
                ];
            } else {
                menuItems = [
                    { icon: 'fas fa-trash', text: 'Delete', action: () => deleteNode(nodeId), danger: true }
                ];
            }
            
            // Build menu HTML
            menuItems.forEach(item => {
                if (item.divider) {
                    const divider = document.createElement('div');
                    divider.className = 'context-menu-divider';
                    menu.appendChild(divider);
                } else {
                    const menuItem = document.createElement('div');
                    menuItem.className = `context-menu-item ${item.danger ? 'danger' : ''}${item.submenu ? ' has-submenu' : ''}`;
                    
                    let iconHtml = `<i class="${item.icon}"></i>${item.text}`;
                    if (item.submenu) {
                        iconHtml += '<i class="fas fa-chevron-right submenu-arrow"></i>';
                    }
                    menuItem.innerHTML = iconHtml;
                    
                    if (item.submenu) {
                        // Handle submenu on hover
                        let submenuTimeout;
                        menuItem.onmouseenter = () => {
                            clearTimeout(submenuTimeout);
                            showSubmenu(menuItem, item.submenu(), event);
                        };
                        menuItem.onmouseleave = () => {
                            submenuTimeout = setTimeout(() => {
                                const submenu = document.getElementById('contextSubmenu');
                                if (submenu && !submenu.matches(':hover')) {
                                    hideSubmenu();
                                }
                            }, 300); // Small delay to allow moving to submenu
                        };
                    } else if (item.action) {
                        menuItem.onclick = () => {
                            item.action();
                            hideContextMenu();
                        };
                    }
                    
                    menu.appendChild(menuItem);
                }
            });
            
            // Position and show menu
            document.body.appendChild(menu);
            
            // Adjust position to stay within viewport
            const rect = menu.getBoundingClientRect();
            let x = event.pageX;
            let y = event.pageY;
            
            if (x + rect.width > window.innerWidth) {
                x = window.innerWidth - rect.width - 10;
            }
            if (y + rect.height > window.innerHeight) {
                y = window.innerHeight - rect.height - 10;
            }
            
            menu.style.left = x + 'px';
            menu.style.top = y + 'px';
            
            log('📋 Context menu shown for node ' + nodeId);
        }
        
        // Show context menu for connection lines
        function showConnectionContextMenu(event, connectionElement) {
            hideContextMenu(); // Hide any existing menu
            
            const menu = document.createElement('div');
            menu.className = 'custom-context-menu';
            menu.id = 'contextMenu';
            
            // Menu items for connections
            const menuItems = [
                { icon: 'fas fa-trash', text: 'Delete Connection', action: () => deleteConnection(connectionElement), danger: true }
            ];
            
            // Build menu HTML
            menuItems.forEach(item => {
                const menuItem = document.createElement('div');
                menuItem.className = `context-menu-item ${item.danger ? 'danger' : ''}`;
                menuItem.innerHTML = `<i class="${item.icon}"></i>${item.text}`;
                menuItem.onclick = () => {
                    item.action();
                    hideContextMenu();
                };
                menu.appendChild(menuItem);
            });
            
            // Position and show menu
            document.body.appendChild(menu);
            
            // Adjust position to stay within viewport
            const rect = menu.getBoundingClientRect();
            let x = event.pageX;
            let y = event.pageY;
            
            if (x + rect.width > window.innerWidth) {
                x = window.innerWidth - rect.width - 10;
            }
            if (y + rect.height > window.innerHeight) {
                y = window.innerHeight - rect.height - 10;
            }
            
            menu.style.left = x + 'px';
            menu.style.top = y + 'px';
            
            log('📋 Context menu shown for connection');
        }
        
        // Generate submenu items for connecting comment to other nodes
        function getConnectSubmenu(commentNodeId) {
            if (!editor || !editor.drawflow || !editor.drawflow.drawflow || !editor.drawflow.drawflow.Home) {
                return [];
            }
            
            const nodes = editor.drawflow.drawflow.Home.data || {};
            const submenuItems = [];
            
            Object.keys(nodes).forEach(nodeId => {
                if (nodeId !== commentNodeId) { // Don't include the comment node itself
                    const node = nodes[nodeId];
                    
                    // Skip other comment nodes - only allow connections to regular nodes
                    const isCommentNode = node && node.class && node.class.includes('comment-node');
                    if (isCommentNode) {
                        return; // Skip this node
                    }
                    
                    const nodeElement = document.getElementById(`node-${nodeId}`);
                    let nodeTitle = 'Untitled Node';
                    
                    // Get the actual title from the node data or DOM
                    if (node.data && node.data.title) {
                        nodeTitle = node.data.title;
                    } else if (nodeElement) {
                        const titleElement = nodeElement.querySelector('.node-title');
                        if (titleElement) {
                            nodeTitle = titleElement.textContent.trim();
                        }
                    }
                    
                    // Check if already connected
                    const isConnected = isCommentConnectedToNode(commentNodeId, nodeId);
                    
                    submenuItems.push({
                        icon: isConnected ? 'fas fa-unlink' : 'fas fa-link',
                        text: isConnected ? `Disconnect from ${nodeTitle}` : `Connect to ${nodeTitle}`,
                        action: () => toggleCommentConnection(commentNodeId, nodeId),
                        connected: isConnected
                    });
                }
            });
            
            if (submenuItems.length === 0) {
                submenuItems.push({
                    icon: 'fas fa-info-circle',
                    text: 'No nodes available',
                    action: null,
                    disabled: true
                });
            }
            
            return submenuItems;
        }
        
        // Check if comment is already connected to a specific node
        function isCommentConnectedToNode(commentNodeId, targetNodeId) {
            // Check custom comment connections first
            if (window.commentConnections && window.commentConnections[commentNodeId]) {
                const connection = window.commentConnections[commentNodeId].find(conn => conn.targetId === targetNodeId);
                if (connection) {
                    return true;
                }
            }
            
            // Fallback to check standard Drawflow connections (for backward compatibility)
            if (!editor || !editor.drawflow || !editor.drawflow.drawflow || !editor.drawflow.drawflow.Home) {
                return false;
            }
            
            const commentNode = editor.drawflow.drawflow.Home.data[commentNodeId];
            if (!commentNode || !commentNode.outputs) return false;
            
            // Check all outputs of the comment node
            for (const outputKey in commentNode.outputs) {
                const output = commentNode.outputs[outputKey];
                if (output.connections) {
                    for (const connection of output.connections) {
                        if (connection.node === targetNodeId) {
                            return true;
                        }
                    }
                }
            }
            
            return false;
        }
        
        // Update all comment connection visuals
        function updateCommentConnections() {
            console.log('🔄 updateCommentConnections called');
            
            if (!window.commentConnections) {
                console.log('⚠️ No commentConnections found');
                return;
            }
            
            console.log('📊 Current commentConnections:', window.commentConnections);
            
            // Remove existing comment connection elements
            const existingConnections = document.querySelectorAll('.comment-connection');
            console.log('🗑️ Removing', existingConnections.length, 'existing connections');
            existingConnections.forEach(el => el.remove());
            
            // Clean up connections for nodes that no longer exist
            cleanupInvalidConnections();
            
            // Redraw all comment connections
            Object.keys(window.commentConnections).forEach(commentNodeId => {
                const connections = window.commentConnections[commentNodeId];
                console.log(`🎨 Drawing ${connections.length} connections for comment ${commentNodeId}:`, connections);
                
                connections.forEach(conn => {
                    console.log(`🖌️ Drawing connection: ${commentNodeId} -> ${conn.targetId} (${conn.connectionId})`);
                    drawCommentConnection(commentNodeId, conn.targetId, conn.connectionId);
                });
            });
            
            console.log('✅ updateCommentConnections completed');
        }
        
        // Clean up connections for nodes that no longer exist in DOM
        function cleanupInvalidConnections() {
            if (!window.commentConnections) return;
            
            let hasChanges = false;
            
            Object.keys(window.commentConnections).forEach(commentNodeId => {
                // Check if comment node still exists
                const commentElement = document.getElementById(`node-${commentNodeId}`);
                if (!commentElement) {
                    console.log('🧹 Removing connections for deleted comment node:', commentNodeId);
                    delete window.commentConnections[commentNodeId];
                    hasChanges = true;
                    return;
                }
                
                // Check if target nodes still exist
                const validConnections = window.commentConnections[commentNodeId].filter(conn => {
                    const targetElement = document.getElementById(`node-${conn.targetId}`);
                    if (!targetElement) {
                        console.log('🧹 Removing connection to deleted target node:', conn.targetId);
                        hasChanges = true;
                        return false;
                    }
                    return true;
                });
                
                window.commentConnections[commentNodeId] = validConnections;
                
                // Remove empty connection groups
                if (validConnections.length === 0) {
                    delete window.commentConnections[commentNodeId];
                    hasChanges = true;
                }
            });
            
            if (hasChanges) {
                console.log('🧹 Cleaned up invalid connections. Updated data:', window.commentConnections);
            }
        }
        
        // Draw a single comment connection line
        function drawCommentConnection(commentNodeId, targetNodeId, connectionId) {
            console.log('🎨 Drawing comment connection:', commentNodeId, '->', targetNodeId, 'ID:', connectionId);
            
            const commentElement = document.getElementById(`node-${commentNodeId}`);
            const targetElement = document.getElementById(`node-${targetNodeId}`);
            
            if (!commentElement) {
                console.log('⚠️ Comment element not found (skipping):', `node-${commentNodeId}`);
                return;
            }
            
            if (!targetElement) {
                console.log('⚠️ Target element not found (skipping):', `node-${targetNodeId}`);
                return;
            }
            
            console.log('✅ Both elements found');
            
            // Get the drawflow container - use the same parent as regular connections
            const drawflowContainer = document.querySelector('.drawflow');
            if (!drawflowContainer) {
                console.error('❌ Drawflow container not found');
                return;
            }
            
            console.log('✅ Drawflow container found');
            
            // Calculate positions (center of each node)
            const commentRect = commentElement.getBoundingClientRect();
            const targetRect = targetElement.getBoundingClientRect();
            const containerRect = drawflowContainer.getBoundingClientRect();
            
            console.log('📐 Comment rect:', commentRect);
            console.log('📐 Target rect:', targetRect);
            console.log('📐 Container rect:', containerRect);
            
            // Calculate relative positions within the drawflow container
            const commentCenter = {
                x: commentRect.left + commentRect.width / 2 - containerRect.left,
                y: commentRect.top + commentRect.height / 2 - containerRect.top
            };
            
            const targetCenter = {
                x: targetRect.left + targetRect.width / 2 - containerRect.left,
                y: targetRect.top + targetRect.height / 2 - containerRect.top
            };
            
            console.log('📍 Comment center:', commentCenter);
            console.log('📍 Target center:', targetCenter);
            
            // Create SVG element similar to how Drawflow creates connections
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.classList.add('comment-connection'); // Use Drawflow-style class structure
            svg.setAttribute('id', connectionId);
            
            // Position SVG to cover the entire drawflow area like regular connections
            svg.style.position = 'absolute';
            svg.style.top = '0';
            svg.style.left = '0';
            svg.style.width = '100%';
            svg.style.height = '100%';
            svg.style.pointerEvents = 'none';
            svg.style.overflow = 'visible';
            // No z-index - let it follow natural stacking order like regular connections
            
            console.log('✅ SVG element created');
            
            // Create the path element with Drawflow-style classes
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.classList.add('comment-path'); // Similar to main-path for regular connections
            
            // Create a smooth curve from comment center to target center
            const pathData = `M ${commentCenter.x} ${commentCenter.y} Q ${(commentCenter.x + targetCenter.x) / 2} ${commentCenter.y - 50} ${targetCenter.x} ${targetCenter.y}`;
            path.setAttribute('d', pathData);
            
            console.log('📏 Path data:', pathData);
            
            // Add context menu capability to the path
            path.style.pointerEvents = 'stroke';
            path.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                showCommentConnectionContextMenu(e, commentNodeId, targetNodeId);
            });
            
            svg.appendChild(path);
            drawflowContainer.appendChild(svg);
            
            console.log('✅ SVG and path added to DOM');
            console.log('🎨 Comment connection drawn successfully!');
        }
        
        // Show context menu for comment connections
        function showCommentConnectionContextMenu(event, commentNodeId, targetNodeId) {
            hideContextMenu(); // Hide any existing menu
            
            const menu = document.createElement('div');
            menu.className = 'custom-context-menu';
            menu.id = 'contextMenu';
            
            // Get node titles for the menu
            const commentNode = editor.drawflow.drawflow.Home.data[commentNodeId];
            const targetNode = editor.drawflow.drawflow.Home.data[targetNodeId];
            
            let commentTitle = 'Comment';
            let targetTitle = 'Node';
            
            if (commentNode && commentNode.data && commentNode.data.title) {
                commentTitle = commentNode.data.title;
            }
            if (targetNode && targetNode.data && targetNode.data.title) {
                targetTitle = targetNode.data.title;
            }
            
            // Menu items for comment connections
            const menuItems = [
                { 
                    icon: 'fas fa-unlink', 
                    text: `Disconnect "${commentTitle}" from "${targetTitle}"`, 
                    action: () => {
                        removeCommentConnection(commentNodeId, targetNodeId);
                        autoSave();
                    }, 
                    danger: true 
                }
            ];
            
            // Build menu HTML
            menuItems.forEach(item => {
                const menuItem = document.createElement('div');
                menuItem.className = `context-menu-item ${item.danger ? 'danger' : ''}`;
                menuItem.innerHTML = `<i class="${item.icon}"></i>${item.text}`;
                menuItem.onclick = () => {
                    item.action();
                    hideContextMenu();
                };
                menu.appendChild(menuItem);
            });
            
            // Position and show menu
            document.body.appendChild(menu);
            
            // Adjust position to stay within viewport
            const rect = menu.getBoundingClientRect();
            let x = event.pageX;
            let y = event.pageY;
            
            if (x + rect.width > window.innerWidth) {
                x = window.innerWidth - rect.width - 10;
            }
            if (y + rect.height > window.innerHeight) {
                y = window.innerHeight - rect.height - 10;
            }
            
            menu.style.left = x + 'px';
            menu.style.top = y + 'px';
            
            log('📋 Comment connection context menu shown');
        }
        
        // Toggle connection between comment and target node
        function toggleCommentConnection(commentNodeId, targetNodeId) {
            try {
                const isConnected = isCommentConnectedToNode(commentNodeId, targetNodeId);
                
                if (isConnected) {
                    // Disconnect
                    disconnectNodes(commentNodeId, targetNodeId);
                    log(`💬 Disconnected comment ${commentNodeId} from node ${targetNodeId}`);
                } else {
                    // Connect
                    connectNodes(commentNodeId, targetNodeId);
                    log(`💬 Connected comment ${commentNodeId} to node ${targetNodeId}`);
                }
                
                autoSave();
            } catch (error) {
                log('❌ Error toggling comment connection: ' + error.message);
            }
        }
        
        // Connect two nodes programmatically
        function connectNodes(fromNodeId, toNodeId) {
            // Check if it's a comment connection (from comment to any node)
            const fromNode = editor.drawflow.drawflow.Home.data[fromNodeId];
            const isCommentConnection = fromNode && fromNode.class && fromNode.class.includes('comment-node');
            
            if (isCommentConnection) {
                // Create custom comment connection
                createCommentConnection(fromNodeId, toNodeId);
            } else {
                // Use standard Drawflow connection for regular nodes
                createStandardConnection(fromNodeId, toNodeId);
            }
        }
        
        // Create standard Drawflow connection
        function createStandardConnection(fromNodeId, toNodeId) {
            const fromNode = editor.drawflow.drawflow.Home.data[fromNodeId];
            const toNode = editor.drawflow.drawflow.Home.data[toNodeId];
            
            if (!fromNode || !toNode) {
                throw new Error('Source or target node not found');
            }
            
            // Find first available output on source node and first available input on target node
            let outputKey = null;
            let inputKey = null;
            
            // Get first output from source node
            if (fromNode.outputs) {
                outputKey = Object.keys(fromNode.outputs)[0];
            }
            
            // Get first input from target node
            if (toNode.inputs) {
                inputKey = Object.keys(toNode.inputs)[0];
            }
            
            if (outputKey && inputKey) {
                // Use Drawflow's connection method
                editor.addConnection(fromNodeId, toNodeId, outputKey, inputKey);
                
                // Update connection positions
                setTimeout(() => {
                    editor.updateConnectionNodes(`node-${fromNodeId}`);
                    editor.updateConnectionNodes(`node-${toNodeId}`);
                }, 100);
            }
        }
        
        // Create custom comment connection (center-to-center, dashed yellow line)
        function createCommentConnection(commentNodeId, targetNodeId) {
            console.log('🔗 Creating comment connection:', commentNodeId, '->', targetNodeId);
            
            const commentElement = document.getElementById(`node-${commentNodeId}`);
            const targetElement = document.getElementById(`node-${targetNodeId}`);
            
            if (!commentElement || !targetElement) {
                console.error('❌ Comment or target element not found:', 
                    'comment:', !!commentElement, 'target:', !!targetElement);
                throw new Error('Comment or target element not found');
            }
            
            console.log('✅ Both elements found for connection');
            
            // Store connection in our custom data structure
            if (!window.commentConnections) {
                window.commentConnections = {};
                console.log('📦 Initialized commentConnections');
            }
            
            if (!window.commentConnections[commentNodeId]) {
                window.commentConnections[commentNodeId] = [];
                console.log('📦 Initialized connections array for comment:', commentNodeId);
            }
            
            // Check if connection already exists
            const existingConnection = window.commentConnections[commentNodeId].find(conn => conn.targetId === targetNodeId);
            if (existingConnection) {
                console.log('⚠️ Comment connection already exists');
                log('💬 Comment connection already exists');
                return;
            }
            
            // Add to our connection data
            const connectionId = `comment-conn-${commentNodeId}-${targetNodeId}`;
            window.commentConnections[commentNodeId].push({
                targetId: targetNodeId,
                connectionId: connectionId
            });
            
            console.log('💾 Connection data stored:', window.commentConnections[commentNodeId]);
            
            // Create the visual connection
            console.log('🎨 Calling updateCommentConnections...');
            updateCommentConnections();
            
            log(`💬 Created comment connection from ${commentNodeId} to ${targetNodeId}`);
        }
        
        // Disconnect two nodes programmatically
        function disconnectNodes(fromNodeId, toNodeId) {
            // Check if it's a comment connection
            const fromNode = editor.drawflow.drawflow.Home.data[fromNodeId];
            const isCommentConnection = fromNode && fromNode.class && fromNode.class.includes('comment-node');
            
            if (isCommentConnection) {
                // Remove custom comment connection
                removeCommentConnection(fromNodeId, toNodeId);
            } else {
                // Remove standard Drawflow connection
                removeStandardConnection(fromNodeId, toNodeId);
            }
        }
        
        // Remove standard Drawflow connection
        function removeStandardConnection(fromNodeId, toNodeId) {
            const fromNode = editor.drawflow.drawflow.Home.data[fromNodeId];
            if (!fromNode || !fromNode.outputs) return;
            
            // Find and remove the connection
            for (const outputKey in fromNode.outputs) {
                const output = fromNode.outputs[outputKey];
                if (output.connections) {
                    for (let i = output.connections.length - 1; i >= 0; i--) {
                        const connection = output.connections[i];
                        if (connection.node === toNodeId) {
                            // Remove connection using Drawflow's method
                            editor.removeSingleConnection(fromNodeId, toNodeId, outputKey, connection.output);
                            break;
                        }
                    }
                }
            }
        }
        
        // Remove custom comment connection
        function removeCommentConnection(commentNodeId, targetNodeId) {
            if (!window.commentConnections || !window.commentConnections[commentNodeId]) {
                return;
            }
            
            // Remove from our connection data
            window.commentConnections[commentNodeId] = window.commentConnections[commentNodeId].filter(
                conn => conn.targetId !== targetNodeId
            );
            
            // Remove visual connection
            const connectionId = `comment-conn-${commentNodeId}-${targetNodeId}`;
            const connectionElement = document.getElementById(connectionId);
            if (connectionElement) {
                connectionElement.remove();
            }
            
            log(`💬 Removed comment connection from ${commentNodeId} to ${targetNodeId}`);
        }
        
        // Show submenu
        function showSubmenu(parentItem, submenuItems, originalEvent) {
            hideSubmenu(); // Hide any existing submenu
            
            const submenu = document.createElement('div');
            submenu.className = 'custom-context-submenu';
            submenu.id = 'contextSubmenu';
            
            submenuItems.forEach(item => {
                const submenuItem = document.createElement('div');
                submenuItem.className = `context-menu-item ${item.connected ? 'connected' : ''} ${item.disabled ? 'disabled' : ''}`;
                submenuItem.innerHTML = `<i class="${item.icon}"></i>${item.text}`;
                
                if (!item.disabled && item.action) {
                    submenuItem.onclick = () => {
                        item.action();
                        hideContextMenu();
                    };
                }
                
                submenu.appendChild(submenuItem);
            });
            
            // Position submenu next to parent item
            document.body.appendChild(submenu);
            
            const parentRect = parentItem.getBoundingClientRect();
            const submenuRect = submenu.getBoundingClientRect();
            
            let x = parentRect.right + 2;
            let y = parentRect.top;
            
            // Adjust if submenu goes off screen
            if (x + submenuRect.width > window.innerWidth) {
                x = parentRect.left - submenuRect.width - 2;
            }
            if (y + submenuRect.height > window.innerHeight) {
                y = window.innerHeight - submenuRect.height - 10;
            }
            
            submenu.style.left = x + 'px';
            submenu.style.top = y + 'px';
            
            // Handle mouse leave from submenu - check if moving to parent menu
            submenu.onmouseenter = () => {
                // Cancel any pending hide timeout when entering submenu
                clearTimeout(window.submenuHideTimeout);
            };
            
            submenu.onmouseleave = () => {
                // Small delay before hiding to allow movement between menu items
                window.submenuHideTimeout = setTimeout(() => {
                    const mainMenu = document.getElementById('contextMenu');
                    const parentMenuItem = parentItem;
                    
                    // Only hide if not hovering over parent menu item
                    if (!parentMenuItem.matches(':hover') && !mainMenu.matches(':hover')) {
                        hideSubmenu();
                    }
                }, 200);
            };
            
            log('📋 Submenu shown with ' + submenuItems.length + ' items');
        }
        
        // Hide submenu
        function hideSubmenu() {
            const existingSubmenu = document.getElementById('contextSubmenu');
            if (existingSubmenu) {
                existingSubmenu.remove();
            }
        }
        
        // Hide context menu
        function hideContextMenu() {
            const existingMenu = document.getElementById('contextMenu');
            if (existingMenu) {
                existingMenu.remove();
            }
            hideSubmenu(); // Also hide any open submenu
        }
        
        // Context menu actions
        function editNodeText(nodeElement) {
            const titleElement = nodeElement.querySelector('.node-title');
            if (titleElement) {
                titleElement.focus();
                // Select all text for easy editing
                if (titleElement.contentEditable === 'true') {
                    const range = document.createRange();
                    range.selectNodeContents(titleElement);
                    const selection = window.getSelection();
                    selection.removeAllRanges();
                    selection.addRange(range);
                }
            }
            log('✏️ Edit mode activated for node');
        }
        
        function deleteNode(nodeId) {
            if (confirm('Are you sure you want to delete this node? This action cannot be undone.')) {
                // Clean up comment connections involving this node
                cleanupCommentConnectionsForNode(nodeId);
                
                editor.removeNodeId('node-' + nodeId);
                autoSave();
                log('🗑️ Node ' + nodeId + ' deleted');
            }
        }
        
        // Clean up comment connections when a node is deleted
        function cleanupCommentConnectionsForNode(nodeId) {
            if (!window.commentConnections) return;
            
            // Remove connections where this node is the comment (source)
            if (window.commentConnections[nodeId]) {
                delete window.commentConnections[nodeId];
            }
            
            // Remove connections where this node is the target
            Object.keys(window.commentConnections).forEach(commentNodeId => {
                window.commentConnections[commentNodeId] = window.commentConnections[commentNodeId].filter(
                    conn => conn.targetId !== nodeId
                );
            });
            
            // Update visual connections
            updateCommentConnections();
            
            log(`💬 Cleaned up comment connections for node ${nodeId}`);
        }
        
        function deleteConnection(connectionElement) {
            if (confirm('Are you sure you want to delete this connection? This action cannot be undone.')) {
                try {
                    // Find the connection data by examining the DOM structure
                    // Drawflow connections have a specific structure we can identify
                    const connectionId = connectionElement.classList[1]; // Usually connection_1, connection_2, etc.
                    
                    if (connectionId) {
                        // Remove the connection element from DOM
                        connectionElement.remove();
                        
                        // Also clean up the connection data from editor's internal data structure
                        // This is a bit complex because we need to find which nodes were connected
                        updateConnectionDataAfterDelete();
                        
                        autoSave();
                        log('🔗 Connection deleted');
                    } else {
                        log('❌ Could not identify connection to delete');
                    }
                } catch (error) {
                    log('❌ Error deleting connection: ' + error.message);
                }
            }
        }
        
        // Clean up connection data after manual deletion
        function updateConnectionDataAfterDelete() {
            // This function ensures the editor's internal state matches the DOM
            // by rebuilding the connection data from visible connections
            if (!editor || !editor.drawflow || !editor.drawflow.drawflow || !editor.drawflow.drawflow.Home) {
                return;
            }
            
            const nodes = editor.drawflow.drawflow.Home.data || {};
            
            // Clear all existing connections in data
            Object.keys(nodes).forEach(nodeId => {
                const node = nodes[nodeId];
                if (node.outputs) {
                    Object.keys(node.outputs).forEach(outputKey => {
                        node.outputs[outputKey].connections = [];
                    });
                }
            });
            
            // Rebuild connections based on what's actually visible in DOM
            const visibleConnections = document.querySelectorAll('.connection');
            visibleConnections.forEach(conn => {
                // This is a simplified approach - in a more complex scenario,
                // you might need more sophisticated connection tracking
                log('🔧 Rebuilding connection data from DOM');
            });
        }
        
        // Function to update all connection positions - useful after layout changes
        function updateAllConnectionPositions() {
            if (!editor || !editor.drawflow || !editor.drawflow.drawflow || !editor.drawflow.drawflow.Home) {
                return;
            }
            
            const nodes = editor.drawflow.drawflow.Home.data || {};
            Object.keys(nodes).forEach(nodeId => {
                editor.updateConnectionNodes('node-' + nodeId);
            });
            
            log('🔧 All connection positions updated');
        }
        
        // Node templates with different configurations (now using database data)
        function getNodeTemplate(type, counter) {
            // Use database template if available, otherwise fallback to hardcoded
            const dbTemplate = window.nodeTemplates ? window.nodeTemplates[type] : null;
            
            if (dbTemplate) {
                return {
                    html: `
                        <div class="node-drag-handle" style="display: flex; justify-content: center; align-items: center; height: 10px; cursor: move; background: rgba(0,0,0,0.05); margin: -7px -7px 3px -7px; border-radius: 4px 4px 0 0;">
                            <i class="fa-solid fa-grip" style="font-size: 8px; color: #999; pointer-events: none;"></i>
                        </div>
                        <div style="padding: 0 7px 7px 7px;">
                            <div style="display: flex; align-items: center; margin-bottom: 3px;">
                                <i class="${dbTemplate.icon_class}" style="color: #444444; margin-right: 5px; font-size: 13px;"></i>
                                <div class="node-title" style="font-weight: bold; cursor: text; flex: 1;" 
                                     contenteditable="true" 
                                     onblur="updateNodeText(this)"
                                     onkeydown="handleTextEdit(event)">${dbTemplate.display_name} ${counter}</div>
                            </div>
                            <textarea class="node-description" style="font-size: 10px; color: #666; cursor: text; border: none; background: transparent; resize: none; width: 100%; outline: none; min-height: 18px; line-height: 1.3;" 
                                      onblur="updateNodeText(this)"
                                      onkeydown="handleTextEdit(event)"
                                      oninput="autoResizeTextarea(this)"
                                      rows="1">${dbTemplate.description}</textarea>
                        </div>
                    `,
                    inputs: dbTemplate.default_inputs,
                    outputs: dbTemplate.default_outputs,
                    class: dbTemplate.css_class
                };
            }
            
            // Fallback for unknown types
            return {
                html: `
                    <div class="node-drag-handle" style="display: flex; justify-content: center; align-items: center; height: 10px; cursor: move; background: rgba(0,0,0,0.05); margin: -7px -7px 3px -7px; border-radius: 4px 4px 0 0;">
                        <i class="fa-solid fa-grip" style="font-size: 8px; color: #999; pointer-events: none;"></i>
                    </div>
                    <div style="padding: 0 7px 7px 7px;">
                        <div style="display: flex; align-items: center; margin-bottom: 3px;">
                            <i class="fa-solid fa-question" style="color: #444444; margin-right: 5px; font-size: 13px;"></i>
                            <div class="node-title" style="font-weight: bold; cursor: text; flex: 1;" 
                                 contenteditable="true" 
                                 onblur="updateNodeText(this)"
                                 onkeydown="handleTextEdit(event)">Unknown ${counter}</div>
                        </div>
                        <textarea class="node-description" style="font-size: 10px; color: #666; cursor: text; border: none; background: transparent; resize: none; width: 100%; outline: none; min-height: 18px; line-height: 1.3;" 
                                  onblur="updateNodeText(this)"
                                  onkeydown="handleTextEdit(event)"
                                  oninput="autoResizeTextarea(this)"
                                  rows="1">Unknown node type</textarea>
                    </div>
                `,
                inputs: 1,
                outputs: 1,
                class: 'unknown-node'
            };
        }
        
        // Load node templates from database
        async function loadNodeTemplates() {
            try {
                log('🔄 Loading node templates...');
                
                const response = await fetch('/public/api/get_node_templates.php');
                const result = await response.json();
                
                if (result.success && result.templates) {
                    const container = document.getElementById('nodeTemplates');
                    container.innerHTML = '';
                    
                    result.templates.forEach(template => {
                        const nodeItem = document.createElement('div');
                        nodeItem.className = 'node-item';
                        nodeItem.onclick = () => addNode(template.type);
                        nodeItem.innerHTML = `
                            <span class="node-icon"><i class="${template.icon_class}"></i></span>
                            <span>${template.display_name}</span>
                        `;
                        container.appendChild(nodeItem);
                    });
                    
                    // Store templates globally for use in addNode function
                    window.nodeTemplates = {};
                    result.templates.forEach(template => {
                        window.nodeTemplates[template.type] = template;
                    });
                    
                    log(`✅ Loaded ${result.templates.length} node templates`);
                } else {
                    throw new Error(result.message || 'Failed to load templates');
                }
            } catch (error) {
                log('❌ Error loading node templates: ' + error.message);
                const container = document.getElementById('nodeTemplates');
                container.innerHTML = '<div style="text-align: center; padding: 20px; color: #dc3545;">Error loading elements</div>';
            }
        }
        
        // Add node with enhanced types
        function addNode(type = 'process') {
            if (!editor) {
                log('❌ Editor not ready');
                return;
            }
            
            const x = Math.random() * 400 + 100;
            const y = Math.random() * 300 + 100;
            
            const template = getNodeTemplate(type, nodeCounter);
            
            // Initialize node data with default text
            const nodeData = {
                type: type,
                title: `${type.charAt(0).toUpperCase() + type.slice(1)} ${nodeCounter}`,
                description: getDefaultDescription(type),
                created: new Date().toISOString()
            };
            
            editor.addNode(
                `${type}_${nodeCounter}`, 
                template.inputs, 
                template.outputs, 
                x, 
                y, 
                template.class, 
                nodeData,
                template.html
            );
            
            // Initialize textarea styling for the new node
            setTimeout(() => {
                initializeTextareas();
            }, 50);
            
            log(`✅ Added ${type} node (#${nodeCounter})`);
            nodeCounter++;
        }
        
        // Get default description for node types
        function getDefaultDescription(type) {
            const descriptions = {
                application: 'Software application or system component',
                service: 'Microservice or business service',
                decision: 'Business rule or decision point',
                data_pipeline: 'Data processing and transformation workflow',
                data_product: 'Consumable data asset or product',
                api_interface: 'API endpoint or system interface',
                database_data_store: 'Database or data storage system',
                external_system: 'Third-party or external system',
                user_role: 'User persona or role in the system',
                security_control_point: 'Security measure or control point',
                visualization: 'Dashboard, report, or data visualization'
            };
            return descriptions[type] || 'Description';
        }
        
        // Auto-save with debouncing
        let autoSaveTimeout;
        function autoSave() {
            if (isAutoSaving) return;
            
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                saveDiagram(true);
            }, 1000); // Wait 1 second after last change
        }
        
        // Save diagram
        async function saveDiagram(isAutoSave = false) {
            if (!editor || isAutoSaving) return;
            
            try {
                isAutoSaving = true;
                updateSaveStatus('saving');
                
                if (!isAutoSave) {
                    log('🔄 Manually saving diagram...');
                }
                
                const data = editor.export();
                
                // Add comment connections to the data
                if (window.commentConnections) {
                    data.commentConnections = window.commentConnections;
                }
                
                const response = await fetch('/public/api/save_drawflow_diagram.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        application_id: applicationId,
                        diagram_data: data,
                        notes: isAutoSave ? 'Auto-saved' : 'Manual save'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    updateSaveStatus('saved');
                    if (!isAutoSave) {
                        log('💾 Diagram saved successfully');
                    }
                } else {
                    updateSaveStatus('error');
                    log('❌ Save failed: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                updateSaveStatus('error');
                log('❌ Save error: ' + error.message);
            } finally {
                isAutoSaving = false;
            }
        }
        
        // Restore comment connections from saved data
        function restoreCommentConnections() {
            // This will be called during diagram loading to restore comment connections
            // The actual restoration happens in loadDiagram when we import the data
            log('🔄 Comment connections restoration initialized');
        }
        
        // Load diagram
        async function loadDiagram() {
            try {
                log('🔄 Loading diagram...');
                
                const response = await fetch(`/public/api/load_drawflow_diagram.php?application_id=${applicationId}`);
                const result = await response.json();
                
                if (result.success && result.diagram_data) {
                    const diagramData = result.diagram_data;
                    
                    if (editor && diagramData && diagramData.drawflow) {
                        editor.import(diagramData);
                        const nodeCount = Object.keys(diagramData.drawflow.Home.data || {}).length;
                        
                        // Restore comment connections from saved data
                        if (diagramData.commentConnections) {
                            window.commentConnections = diagramData.commentConnections;
                            log(`💬 Loaded ${Object.keys(diagramData.commentConnections).length} comment connection groups`);
                        } else {
                            window.commentConnections = {};
                        }
                        
                        // Update node counter based on loaded nodes
                        nodeCounter = nodeCount + 1;
                        
                        // Initialize textarea styling for loaded nodes and restore text content
                        setTimeout(() => {
                            initializeTextareas();
                            restoreNodeTexts();
                            
                            // Restore comment connections from saved data
                            restoreCommentConnections();
                            
                            // FIX: Update connection positions after import
                            // This fixes the issue where connection lines don't align properly with input/output circles
                            Object.keys(diagramData.drawflow.Home.data || {}).forEach(nodeId => {
                                editor.updateConnectionNodes('node-' + nodeId);
                            });
                            
                            // Update comment connections after everything is loaded
                            updateCommentConnections();
                            
                            log('🔧 Connection positions updated for all nodes');
                        }, 100);
                        
                        log(`📂 Loaded diagram with ${nodeCount} nodes`);
                        updateSaveStatus('saved');
                    } else {
                        log('📂 No diagram data found - starting with empty canvas');
                    }
                } else {
                    log('📂 No saved diagram found - starting fresh');
                }
            } catch (error) {
                log('❌ Load error: ' + error.message);
            }
        }
        
        // Clear diagram
        function clearDiagram() {
            if (!editor) return;
            
            if (confirm('Are you sure you want to clear the entire diagram? This action cannot be undone.')) {
                editor.clear();
                nodeCounter = 1;
                
                // Clear comment connections
                window.commentConnections = {};
                document.querySelectorAll('.comment-connection').forEach(el => el.remove());
                
                log('🗑️ Diagram cleared');
                autoSave();
            }
        }
        
        // Export diagram
        function exportDiagram() {
            if (!editor) return;
            
            const data = editor.export();
            const blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/json'});
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `datamap-app${applicationId}-${new Date().toISOString().slice(0,10)}.json`;
            link.click();
            log('📥 DataMap exported');
        }
        
        // Zoom functions
        function zoomIn() { 
            if (editor) {
                editor.zoom_in(); 
                log('🔍 Zoomed in');
            }
        }
        
        function zoomOut() { 
            if (editor) {
                editor.zoom_out(); 
                log('🔍 Zoomed out');
            }
        }
        
        function zoomReset() { 
            if (editor) {
                editor.zoom_reset(); 
                log('🔄 Zoom reset');
            }
        }
        
        // Update save status indicator
        function updateSaveStatus(status) {
            const indicator = document.getElementById('saveStatus');
            indicator.className = `auto-save-indicator ${status}`;
            
            switch (status) {
                case 'saving':
                    indicator.textContent = '⏳ Saving...';
                    break;
                case 'saved':
                    indicator.textContent = '✓ Saved';
                    setTimeout(() => {
                        indicator.textContent = '✓ Auto-save enabled';
                        indicator.className = 'auto-save-indicator';
                    }, 2000);
                    break;
                case 'error':
                    indicator.textContent = '❌ Save failed';
                    break;
            }
        }
        
        // Enhanced logging - simplified since we don't have status panel
        function log(message) {
            console.log(`[${new Date().toLocaleTimeString()}] ${message}`);
        }
        
        // Handle text editing in nodes
        function handleTextEdit(event) {
            const element = event.target;
            
            // For titles (contenteditable divs with node-title class), prevent Enter key
            if (element.classList.contains('node-title') && event.key === 'Enter') {
                event.preventDefault();
                element.blur(); // Unfocus the element
                return;
            }
            
            // For descriptions (textarea elements), handle auto-resize on all input
            if (element.classList.contains('node-description') && element.tagName === 'TEXTAREA') {
                // Auto-resize textarea based on content for any key input
                setTimeout(() => {
                    autoResizeTextarea(element);
                }, 0);
            }
        }
        
        // Auto-resize textarea based on content
        function autoResizeTextarea(textarea) {
            // Reset height to auto to get the natural height
            textarea.style.height = 'auto';
            // Set height based on scroll height with minimum height
            const newHeight = Math.max(20, textarea.scrollHeight);
            textarea.style.height = newHeight + 'px';
            
            // Also resize the parent node to accommodate the new textarea size
            const nodeElement = textarea.closest('.drawflow-node');
            if (nodeElement) {
                // Force the node to recalculate its size
                nodeElement.style.height = 'auto';
                nodeElement.style.minHeight = 'auto';
            }
        }
        
        function updateNodeText(element) {
            // Get text content from both contenteditable divs and textarea elements
            let text = element.tagName === 'TEXTAREA' ? element.value : element.textContent;
            
            console.log('updateNodeText called with:', element, 'text:', text);
            
            // For titles, the text is already clean since icon is separate
            if (element.classList.contains('node-title')) {
                text = element.textContent.trim();
                console.log('Title text extracted:', text);
            }
            
            // Find the node that contains this element - try different approaches
            let nodeElement = element;
            let attempts = 0;
            while (nodeElement && !nodeElement.classList.contains('drawflow-node') && attempts < 10) {
                nodeElement = nodeElement.parentElement;
                attempts++;
            }
            
            console.log('Found node element:', nodeElement, 'after', attempts, 'attempts');
            
            if (nodeElement) {
                // The node element itself should have the ID, not its parent
                let nodeId = nodeElement.id;
                
                // If the node element doesn't have ID, check parent
                if (!nodeId && nodeElement.parentElement) {
                    nodeId = nodeElement.parentElement.id;
                }
                
                console.log('Node ID found:', nodeId);
                
                // Update the node data with the new text
                if (editor && editor.drawflow && editor.drawflow.drawflow && editor.drawflow.drawflow.Home && editor.drawflow.drawflow.Home.data) {
                    console.log('All available nodes:', Object.keys(editor.drawflow.drawflow.Home.data));
                    
                    // Extract numeric ID from node ID (e.g., "node-2" -> "2")
                    let numericId = nodeId;
                    if (nodeId.startsWith('node-')) {
                        numericId = nodeId.replace('node-', '');
                    }
                    
                    // Try different ID formats
                    let nodeData = null;
                    let actualNodeId = null;
                    
                    // Try the numeric ID first
                    if (editor.drawflow.drawflow.Home.data[numericId]) {
                        nodeData = editor.drawflow.drawflow.Home.data[numericId].data;
                        actualNodeId = numericId;
                    } else if (editor.drawflow.drawflow.Home.data[parseInt(numericId)]) {
                        nodeData = editor.drawflow.drawflow.Home.data[parseInt(numericId)].data;
                        actualNodeId = parseInt(numericId);
                    } else if (editor.drawflow.drawflow.Home.data[nodeId]) {
                        nodeData = editor.drawflow.drawflow.Home.data[nodeId].data;
                        actualNodeId = nodeId;
                    }
                    
                    console.log('Trying numeric ID:', numericId);
                    
                    if (nodeData) {
                        console.log('Current node data:', nodeData);
                        
                        // Store the text based on element type
                        if (element.classList.contains('node-title')) {
                            nodeData.title = text;
                            console.log('Updated title to:', text);
                        } else if (element.classList.contains('node-description')) {
                            nodeData.description = text;
                            console.log('Updated description to:', text);
                        }
                        
                        console.log('Updated node data:', nodeData);
                        log(`📝 Node ${actualNodeId} text updated: ${element.classList.contains('node-title') ? 'title' : 'description'} = "${text}"`);
                    } else {
                        console.error('Could not find node data for ID:', nodeId);
                        console.log('Available node IDs:', Object.keys(editor.drawflow.drawflow.Home.data));
                        console.log('Looking for ID:', nodeId, 'type:', typeof nodeId);
                        console.log('Numeric ID tried:', numericId, 'type:', typeof numericId);
                    }
                } else {
                    console.error('Editor structure not available');
                }
            } else {
                console.error('Could not find node element');
            }
            
            // Auto-save when text is updated
            autoSave();
        }
        
        // Initialize textarea auto-resize for loaded nodes
        function initializeTextareas() {
            const textareas = document.querySelectorAll('.node-description');
            textareas.forEach(textarea => {
                if (textarea.tagName === 'TEXTAREA') {
                    // Auto-resize based on content
                    autoResizeTextarea(textarea);
                    
                    // Add input event listener for dynamic resizing
                    if (!textarea.hasAttribute('data-resize-initialized')) {
                        textarea.addEventListener('input', function() {
                            autoResizeTextarea(this);
                        });
                        textarea.setAttribute('data-resize-initialized', 'true');
                    }
                }
            });
            
            // Also set up drag handles for loaded nodes
            setupDragHandles();
        }
        
        // Restore text content from node data after loading
        function restoreNodeTexts() {
            if (!editor || !editor.drawflow || !editor.drawflow.drawflow.Home.data) return;
            
            const nodes = editor.drawflow.drawflow.Home.data;
            
            Object.keys(nodes).forEach(nodeId => {
                const nodeData = nodes[nodeId];
                // Try both "nodeId" and "node-nodeId" formats for finding the element
                let nodeElement = document.getElementById(nodeId);
                if (!nodeElement) {
                    nodeElement = document.getElementById(`node-${nodeId}`);
                }
                
                if (nodeElement && nodeData.data) {
                    console.log(`Restoring text for node ${nodeId}:`, nodeData.data);
                    
                    // Update title if saved - icon is now separate so just update text content
                    if (nodeData.data.title) {
                        const titleElement = nodeElement.querySelector('.node-title');
                        if (titleElement) {
                            titleElement.textContent = nodeData.data.title;
                            console.log(`✓ Title restored: ${nodeData.data.title}`);
                        }
                    }
                    
                    // Update description if saved
                    if (nodeData.data.description) {
                        const descElement = nodeElement.querySelector('.node-description');
                        if (descElement && descElement.tagName === 'TEXTAREA') {
                            descElement.value = nodeData.data.description;
                            // Auto-resize textarea
                            descElement.style.height = 'auto';
                            descElement.style.height = Math.max(15, descElement.scrollHeight) + 'px';
                            console.log(`✓ Description restored: ${nodeData.data.description}`);
                        }
                    }
                } else {
                    console.log(`Could not find node element for ID: ${nodeId}, tried both "${nodeId}" and "node-${nodeId}"`);
                }
            });
            
            log('🔄 Node texts restored from saved data');
        }
    </script>
</body>
</html>
