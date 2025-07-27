<?php
// Simple Drawflow test - no authentication required
$application_id = isset($_GET['app_id']) ? intval($_GET['app_id']) : 429;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drawflow Test - Simple</title>
    
    <!-- Drawflow CSS and Custom Theme -->
    <link href="/assets/vendor/drawflow.min.css" rel="stylesheet">
    <link href="/assets/css/components/drawflow-theme.css" rel="stylesheet">
    
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        
        .header {
            background: #4EA5D9;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .toolbar {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px 8px 0 0;
            padding: 10px;
            display: flex;
            gap: 10px;
        }
        
        .toolbar button {
            padding: 8px 15px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .toolbar button:hover {
            background: #f8f9fa;
        }
        
        .editor-container {
            width: 100%;
            height: 600px;
            border: 1px solid #ddd;
            border-radius: 0 0 8px 8px;
            background: white;
        }
        
        #drawflow {
            width: 100%;
            height: 100%;
            background: white;
            position: relative;
        }
        
        .status {
            margin-top: 20px;
            padding: 15px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔧 Drawflow Test - Simple</h1>
        <div>Application ID: <?php echo $application_id; ?> | Time: <?php echo date('Y-m-d H:i:s'); ?></div>
    </div>
    
    <div class="toolbar">
        <button onclick="addNode('process')">➕ Process Node</button>
        <button onclick="addNode('database')">🗄️ Database Node</button>
        <button onclick="addNode('api')">🔌 API Node</button>
        <button onclick="clearDiagram()">🗑️ Clear</button>
        <button onclick="saveDiagram()">💾 Save</button>
        <button onclick="loadDiagram()">📂 Load</button>
        <button onclick="exportDiagram()">📥 Export</button>
        <button onclick="zoomIn()">🔍+ Zoom In</button>
        <button onclick="zoomOut()">🔍- Zoom Out</button>
        <button onclick="zoomReset()">🔄 Reset</button>
    </div>
    
    <div class="editor-container">
        <div id="drawflow"></div>
    </div>
    
    <div class="status" id="status">Ready...</div>

    <!-- Only Drawflow JS -->
    <script src="/assets/vendor/drawflow.min.js"></script>

    <script>
        let editor;
        let nodeCounter = 1;
        const applicationId = <?php echo $application_id; ?>;
        
        // Initialize
        window.addEventListener('DOMContentLoaded', function() {
            log('🔄 Initializing Drawflow...');
            
            const container = document.getElementById('drawflow');
            editor = new Drawflow(container);
            editor.reroute = true;
            editor.start();
            
            log('✅ Drawflow initialized');
            loadDiagram();
        });
        
        // Add node with different types
        function addNode(type = 'process') {
            if (!editor) {
                log('❌ Editor not ready');
                return;
            }
            
            const x = Math.random() * 400 + 50;
            const y = Math.random() * 300 + 50;
            
            let html, nodeClass, inputs, outputs;
            
            switch(type) {
                case 'database':
                    html = `
                        <div>
                            <div style="font-weight: bold; margin-bottom: 5px;">🗄️ Database ${nodeCounter}</div>
                            <div style="font-size: 12px; color: #666;">Storage Layer</div>
                        </div>
                    `;
                    nodeClass = 'database';
                    inputs = 2;
                    outputs = 1;
                    break;
                case 'api':
                    html = `
                        <div>
                            <div style="font-weight: bold; margin-bottom: 5px;">🔌 API ${nodeCounter}</div>
                            <div style="font-size: 12px; color: #666;">Service Layer</div>
                        </div>
                    `;
                    nodeClass = 'api';
                    inputs = 1;
                    outputs = 2;
                    break;
                default: // process
                    html = `
                        <div>
                            <div style="font-weight: bold; margin-bottom: 5px;">⚙️ Process ${nodeCounter}</div>
                            <div style="font-size: 12px; color: #666;">Business Logic</div>
                        </div>
                    `;
                    nodeClass = 'process';
                    inputs = 1;
                    outputs = 1;
            }
            
            editor.addNode(`${type}${nodeCounter}`, inputs, outputs, x, y, nodeClass, {type: type}, html);
            log(`✅ Added ${type} Node ${nodeCounter}`);
            nodeCounter++;
            
            // Auto-save
            setTimeout(() => saveDiagram(), 100);
        }
        
        // Clear
        function clearDiagram() {
            if (editor) {
                editor.clear();
                nodeCounter = 1;
                log('🗑️ Cleared');
                setTimeout(() => saveDiagram(), 100);
            }
        }
        
        // Zoom functions
        function zoomIn() { if (editor) editor.zoom_in(); }
        function zoomOut() { if (editor) editor.zoom_out(); }
        function zoomReset() { if (editor) editor.zoom_reset(); }
        
        // Save to database using original API
        async function saveDiagram() {
            if (!editor) return;
            
            try {
                log('🔄 Saving...');
                const data = editor.export();
                
                const response = await fetch('/public/api/save_drawflow_diagram.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        application_id: applicationId,
                        diagram_data: data,
                        notes: 'Auto-saved'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    log('💾 Saved to database');
                } else {
                    log('❌ Save failed: ' + (result.message || result.error || 'Unknown'));
                }
            } catch (error) {
                log('❌ Save error: ' + error.message);
            }
        }
        
        // Load from database using original API
        async function loadDiagram() {
            try {
                log('🔄 Loading...');
                
                const response = await fetch(`/public/api/load_drawflow_diagram.php?application_id=${applicationId}`);
                const result = await response.json();
                
                if (result.success && result.diagram_data) {
                    const diagramData = result.diagram_data;
                    
                    if (editor && diagramData && diagramData.drawflow) {
                        editor.import(diagramData);
                        const nodeCount = Object.keys(diagramData.drawflow.Home.data || {}).length;
                        nodeCounter = nodeCount + 1;
                        log(`📂 Loaded ${nodeCount} nodes`);
                    } else {
                        log('📂 No diagram data to import');
                    }
                } else {
                    log('📂 No saved diagram found - starting fresh');
                }
            } catch (error) {
                log('❌ Load error: ' + error.message);
            }
        }
        
        // Export as JSON
        function exportDiagram() {
            if (!editor) return;
            
            const data = editor.export();
            const blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/json'});
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `drawflow-${applicationId}-${new Date().toISOString().slice(0,10)}.json`;
            link.click();
            log('📥 Exported');
        }
        
        // Simple logging
        function log(message) {
            const status = document.getElementById('status');
            const time = new Date().toLocaleTimeString();
            status.textContent = `[${time}] ${message}\n` + status.textContent;
            
            // Keep last 10 messages
            const lines = status.textContent.split('\n');
            if (lines.length > 11) {
                status.textContent = lines.slice(0, 11).join('\n');
            }
            
            console.log(message);
        }
    </script>
</body>
</html>
