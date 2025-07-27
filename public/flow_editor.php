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
    header('Location: /public/dashboard.php?error=Flow editor requires an application ID. Please select an application first.');
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
    <title>Flow Editor - <?php echo htmlspecialchars($application['name']); ?> | AppTrack</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            font-size: 14px;
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
            background: white;
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
                <div class="sidebar-content">
                    <div class="node-item" onclick="addNode('process')">
                        <span class="node-icon"><i class="fas fa-cog"></i></span>
                        <span>Process</span>
                    </div>
                    <div class="node-item" onclick="addNode('database')">
                        <span class="node-icon"><i class="fas fa-database"></i></span>
                        <span>Database</span>
                    </div>
                    <div class="node-item" onclick="addNode('api')">
                        <span class="node-icon"><i class="fas fa-plug"></i></span>
                        <span>API</span>
                    </div>
                    <div class="node-item" onclick="addNode('service')">
                        <span class="node-icon"><i class="fas fa-server"></i></span>
                        <span>Service</span>
                    </div>
                    <div class="node-item" onclick="addNode('decision')">
                        <span class="node-icon"><i class="fas fa-question-circle"></i></span>
                        <span>Decision</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Application Header -->
            <div class="app-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h1 class="app-title" style="margin: 0;">
                        🔄 Flow Editor: <?php echo htmlspecialchars($application['name']); ?>
                        <span class="status-badge <?php echo strtolower($application['status']); ?>">
                            <?php echo htmlspecialchars($application['status']); ?>
                        </span>
                    </h1>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button onclick="goBack()" style="padding: 8px 16px; border: 1px solid #ddd; background: #fff; border-radius: 6px; cursor: pointer; color: #6c757d;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button onclick="saveDiagram()" style="padding: 8px 16px; border: 1px solid #4EA5D9; background: #4EA5D9; color: white; border-radius: 6px; cursor: pointer;">
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
            log('🔄 Initializing Flow Editor...');
            
            const container = document.getElementById('drawflow');
            editor = new Drawflow(container);
            editor.reroute = true;
            editor.curvature = 0.5;
            editor.start();
            
            log('✅ Flow Editor initialized');
            loadDiagram();
            
            // Set up auto-save on changes
            editor.on('nodeCreated', () => autoSave());
            editor.on('nodeRemoved', () => autoSave());
            editor.on('nodeMoved', () => autoSave());
            editor.on('connectionCreated', () => autoSave());
            editor.on('connectionRemoved', () => autoSave());
        });
        
        // Node templates with different configurations
        function getNodeTemplate(type, counter) {
            const templates = {
                process: {
                    html: `
                        <div style="padding: 8px;">
                            <div class="node-title" style="font-weight: bold; margin-bottom: 4px; cursor: text;" 
                                 contenteditable="true" 
                                 onblur="updateNodeText(this)"
                                 onkeydown="handleTextEdit(event)"><i class="fas fa-cog" style="color: #007bff;"></i>Process ${counter}</div>
                            <textarea class="node-description" style="font-size: 11px; color: #666; cursor: text; border: none; background: transparent; resize: none; width: 100%; outline: none; min-height: 15px;" 
                                      onblur="updateNodeText(this)"
                                      onkeydown="handleTextEdit(event)"
                                      rows="1">Business Logic</textarea>
                        </div>
                    `,
                    inputs: 1,
                    outputs: 1,
                    class: 'process-node'
                },
                database: {
                    html: `
                        <div style="padding: 8px;">
                            <div class="node-title" style="font-weight: bold; margin-bottom: 4px; cursor: text;" 
                                 contenteditable="true" 
                                 onblur="updateNodeText(this)"
                                 onkeydown="handleTextEdit(event)"><i class="fas fa-database" style="color: #28a745;"></i>Database ${counter}</div>
                            <textarea class="node-description" style="font-size: 11px; color: #666; cursor: text; border: none; background: transparent; resize: none; width: 100%; outline: none; min-height: 15px;" 
                                      onblur="updateNodeText(this)"
                                      onkeydown="handleTextEdit(event)"
                                      rows="1">Data Storage</textarea>
                        </div>
                    `,
                    inputs: 2,
                    outputs: 1,
                    class: 'database-node'
                },
                api: {
                    html: `
                        <div style="padding: 8px;">
                            <div class="node-title" style="font-weight: bold; margin-bottom: 4px; cursor: text;" 
                                 contenteditable="true" 
                                 onblur="updateNodeText(this)"
                                 onkeydown="handleTextEdit(event)"><i class="fas fa-plug" style="color: #ffc107;"></i>API ${counter}</div>
                            <textarea class="node-description" style="font-size: 11px; color: #666; cursor: text; border: none; background: transparent; resize: none; width: 100%; outline: none; min-height: 15px;" 
                                      onblur="updateNodeText(this)"
                                      onkeydown="handleTextEdit(event)"
                                      rows="1">Interface</textarea>
                        </div>
                    `,
                    inputs: 1,
                    outputs: 2,
                    class: 'api-node'
                },
                service: {
                    html: `
                        <div style="padding: 8px;">
                            <div class="node-title" style="font-weight: bold; margin-bottom: 4px; cursor: text;" 
                                 contenteditable="true" 
                                 onblur="updateNodeText(this)"
                                 onkeydown="handleTextEdit(event)"><i class="fas fa-server" style="color: #6c757d;"></i>Service ${counter}</div>
                            <textarea class="node-description" style="font-size: 11px; color: #666; cursor: text; border: none; background: transparent; resize: none; width: 100%; outline: none; min-height: 15px;" 
                                      onblur="updateNodeText(this)"
                                      onkeydown="handleTextEdit(event)"
                                      rows="1">External Service</textarea>
                        </div>
                    `,
                    inputs: 1,
                    outputs: 1,
                    class: 'service-node'
                },
                decision: {
                    html: `
                        <div style="padding: 8px;">
                            <div class="node-title" style="font-weight: bold; margin-bottom: 4px; cursor: text;" 
                                 contenteditable="true" 
                                 onblur="updateNodeText(this)"
                                 onkeydown="handleTextEdit(event)"><i class="fas fa-question-circle" style="color: #dc3545;"></i>Decision ${counter}</div>
                            <textarea class="node-description" style="font-size: 11px; color: #666; cursor: text; border: none; background: transparent; resize: none; width: 100%; outline: none; min-height: 15px;" 
                                      onblur="updateNodeText(this)"
                                      onkeydown="handleTextEdit(event)"
                                      rows="1">Logic Gate</textarea>
                        </div>
                    `,
                    inputs: 1,
                    outputs: 2,
                    class: 'decision-node'
                }
            };
            
            return templates[type] || templates.process;
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
                process: 'Business Logic',
                database: 'Data Storage',
                api: 'Interface',
                service: 'External Service',
                decision: 'Logic Gate'
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
                        
                        // Update node counter based on loaded nodes
                        nodeCounter = nodeCount + 1;
                        
                        // Initialize textarea styling for loaded nodes and restore text content
                        setTimeout(() => {
                            initializeTextareas();
                            restoreNodeTexts();
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
            link.download = `flow-diagram-app${applicationId}-${new Date().toISOString().slice(0,10)}.json`;
            link.click();
            log('📥 Diagram exported');
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
            
            // For descriptions (textarea elements), allow Enter key and handle auto-resize
            if (element.classList.contains('node-description') && element.tagName === 'TEXTAREA') {
                if (event.key === 'Enter') {
                    // Auto-resize textarea based on content
                    setTimeout(() => {
                        element.style.height = 'auto';
                        element.style.height = Math.max(15, element.scrollHeight) + 'px';
                    }, 0);
                    return; // Allow the Enter key to work normally
                }
            }
        }
        
        function updateNodeText(element) {
            // Get text content from both contenteditable divs and textarea elements
            let text = element.tagName === 'TEXTAREA' ? element.value : element.textContent;
            
            console.log('updateNodeText called with:', element, 'text:', text);
            
            // For titles, remove the icon from the text if it exists
            if (element.classList.contains('node-title')) {
                // Extract just the text part (remove icon)
                const icon = element.querySelector('i');
                if (icon) {
                    // Clone the element to avoid modifying the original
                    const tempElement = element.cloneNode(true);
                    const tempIcon = tempElement.querySelector('i');
                    if (tempIcon) {
                        tempIcon.remove();
                    }
                    text = tempElement.textContent.trim();
                }
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
                    textarea.style.height = 'auto';
                    textarea.style.height = Math.max(15, textarea.scrollHeight) + 'px';
                }
            });
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
                    
                    // Update title if saved
                    if (nodeData.data.title) {
                        const titleElement = nodeElement.querySelector('.node-title');
                        if (titleElement) {
                            // Keep the icon and add the saved title text
                            const icon = titleElement.querySelector('i');
                            if (icon) {
                                // Clear content and rebuild with icon + saved title
                                titleElement.innerHTML = '';
                                titleElement.appendChild(icon);
                                titleElement.appendChild(document.createTextNode(nodeData.data.title));
                            } else {
                                titleElement.textContent = nodeData.data.title;
                            }
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
        
        // Go back function
        function goBack() {
            if (confirm('Are you sure you want to leave? Any unsaved changes will be lost.')) {
                window.history.back();
            }
        }
    </script>
</body>
</html>
