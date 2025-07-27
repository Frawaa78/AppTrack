<?php
// Working test version - no authentication required
$application_id = isset($_GET['app_id']) ? intval($_GET['app_id']) : 429;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drawflow Integration - Production Ready</title>
    
    <!-- Only Drawflow CSS - no conflicts -->
    <link href="/assets/vendor/drawflow.min.css" rel="stylesheet">
    
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f8f9fa;
        }
        
        .page-header {
            background: #4EA5D9;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .page-header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        
        .status-info {
            background: #e8f5e8;
            border: 1px solid #4caf50;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .main-content {
            display: flex;
            gap: 20px;
        }
        
        .editor-section {
            flex: 1;
        }
        
        .sidebar {
            width: 300px;
            background: white;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        /* Component Palette */
        .component-palette {
            background: white;
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .component-palette h4 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #333;
        }
        
        .palette-item {
            display: inline-block;
            padding: 10px 15px;
            margin: 5px;
            background: #f8f9fa;
            border: 2px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            min-width: 80px;
            transition: all 0.2s;
        }
        
        .palette-item:hover {
            background: #e9ecef;
            border-color: #4EA5D9;
        }
        
        .palette-item span {
            display: block;
            font-size: 12px;
            margin-top: 5px;
        }
        
        /* Toolbar */
        .toolbar {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px 8px 0 0;
            padding: 10px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .toolbar button {
            padding: 8px 15px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .toolbar button:hover {
            background: #f8f9fa;
            border-color: #4EA5D9;
        }
        
        /* Editor Container - Clean setup like minimal test */
        .editor-container {
            width: 100%;
            height: 600px;
            border: 1px solid #ddd;
            border-radius: 0 0 8px 8px;
            position: relative;
            background: white;
        }
        
        #drawflow {
            width: 100%;
            height: 100%;
            background: white;
            position: relative;
        }
        
        /* Sidebar styling */
        .sidebar-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
            font-size: 14px;
        }
        
        .sidebar-content {
            padding: 15px;
            font-size: 14px;
        }
        
        .sidebar-content ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .sidebar-content li {
            margin-bottom: 5px;
        }
        
        #status-info {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>🔧 Drawflow Integration - Production Ready (v1.1)</h1>
        <div>Application ID: <?php echo $application_id; ?> | PHP: <?php echo phpversion(); ?> | Time: <?php echo date('Y-m-d H:i:s'); ?></div>
    </div>
    
    <div class="status-info">
        <strong>✅ All Systems Working!</strong> Drawflow editor ready with clean setup - no CSS conflicts.
    </div>
    
    <div class="main-content">
        <div class="editor-section">
            <!-- Component Palette -->
            <div class="component-palette">
                <h4>Components</h4>
                <div class="palette-item database" onclick="addComponent('database')">
                    <div>📊</div>
                    <span>Database</span>
                </div>
                <div class="palette-item api" onclick="addComponent('api')">
                    <div>☁️</div>
                    <span>API</span>
                </div>
                <div class="palette-item frontend" onclick="addComponent('frontend')">
                    <div>💻</div>
                    <span>Frontend</span>
                </div>
                <div class="palette-item service" onclick="addComponent('service')">
                    <div>⚙️</div>
                    <span>Service</span>
                </div>
            </div>

            <!-- Editor with Clean Setup -->
            <div class="toolbar">
                <button onclick="testDrawflow()">
                    <span>➕ Add Node</span>
                </button>
                <button onclick="clearDiagram()">
                    <span>🗑️ Clear</span>
                </button>
                <button onclick="saveDiagram()">
                    <span>💾 Save</span>
                </button>
                <button onclick="loadDiagram()">
                    <span>📂 Load</span>
                </button>
                <button onclick="exportDiagram()">
                    <span>📥 Export</span>
                </button>
                <button onclick="zoomIn()">
                    <span>🔍+ Zoom In</span>
                </button>
                <button onclick="zoomOut()">
                    <span>🔍- Zoom Out</span>
                </button>
                <button onclick="zoomReset()">
                    <span>🔄 Reset Zoom</span>
                </button>
            </div>
            
            <!-- Clean Drawflow Container - Like Minimal Test -->
            <div class="editor-container">
                <div id="drawflow"></div>
            </div>
        </div>
        
        <div class="sidebar">
            <div class="sidebar-header">
                📋 Instructions & Status
            </div>
            <div class="sidebar-content">
                <p><strong>Adding Nodes:</strong></p>
                <ul>
                    <li>Click component buttons above</li>
                    <li>Or use "Add Node" button</li>
                </ul>
                
                <p><strong>Connecting Nodes:</strong></p>
                <ul>
                    <li>Drag from output to input circles</li>
                    <li>Click connections to select/delete</li>
                </ul>
                
                <p><strong>Controls:</strong></p>
                <ul>
                    <li>Save/Load: Database persistence</li>
                    <li>Export: Download as JSON</li>
                    <li>Zoom: Use zoom buttons</li>
                </ul>
                
                <div id="status-info">
                    Ready to start diagramming...
                </div>
            </div>
        </div>
    </div>
    
    <!-- Only Drawflow JS - no Bootstrap or other dependencies -->
    <script src="/assets/vendor/drawflow.min.js"></script>

    <script>
        // Clean setup - like minimal test
        let editor;
        let nodeIdCounter = 1;
        const applicationId = <?php echo $application_id; ?>;
        
        // Initialize on page load - clean approach
        window.addEventListener('DOMContentLoaded', function() {
            updateStatus('🔄 Initializing Drawflow with clean setup...');
            initializeDrawflow();
        });
        
        // Initialize Drawflow editor - clean like minimal test
        function initializeDrawflow() {
            try {
                const container = document.getElementById('drawflow');
                
                updateStatus('✅ Container found, initializing...');
                
                // Initialize Drawflow with minimal settings - exact same as working minimal test
                editor = new Drawflow(container);
                editor.reroute = true;
                editor.start();
                
                updateStatus('✅ Drawflow initialized successfully!');
                updateStatus('📊 Ready for production use - smooth drag-and-drop enabled');
                
                // Load existing diagram if available
                loadDiagram();
                
            } catch (error) {
                updateStatus('❌ Error: ' + error.message);
                console.error('Initialization error:', error);
            }
        }
        
        // Add test node - simple like minimal test
        function testDrawflow() {
            if (!editor) {
                updateStatus('❌ Editor not initialized');
                return;
            }
            
            // Random position
            const x = Math.random() * 400 + 50;
            const y = Math.random() * 300 + 50;
            
            // Simple node HTML
            const nodeHtml = `
                <div style="padding: 10px; background: #4EA5D9; color: white; border-radius: 5px; min-width: 120px;">
                    <div style="font-weight: bold;">Node ${nodeIdCounter}</div>
                    <div style="font-size: 12px;">Drag me!</div>
                </div>
            `;
            
            const nodeId = editor.addNode(
                `node${nodeIdCounter}`,
                1, // inputs
                1, // outputs  
                x, y,
                'test-node',
                {},
                nodeHtml
            );
            
            updateStatus(`✅ Added Node ${nodeIdCounter} at (${Math.round(x)}, ${Math.round(y)})`);
            nodeIdCounter++;
            
            // Auto-save after adding node
            setTimeout(() => saveDiagram(), 100);
        }
        
        // Add component - specialized nodes
        function addComponent(type) {
            if (!editor) {
                updateStatus('❌ Editor not initialized');
                return;
            }
            
            const x = Math.random() * 400 + 50;
            const y = Math.random() * 300 + 50;
            
            const components = {
                database: {
                    html: `<div style="padding: 12px; background: #2E7D4A; color: white; border-radius: 6px; min-width: 130px; text-align: center;">
                        <div style="font-size: 18px; margin-bottom: 5px;">📊</div>
                        <div style="font-weight: bold;">Database</div>
                        <div style="font-size: 11px; opacity: 0.9;">Data Storage</div>
                    </div>`,
                    inputs: 2,
                    outputs: 2
                },
                api: {
                    html: `<div style="padding: 12px; background: #D97706; color: white; border-radius: 6px; min-width: 130px; text-align: center;">
                        <div style="font-size: 18px; margin-bottom: 5px;">☁️</div>
                        <div style="font-weight: bold;">API Service</div>
                        <div style="font-size: 11px; opacity: 0.9;">REST/GraphQL</div>
                    </div>`,
                    inputs: 1,
                    outputs: 3
                },
                frontend: {
                    html: `<div style="padding: 12px; background: #7C3AED; color: white; border-radius: 6px; min-width: 130px; text-align: center;">
                        <div style="font-size: 18px; margin-bottom: 5px;">💻</div>
                        <div style="font-weight: bold;">Frontend</div>
                        <div style="font-size: 11px; opacity: 0.9;">User Interface</div>
                    </div>`,
                    inputs: 1,
                    outputs: 2
                },
                service: {
                    html: `<div style="padding: 12px; background: #DC2626; color: white; border-radius: 6px; min-width: 130px; text-align: center;">
                        <div style="font-size: 18px; margin-bottom: 5px;">⚙️</div>
                        <div style="font-weight: bold;">Service</div>
                        <div style="font-size: 11px; opacity: 0.9;">Business Logic</div>
                    </div>`,
                    inputs: 2,
                    outputs: 2
                }
            };
            
            const comp = components[type];
            if (!comp) return;
            
            const nodeId = editor.addNode(
                `${type}_${nodeIdCounter}`,
                comp.inputs,
                comp.outputs,  
                x, y,
                type,
                { type: type },
                comp.html
            );
            
            updateStatus(`✅ Added ${type} component at (${Math.round(x)}, ${Math.round(y)})`);
            nodeIdCounter++;
            
            // Auto-save after adding component
            setTimeout(() => saveDiagram(), 100);
        }
        
        // Clear diagram
        function clearDiagram() {
            if (editor) {
                editor.clear();
                nodeIdCounter = 1;
                updateStatus('🗑️ Diagram cleared');
                
                // Save empty state
                setTimeout(() => saveDiagram(), 100);
            }
        }
        
        // Zoom functions
        function zoomIn() {
            if (editor) {
                editor.zoom_in();
                updateStatus('🔍+ Zoomed in');
            }
        }
        
        function zoomOut() {
            if (editor) {
                editor.zoom_out();
                updateStatus('🔍- Zoomed out');
            }
        }
        
        function zoomReset() {
            if (editor) {
                editor.zoom_reset();
                updateStatus('🔄 Zoom reset to 100%');
            }
        }
        
        // Save diagram to database
        async function saveDiagram() {
            if (!editor) {
                updateStatus('❌ Cannot save - editor not initialized');
                return;
            }
            
            try {
                const diagramData = editor.export();
                updateStatus('🔄 Saving diagram to test API...');
                
                const response = await fetch('/api/save_drawflow_diagram_test.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        application_id: applicationId,
                        diagram_data: diagramData,
                        notes: 'Auto-saved diagram'
                    })
                });
                
                // Debug: Check response status
                updateStatus(`📡 Response status: ${response.status}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    updateStatus('💾 Diagram saved to database');
                } else {
                    updateStatus('❌ Save failed: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                updateStatus('❌ Save error: ' + error.message);
                console.error('Save error details:', error);
            }
        }
        
        // Load diagram from database
        async function loadDiagram() {
            try {
                updateStatus('🔄 Loading diagram from test API...');
                
                const response = await fetch(`/api/load_drawflow_diagram_test.php?application_id=${applicationId}`);
                
                // Debug: Check response status
                updateStatus(`📡 Load response status: ${response.status}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const result = await response.json();
                
                if (result.success && result.data && result.data.drawflow_diagram) {
                    const diagramData = JSON.parse(result.data.drawflow_diagram);
                    
                    if (editor && diagramData.drawflow) {
                        editor.import(diagramData);
                        
                        // Update counter based on loaded nodes
                        const nodeCount = Object.keys(diagramData.drawflow.Home.data).length;
                        nodeIdCounter = nodeCount + 1;
                        
                        updateStatus(`📂 Loaded diagram with ${nodeCount} nodes`);
                    }
                } else {
                    updateStatus('📂 No existing diagram found - starting fresh');
                }
            } catch (error) {
                updateStatus('⚠️ Load error: ' + error.message);
                console.error('Load error details:', error);
            }
        }
        
        // Export diagram as JSON file
        function exportDiagram() {
            if (!editor) {
                updateStatus('❌ Cannot export - editor not initialized');
                return;
            }
            
            try {
                const diagramData = editor.export();
                const dataStr = JSON.stringify(diagramData, null, 2);
                const dataBlob = new Blob([dataStr], {type: 'application/json'});
                
                const link = document.createElement('a');
                link.href = URL.createObjectURL(dataBlob);
                link.download = `drawflow-diagram-app-${applicationId}-${new Date().toISOString().slice(0,10)}.json`;
                link.click();
                
                updateStatus('� Diagram exported as JSON file');
            } catch (error) {
                updateStatus('❌ Export error: ' + error.message);
                console.error('Export error:', error);
            }
        }
        
        // Update status log
        function updateStatus(message) {
            const statusDiv = document.getElementById('status-info');
            if (statusDiv) {
                const timestamp = new Date().toLocaleTimeString();
                const newMessage = `[${timestamp}] ${message}\n`;
                statusDiv.textContent = newMessage + statusDiv.textContent;
                
                // Keep only last 20 messages
                const lines = statusDiv.textContent.split('\n');
                if (lines.length > 21) {
                    statusDiv.textContent = lines.slice(0, 21).join('\n');
                }
            }
            console.log(message);
        }
    </script>
</body>
</html>
