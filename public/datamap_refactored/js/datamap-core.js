// DataMap Core JavaScript - Refactored Version
// Enhanced with full functionality from original datamap.php

window.DataMapCore = {
    editor: null,
    nodeCounter: 1,
    isAutoSaving: false,
    
    init: function() {
        console.log('🔄 Initializing DataMap Core...');
        
        try {
            // Initialize DrawFlow editor
            const container = document.getElementById('drawflow');
            if (!container) {
                throw new Error('DrawFlow container not found');
            }
            
            // Debug container dimensions
            console.log('📐 DrawFlow container dimensions:', {
                width: container.offsetWidth,
                height: container.offsetHeight,
                display: window.getComputedStyle(container).display
            });
            
            this.editor = new Drawflow(container);
            this.editor.start();
            
            // Force container to have minimum dimensions
            if (container.offsetHeight < 100) {
                container.style.height = '400px';
                console.log('📐 Fixed container height to 400px');
            }
            
            console.log('✅ DrawFlow initialized');
            
            // Initialize comment connections
            window.commentConnections = {};
            
            // Load node templates
            this.loadNodeTemplates();
            
            // Load existing diagram
            this.loadDiagram();
            
            // Setup event handlers
            this.setupEventHandlers();
            
            // Setup keyboard shortcuts
            this.setupKeyboardShortcuts();
            
            // Setup context menu
            this.setupContextMenu();
            
            // Remove any grip handles that might be present (one-time setup)
            setTimeout(() => {
                this.removeGripHandles();
                this.setupCursorStyling(); // Initial cursor setup
            }, 500);
            
            console.log('✅ DataMap Core initialized successfully');
            
        } catch (error) {
            console.error('❌ DataMap Core initialization failed:', error);
        }
    },
    
    loadNodeTemplates: async function() {
        try {
            console.log('🔄 Loading node templates...');
            
            const response = await fetch('/public/api/get_node_templates.php');
            const result = await response.json();
            
            if (result.success && result.templates) {
                const container = document.getElementById('nodeTemplates');
                if (container) {
                    container.innerHTML = '';
                    
                    result.templates.forEach(template => {
                        const nodeItem = document.createElement('div');
                        nodeItem.className = 'node-item';
                        nodeItem.onclick = () => this.addNode(template.type);
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
                    
                    console.log(`✅ Loaded ${result.templates.length} node templates`);
                } else {
                    console.error('❌ Node templates container not found');
                }
            } else {
                throw new Error(result.message || 'Failed to load templates');
            }
        } catch (error) {
            console.error('❌ Error loading node templates:', error);
            const container = document.getElementById('nodeTemplates');
            if (container) {
                container.innerHTML = '<div style="text-align: center; padding: 20px; color: #dc3545;">Error loading elements</div>';
            }
        }
    },
    
    loadDiagram: async function() {
        try {
            console.log('🔄 Loading diagram...');
            
            const response = await fetch(`/public/api/load_drawflow_diagram.php?application_id=${window.applicationId}`);
            const result = await response.json();
            
            console.log('📄 Raw diagram data from database:', result);
            
            // Debug: Log connections in detail from loaded data
            if (result.success && result.diagram_data && result.diagram_data.drawflow && result.diagram_data.drawflow.Home && result.diagram_data.drawflow.Home.data) {
                console.log('📄 Connections being loaded:');
                Object.keys(result.diagram_data.drawflow.Home.data).forEach(nodeId => {
                    const node = result.diagram_data.drawflow.Home.data[nodeId];
                    if (node) {  // Check if node exists
                        console.log(`Node ${nodeId}:`);
                        
                        if (node.outputs) {
                            Object.keys(node.outputs).forEach(outputKey => {
                                const output = node.outputs[outputKey];
                                if (output.connections && output.connections.length > 0) {
                                    console.log(`  ${outputKey}: `, output.connections);
                                    // Log each connection in detail
                                    output.connections.forEach((conn, idx) => {
                                        console.log(`    Connection ${idx}: node=${conn.node}, output=${conn.output}, input=${conn.input}`);
                                    });
                                }
                            });
                        }
                        
                        if (node.inputs) {
                            Object.keys(node.inputs).forEach(inputKey => {
                                const input = node.inputs[inputKey];
                                if (input.connections && input.connections.length > 0) {
                                    console.log(`  ${inputKey}: `, input.connections);
                                    // Log each connection in detail
                                    input.connections.forEach((conn, idx) => {
                                        console.log(`    Connection ${idx}: node=${conn.node}, input=${conn.input}, output=${conn.output}`);
                                    });
                                }
                            });
                        }
                    } else {
                        console.log(`Node ${nodeId}: null/undefined`);
                    }
                });
            }
            
            if (result.success && result.diagram_data) {
                if (this.editor && result.diagram_data.drawflow) {
                    try {
                        // Validate and repair diagram data before importing
                        const validatedData = this.validateAndRepairDiagramData(result.diagram_data);
                        
                        // Convert array-based data to object-based data if needed
                        if (validatedData.drawflow && validatedData.drawflow.Home && validatedData.drawflow.Home.data) {
                            const data = validatedData.drawflow.Home.data;
                            
                            // If data is an array, convert to object format for DrawFlow
                            if (Array.isArray(data)) {
                                console.log('🔄 Converting array-based data to object format for DrawFlow compatibility');
                                const objectData = {};
                                
                                data.forEach((node, index) => {
                                    if (node && node.id) {
                                        objectData[node.id] = node;
                                    }
                                });
                                
                                validatedData.drawflow.Home.data = objectData;
                                console.log(`✅ Converted ${Object.keys(objectData).length} nodes from array to object format`);
                            }
                        }
                        
                        // Try to import the validated diagram data
                        this.editor.import(validatedData);
                        
                        // Load comment connections if available
                        if (result.diagram_data.commentConnections) {
                            // Clean up old comment connections first
                            window.commentConnections = {};
                            
                            // Only load valid comment connections
                            if (Array.isArray(result.diagram_data.commentConnections)) {
                                // Handle array format (sparse array with null values)
                                result.diagram_data.commentConnections.forEach((connections, index) => {
                                    if (connections && Array.isArray(connections) && connections.length > 0) {
                                        window.commentConnections[index] = connections;
                                    }
                                });
                            } else if (typeof result.diagram_data.commentConnections === 'object') {
                                // Handle object format
                                Object.keys(result.diagram_data.commentConnections).forEach(key => {
                                    const connections = result.diagram_data.commentConnections[key];
                                    if (connections && Array.isArray(connections) && connections.length > 0) {
                                        window.commentConnections[key] = connections;
                                    }
                                });
                            }
                            
                            console.log('💬 Loaded comment connections:', window.commentConnections);
                            
                            // Clean up invalid connections (where nodes don't exist)
                            this.cleanupInvalidCommentConnections();
                            
                            // Redraw comment connections after a short delay to ensure nodes are rendered
                            setTimeout(() => {
                                this.updateCommentConnections();
                                this.removeGripHandles(); // Remove any grip handles from loaded nodes
                                this.setupCursorStyling(); // Apply proper cursor styling to loaded nodes
                                
                                // Force update all connection positions after diagram load
                                setTimeout(() => {
                                    this.updateAllConnectionPositions();
                                    this.updateConnectionIndicators(); // Update connection indicators after load
                                    console.log('🔧 Connection positions updated after diagram load');
                                }, 200);
                                
                                console.log('🎨 Comment connections redrawn, grip handles removed, and cursor styling applied');
                            }, 500);
                        }
                        
                        console.log('✅ Diagram loaded successfully');
                    } catch (importError) {
                        console.log('⚠️ Legacy diagram data detected, starting with clean diagram');
                        console.log('📝 Import error details:', importError.message);
                        
                        // If import fails, start with a clean diagram
                        this.editor.clear();
                        console.log('✅ Started with clean diagram (legacy data incompatible)');
                    }
                } else {
                    console.log('ℹ️ No existing diagram data found');
                }
            }
        } catch (error) {
            console.error('❌ Error loading diagram:', error);
            // Ensure we still have a working editor even if loading fails
            if (this.editor) {
                this.editor.clear();
                console.log('✅ Fallback: Started with clean diagram');
            }
        }
    },
    
    addNode: function(type) {
        if (!this.editor) {
            console.error('❌ Editor not ready');
            return;
        }
        
        const x = Math.random() * 400 + 100;
        const y = Math.random() * 300 + 100;
        
        const template = this.getNodeTemplate(type, this.nodeCounter);
        
        // Initialize node data with default text
        const nodeData = {
            type: type,
            title: `${type.charAt(0).toUpperCase() + type.slice(1)} ${this.nodeCounter}`,
            description: this.getDefaultDescription(type),
            created: new Date().toISOString()
        };
        
        this.editor.addNode(
            `${type}_${this.nodeCounter}`, 
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
            this.initializeTextareas();
            this.removeGripHandles();
            this.setupCursorStyling(); // Apply proper cursor styling
        }, 50);
        
        console.log(`✅ Added ${type} node (#${this.nodeCounter}) at position (${x}, ${y})`);
        this.nodeCounter++;
        
        // Auto-save
        this.autoSave();
    },
    
    setupEventHandlers: function() {
        if (!this.editor) return;
        
        this.editor.on('nodeCreated', () => this.autoSave());
        this.editor.on('nodeRemoved', () => this.autoSave());
        this.editor.on('nodeMoved', () => {
            this.autoSave();
            // Update comment connections when nodes are moved
            this.updateCommentConnections();
            // Also update regular connection positions
            setTimeout(() => {
                this.updateAllConnectionPositions();
            }, 100);
        });
        this.editor.on('connectionCreated', (info) => {
            console.log('🔗 Connection created:', {
                output_id: info.output_id,
                input_id: info.input_id,
                output_class: info.output_class,
                input_class: info.input_class
            });
            
            // Check if we need to enforce single connection rule
            if (this.shouldEnforceSingleConnection()) {
                const connectionAllowed = this.validateSingleConnection(info);
                if (!connectionAllowed) {
                    // Remove the connection that was just created
                    console.log('🚫 Connection not allowed - removing duplicate connection');
                    setTimeout(() => {
                        this.editor.removeSingleConnection(
                            info.output_id,
                            info.input_id,
                            info.output_class,
                            info.input_class
                        );
                    }, 50); // Small delay to ensure connection is fully created before removal
                    return;
                }
            }
            
            // Debug: Check all connections after creation
            setTimeout(() => {
                console.log('🔍 All connections after creation:');
                const data = this.editor.export();
                if (data.drawflow && data.drawflow.Home && data.drawflow.Home.data) {
                    Object.keys(data.drawflow.Home.data).forEach(nodeId => {
                        const node = data.drawflow.Home.data[nodeId];
                        if (node && (node.inputs || node.outputs)) {
                            console.log(`Node ${nodeId}:`);
                            if (node.inputs) {
                                Object.keys(node.inputs).forEach(inputKey => {
                                    const input = node.inputs[inputKey];
                                    if (input.connections && input.connections.length > 0) {
                                        console.log(`  ${inputKey}: ${input.connections.length} connections`);
                                    }
                                });
                            }
                            if (node.outputs) {
                                Object.keys(node.outputs).forEach(outputKey => {
                                    const output = node.outputs[outputKey];
                                    if (output.connections && output.connections.length > 0) {
                                        console.log(`  ${outputKey}: ${output.connections.length} connections`);
                                    }
                                });
                            }
                        }
                    });
                }
                this.autoSave();
                this.updateConnectionIndicators(); // Update visual indicators
            }, 100);
        });
        this.editor.on('connectionRemoved', () => {
            this.autoSave();
            setTimeout(() => {
                this.updateConnectionIndicators(); // Update visual indicators after removal
            }, 50);
        });
        
        // Set up custom drag handling
        this.setupCustomDragHandling();
        
        // Set up node observer for automatic textarea initialization
        this.setupNodeObserver();
        
        // Set up real-time comment connection updates during node dragging
        const drawflowContainer = document.getElementById('drawflow');
        if (drawflowContainer) {
            // Listen for mouse move events to update comment connections in real-time
            let lastUpdateTime = 0;
            const updateThrottle = 100; // Limit updates to every 100ms
            
            drawflowContainer.addEventListener('mousemove', (e) => {
                // Only update if a node is being dragged
                if (e.target.closest('.drawflow-node') && e.buttons === 1) {
                    const now = Date.now();
                    if (now - lastUpdateTime > updateThrottle) {
                        lastUpdateTime = now;
                        
                        // Throttle updates using animation frame
                        if (window.commentConnectionAnimationFrame) {
                            cancelAnimationFrame(window.commentConnectionAnimationFrame);
                        }
                        window.commentConnectionAnimationFrame = requestAnimationFrame(() => {
                            this.updateCommentConnections();
                        });
                    }
                }
            });
        }
    },

    setupCustomDragHandling: function() {
        const drawflowContainer = document.getElementById('drawflow');
        if (!drawflowContainer) return;

        // Smart drag handling - prevent drag only on text elements
        drawflowContainer.addEventListener('mousedown', (e) => {
            const isTextElement = e.target.tagName === 'INPUT' || 
                                  e.target.tagName === 'TEXTAREA' || 
                                  e.target.contentEditable === 'true' || 
                                  e.target.isContentEditable;
            
            if (isTextElement) {
                // Prevent dragging when clicking on text elements
                e.stopPropagation();
                console.log('💬 Text element clicked, preventing drag:', e.target.tagName);
                return;
            }
            
            // Allow normal dragging for everything else
            const nodeElement = e.target.closest('.drawflow-node');
            if (nodeElement) {
                nodeElement.classList.add('dragging');
                console.log('🖱️ Node drag started');
            }
        }, true);

        // Clean up dragging class
        document.addEventListener('mouseup', () => {
            document.querySelectorAll('.drawflow-node.dragging').forEach(node => {
                node.classList.remove('dragging');
            });
        });

        // Add focus/blur event handlers for textarea styling
        drawflowContainer.addEventListener('focus', (e) => {
            if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT' || e.target.isContentEditable) {
                e.target.style.background = '#fff';
                e.target.style.borderColor = '#007bff';
                e.target.style.boxShadow = '0 0 0 2px rgba(0,123,255,0.25)';
                console.log('🎯 Input focused, background set to white');
            }
        }, true);

        drawflowContainer.addEventListener('blur', (e) => {
            if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT' || e.target.isContentEditable) {
                e.target.style.background = 'transparent';
                e.target.style.borderColor = 'transparent';
                e.target.style.boxShadow = 'none';
                console.log('🎯 Input blurred, background set to transparent');
            }
        }, true);
    },

    setupNodeObserver: function() {
        const drawflowContainer = document.getElementById('drawflow');
        if (!drawflowContainer) return;

        // Use MutationObserver to watch for new nodes
        const observer = new MutationObserver((mutations) => {
            let needsInit = false;
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE && 
                            (node.classList.contains('drawflow-node') || 
                             node.querySelector('.drawflow-node'))) {
                            needsInit = true;
                        }
                    });
                }
            });
            
            if (needsInit) {
                // Delay initialization to allow DrawFlow to complete node creation
                setTimeout(() => {
                    this.initializeTextareas();
                    this.setupCursorStyling(); // Apply cursor styling to newly added nodes
                }, 100);
            }
        });

        observer.observe(drawflowContainer, {
            childList: true,
            subtree: true
        });
    },
    
    autoSave: function() {
        if (this.isAutoSaving) return;
        
        clearTimeout(this.autoSaveTimeout);
        this.autoSaveTimeout = setTimeout(() => {
            this.saveDiagram(true);
        }, 1000);
    },
    
    saveDiagram: async function(isAutoSave = false) {
        if (!this.editor || this.isAutoSaving) return;
        
        try {
            this.isAutoSaving = true;
            this.updateSaveStatus('saving');
            
            const data = this.editor.export();
            
            // Debug: Log connections in detail  
            if (data.drawflow && data.drawflow.Home && data.drawflow.Home.data) {
                console.log('💾 Connections being saved:');
                Object.keys(data.drawflow.Home.data).forEach(nodeId => {
                    const node = data.drawflow.Home.data[nodeId];
                    if (node) {  // Check if node exists
                        console.log(`Node ${nodeId}:`);
                        
                        if (node.outputs) {
                            Object.keys(node.outputs).forEach(outputKey => {
                                const output = node.outputs[outputKey];
                                if (output.connections && output.connections.length > 0) {
                                    console.log(`  ${outputKey}: `, output.connections);
                                }
                            });
                        }
                        
                        if (node.inputs) {
                            Object.keys(node.inputs).forEach(inputKey => {
                                const input = node.inputs[inputKey];
                                if (input.connections && input.connections.length > 0) {
                                    console.log(`  ${inputKey}: `, input.connections);
                                }
                            });
                        }
                    } else {
                        console.log(`Node ${nodeId}: null/undefined`);
                    }
                });
            }
            
            // Debug: Log the data being saved
            console.log('💾 Saving diagram data:', JSON.stringify(data, null, 2));
            
            // Add comment connections to the data
            if (window.commentConnections) {
                data.commentConnections = window.commentConnections;
            }
            
            const response = await fetch('/public/api/save_drawflow_diagram.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    application_id: window.applicationId,
                    diagram_data: data,
                    notes: isAutoSave ? 'Auto-saved' : 'Manual save'
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.updateSaveStatus('saved');
                if (!isAutoSave) {
                    console.log('💾 Diagram saved successfully');
                }
            } else {
                this.updateSaveStatus('error');
                console.error('❌ Save failed:', result.message || 'Unknown error');
            }
        } catch (error) {
            this.updateSaveStatus('error');
            console.error('❌ Save error:', error);
        } finally {
            this.isAutoSaving = false;
        }
    },
    
    // Zoom functions
    zoomIn: function() {
        if (this.editor) {
            this.editor.zoom_in();
            console.log('🔍 Zoomed in');
        }
    },
    
    zoomOut: function() {
        if (this.editor) {
            this.editor.zoom_out();
            console.log('🔍 Zoomed out');
        }
    },
    
    zoomReset: function() {
        if (this.editor) {
            this.editor.zoom_reset();
            console.log('🔍 Zoom reset to 100%');
        }
    },
    
    // Clear diagram function
    clearDiagram: function() {
        if (!this.editor) return;
        
        if (confirm('Are you sure you want to clear all nodes and connections? This action cannot be undone.')) {
            this.editor.clear();
            console.log('🗑️ Diagram cleared');
            this.saveDiagram(false); // Save the cleared state
        }
    },
    
    // Export diagram function
    exportDiagram: function() {
        if (!this.editor) return;
        
        try {
            const data = this.editor.export();
            const jsonString = JSON.stringify(data, null, 2);
            
            // Create download link
            const blob = new Blob([jsonString], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `datamap_app_${window.applicationId}_${new Date().toISOString().slice(0, 10)}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            console.log('📥 Diagram exported successfully');
        } catch (error) {
            console.error('❌ Export failed:', error);
            alert('Export failed: ' + error.message);
        }
    },
    
    // Validate and repair diagram data to prevent import errors
    validateAndRepairDiagramData: function(diagramData) {
        console.log('🔍 Validating diagram data...');
        
        try {
            // Create a deep copy to avoid modifying original data
            const validatedData = JSON.parse(JSON.stringify(diagramData));
            
            if (!validatedData.drawflow || !validatedData.drawflow.Home || !validatedData.drawflow.Home.data) {
                console.log('⚠️ Invalid diagram structure, using clean data');
                return { drawflow: { Home: { data: {} } } };
            }
            
            const nodes = validatedData.drawflow.Home.data;
            let repairCount = 0;
            
            // Handle both array and object format
            let nodesList = [];
            if (Array.isArray(nodes)) {
                // Convert array to list, filtering out null entries
                nodesList = nodes.map((node, index) => ({ nodeId: index.toString(), node }))
                    .filter(item => item.node !== null && item.node !== undefined);
            } else {
                // Convert object to list
                nodesList = Object.keys(nodes).map(nodeId => ({ nodeId, node: nodes[nodeId] }))
                    .filter(item => item.node !== null && item.node !== undefined);
            }
            
            // Validate and repair each node
            nodesList.forEach(({ nodeId, node }) => {
                // Ensure node has required properties
                if (!node.id) {
                    node.id = parseInt(nodeId);
                    repairCount++;
                }
                
                // Ensure inputs and outputs are objects, not null/undefined
                if (!node.inputs || typeof node.inputs !== 'object') {
                    node.inputs = {};
                    repairCount++;
                }
                
                if (!node.outputs || typeof node.outputs !== 'object') {
                    node.outputs = {};
                    repairCount++;
                }
                
                // Validate input connections
                Object.keys(node.inputs).forEach(inputKey => {
                    const input = node.inputs[inputKey];
                    if (!input.connections || !Array.isArray(input.connections)) {
                        input.connections = [];
                        repairCount++;
                    } else {
                        // Validate each connection in the input
                        input.connections = input.connections.filter(conn => {
                            return conn && 
                                   typeof conn.node === 'string' && 
                                   typeof conn.input === 'string' &&
                                   nodes[conn.node]; // Referenced node exists
                        });
                    }
                });
                
                // Validate output connections
                Object.keys(node.outputs).forEach(outputKey => {
                    const output = node.outputs[outputKey];
                    if (!output.connections || !Array.isArray(output.connections)) {
                        output.connections = [];
                        repairCount++;
                    } else {
                        // Validate each connection in the output
                        output.connections = output.connections.filter(conn => {
                            return conn && 
                                   typeof conn.node === 'string' && 
                                   typeof conn.output === 'string' &&
                                   nodes[conn.node]; // Referenced node exists
                        });
                    }
                });
                
                // Ensure position data exists
                if (typeof node.pos_x !== 'number') {
                    node.pos_x = 100;
                    repairCount++;
                }
                if (typeof node.pos_y !== 'number') {
                    node.pos_y = 100;
                    repairCount++;
                }
                
                // Ensure essential properties exist
                if (!node.name) node.name = node.class || 'unknown';
                if (!node.class) node.class = '';
                if (!node.html) node.html = '';
                if (!node.data) node.data = {};
                if (typeof node.typenode === 'undefined') node.typenode = false;
            });
            
            // Repair connection consistency - ensure both sides of each connection exist
            this.repairConnectionConsistency(validatedData.drawflow.Home.data);
            
            if (repairCount > 0) {
                console.log(`🔧 Repaired ${repairCount} issues in diagram data`);
            } else {
                console.log('✅ Diagram data validation passed');
            }
            
            return validatedData;
            
        } catch (error) {
            console.error('❌ Error validating diagram data:', error);
            console.log('🔄 Falling back to clean diagram');
            return { drawflow: { Home: { data: {} } } };
        }
    },
    
    // Repair connection consistency - ensure both sides of connections exist
    repairConnectionConsistency: function(nodes) {
        console.log('🔧 Repairing connection consistency...');
        let repairCount = 0;
        
        // Convert to list format for easier processing
        const nodesList = Array.isArray(nodes) 
            ? nodes.map((node, index) => ({ nodeId: index.toString(), node })).filter(item => item.node)
            : Object.keys(nodes).map(nodeId => ({ nodeId, node: nodes[nodeId] })).filter(item => item.node);
        
        // For each node's output connections, ensure the target node has corresponding input connection
        nodesList.forEach(({ nodeId, node }) => {
            if (!node.outputs) return;
            
            Object.keys(node.outputs).forEach(outputKey => {
                const output = node.outputs[outputKey];
                if (!output.connections) return;
                
                output.connections.forEach(connection => {
                    const targetNodeId = connection.node;
                    const targetInputKey = connection.output; // This is actually the input key
                    
                    const targetNode = nodes[targetNodeId];
                    if (!targetNode || !targetNode.inputs || !targetNode.inputs[targetInputKey]) {
                        console.log(`⚠️ Missing target input: ${nodeId}[${outputKey}] → ${targetNodeId}[${targetInputKey}]`);
                        return;
                    }
                    
                    // Check if target input has corresponding connection back
                    const targetInput = targetNode.inputs[targetInputKey];
                    if (!targetInput.connections) {
                        targetInput.connections = [];
                    }
                    
                    // Look for existing connection back to source
                    const existingConnection = targetInput.connections.find(conn => 
                        conn.node === nodeId && conn.input === outputKey
                    );
                    
                    if (!existingConnection) {
                        // Add missing reverse connection
                        targetInput.connections.push({
                            node: nodeId,
                            input: outputKey
                        });
                        repairCount++;
                        console.log(`🔧 Repaired missing input connection: ${targetNodeId}[${targetInputKey}] ← ${nodeId}[${outputKey}]`);
                    }
                });
            });
        });
        
        // For each node's input connections, ensure the source node has corresponding output connection
        nodesList.forEach(({ nodeId, node }) => {
            if (!node.inputs) return;
            
            Object.keys(node.inputs).forEach(inputKey => {
                const input = node.inputs[inputKey];
                if (!input.connections) return;
                
                input.connections.forEach(connection => {
                    const sourceNodeId = connection.node;
                    const sourceOutputKey = connection.input; // This is actually the output key
                    
                    const sourceNode = nodes[sourceNodeId];
                    if (!sourceNode || !sourceNode.outputs || !sourceNode.outputs[sourceOutputKey]) {
                        console.log(`⚠️ Missing source output: ${sourceNodeId}[${sourceOutputKey}] → ${nodeId}[${inputKey}]`);
                        return;
                    }
                    
                    // Check if source output has corresponding connection forward
                    const sourceOutput = sourceNode.outputs[sourceOutputKey];
                    if (!sourceOutput.connections) {
                        sourceOutput.connections = [];
                    }
                    
                    // Look for existing connection forward to target
                    const existingConnection = sourceOutput.connections.find(conn => 
                        conn.node === nodeId && conn.output === inputKey
                    );
                    
                    if (!existingConnection) {
                        // Add missing forward connection
                        sourceOutput.connections.push({
                            node: nodeId,
                            output: inputKey
                        });
                        repairCount++;
                        console.log(`🔧 Repaired missing output connection: ${sourceNodeId}[${sourceOutputKey}] → ${nodeId}[${inputKey}]`);
                    }
                });
            });
        });
        
        if (repairCount > 0) {
            console.log(`✅ Repaired ${repairCount} connection consistency issues`);
        } else {
            console.log('✅ Connection consistency is good');
        }
    },

    // Update connection positions (for layout fixes)
    updateAllConnectionPositions: function() {
        if (!this.editor) return;
        
        try {
            // Use DrawFlow's native connection update method (like original datamap.php)
            const data = this.editor.export();
            if (!data || !data.drawflow || !data.drawflow.Home || !data.drawflow.Home.data) {
                console.log('ℹ️ No diagram data to update connections for');
                return;
            }
            
            const nodes = data.drawflow.Home.data;
            let updatedNodes = 0;
            
            // Update each node's connections using DrawFlow's built-in method
            Object.keys(nodes).forEach(nodeId => {
                if (this.editor && typeof this.editor.updateConnectionNodes === 'function') {
                    this.editor.updateConnectionNodes(`node-${nodeId}`);
                    updatedNodes++;
                }
            });
            
            // Also force a complete redraw by triggering DrawFlow's internal positioning
            setTimeout(() => {
                Object.keys(nodes).forEach(nodeId => {
                    if (this.editor && typeof this.editor.updateConnectionNodes === 'function') {
                        this.editor.updateConnectionNodes(`node-${nodeId}`);
                    }
                });
                
                // Additional force refresh of all connection paths
                const connections = document.querySelectorAll('.connection .main-path');
                connections.forEach(path => {
                    const parentConnection = path.parentElement;
                    if (parentConnection && parentConnection.classList.length >= 4) {
                        const nodeOutClass = parentConnection.classList[2]; // node_out_node-X
                        const nodeInClass = parentConnection.classList[1];  // node_in_node-X
                        
                        if (nodeOutClass && nodeInClass) {
                            const outputNodeId = nodeOutClass.replace('node_out_node-', '');
                            const inputNodeId = nodeInClass.replace('node_in_node-', '');
                            
                            // Force update both nodes
                            if (this.editor && typeof this.editor.updateConnectionNodes === 'function') {
                                this.editor.updateConnectionNodes(`node-${outputNodeId}`);
                                this.editor.updateConnectionNodes(`node-${inputNodeId}`);
                            }
                        }
                    }
                });
            }, 100);
            
            console.log(`🔧 Updated connection positions for ${updatedNodes} nodes`);
            
        } catch (error) {
            console.error('❌ Error updating connection positions:', error);
        }
    },
    
    // Setup keyboard shortcuts
    setupKeyboardShortcuts: function() {
        document.addEventListener('keydown', (e) => {
            // Only process shortcuts when not typing in input fields
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            
            // Ctrl/Cmd + S = Save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                this.saveDiagram(false);
                return;
            }
            
            // Ctrl/Cmd + Plus = Zoom In
            if ((e.ctrlKey || e.metaKey) && (e.key === '+' || e.key === '=')) {
                e.preventDefault();
                this.zoomIn();
                return;
            }
            
            // Ctrl/Cmd + Minus = Zoom Out
            if ((e.ctrlKey || e.metaKey) && e.key === '-') {
                e.preventDefault();
                this.zoomOut();
                return;
            }
            
            // Ctrl/Cmd + 0 = Zoom Reset
            if ((e.ctrlKey || e.metaKey) && e.key === '0') {
                e.preventDefault();
                this.zoomReset();
                return;
            }
            
            // Delete key = Delete selected node
            if (e.key === 'Delete' || e.key === 'Backspace') {
                // This would need more implementation for node selection
                console.log('🗑️ Delete key pressed');
            }
        });
        
        console.log('⌨️ Keyboard shortcuts enabled');
    },
    
    updateSaveStatus: function(status) {
        const statusElement = document.getElementById('saveStatus');
        if (!statusElement) return;
        
        switch(status) {
            case 'saving':
                statusElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                statusElement.style.color = '#007bff';
                statusElement.style.background = '#e3f2fd';
                break;
            case 'saved':
                statusElement.innerHTML = '<i class="fas fa-check-circle"></i> Saved';
                statusElement.style.color = '#28a745';
                statusElement.style.background = '#d4edda';
                
                // Reset to default after 2 seconds
                setTimeout(() => {
                    statusElement.innerHTML = '<i class="fas fa-check-circle"></i> Auto-save enabled';
                    statusElement.style.color = '#666';
                    statusElement.style.background = '#fff';
                }, 2000);
                break;
            case 'error':
                statusElement.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Save Error';
                statusElement.style.color = '#dc3545';
                statusElement.style.background = '#f8d7da';
                
                // Reset to default after 5 seconds
                setTimeout(() => {
                    statusElement.innerHTML = '<i class="fas fa-check-circle"></i> Auto-save enabled';
                    statusElement.style.color = '#666';
                    statusElement.style.background = '#fff';
                }, 5000);
                break;
        }
    },
    
    // Helper functions for node templates and descriptions
    getNodeTemplate: function(type, counter) {
        // Use database template if available, otherwise fallback to hardcoded
        const dbTemplate = window.nodeTemplates ? window.nodeTemplates[type] : null;
        
        if (dbTemplate) {
            return {
                html: `
                    <div style="padding: 7px;">
                        <div style="display: flex; align-items: center; margin-bottom: 3px;">
                            <i class="${dbTemplate.icon_class}" style="color: #444444; margin-right: 5px; font-size: 13px;"></i>
                            <div class="node-title" style="font-weight: bold; flex: 1;" 
                                 contenteditable="true" 
                                 onblur="window.DataMapCore.updateNodeText(this)"
                                 onkeydown="window.DataMapCore.handleTextEdit(event)">${dbTemplate.display_name} ${counter}</div>
                        </div>
                        <textarea class="node-description auto-resize" 
                                  onblur="window.DataMapCore.updateNodeText(this)"
                                  onkeydown="window.DataMapCore.handleTextEdit(event)"
                                  oninput="window.DataMapCore.autoResizeTextarea(this)"
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
                <div style="padding: 7px;">
                    <div style="display: flex; align-items: center; margin-bottom: 3px;">
                        <i class="fa-solid fa-question" style="color: #444444; margin-right: 5px; font-size: 13px;"></i>
                        <div class="node-title" style="font-weight: bold; flex: 1;" 
                             contenteditable="true" 
                             onblur="window.DataMapCore.updateNodeText(this)"
                             onkeydown="window.DataMapCore.handleTextEdit(event)">Unknown ${counter}</div>
                    </div>
                    <textarea class="node-description auto-resize" 
                              onblur="window.DataMapCore.updateNodeText(this)"
                              onkeydown="window.DataMapCore.handleTextEdit(event)"
                              oninput="window.DataMapCore.autoResizeTextarea(this)"
                              rows="1">Unknown node type</textarea>
                </div>
            `,
            inputs: 1,
            outputs: 1,
            class: 'unknown-node'
        };
    },
    
    getDefaultDescription: function(type) {
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
            visualization: 'Dashboard, report, or data visualization',
            comment: 'Comment or annotation'
        };
        return descriptions[type] || 'Description';
    },
    
    // Context menu setup
    setupContextMenu: function() {
        // Hide any existing context menu on document click
        document.addEventListener('click', this.hideContextMenu.bind(this));
        
        // Disable default browser context menu on drawflow nodes and connections
        document.addEventListener('contextmenu', (e) => {
            // Check if right-clicking on a node
            if (e.target.closest('.drawflow-node')) {
                e.preventDefault();
                this.showContextMenu(e, e.target.closest('.drawflow-node'));
            }
            // Check if right-clicking on a connection line
            else if (e.target.closest('.connection')) {
                e.preventDefault();
                this.showConnectionContextMenu(e, e.target.closest('.connection'));
            }
        });
        
        console.log('📋 Context menu initialized');
    },
    
    // Show custom context menu
    showContextMenu: function(event, nodeElement) {
        this.hideContextMenu(); // Hide any existing menu
        
        // Check if nodeElement is valid
        if (!nodeElement || !nodeElement.id) {
            console.warn('⚠️ Invalid nodeElement passed to showContextMenu');
            return;
        }
        
        const nodeId = nodeElement.id.replace('node-', '');
        
        // Check if editor and node data exist
        if (!this.editor || !this.editor.drawflow || !this.editor.drawflow.drawflow.Home.data[nodeId]) {
            console.warn('⚠️ Node data not found for nodeId:', nodeId);
            return;
        }
        
        const nodeData = this.editor.drawflow.drawflow.Home.data[nodeId];
        const isCommentNode = nodeData && nodeData.class && nodeData.class.includes('comment-node');
        
        const menu = document.createElement('div');
        menu.className = 'custom-context-menu';
        menu.id = 'contextMenu';
        
        // Menu items based on node type
        let menuItems = [];
        
        if (isCommentNode) {
            menuItems = [
                { icon: 'fas fa-link', text: 'Connect to', action: null, submenu: () => this.getConnectSubmenu(nodeId) },
                { divider: true },
                { icon: 'fas fa-trash', text: 'Delete', action: () => this.deleteNode(nodeId), danger: true }
            ];
        } else {
            // Get current input/output counts for validation
            const currentInputs = Object.keys(nodeData.inputs || {}).length || 1;
            const currentOutputs = Object.keys(nodeData.outputs || {}).length || 1;
            
            menuItems = [
                { icon: 'fas fa-plus-circle', text: 'Add Input', action: () => this.addNodeInput(nodeId) },
                { icon: 'fas fa-plus-circle', text: 'Add Output', action: () => this.addNodeOutput(nodeId) },
                { icon: 'fas fa-minus-circle', text: 'Remove Input', action: () => this.removeNodeInput(nodeId), disabled: currentInputs <= 1 },
                { icon: 'fas fa-minus-circle', text: 'Remove Output', action: () => this.removeNodeOutput(nodeId), disabled: currentOutputs <= 1 },
                { divider: true },
                { icon: 'fas fa-trash', text: 'Delete', action: () => this.deleteNode(nodeId), danger: true }
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
                menuItem.className = `context-menu-item ${item.danger ? 'danger' : ''}${item.submenu ? ' has-submenu' : ''}${item.disabled ? ' disabled' : ''}`;
                
                let iconHtml = `<i class="${item.icon}"></i>${item.text}`;
                if (item.submenu) {
                    iconHtml += '<i class="fas fa-chevron-right submenu-arrow"></i>';
                }
                menuItem.innerHTML = iconHtml;
                
                if (item.disabled) {
                    // Disabled items should not have click handlers
                    menuItem.style.opacity = '0.5';
                    menuItem.style.cursor = 'not-allowed';
                } else if (item.submenu) {
                    // Handle submenu on hover
                    let submenuTimeout;
                    menuItem.onmouseenter = () => {
                        clearTimeout(submenuTimeout);
                        this.showSubmenu(menuItem, item.submenu(), event);
                    };
                    menuItem.onmouseleave = () => {
                        submenuTimeout = setTimeout(() => {
                            const submenu = document.getElementById('contextSubmenu');
                            if (submenu && !submenu.matches(':hover')) {
                                this.hideSubmenu();
                            }
                        }, 300); // Small delay to allow moving to submenu
                    };
                } else if (item.action) {
                    menuItem.onclick = () => {
                        item.action();
                        this.hideContextMenu();
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
        
        console.log('📋 Context menu shown for node ' + nodeId);
    },
    
    // Show context menu for connection lines
    showConnectionContextMenu: function(event, connectionElement) {
        this.hideContextMenu(); // Hide any existing menu
        
        const menu = document.createElement('div');
        menu.className = 'custom-context-menu';
        menu.id = 'contextMenu';
        
        // Menu items for connections
        const menuItems = [
            { icon: 'fas fa-trash', text: 'Delete Connection', action: () => this.deleteConnection(connectionElement), danger: true }
        ];
        
        // Build menu HTML
        menuItems.forEach(item => {
            const menuItem = document.createElement('div');
            menuItem.className = `context-menu-item ${item.danger ? 'danger' : ''}`;
            menuItem.innerHTML = `<i class="${item.icon}"></i>${item.text}`;
            menuItem.onclick = () => {
                item.action();
                this.hideContextMenu();
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
    },
    
    hideContextMenu: function() {
        const existingMenu = document.getElementById('contextMenu');
        if (existingMenu) {
            existingMenu.remove();
        }
        this.hideSubmenu();
    },
    
    hideSubmenu: function() {
        const existingSubmenu = document.getElementById('contextSubmenu');
        if (existingSubmenu) {
            existingSubmenu.remove();
        }
    },
    
    showSubmenu: function(parentItem, submenuItems, originalEvent) {
        // Remove any existing submenu
        this.hideSubmenu();
        
        const submenu = document.createElement('div');
        submenu.className = 'context-submenu';
        submenu.id = 'contextSubmenu';
        
        // Build submenu HTML
        submenuItems.forEach(item => {
            const menuItem = document.createElement('div');
            menuItem.className = `context-menu-item ${item.disabled ? 'disabled' : ''}`;
            menuItem.innerHTML = `<i class="${item.icon}"></i>${item.text}`;
            
            if (!item.disabled && item.action) {
                menuItem.onclick = () => {
                    item.action();
                    this.hideContextMenu();
                };
            }
            
            submenu.appendChild(menuItem);
        });
        
        // Position submenu relative to parent item
        document.body.appendChild(submenu);
        
        const parentRect = parentItem.getBoundingClientRect();
        let x = parentRect.right;
        let y = parentRect.top;
        
        // Adjust position to stay within viewport
        const submenuRect = submenu.getBoundingClientRect();
        if (x + submenuRect.width > window.innerWidth) {
            x = parentRect.left - submenuRect.width;
        }
        if (y + submenuRect.height > window.innerHeight) {
            y = window.innerHeight - submenuRect.height - 10;
        }
        
        submenu.style.left = x + 'px';
        submenu.style.top = y + 'px';
        
        // Handle mouse leave to close submenu
        submenu.onmouseleave = () => {
            setTimeout(() => {
                if (!submenu.matches(':hover') && !parentItem.matches(':hover')) {
                    this.hideSubmenu();
                }
            }, 300);
        };
    },
    
    // Node manipulation functions
    deleteNode: function(nodeId) {
        if (confirm('Are you sure you want to delete this node? This action cannot be undone.')) {
            // Clean up comment connections involving this node
            this.cleanupCommentConnectionsForNode(nodeId);
            
            this.editor.removeNodeId('node-' + nodeId);
            this.autoSave();
            console.log('🗑️ Node ' + nodeId + ' deleted');
        }
    },
    
    deleteConnection: function(connectionElement) {
        if (confirm('Are you sure you want to delete this connection? This action cannot be undone.')) {
            try {
                let connectionInfo = null;
                
                // Parse connection info from CSS classes
                const classes = Array.from(connectionElement.classList);
                
                let fromNodeId = null;
                let toNodeId = null;
                let fromOutput = null;
                let toInput = null;
                
                // Extract node IDs and connection points from classes
                classes.forEach(className => {
                    if (className.startsWith('node_out_node-')) {
                        fromNodeId = className.replace('node_out_node-', '');
                    } else if (className.startsWith('node_in_node-')) {
                        toNodeId = className.replace('node_in_node-', '');
                    } else if (className.startsWith('output_')) {
                        fromOutput = className.replace('output_', '');
                    } else if (className.startsWith('input_')) {
                        toInput = className.replace('input_', '');
                    }
                });
                
                if (fromNodeId && toNodeId && fromOutput && toInput) {
                    connectionInfo = {
                        fromNodeId: fromNodeId,
                        toNodeId: toNodeId,
                        fromOutput: 'output_' + fromOutput,
                        toInput: 'input_' + toInput
                    };
                }
                
                if (connectionInfo) {
                    // Use DrawFlow's official API to remove the connection
                    this.editor.removeSingleConnection(
                        connectionInfo.fromNodeId, 
                        connectionInfo.toNodeId, 
                        connectionInfo.fromOutput, 
                        connectionInfo.toInput
                    );
                    console.log('🗑️ Connection deleted');
                    this.autoSave();
                } else {
                    console.error('Could not identify connection to delete');
                    console.error('Available classes: ' + Array.from(connectionElement.classList).join(', '));
                }
            } catch (error) {
                console.error('Error deleting connection: ' + error.message);
            }
        }
    },
    
    // Add input to node with smart connection handling
    addNodeInput: function(nodeId) {
        const nodeData = this.editor.drawflow.drawflow.Home.data[nodeId];
        if (!nodeData) return;
        
        const currentInputs = Object.keys(nodeData.inputs || {}).length || 1;
        const currentOutputs = Object.keys(nodeData.outputs || {}).length || 1;
        
        console.log(`➕ Adding input to node ${nodeId} (current: ${currentInputs} inputs, ${currentOutputs} outputs)`);
        
        const newNodeId = this.recreateNodeWithPorts(nodeId, currentInputs + 1, currentOutputs);
        if (newNodeId) {
            console.log(`✅ Successfully added input to node ${nodeId} → ${newNodeId}`);
            
            // Delay auto-save to allow connection restoration to complete
            setTimeout(() => {
                this.autoSave();
            }, 1500);
        }
    },
    
    // Add output to node with smart connection handling
    addNodeOutput: function(nodeId) {
        const nodeData = this.editor.drawflow.drawflow.Home.data[nodeId];
        if (!nodeData) return;
        
        const currentInputs = Object.keys(nodeData.inputs || {}).length || 1;
        const currentOutputs = Object.keys(nodeData.outputs || {}).length || 1;
        
        console.log(`➕ Adding output to node ${nodeId} (current: ${currentInputs} inputs, ${currentOutputs} outputs)`);
        
        const newNodeId = this.recreateNodeWithPorts(nodeId, currentInputs, currentOutputs + 1);
        if (newNodeId) {
            console.log(`✅ Successfully added output to node ${nodeId} → ${newNodeId}`);
        }
        
        // Delay auto-save to allow connection restoration to complete
        setTimeout(() => {
            this.autoSave();
        }, 1500);
    },
    
    // Remove input from node with connection preservation
    removeNodeInput: function(nodeId) {
        const nodeData = this.editor.drawflow.drawflow.Home.data[nodeId];
        if (!nodeData) return;
        
        const currentInputs = Object.keys(nodeData.inputs || {}).length || 1;
        const currentOutputs = Object.keys(nodeData.outputs || {}).length || 1;
        
        if (currentInputs <= 1) {
            console.log(`⚠️ Cannot remove input: node ${nodeId} must have at least 1 input`);
            return;
        }
        
        console.log(`➖ Removing input from node ${nodeId} (current: ${currentInputs} inputs, ${currentOutputs} outputs)`);
        
        const newNodeId = this.recreateNodeWithPorts(nodeId, currentInputs - 1, currentOutputs);
        if (newNodeId) {
            console.log(`✅ Successfully removed input from node ${nodeId} → ${newNodeId}`);
        }
        
        // Delay auto-save to allow connection restoration to complete
        setTimeout(() => {
            this.autoSave();
        }, 1500);
    },
    
    // Remove output from node with connection preservation
    removeNodeOutput: function(nodeId) {
        const nodeData = this.editor.drawflow.drawflow.Home.data[nodeId];
        if (!nodeData) return;
        
        const currentInputs = Object.keys(nodeData.inputs || {}).length || 1;
        const currentOutputs = Object.keys(nodeData.outputs || {}).length || 1;
        
        if (currentOutputs <= 1) {
            console.log(`⚠️ Cannot remove output: node ${nodeId} must have at least 1 output`);
            return;
        }
        
        console.log(`➖ Removing output from node ${nodeId} (current: ${currentInputs} inputs, ${currentOutputs} outputs)`);
        
        const newNodeId = this.recreateNodeWithPorts(nodeId, currentInputs, currentOutputs - 1);
        if (newNodeId) {
            console.log(`✅ Successfully removed output from node ${nodeId} → ${newNodeId}`);
        }
        
        // Delay auto-save to allow connection restoration to complete
        setTimeout(() => {
            this.autoSave();
        }, 1500);
    },
    
    // Save node connections before recreation (based on original datamap.php)
    saveNodeConnections: function(nodeId) {
        const connections = { inputs: [], outputs: [] };
        const nodeData = this.editor.drawflow.drawflow.Home.data[nodeId];
        
        if (!nodeData) return connections;
        
        // Save input connections
        Object.keys(nodeData.inputs || {}).forEach(inputKey => {
            const input = nodeData.inputs[inputKey];
            if (input.connections) {
                input.connections.forEach(conn => {
                    connections.inputs.push({
                        inputKey: inputKey,
                        sourceNodeId: conn.node,
                        sourceOutput: conn.input
                    });
                });
            }
        });
        
        // Save output connections
        Object.keys(nodeData.outputs || {}).forEach(outputKey => {
            const output = nodeData.outputs[outputKey];
            if (output.connections) {
                output.connections.forEach(conn => {
                    connections.outputs.push({
                        outputKey: outputKey,
                        targetNodeId: conn.node,
                        targetInput: conn.output
                    });
                });
            }
        });
        
        console.log(`💾 Saved ${connections.inputs.length} input and ${connections.outputs.length} output connections for node ${nodeId}`);
        return connections;
    },
    
    // Clear all connections for a specific node to prevent duplicates
    clearNodeConnections: function(nodeId) {
        try {
            const nodeData = this.editor.drawflow.drawflow.Home.data[nodeId];
            if (!nodeData) return;
            
            let clearedCount = 0;
            
            // Clear input connections
            Object.keys(nodeData.inputs || {}).forEach(inputKey => {
                if (nodeData.inputs[inputKey].connections) {
                    clearedCount += nodeData.inputs[inputKey].connections.length;
                    nodeData.inputs[inputKey].connections = [];
                }
            });
            
            // Clear output connections  
            Object.keys(nodeData.outputs || {}).forEach(outputKey => {
                if (nodeData.outputs[outputKey].connections) {
                    clearedCount += nodeData.outputs[outputKey].connections.length;
                    nodeData.outputs[outputKey].connections = [];
                }
            });
            
            // Also clean up connections from other nodes pointing to this node
            Object.keys(this.editor.drawflow.drawflow.Home.data).forEach(otherNodeId => {
                const otherNode = this.editor.drawflow.drawflow.Home.data[otherNodeId];
                if (otherNode && otherNodeId !== nodeId) {
                    // Clear output connections pointing to this node
                    Object.keys(otherNode.outputs || {}).forEach(outputKey => {
                        if (otherNode.outputs[outputKey].connections) {
                            otherNode.outputs[outputKey].connections = otherNode.outputs[outputKey].connections.filter(
                                conn => conn.node !== nodeId
                            );
                        }
                    });
                }
            });
            
            console.log(`🧹 Cleared ${clearedCount} existing connections for node ${nodeId}`);
        } catch (error) {
            console.log(`❌ Error clearing connections for node ${nodeId}:`, error.message);
        }
    },
    
    // Restore node connections after recreation (based on original datamap.php)
    restoreNodeConnections: function(nodeId, savedConnections) {
        if (!savedConnections) return;
        
        console.log(`🔗 Starting connection restoration for node ${nodeId}`);
        
        // Longer delay to ensure node is fully created and initialized
        setTimeout(() => {
            let restoredCount = 0;
            
            // Verify the node exists before attempting restoration
            const nodeData = this.editor.drawflow.drawflow.Home.data[nodeId];
            
            if (!nodeData) {
                console.log(`❌ Node ${nodeId} not found in editor data for connection restoration`);
                console.log(`📋 Available nodes: [${Object.keys(this.editor.drawflow.drawflow.Home.data).join(', ')}]`);
                return;
            }
            
            // Get available ports on the recreated node
            const availableInputs = Object.keys(nodeData.inputs || {});
            const availableOutputs = Object.keys(nodeData.outputs || {});
            
            console.log(`📍 Node ${nodeId} available ports - Inputs: [${availableInputs.join(', ')}], Outputs: [${availableOutputs.join(', ')}]`);
            
            // Track used ports to distribute connections intelligently
            const usedInputs = {};
            const usedOutputs = {};
            
            // Restore input connections (connections coming INTO this node)
            savedConnections.inputs.forEach((conn, index) => {
                try {
                    console.log(`🔄 Attempting to restore input connection ${index + 1}/${savedConnections.inputs.length}: ${conn.sourceNodeId}[${conn.sourceOutput}] → ${nodeId}[${conn.inputKey}]`);
                    
                    // Check if source node still exists
                    if (this.editor.drawflow.drawflow.Home.data[conn.sourceNodeId]) {
                        let targetInput = conn.inputKey;
                        
                        // If the original input port doesn't exist, find the best available one
                        if (!availableInputs.includes(conn.inputKey)) {
                            // Strategy: Use the first available input or distribute evenly
                            targetInput = this.findBestAvailablePort(availableInputs, usedInputs, 'input');
                            console.log(`🔄 Redirecting input connection from port ${conn.inputKey} to ${targetInput}`);
                        }
                        
                        if (targetInput && availableInputs.includes(targetInput)) {
                            // Check if connection already exists to avoid duplicates
                            const existingConnections = nodeData.inputs[targetInput]?.connections || [];
                            const connectionExists = existingConnections.some(existing => 
                                existing.node === conn.sourceNodeId && existing.input === conn.sourceOutput
                            );
                            
                            if (!connectionExists) {
                                this.editor.addConnection(conn.sourceNodeId, nodeId, conn.sourceOutput, targetInput);
                                usedInputs[targetInput] = (usedInputs[targetInput] || 0) + 1;
                                restoredCount++;
                                console.log(`✅ Restored input: ${conn.sourceNodeId}[${conn.sourceOutput}] → ${nodeId}[${targetInput}]`);
                            } else {
                                console.log(`⏭️ Connection already exists: ${conn.sourceNodeId}[${conn.sourceOutput}] → ${nodeId}[${targetInput}]`);
                            }
                        } else {
                            console.log(`❌ No available input port for connection from ${conn.sourceNodeId}`);
                        }
                    } else {
                        console.log(`⚠️ Source node ${conn.sourceNodeId} no longer exists`);
                    }
                } catch (error) {
                    console.log(`⚠️ Failed to restore input connection: ${error.message}`);
                }
            });
            
            // Restore output connections (connections going OUT of this node)
            savedConnections.outputs.forEach((conn, index) => {
                try {
                    // Check if target node still exists
                    if (this.editor.drawflow.drawflow.Home.data[conn.targetNodeId]) {
                        let sourceOutput = conn.outputKey;
                        
                        // If the original output port doesn't exist, find the best available one
                        if (!availableOutputs.includes(conn.outputKey)) {
                            // Strategy: Use the first available output or distribute evenly
                            sourceOutput = this.findBestAvailablePort(availableOutputs, usedOutputs, 'output');
                            console.log(`🔄 Redirecting output connection from port ${conn.outputKey} to ${sourceOutput}`);
                        }
                        
                        if (sourceOutput && availableOutputs.includes(sourceOutput)) {
                            // Check if connection already exists to avoid duplicates
                            const existingConnections = nodeData.outputs[sourceOutput]?.connections || [];
                            const connectionExists = existingConnections.some(existing => 
                                existing.node === conn.targetNodeId && existing.output === conn.targetInput
                            );
                            
                            if (!connectionExists) {
                                this.editor.addConnection(nodeId, conn.targetNodeId, sourceOutput, conn.targetInput);
                                usedOutputs[sourceOutput] = (usedOutputs[sourceOutput] || 0) + 1;
                                restoredCount++;
                                console.log(`✅ Restored output: ${nodeId}[${sourceOutput}] → ${conn.targetNodeId}[${conn.targetInput}]`);
                            } else {
                                console.log(`⏭️ Connection already exists: ${nodeId}[${sourceOutput}] → ${conn.targetNodeId}[${conn.targetInput}]`);
                            }
                        } else {
                            console.log(`❌ No available output port for connection to ${conn.targetNodeId}`);
                        }
                    } else {
                        console.log(`⚠️ Target node ${conn.targetNodeId} no longer exists`);
                    }
                } catch (error) {
                    console.log(`⚠️ Failed to restore output connection: ${error.message}`);
                }
            });
            
            console.log(`🔗 Restored ${restoredCount} connections for node ${nodeId}`);
            
            // Update connection positions after restoration
            setTimeout(() => {
                this.updateAllConnectionPositions();
                console.log(`🔧 Connection positions updated for node ${nodeId}`);
                
                // Update connection indicators after connections are restored
                this.updateConnectionIndicators();
                
                // Trigger save to ensure connection state is persisted
                setTimeout(() => {
                    this.autoSave();
                }, 200);
            }, 100);
        }, 200); // Increased delay to ensure node is fully ready
    },
    
    // Find the best available port for connection restoration (based on original datamap.php)
    findBestAvailablePort: function(availablePorts, usedPorts, portType) {
        if (!availablePorts || availablePorts.length === 0) {
            console.log(`❌ No available ${portType} ports`);
            return null;
        }
        
        // If only one port available, use it
        if (availablePorts.length === 1) {
            return availablePorts[0];
        }
        
        // Find the least used port
        let leastUsedPort = availablePorts[0];
        let minUsage = usedPorts[leastUsedPort] || 0;
        
        availablePorts.forEach(port => {
            const usage = usedPorts[port] || 0;
            if (usage < minUsage) {
                minUsage = usage;
                leastUsedPort = port;
            }
        });
        
        // Prefer the first port if it's not significantly more used than the least used
        const firstPort = availablePorts[0];
        const firstPortUsage = usedPorts[firstPort] || 0;
        
        if (firstPortUsage <= minUsage + 1) {
            console.log(`📌 Using first available ${portType} port: ${firstPort} (usage: ${firstPortUsage})`);
            return firstPort;
        } else {
            console.log(`📌 Using least used ${portType} port: ${leastUsedPort} (usage: ${minUsage})`);
            return leastUsedPort;
        }
    },
    
    // Recreate node with different number of ports while preserving connections
    recreateNodeWithPorts: function(nodeId, newInputs, newOutputs) {
        const nodeData = this.editor.drawflow.drawflow.Home.data[nodeId];
        if (!nodeData) return null;
        
        console.log(`🔄 Recreating node ${nodeId} with ${newInputs} inputs and ${newOutputs} outputs`);
        
        // Save current text content from DOM
        const nodeElement = document.getElementById(`node-${nodeId}`);
        let currentTitle = nodeData.data?.title || '';
        let currentDescription = nodeData.data?.description || '';
        
        if (nodeElement) {
            const titleElement = nodeElement.querySelector('.node-title');
            const descElement = nodeElement.querySelector('.node-description');
            
            if (titleElement) {
                currentTitle = titleElement.textContent.trim();
            }
            if (descElement && descElement.tagName === 'TEXTAREA') {
                currentDescription = descElement.value;
            }
            console.log(`💾 Preserving current text - Title: "${currentTitle}", Description: "${currentDescription}"`);
        }
        
        // Save connections using the sophisticated system
        const savedConnections = this.saveNodeConnections(nodeId);
        
        console.log(`💾 Saved ${savedConnections.inputs.length} input and ${savedConnections.outputs.length} output connections`);
        
        // Save comment connections (if any exist)
        const commentConnections = [];
        if (window.commentConnections) {
            // If this node is a comment node
            if (window.commentConnections[nodeId]) {
                commentConnections.push({
                    type: 'comment',
                    commentId: nodeId,
                    targets: [...window.commentConnections[nodeId]]
                });
            }
            
            // If this node is a target of comment connections
            Object.keys(window.commentConnections).forEach(commentId => {
                const connections = window.commentConnections[commentId];
                if (connections && Array.isArray(connections)) {
                    connections.forEach(conn => {
                        if (conn && conn.targetId === nodeId) {
                            commentConnections.push({
                                type: 'target',
                                commentId: commentId,
                                targetId: nodeId,
                                connectionId: conn.connectionId
                            });
                        }
                    });
                }
            });
        }
        
        console.log(`💬 Found ${commentConnections.length} comment connections`);
        
        // Store the key properties we need to preserve
        const x = nodeData.pos_x;
        const y = nodeData.pos_y;
        const nodeClass = nodeData.class;
        const data = {
            ...nodeData.data,
            title: currentTitle,
            description: currentDescription
        };
        const html = nodeData.html;
        
        // Modify the existing node in place instead of deleting and recreating
        try {
            // Update the node data structure first
            const oldInputs = Object.keys(nodeData.inputs).length;
            const oldOutputs = Object.keys(nodeData.outputs).length;
            
            // Clear existing inputs/outputs
            nodeData.inputs = {};
            nodeData.outputs = {};
            
            // Add new inputs
            for (let i = 1; i <= newInputs; i++) {
                nodeData.inputs[`input_${i}`] = { connections: [] };
            }
            
            // Add new outputs  
            for (let i = 1; i <= newOutputs; i++) {
                nodeData.outputs[`output_${i}`] = { connections: [] };
            }
            
            // Update the DOM element to match new port structure
            if (nodeElement) {
                const inputsContainer = nodeElement.querySelector('.inputs');
                const outputsContainer = nodeElement.querySelector('.outputs');
                
                // Clear existing input/output elements
                if (inputsContainer) {
                    inputsContainer.innerHTML = '';
                    // Add new input elements
                    for (let i = 1; i <= newInputs; i++) {
                        const inputDiv = document.createElement('div');
                        inputDiv.classList.add('input', `input_${i}`);
                        inputsContainer.appendChild(inputDiv);
                    }
                }
                
                if (outputsContainer) {
                    outputsContainer.innerHTML = '';
                    // Add new output elements
                    for (let i = 1; i <= newOutputs; i++) {
                        const outputDiv = document.createElement('div');
                        outputDiv.classList.add('output', `output_${i}`);
                        outputsContainer.appendChild(outputDiv);
                    }
                }
            }
            
            console.log(`✅ Node ${nodeId} structure updated successfully`);
            
            // Restore connections after a short delay to ensure DOM is ready
            setTimeout(() => {
                console.log('🔄 Restoring connections...');
                
                // Restore regular connections using the sophisticated system
                this.restoreNodeConnections(nodeId, savedConnections);
                
                // Restore comment connections
                let restoredComments = 0;
                commentConnections.forEach(commentConn => {
                    try {
                        if (commentConn.type === 'target') {
                            // Restore connection from comment to this node
                            if (!window.commentConnections) window.commentConnections = {};
                            if (!window.commentConnections[commentConn.commentId]) {
                                window.commentConnections[commentConn.commentId] = [];
                            }
                            
                            // Check if connection doesn't already exist
                            const exists = window.commentConnections[commentConn.commentId].find(
                                conn => conn && conn.targetId === nodeId
                            );
                            
                            if (!exists) {
                                window.commentConnections[commentConn.commentId].push({
                                    targetId: nodeId,
                                    connectionId: `comment-conn-${commentConn.commentId}-${nodeId}`
                                });
                                restoredComments++;
                                console.log(`💬 Restored comment connection from ${commentConn.commentId} to ${nodeId}`);
                            }
                        }
                    } catch (e) {
                        console.warn(`❌ Failed to restore comment connection: ${e.message}`);
                    }
                });
                
                // Redraw comment connections if any were restored
                if (restoredComments > 0) {
                    setTimeout(() => {
                        this.updateCommentConnections();
                    }, 100);
                }
                
                // Initialize textareas and remove grip handles
                setTimeout(() => {
                    this.initializeTextareas();
                    this.removeGripHandles();
                    this.setupCursorStyling();
                    
                    // Restore text content to the node
                    this.restoreTextToNode(nodeId, currentTitle, currentDescription);
                    
                    // Force update of all connection lines to fix positioning
                    this.updateAllConnectionPositions();
                    
                    // Update connection indicators after recreation
                    this.updateConnectionIndicators();
                }, 100);
                
                console.log(`🎉 Node recreation complete with ${restoredComments} comment connections restored`);
                
            }, 50); // Shorter initial delay
            
            return nodeId; // Return same nodeId since we're keeping it
            
        } catch (error) {
            console.error(`❌ Error during node recreation: ${error.message}`);
            return null;
        }
    },
    
    // Restore text content to a specific node after recreation
    restoreTextToNode: function(nodeId, title, description) {
        console.log(`🔄 Attempting to restore text to node ${nodeId}`);
        
        // Try multiple ways to find the node element
        let nodeElement = document.getElementById(`node-${nodeId}`);
        if (!nodeElement) {
            nodeElement = document.getElementById(nodeId);
        }
        
        if (!nodeElement) {
            // Try finding by scanning all nodes
            const allNodes = document.querySelectorAll('.drawflow-node');
            allNodes.forEach(node => {
                if (node.id === `node-${nodeId}` || node.id === nodeId) {
                    nodeElement = node;
                }
            });
        }
        
        if (nodeElement) {
            console.log(`✅ Found node element for ${nodeId}: ${nodeElement.id}`);
            
            // Restore title
            if (title) {
                const titleElement = nodeElement.querySelector('.node-title');
                if (titleElement) {
                    titleElement.textContent = title;
                    console.log(`✅ Title restored to node ${nodeId}: "${title}"`);
                } else {
                    console.log(`⚠️ Title element not found in node ${nodeId}`);
                }
            }
            
            // Restore description
            if (description) {
                const descElement = nodeElement.querySelector('.node-description');
                if (descElement && descElement.tagName === 'TEXTAREA') {
                    descElement.value = description;
                    // Auto-resize textarea
                    this.autoResizeTextarea(descElement);
                    console.log(`✅ Description restored to node ${nodeId}: "${description}"`);
                } else {
                    console.log(`⚠️ Description textarea not found in node ${nodeId}`);
                }
            }
            
            // Update the node data in the editor as well
            const nodeData = this.editor.drawflow.drawflow.Home.data[nodeId];
            if (nodeData && nodeData.data) {
                nodeData.data.title = title;
                nodeData.data.description = description;
                console.log(`💾 Node data updated for ${nodeId}`);
            } else {
                console.log(`⚠️ Node data not found for ${nodeId} in editor`);
                console.log(`📋 Available nodes: [${Object.keys(this.editor.drawflow.drawflow.Home.data).join(', ')}]`);
            }
        } else {
            console.log(`❌ Could not find node element for ${nodeId} for text restoration`);
            console.log(`🔍 Tried IDs: node-${nodeId}, ${nodeId}`);
        }
    },
    
    // Text editing functions
    updateNodeText: function(element) {
        const nodeElement = element.closest('.drawflow-node');
        if (!nodeElement) return;
        
        const nodeId = nodeElement.id.replace('node-', '');
        const nodeData = this.editor.drawflow.drawflow.Home.data[nodeId];
        
        if (nodeData) {
            if (element.classList.contains('node-title')) {
                nodeData.data = nodeData.data || {};
                nodeData.data.title = element.textContent || element.value;
                console.log('✏️ Updated node title:', nodeData.data.title);
            } else if (element.classList.contains('node-description')) {
                nodeData.data = nodeData.data || {};
                nodeData.data.description = element.textContent || element.value;
                console.log('✏️ Updated node description:', nodeData.data.description);
            }
            this.autoSave();
        }
    },
    
    handleTextEdit: function(event) {
        // Handle Enter key in contenteditable elements
        if (event.key === 'Enter') {
            if (event.target.classList.contains('node-title')) {
                // For titles, Enter should finish editing
                event.preventDefault();
                event.target.blur();
            }
            // For textareas, Enter creates new line (default behavior)
        }
        
        // Handle Escape key to cancel editing
        if (event.key === 'Escape') {
            event.target.blur();
        }
    },
    
    autoResizeTextarea: function(textarea) {
        // Reset height to calculate new required height
        textarea.style.height = 'auto';
        
        // Set height based on scroll height
        const newHeight = Math.max(18, textarea.scrollHeight);
        textarea.style.height = newHeight + 'px';
        
        // Ensure minimum and maximum heights
        if (newHeight < 18) {
            textarea.style.height = '18px';
        } else if (newHeight > 200) {
            textarea.style.height = '200px';
            textarea.style.overflowY = 'auto';
        } else {
            textarea.style.overflowY = 'hidden';
        }
    },
    
    initializeTextareas: function() {
        // Find all textareas in the drawflow container and initialize them
        const textareas = document.querySelectorAll('#drawflow textarea.node-description');
        textareas.forEach(textarea => {
            // Add auto-resize class if not present
            if (!textarea.classList.contains('auto-resize')) {
                textarea.classList.add('auto-resize');
            }
            
            // Initialize height
            this.autoResizeTextarea(textarea);
            
            // Ensure proper event handling
            if (!textarea.hasAttribute('data-initialized')) {
                // Input event for auto-resize
                textarea.addEventListener('input', () => this.autoResizeTextarea(textarea));
                
                // Focus event for white background
                textarea.addEventListener('focus', () => {
                    textarea.style.background = '#fff';
                    textarea.style.borderColor = '#007bff';
                    textarea.style.boxShadow = '0 0 0 2px rgba(0,123,255,0.25)';
                });
                
                // Blur event to reset background
                textarea.addEventListener('blur', () => {
                    textarea.style.background = 'transparent';
                    textarea.style.borderColor = 'transparent';
                    textarea.style.boxShadow = 'none';
                });
                
                // Prevent drag events when editing
                textarea.addEventListener('mousedown', (e) => {
                    e.stopPropagation();
                });
                
                textarea.addEventListener('click', (e) => {
                    e.stopPropagation();
                    textarea.focus();
                });
                
                textarea.setAttribute('data-initialized', 'true');
            }
        });
        
        // Also initialize title elements
        const titles = document.querySelectorAll('#drawflow .node-title');
        titles.forEach(title => {
            if (!title.hasAttribute('data-initialized')) {
                // Focus event for white background
                title.addEventListener('focus', () => {
                    title.style.background = '#fff';
                    title.style.borderColor = '#007bff';
                    title.style.boxShadow = '0 0 0 2px rgba(0,123,255,0.25)';
                });
                
                // Blur event to reset background
                title.addEventListener('blur', () => {
                    title.style.background = 'transparent';
                    title.style.borderColor = 'transparent';
                    title.style.boxShadow = 'none';
                });
                
                // Prevent drag events when editing
                title.addEventListener('mousedown', (e) => {
                    e.stopPropagation();
                });
                
                title.addEventListener('click', (e) => {
                    e.stopPropagation();
                    title.focus();
                });
                
                title.setAttribute('data-initialized', 'true');
            }
        });
    },
    
    // Comment connection functions (simplified for now)
    cleanupCommentConnectionsForNode: function(nodeId) {
        if (!window.commentConnections) return;
        
        // Remove connections where this node is the comment (source)
        if (window.commentConnections[nodeId]) {
            // Remove visual connections first
            window.commentConnections[nodeId].forEach(conn => {
                const connectionElement = document.getElementById(conn.connectionId);
                if (connectionElement) {
                    connectionElement.remove();
                }
            });
            
            delete window.commentConnections[nodeId];
        }
        
        // Remove connections where this node is the target
        Object.keys(window.commentConnections).forEach(commentNodeId => {
            const originalLength = window.commentConnections[commentNodeId].length;
            
            // Remove connections and their visual elements
            window.commentConnections[commentNodeId] = window.commentConnections[commentNodeId].filter(
                conn => {
                    if (conn.targetId === nodeId) {
                        // Remove visual connection
                        const connectionElement = document.getElementById(conn.connectionId);
                        if (connectionElement) {
                            connectionElement.remove();
                        }
                        return false; // Remove this connection
                    }
                    return true; // Keep this connection
                }
            );
            
            if (window.commentConnections[commentNodeId].length !== originalLength) {
                console.log(`🗑️ Removed comment connections targeting node ${nodeId}`);
            }
        });
        
        console.log(`💬 Cleaned up comment connections for node ${nodeId}`);
    },
    
    getConnectSubmenu: function(commentNodeId) {
        if (!this.editor || !this.editor.drawflow || !this.editor.drawflow.drawflow || !this.editor.drawflow.drawflow.Home) {
            return [];
        }
        
        const nodes = this.editor.drawflow.drawflow.Home.data || {};
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
                const isConnected = this.isCommentConnectedToNode(commentNodeId, nodeId);
                
                submenuItems.push({
                    icon: isConnected ? 'fas fa-unlink' : 'fas fa-link',
                    text: isConnected ? `Disconnect from ${nodeTitle}` : `Connect to ${nodeTitle}`,
                    action: () => this.toggleCommentConnection(commentNodeId, nodeId),
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
    },
    
    // Check if comment is already connected to a specific node
    isCommentConnectedToNode: function(commentNodeId, targetNodeId) {
        if (!window.commentConnections || !window.commentConnections[commentNodeId]) {
            return false;
        }
        
        const connection = window.commentConnections[commentNodeId].find(conn => conn.targetId === targetNodeId);
        return !!connection;
    },
    
    // Toggle connection between comment and target node
    toggleCommentConnection: function(commentNodeId, targetNodeId) {
        try {
            const isConnected = this.isCommentConnectedToNode(commentNodeId, targetNodeId);
            
            if (isConnected) {
                // Disconnect
                this.removeCommentConnection(commentNodeId, targetNodeId);
                console.log(`💬 Disconnected comment ${commentNodeId} from node ${targetNodeId}`);
            } else {
                // Connect
                this.createCommentConnection(commentNodeId, targetNodeId);
                console.log(`💬 Connected comment ${commentNodeId} to node ${targetNodeId}`);
            }
            
            this.autoSave();
        } catch (error) {
            console.log('❌ Error toggling comment connection: ' + error.message);
        }
    },
    
    // Create custom comment connection (center-to-center, dashed yellow line)
    createCommentConnection: function(commentNodeId, targetNodeId) {
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
        this.updateCommentConnections();
        
        console.log(`💬 Created comment connection from ${commentNodeId} to ${targetNodeId}`);
    },
    
    // Remove comment connection
    removeCommentConnection: function(commentNodeId, targetNodeId) {
        console.log('🔗 Removing comment connection:', commentNodeId, '->', targetNodeId);
        
        if (!window.commentConnections || !window.commentConnections[commentNodeId]) {
            console.log('⚠️ No connections found for comment node');
            return;
        }
        
        // Remove from data structure
        const connectionIndex = window.commentConnections[commentNodeId].findIndex(conn => conn.targetId === targetNodeId);
        if (connectionIndex === -1) {
            console.log('⚠️ Connection not found in data structure');
            return;
        }
        
        const connection = window.commentConnections[commentNodeId][connectionIndex];
        window.commentConnections[commentNodeId].splice(connectionIndex, 1);
        
        // Remove visual connection
        const connectionElement = document.getElementById(connection.connectionId);
        if (connectionElement) {
            connectionElement.remove();
            console.log('🗑️ Visual connection removed');
        }
        
        console.log(`💬 Removed comment connection from ${commentNodeId} to ${targetNodeId}`);
    },
    
    // Update all comment connections (call when nodes move)
    updateCommentConnections: function() {
        console.log('🔄 updateCommentConnections called');
        
        if (!window.commentConnections || Object.keys(window.commentConnections).length === 0) {
            console.log('ℹ️ No comment connections to update');
            return;
        }
        
        // Clean up invalid connections first
        this.cleanupInvalidCommentConnections();
        
        console.log('📊 Valid comment connections:', window.commentConnections);
        
        // Update each comment connection
        Object.keys(window.commentConnections).forEach(commentNodeId => {
            const connections = window.commentConnections[commentNodeId];
            if (!connections || !Array.isArray(connections) || connections.length === 0) return;
            
            console.log(`🔄 Updating connections for comment ${commentNodeId}:`, connections);
            
            connections.forEach(conn => {
                if (conn && conn.connectionId && conn.targetId) {
                    console.log(`🎨 Drawing connection ${conn.connectionId}`);
                    this.drawCommentConnection(commentNodeId, conn.targetId, conn.connectionId);
                }
            });
        });
        
        console.log('✅ updateCommentConnections completed');
    },
    
    // Draw visual comment connection
    drawCommentConnection: function(commentNodeId, targetNodeId, connectionId) {
        console.log('🎨 Drawing comment connection:', commentNodeId, '->', targetNodeId, 'ID:', connectionId);
        
        const commentElement = document.getElementById(`node-${commentNodeId}`);
        const targetElement = document.getElementById(`node-${targetNodeId}`);
        
        if (!commentElement) {
            console.log('⚠️ Comment element not found (skipping):', `node-${commentNodeId}`);
            return false;
        }
        
        if (!targetElement) {
            console.log('⚠️ Target element not found (skipping):', `node-${targetNodeId}`);
            return false;
        }
        
        console.log('✅ Both elements found');
        
        // Get the drawflow container - use the same parent as regular connections
        const drawflowContainer = document.querySelector('.drawflow');
        if (!drawflowContainer) {
            console.error('❌ Drawflow container not found');
            return false;
        }
        
        console.log('✅ Drawflow container found');
        
        // Remove existing connection if it exists
        const existingConnection = document.getElementById(connectionId);
        if (existingConnection) {
            existingConnection.remove();
            console.log('🗑️ Removed existing connection');
        }
        
        // Check if elements are still in the DOM and visible
        if (!commentElement.isConnected || !targetElement.isConnected) {
            console.log('⚠️ Elements not connected to DOM, skipping connection draw');
            return false;
        }
        
        try {
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
            svg.classList.add('comment-connection');
            svg.setAttribute('id', connectionId);
            
            // Position SVG to cover the entire drawflow area like regular connections
            svg.style.position = 'absolute';
            svg.style.top = '0';
            svg.style.left = '0';
            svg.style.width = '100%';
            svg.style.height = '100%';
            svg.style.pointerEvents = 'none';
            svg.style.overflow = 'visible';
            
            console.log('✅ SVG element created');
            
            // Create the path element
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.classList.add('comment-path');
            
            // Create a smooth curve from comment center to target center
            const pathData = `M ${commentCenter.x} ${commentCenter.y} Q ${(commentCenter.x + targetCenter.x) / 2} ${commentCenter.y - 50} ${targetCenter.x} ${targetCenter.y}`;
            path.setAttribute('d', pathData);
            
            console.log('📏 Path data:', pathData);
            
            // Style the path - dashed yellow line
            path.style.stroke = '#F6EC55';
            path.style.strokeWidth = '2px';
            path.style.strokeDasharray = '8,4';
            path.style.fill = 'none';
            path.style.pointerEvents = 'stroke';
            path.style.cursor = 'pointer';
            
            // Add context menu capability to the path
            path.addEventListener('contextmenu', (e) => {
                e.preventDefault();
                this.showCommentConnectionContextMenu(e, commentNodeId, targetNodeId);
            });
            
            svg.appendChild(path);
            drawflowContainer.appendChild(svg);
            
            console.log('✅ SVG and path added to DOM');
            console.log('🎨 Comment connection drawn successfully!');
            return true;
            
        } catch (error) {
            console.error('❌ Error drawing comment connection:', error);
            return false;
        }
    },
    
    // Show context menu for comment connections
    showCommentConnectionContextMenu: function(event, commentNodeId, targetNodeId) {
        this.hideContextMenu(); // Hide any existing menu
        
        const menu = document.createElement('div');
        menu.className = 'custom-context-menu';
        menu.id = 'contextMenu';
        
        // Get node titles for the menu
        const commentNode = this.editor.drawflow.drawflow.Home.data[commentNodeId];
        const targetNode = this.editor.drawflow.drawflow.Home.data[targetNodeId];
        
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
                    this.removeCommentConnection(commentNodeId, targetNodeId);
                    this.autoSave();
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
                this.hideContextMenu();
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
        
        console.log('📋 Comment connection context menu shown');
    },
    
    // Clean up invalid comment connections where nodes don't exist
    cleanupInvalidCommentConnections: function() {
        if (!window.commentConnections || !this.editor || !this.editor.drawflow || !this.editor.drawflow.drawflow || !this.editor.drawflow.drawflow.Home) {
            return;
        }
        
        const existingNodes = this.editor.drawflow.drawflow.Home.data || {};
        const validConnections = {};
        let cleanedCount = 0;
        
        Object.keys(window.commentConnections).forEach(commentNodeId => {
            const connections = window.commentConnections[commentNodeId];
            
            // Check if comment node exists
            if (!existingNodes[commentNodeId]) {
                console.log(`🗑️ Removing connections for non-existent comment node ${commentNodeId}`);
                cleanedCount++;
                return; // Skip this comment node entirely
            }
            
            // Filter connections to existing target nodes
            if (Array.isArray(connections)) {
                const validTargets = connections.filter(conn => {
                    if (!conn || !conn.targetId) return false;
                    const exists = !!existingNodes[conn.targetId];
                    if (!exists) {
                        console.log(`🗑️ Removing connection to non-existent target node ${conn.targetId}`);
                        cleanedCount++;
                    }
                    return exists;
                });
                
                if (validTargets.length > 0) {
                    validConnections[commentNodeId] = validTargets;
                }
            }
        });
        
        window.commentConnections = validConnections;
        
        if (cleanedCount > 0) {
            console.log(`🧹 Cleaned up ${cleanedCount} invalid comment connections`);
        }
    },
    
    // Remove grip handles from DOM (debounced for performance)
    removeGripHandles: function() {
        // Debounce to avoid excessive DOM operations
        if (this._gripRemovalTimeout) {
            return; // Already scheduled, skip this call
        }
        
        this._gripRemovalTimeout = setTimeout(() => {
            try {
                // Only remove grip handles if they exist
                const gripHandles = document.querySelectorAll('.node-drag-handle, .fa-grip');
                if (gripHandles.length > 0) {
                    gripHandles.forEach(handle => {
                        if (handle.closest('.node-drag-handle')) {
                            handle.closest('.node-drag-handle').remove();
                        } else {
                            handle.remove();
                        }
                    });
                    console.log('✅ Grip handles cleanup completed');
                }
            } catch (error) {
                console.log('⚠️ Error removing grip handles:', error.message);
            }
            this._gripRemovalTimeout = null;
        }, 200); // Debounce for 200ms
    },
    
    // Ensure proper cursor styling on all nodes
    setupCursorStyling: function() {
        try {
            const drawflowContainer = document.getElementById('drawflow');
            if (!drawflowContainer) return;
            
            // Apply move cursor to all nodes
            const nodes = drawflowContainer.querySelectorAll('.drawflow-node');
            nodes.forEach(node => {
                // Set move cursor on the node itself
                node.style.cursor = 'move';
                
                // Set move cursor on all child elements except text inputs
                const children = node.querySelectorAll('*');
                children.forEach(child => {
                    const isTextElement = child.tagName === 'INPUT' || 
                                          child.tagName === 'TEXTAREA' || 
                                          child.contentEditable === 'true' || 
                                          child.classList.contains('node-title') ||
                                          child.classList.contains('node-description');
                    
                    if (isTextElement) {
                        child.style.cursor = 'text';
                    } else if (child.tagName === 'BUTTON') {
                        child.style.cursor = 'pointer';
                    } else {
                        child.style.cursor = 'move';
                    }
                });
            });
            
            console.log('✅ Cursor styling applied to all nodes');
        } catch (error) {
            console.log('⚠️ Error applying cursor styling:', error.message);
        }
    },
    
    // Check if single connection rule should be enforced
    shouldEnforceSingleConnection: function() {
        // You can add configuration logic here if needed
        // For now, always enforce the rule
        return true;
    },
    
    // Validate if a new connection should be allowed based on single connection rule
    validateSingleConnection: function(connectionInfo) {
        const outputNodeId = connectionInfo.output_id;
        const inputNodeId = connectionInfo.input_id;
        const outputClass = connectionInfo.output_class;
        const inputClass = connectionInfo.input_class;
        
        // Use DrawFlow's internal connection data for more accurate checking
        const drawflowData = this.editor.drawflow.drawflow.Home.data;
        
        console.log(`🔍 Validating connection: ${outputNodeId}[${outputClass}] -> ${inputNodeId}[${inputClass}]`);
        
        // Check if output already has a connection (excluding the one just created)
        const outputNode = drawflowData[outputNodeId];
        if (outputNode && outputNode.outputs && outputNode.outputs[outputClass]) {
            const outputConnections = outputNode.outputs[outputClass].connections;
            console.log(`📊 Output ${outputClass} on node ${outputNodeId} has ${outputConnections?.length || 0} connections`);
            
            // Filter out the connection we just tried to create to get existing connections
            const existingConnections = outputConnections?.filter(conn => {
                return !(conn.node === inputNodeId && conn.output === inputClass);
            }) || [];
            
            if (existingConnections.length > 0) {
                console.log(`🚫 Output ${outputClass} on node ${outputNodeId} already has ${existingConnections.length} existing connection(s)`);
                return false;
            }
        }
        
        // Check if input already has a connection (excluding the one just created)
        const inputNode = drawflowData[inputNodeId];
        if (inputNode && inputNode.inputs && inputNode.inputs[inputClass]) {
            const inputConnections = inputNode.inputs[inputClass].connections;
            console.log(`📊 Input ${inputClass} on node ${inputNodeId} has ${inputConnections?.length || 0} connections`);
            
            // Filter out the connection we just tried to create to get existing connections
            const existingConnections = inputConnections?.filter(conn => {
                return !(conn.node === outputNodeId && conn.input === outputClass);
            }) || [];
            
            if (existingConnections.length > 0) {
                console.log(`🚫 Input ${inputClass} on node ${inputNodeId} already has ${existingConnections.length} existing connection(s)`);
                return false;
            }
        }
        
        console.log(`✅ Connection allowed: ${outputClass} -> ${inputClass}`);
        return true;
    },
    
    // Update visual indicators for connected inputs/outputs
    updateConnectionIndicators: function() {
        try {
            // Remove all existing connection indicators
            document.querySelectorAll('.drawflow .input, .drawflow .output').forEach(element => {
                element.classList.remove('connected', 'unavailable', 'available');
            });
            
            const data = this.editor.export();
            if (!data.drawflow || !data.drawflow.Home || !data.drawflow.Home.data) {
                return;
            }
            
            // Mark connected inputs and outputs
            Object.keys(data.drawflow.Home.data).forEach(nodeId => {
                const node = data.drawflow.Home.data[nodeId];
                
                // Check outputs
                if (node.outputs) {
                    Object.keys(node.outputs).forEach(outputKey => {
                        const output = node.outputs[outputKey];
                        if (output.connections && output.connections.length > 0) {
                            const outputElement = document.querySelector(`#node-${nodeId} .outputs .${outputKey}`);
                            if (outputElement) {
                                outputElement.classList.add('connected');
                            }
                        }
                    });
                }
                
                // Check inputs
                if (node.inputs) {
                    Object.keys(node.inputs).forEach(inputKey => {
                        const input = node.inputs[inputKey];
                        if (input.connections && input.connections.length > 0) {
                            const inputElement = document.querySelector(`#node-${nodeId} .inputs .${inputKey}`);
                            if (inputElement) {
                                inputElement.classList.add('connected');
                            }
                        }
                    });
                }
            });
            
            console.log('✅ Connection indicators updated');
        } catch (error) {
            console.log('⚠️ Error updating connection indicators:', error.message);
        }
    }
};

// Global functions for backwards compatibility
window.saveDiagram = function() { window.DataMapCore.saveDiagram(false); };
window.zoomIn = function() { window.DataMapCore.zoomIn(); };
window.zoomOut = function() { window.DataMapCore.zoomOut(); };
window.zoomReset = function() { window.DataMapCore.zoomReset(); };
window.clearDiagram = function() { window.DataMapCore.clearDiagram(); };
window.exportDiagram = function() { window.DataMapCore.exportDiagram(); };
window.updateAllConnectionPositions = function() { 
    if (window.DataMapCore && window.DataMapCore.updateAllConnectionPositions) {
        window.DataMapCore.updateAllConnectionPositions(); 
    }
};
