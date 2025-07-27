/**
 * Drawflow Editor Wrapper for AppTrack
 * Provides a simplified interface for the Drawflow library
 */
class DrawflowEditor {
    constructor(containerId) {
        this.containerId = containerId;
        this.editor = null;
        this.applicationId = null;
        this.autoSaveInterval = null;
        this.isInitialized = false;
    }

    /**
     * Initialize the Drawflow editor
     */
    init(applicationId = null) {
        if (!window.Drawflow) {
            console.error('Drawflow library not loaded');
            return false;
        }

        this.applicationId = applicationId;
        const container = document.getElementById(this.containerId);
        
        if (!container) {
            console.error('Container not found:', this.containerId);
            return false;
        }

        // Initialize Drawflow
        this.editor = new Drawflow(container);
        this.editor.reroute = true;
        this.editor.reroute_fix_curvature = true;
        this.editor.force_first_input = false;
        
        // Fix drag and drop coordinate issues
        this.editor.zoom_value = 1;
        this.editor.zoom_last_value = 1;
        
        // Start the editor
        this.editor.start();
        
        // Fix container positioning for accurate drag coordinates
        this.fixContainerPositioning(container);
        
        // Add event listeners
        this.setupEventListeners();
        
        // Load existing diagram if applicationId is provided
        if (this.applicationId) {
            this.loadDiagram();
        }
        
        this.isInitialized = true;
        this.startAutoSave();
        
        return true;
    }

    /**
     * Fix container positioning for accurate drag coordinates
     */
    fixContainerPositioning(container) {
        // Ensure container has proper positioning
        const computedStyle = window.getComputedStyle(container);
        if (computedStyle.position === 'static') {
            container.style.position = 'relative';
        }
        
        // Reset any transforms that might interfere
        container.style.transform = 'none';
        container.style.zoom = '1';
        
        // Ensure proper box-sizing
        container.style.boxSizing = 'border-box';
        
        // Fix for coordinate calculation
        if (this.editor) {
            // Override Drawflow's coordinate calculation if needed
            const originalGetMousePos = this.editor.getMousePosition;
            if (originalGetMousePos) {
                this.editor.getMousePosition = function(e) {
                    const rect = container.getBoundingClientRect();
                    return {
                        x: e.clientX - rect.left,
                        y: e.clientY - rect.top
                    };
                };
            }
        }
    }

    /**
     * Setup event listeners for the editor
     */
    setupEventListeners() {
        // Node events
        this.editor.on('nodeCreated', (id) => {
            console.log('Node created:', id);
            this.onDiagramChange();
        });

        this.editor.on('nodeRemoved', (id) => {
            console.log('Node removed:', id);
            this.onDiagramChange();
        });

        this.editor.on('nodeMoved', (id) => {
            console.log('Node moved:', id);
            this.onDiagramChange();
        });

        // Connection events
        this.editor.on('connectionCreated', (connection) => {
            console.log('Connection created:', connection);
            this.onDiagramChange();
        });

        this.editor.on('connectionRemoved', (connection) => {
            console.log('Connection removed:', connection);
            this.onDiagramChange();
        });
    }

    /**
     * Handle diagram changes
     */
    onDiagramChange() {
        // Debounce auto-save
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }
        
        this.saveTimeout = setTimeout(() => {
            if (this.applicationId) {
                this.saveDiagram();
            }
        }, 1000); // Save after 1 second of inactivity
    }

    /**
     * Add a new node to the diagram
     */
    addNode(name, inputs, outputs, posX, posY, className, data, html) {
        if (!this.isInitialized) {
            console.error('Editor not initialized');
            return null;
        }

        const nodeId = this.editor.addNode(name, inputs, outputs, posX, posY, className, data, html);
        return nodeId;
    }

    /**
     * Add a predefined component node
     */
    addComponentNode(type, posX = 100, posY = 100) {
        const components = {
            'database': {
                name: 'database',
                inputs: 0,
                outputs: 1,
                className: 'drawflow-node database-node',
                data: { type: 'database', title: 'Database' },
                html: `
                    <div class="node-header">
                        <i class="fas fa-database"></i>
                        <span>Database</span>
                    </div>
                    <div class="node-content">
                        <input type="text" placeholder="Database name" />
                    </div>
                `
            },
            'api': {
                name: 'api',
                inputs: 1,
                outputs: 1,
                className: 'drawflow-node api-node',
                data: { type: 'api', title: 'API' },
                html: `
                    <div class="node-header">
                        <i class="fas fa-cloud"></i>
                        <span>API</span>
                    </div>
                    <div class="node-content">
                        <input type="text" placeholder="API endpoint" />
                    </div>
                `
            },
            'frontend': {
                name: 'frontend',
                inputs: 1,
                outputs: 0,
                className: 'drawflow-node frontend-node',
                data: { type: 'frontend', title: 'Frontend' },
                html: `
                    <div class="node-header">
                        <i class="fas fa-desktop"></i>
                        <span>Frontend</span>
                    </div>
                    <div class="node-content">
                        <input type="text" placeholder="Component name" />
                    </div>
                `
            },
            'service': {
                name: 'service',
                inputs: 1,
                outputs: 1,
                className: 'drawflow-node service-node',
                data: { type: 'service', title: 'Service' },
                html: `
                    <div class="node-header">
                        <i class="fas fa-cogs"></i>
                        <span>Service</span>
                    </div>
                    <div class="node-content">
                        <input type="text" placeholder="Service name" />
                    </div>
                `
            }
        };

        const component = components[type];
        if (!component) {
            console.error('Unknown component type:', type);
            return null;
        }

        return this.addNode(
            component.name,
            component.inputs,
            component.outputs,
            posX,
            posY,
            component.className,
            component.data,
            component.html
        );
    }

    /**
     * Load diagram from server
     */
    async loadDiagram() {
        if (!this.applicationId) {
            console.error('No application ID set');
            return false;
        }

        try {
            const response = await fetch(`/public/api/load_drawflow_diagram.php?application_id=${this.applicationId}`);
            const data = await response.json();

            if (data.success && data.diagram_data) {
                this.editor.import(data.diagram_data);
                console.log('Diagram loaded successfully');
                return true;
            } else {
                console.log('No existing diagram found or failed to load');
                return false;
            }
        } catch (error) {
            console.error('Failed to load diagram:', error);
            return false;
        }
    }

    /**
     * Save diagram to server
     */
    async saveDiagram(notes = '') {
        if (!this.applicationId) {
            console.error('No application ID set');
            return false;
        }

        try {
            const diagramData = this.editor.export();
            
            const response = await fetch('/public/api/save_drawflow_diagram.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    application_id: this.applicationId,
                    diagram_data: diagramData,
                    notes: notes
                })
            });

            const data = await response.json();
            
            if (data.success) {
                console.log('Diagram saved successfully');
                this.showSaveStatus('Saved', 'success');
                return true;
            } else {
                console.error('Failed to save diagram:', data.message);
                this.showSaveStatus('Save failed', 'error');
                return false;
            }
        } catch (error) {
            console.error('Failed to save diagram:', error);
            this.showSaveStatus('Save error', 'error');
            return false;
        }
    }

    /**
     * Show save status indicator
     */
    showSaveStatus(message, type) {
        // Remove existing status indicators
        const existingStatus = document.querySelector('.save-status');
        if (existingStatus) {
            existingStatus.remove();
        }

        // Create new status indicator
        const status = document.createElement('div');
        status.className = `save-status save-status-${type}`;
        status.textContent = message;
        status.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 8px 16px;
            border-radius: 4px;
            color: white;
            font-size: 14px;
            z-index: 10000;
            ${type === 'success' ? 'background-color: #4CAF50;' : 'background-color: #f44336;'}
        `;

        document.body.appendChild(status);

        // Remove after 3 seconds
        setTimeout(() => {
            if (status.parentNode) {
                status.parentNode.removeChild(status);
            }
        }, 3000);
    }

    /**
     * Start auto-save functionality
     */
    startAutoSave() {
        // Auto-save every 30 seconds
        this.autoSaveInterval = setInterval(() => {
            if (this.applicationId) {
                this.saveDiagram();
            }
        }, 30000);
    }

    /**
     * Stop auto-save functionality
     */
    stopAutoSave() {
        if (this.autoSaveInterval) {
            clearInterval(this.autoSaveInterval);
            this.autoSaveInterval = null;
        }
    }

    /**
     * Clear the diagram
     */
    clear() {
        if (this.isInitialized) {
            this.editor.clear();
        }
    }

    /**
     * Zoom in
     */
    zoomIn() {
        if (this.isInitialized) {
            this.editor.zoom_in();
        }
    }

    /**
     * Zoom out
     */
    zoomOut() {
        if (this.isInitialized) {
            this.editor.zoom_out();
        }
    }

    /**
     * Reset zoom
     */
    zoomReset() {
        if (this.isInitialized) {
            this.editor.zoom_reset();
        }
    }

    /**
     * Destroy the editor
     */
    destroy() {
        this.stopAutoSave();
        
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }
        
        if (this.isInitialized && this.editor) {
            // Save before destroying
            if (this.applicationId) {
                this.saveDiagram();
            }
            
            this.editor = null;
        }
        
        this.isInitialized = false;
    }
}
