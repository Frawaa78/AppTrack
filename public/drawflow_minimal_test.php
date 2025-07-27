<?php
// Minimal Drawflow test - matching official demo as closely as possible
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Drawflow - Minimal Test (Like Official Demo)</title>
    
    <!-- Only Drawflow CSS - no Bootstrap or custom CSS -->
    <link href="/assets/vendor/drawflow.min.css" rel="stylesheet">
    
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        
        .container {
            width: 100%;
            height: 600px;
            border: 1px solid #ccc;
            position: relative;
        }
        
        /* Minimal styling - exactly like official demo */
        #drawflow {
            width: 100%;
            height: 100%;
            background: white;
            position: relative;
        }
        
        /* Simple toolbar */
        .toolbar {
            margin-bottom: 10px;
        }
        
        .toolbar button {
            margin-right: 10px;
            padding: 5px 10px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Drawflow - Minimal Test (Like Official Demo)</h1>
    
    <div class="toolbar">
        <button onclick="addNode()">Add Node</button>
        <button onclick="clearAll()">Clear</button>
        <button onclick="zoomIn()">Zoom In</button>
        <button onclick="zoomOut()">Zoom Out</button>
        <button onclick="zoomReset()">Reset Zoom</button>
    </div>
    
    <div class="container">
        <div id="drawflow"></div>
    </div>
    
    <div style="margin-top: 10px;">
        <strong>Test Instructions:</strong>
        <ul>
            <li>Click "Add Node" to create nodes</li>
            <li>Try dragging them - should be smooth 1:1 movement</li>
            <li>This matches the official demo setup exactly</li>
        </ul>
    </div>

    <!-- Only Drawflow JS - no other libraries -->
    <script src="/assets/vendor/drawflow.min.js"></script>

    <script>
        // Minimal setup - exactly like official demo
        let editor;
        let nodeIdCounter = 1;
        
        // Initialize on page load
        window.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('drawflow');
            
            console.log('Initializing Drawflow...');
            console.log('Container:', container);
            console.log('Container styles:', window.getComputedStyle(container));
            
            // Initialize Drawflow with minimal settings
            editor = new Drawflow(container);
            editor.reroute = true;
            editor.start();
            
            console.log('Drawflow initialized:', editor);
            
            // Add initial test node
            addNode();
        });
        
        // Add a simple node
        function addNode() {
            if (!editor) {
                console.error('Editor not initialized');
                return;
            }
            
            // Random position
            const x = Math.random() * 400 + 50;
            const y = Math.random() * 300 + 50;
            
            // Simple node HTML - like official demo
            const nodeHtml = `
                <div style="padding: 10px; background: #4EA5D9; color: white; border-radius: 5px; min-width: 120px;">
                    <div style="font-weight: bold;">Node ${nodeIdCounter}</div>
                    <div style="font-size: 12px;">Drag me!</div>
                </div>
            `;
            
            const nodeId = editor.addNode(
                `node${nodeIdCounter}`, // name
                1, // inputs
                1, // outputs  
                x, // pos x
                y, // pos y
                'test-node', // class
                {}, // data
                nodeHtml // html
            );
            
            console.log(`Added node ${nodeIdCounter} at (${x.toFixed(0)}, ${y.toFixed(0)}) with ID: ${nodeId}`);
            nodeIdCounter++;
        }
        
        // Clear all nodes
        function clearAll() {
            if (editor) {
                editor.clear();
                nodeIdCounter = 1;
                console.log('Cleared all nodes');
            }
        }
        
        // Zoom functions
        function zoomIn() {
            if (editor) {
                editor.zoom_in();
                console.log('Zoomed in');
            }
        }
        
        function zoomOut() {
            if (editor) {
                editor.zoom_out();
                console.log('Zoomed out');
            }
        }
        
        function zoomReset() {
            if (editor) {
                editor.zoom_reset();
                console.log('Zoom reset');
            }
        }
        
        // Debug mouse events
        document.getElementById('drawflow').addEventListener('mousedown', function(e) {
            console.log('Mouse down at:', e.clientX, e.clientY);
        });
        
        document.getElementById('drawflow').addEventListener('mousemove', function(e) {
            // Only log when dragging (reduce console spam)
            if (e.buttons === 1) {
                console.log('Dragging at:', e.clientX, e.clientY);
            }
        });
    </script>
</body>
</html>
