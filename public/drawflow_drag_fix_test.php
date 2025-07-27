<?php
// Test version with drag-and-drop fixes
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$application_id = isset($_GET['app_id']) ? intval($_GET['app_id']) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drawflow - Drag Fix Test</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Drawflow CSS -->
    <link href="/assets/vendor/drawflow.min.css" rel="stylesheet">
    
    <!-- Custom Drawflow CSS -->
    <link href="/assets/css/components/drawflow-custom.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 20px;
        }
        
        .success-info {
            background: #e8f5e8;
            border: 1px solid #4caf50;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .test-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-project-diagram text-primary"></i> Drawflow - Drag & Drop Fix Test</h1>
        
        <div class="success-info">
            <h5>🔧 Drag & Drop Fix Applied!</h5>
            <p><strong>Application ID:</strong> <?php echo $application_id; ?></p>
            <p class="mb-0">Testing improved drag and drop with coordinate fixes.</p>
        </div>
        
        <div class="test-info">
            <h6><i class="fas fa-mouse"></i> Drag & Drop Test:</h6>
            <ol class="mb-0">
                <li>Try dragging the nodes around - should move 1:1 with mouse</li>
                <li>Check if nodes follow mouse cursor precisely</li>
                <li>Test zoom in/out and dragging</li>
                <li>Report any coordinate issues in status log</li>
            </ol>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <!-- Component Palette -->
                <div class="component-palette">
                    <h4>Components</h4>
                    <div class="palette-item database" onclick="addComponent('database')">
                        <i class="fas fa-database"></i>
                        <span>Database</span>
                    </div>
                    <div class="palette-item api" onclick="addComponent('api')">
                        <i class="fas fa-cloud"></i>
                        <span>API</span>
                    </div>
                    <div class="palette-item frontend" onclick="addComponent('frontend')">
                        <i class="fas fa-desktop"></i>
                        <span>Frontend</span>
                    </div>
                    <div class="palette-item service" onclick="addComponent('service')">
                        <i class="fas fa-cogs"></i>
                        <span>Service</span>
                    </div>
                </div>

                <!-- Drawflow Container -->
                <div class="drawflow-container">
                    <div class="drawflow-toolbar">
                        <button onclick="addTestNodes()" title="Add Test Nodes" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i>
                            <span>Add Test Nodes</span>
                        </button>
                        <button onclick="clearDiagram()" title="Clear All" class="btn btn-warning btn-sm">
                            <i class="fas fa-trash"></i>
                            <span>Clear</span>
                        </button>
                        <button onclick="testCoordinates()" title="Test Coordinates" class="btn btn-info btn-sm">
                            <i class="fas fa-crosshairs"></i>
                            <span>Test Coords</span>
                        </button>
                        <button onclick="debugContainer()" title="Debug Container" class="btn btn-secondary btn-sm">
                            <i class="fas fa-bug"></i>
                            <span>Debug</span>
                        </button>
                    </div>
                    
                    <!-- Zoom Controls -->
                    <div class="zoom-controls">
                        <button onclick="zoomIn()" title="Zoom In">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button onclick="zoomReset()" title="Reset Zoom">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </button>
                        <button onclick="zoomOut()" title="Zoom Out">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                    
                    <!-- Drawflow Editor -->
                    <div id="drawflow" class="drawflow"></div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-mouse"></i> Drag Test Guide</h6>
                    </div>
                    <div class="card-body small">
                        <p><strong>What to Test:</strong></p>
                        <ul class="mb-3">
                            <li>Click and drag nodes - should follow mouse 1:1</li>
                            <li>No "jumping" or coordinate offset</li>
                            <li>Smooth dragging experience</li>
                        </ul>
                        
                        <p><strong>Expected Behavior:</strong></p>
                        <ul class="mb-3">
                            <li>Node moves exactly where you drag</li>
                            <li>No mouse vs. element mismatch</li>
                            <li>Works at all zoom levels</li>
                        </ul>
                        
                        <p><strong>Fixes Applied:</strong></p>
                        <ul class="mb-0">
                            <li>CSS transform fixes</li>
                            <li>Container positioning correction</li>
                            <li>Coordinate calculation override</li>
                        </ul>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h6><i class="fas fa-terminal"></i> Status Log</h6>
                    </div>
                    <div class="card-body">
                        <div id="status-info" class="small">
                            <div class="text-muted">Initializing drag fix test...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Drawflow JS -->
    <script src="/assets/vendor/drawflow.min.js"></script>
    
    <!-- Custom Drawflow Editor -->
    <script src="/assets/js/components/drawflow-editor.js"></script>

    <script>
        // Global variables
        let drawflowEditor;
        let testEditor;
        const applicationId = <?php echo $application_id; ?>;
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            updateStatus('🔄 Starting drag fix test...');
            initializeDrawflow();
        });
        
        // Initialize Drawflow editor
        function initializeDrawflow() {
            try {
                updateStatus('✅ Libraries loaded');
                
                if (typeof DrawflowEditor !== 'undefined') {
                    updateStatus('✅ DrawflowEditor class found');
                    
                    drawflowEditor = new DrawflowEditor('drawflow');
                    const success = drawflowEditor.init(); // No app ID for testing
                    
                    if (success) {
                        updateStatus('✅ Drawflow editor initialized with drag fixes');
                        
                        // Add test nodes after a delay
                        setTimeout(() => {
                            addTestNodes();
                        }, 500);
                    } else {
                        updateStatus('❌ Failed to initialize editor');
                        initBasicDrawflow();
                    }
                } else {
                    updateStatus('❌ DrawflowEditor class not found');
                    initBasicDrawflow();
                }
            } catch (error) {
                updateStatus('❌ Error: ' + error.message);
                console.error('Initialization error:', error);
                initBasicDrawflow();
            }
        }
        
        // Fallback to basic Drawflow
        function initBasicDrawflow() {
            try {
                updateStatus('🔄 Using basic Drawflow...');
                
                const container = document.getElementById('drawflow');
                testEditor = new Drawflow(container);
                testEditor.reroute = true;
                testEditor.start();
                
                updateStatus('✅ Basic Drawflow initialized');
                addBasicTestNodes();
                
            } catch (error) {
                updateStatus('❌ Basic Drawflow failed: ' + error.message);
            }
        }
        
        // Add test nodes for dragging
        function addTestNodes() {
            if (drawflowEditor && drawflowEditor.isInitialized) {
                try {
                    const node1 = drawflowEditor.addComponentNode('database', 100, 100);
                    const node2 = drawflowEditor.addComponentNode('api', 300, 150);
                    const node3 = drawflowEditor.addComponentNode('frontend', 500, 200);
                    updateStatus('✅ Added 3 test nodes for drag testing');
                    updateStatus('🖱️ Try dragging the nodes - they should follow mouse precisely');
                } catch (error) {
                    updateStatus('❌ Error adding test nodes: ' + error.message);
                }
            }
        }
        
        // Add basic test nodes
        function addBasicTestNodes() {
            if (testEditor) {
                const node1 = testEditor.addNode('test1', 0, 1, 100, 100, 'drawflow-node', {}, 
                    '<div style="padding:10px; background:lightblue; border-radius:5px;">Drag Test 1</div>');
                const node2 = testEditor.addNode('test2', 1, 1, 300, 150, 'drawflow-node', {}, 
                    '<div style="padding:10px; background:lightgreen; border-radius:5px;">Drag Test 2</div>');
                const node3 = testEditor.addNode('test3', 1, 0, 500, 200, 'drawflow-node', {}, 
                    '<div style="padding:10px; background:orange; border-radius:5px;">Drag Test 3</div>');
                updateStatus('✅ Added 3 basic test nodes');
            }
        }
        
        // Test coordinates
        function testCoordinates() {
            updateStatus('🔍 Testing coordinate system...');
            
            const container = document.getElementById('drawflow');
            const rect = container.getBoundingClientRect();
            
            updateStatus(`📐 Container position: ${rect.left.toFixed(0)}, ${rect.top.toFixed(0)}`);
            updateStatus(`📐 Container size: ${rect.width.toFixed(0)} x ${rect.height.toFixed(0)}`);
            
            // Test mouse position tracking
            let mouseTestActive = true;
            const mouseHandler = function(e) {
                if (mouseTestActive) {
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    updateStatus(`🖱️ Mouse in container: ${x.toFixed(0)}, ${y.toFixed(0)}`);
                    mouseTestActive = false; // Only show first click
                    container.removeEventListener('click', mouseHandler);
                }
            };
            
            container.addEventListener('click', mouseHandler);
            updateStatus('🖱️ Click anywhere in the canvas to test coordinates');
        }
        
        // Debug container
        function debugContainer() {
            const container = document.getElementById('drawflow');
            const style = window.getComputedStyle(container);
            
            updateStatus('🔍 Container Debug Info:');
            updateStatus(`Position: ${style.position}`);
            updateStatus(`Transform: ${style.transform}`);
            updateStatus(`Zoom: ${style.zoom || 'none'}`);
            updateStatus(`Box-sizing: ${style.boxSizing}`);
            
            if (drawflowEditor && drawflowEditor.editor) {
                updateStatus(`Zoom Value: ${drawflowEditor.editor.zoom_value || 'unknown'}`);
            }
        }
        
        // Add component from palette
        function addComponent(type) {
            if (drawflowEditor && drawflowEditor.isInitialized) {
                try {
                    const x = Math.random() * 400 + 50;
                    const y = Math.random() * 300 + 50;
                    const nodeId = drawflowEditor.addComponentNode(type, x, y);
                    updateStatus(`➕ Added ${type} node at (${x.toFixed(0)}, ${y.toFixed(0)}) - ID: ${nodeId}`);
                } catch (error) {
                    updateStatus('❌ Error adding component: ' + error.message);
                }
            } else if (testEditor) {
                const x = Math.random() * 400 + 50;
                const y = Math.random() * 300 + 50;
                const nodeId = testEditor.addNode(type, 1, 1, x, y, 'drawflow-node', {}, 
                    `<div style="padding:10px; background:lightcoral; border-radius:5px;">${type}</div>`);
                updateStatus(`➕ Added ${type} node - ID: ${nodeId}`);
            } else {
                updateStatus('❌ No editor available');
            }
        }
        
        // Clear diagram
        function clearDiagram() {
            if (drawflowEditor && drawflowEditor.isInitialized) {
                drawflowEditor.clear();
                updateStatus('🗑️ Diagram cleared');
            } else if (testEditor) {
                testEditor.clear();
                updateStatus('🗑️ Diagram cleared');
            }
        }
        
        // Zoom functions
        function zoomIn() {
            if (drawflowEditor && drawflowEditor.isInitialized) {
                drawflowEditor.zoomIn();
                updateStatus('🔍 Zoomed in - test dragging now');
            } else if (testEditor) {
                testEditor.zoom_in();
                updateStatus('🔍 Zoomed in');
            }
        }
        
        function zoomOut() {
            if (drawflowEditor && drawflowEditor.isInitialized) {
                drawflowEditor.zoomOut();
                updateStatus('🔍 Zoomed out - test dragging now');
            } else if (testEditor) {
                testEditor.zoom_out();
                updateStatus('🔍 Zoomed out');
            }
        }
        
        function zoomReset() {
            if (drawflowEditor && drawflowEditor.isInitialized) {
                drawflowEditor.zoomReset();
                updateStatus('🔍 Zoom reset - test dragging now');
            } else if (testEditor) {
                testEditor.zoom_reset();
                updateStatus('🔍 Zoom reset');
            }
        }
        
        // Update status
        function updateStatus(message) {
            const statusDiv = document.getElementById('status-info');
            const timestamp = new Date().toLocaleTimeString();
            const newMessage = `<div class="mb-1"><small class="text-muted">${timestamp}:</small> ${message}</div>`;
            
            statusDiv.innerHTML = newMessage + statusDiv.innerHTML;
            
            // Keep only last 15 messages
            const messages = statusDiv.children;
            while (messages.length > 15) {
                statusDiv.removeChild(messages[messages.length - 1]);
            }
        }
    </script>
</body>
</html>
