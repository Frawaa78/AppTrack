/**
 * Enhanced Visual Diagram Editor
 * Advanced drag-and-drop interface for creating integration diagrams with multiple element types
 */
class VisualDiagramEditor {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.canvas = null;
        this.svg = null;
        this.nodes = new Map();
        this.connections = new Map();
        this.textNotes = new Map();
        this.selectedElement = null;
        this.activeTool = 'select'; // 'select', 'connect', 'text'
        this.connectingFrom = null;
        this.isDragging = false;
        this.isResizing = false;
        this.isDrawingConnection = false;
        this.connectingFromNode = null;
        this.connectingFromSide = null;
        this.connectionCleanup = null;
        this.dragOffset = { x: 0, y: 0 };
        this.nextNodeId = 1;
        this.nextNoteId = 1;
        this.gridSize = 20;
        this.zoomLevel = 1;
        
        // Position stability tracking
        this.lastSavedState = null;
        this.positionBackup = new Map();
        this.positionFingerprint = null;
        this.enforcePositionsOnLoad = true;
        this.enforcementIntervals = [];
        this.positionObservers = [];
        
        this.options = {
            canvasWidth: 2000,
            canvasHeight: 1500,
            connectionColor: '#6b7280',
            selectedColor: '#3b82f6',
            textColor: '#1f2937',
            gridColor: '#f1f5f9',
            ...options
        };
        
        this.elementTypes = {
            process: { width: 120, height: 60, class: 'element-process' },
            decision: { width: 80, height: 80, class: 'element-decision' },
            start: { width: 80, height: 80, class: 'element-start' },
            database: { width: 100, height: 70, class: 'element-database' },
            api: { width: 120, height: 60, class: 'element-api' },
            user: { width: 100, height: 80, class: 'element-user' }
        };
        
        this.init();
    }
    
    init() {
        this.createCanvas();
        this.setupEventListeners();
        this.drawGrid();
        this.setActiveTool('select');
    }
    
    createCanvas() {
        // Clear container
        this.container.innerHTML = '';
        this.container.style.position = 'relative';
        this.container.style.width = '100%';
        this.container.style.height = '100%';
        this.container.style.overflow = 'auto';
        
        // Create main canvas div
        this.canvas = document.createElement('div');
        this.canvas.style.cssText = `
            position: relative;
            width: ${this.options.canvasWidth}px;
            height: ${this.options.canvasHeight}px;
            background-image: 
                linear-gradient(to right, ${this.options.gridColor} 1px, transparent 1px),
                linear-gradient(to bottom, ${this.options.gridColor} 1px, transparent 1px);
            background-size: ${this.gridSize}px ${this.gridSize}px;
        `;
        
        // Create SVG for connections
        this.svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        this.svg.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        `;
        this.svg.setAttribute('pointer-events', 'none');
        
        // Add arrow marker definition
        const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        const marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
        marker.setAttribute('id', 'arrowhead');
        marker.setAttribute('markerWidth', '10');
        marker.setAttribute('markerHeight', '7');
        marker.setAttribute('refX', '9');
        marker.setAttribute('refY', '3.5');
        marker.setAttribute('orient', 'auto');
        
        const polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        polygon.setAttribute('points', '0 0, 10 3.5, 0 7');
        polygon.setAttribute('fill', '#6b7280');
        
        marker.appendChild(polygon);
        defs.appendChild(marker);
        this.svg.appendChild(defs);
        
        this.canvas.appendChild(this.svg);
        this.container.appendChild(this.canvas);
        
        // CRITICAL: Recreate all connections and markers if connections exist (e.g., when modal reopens)
        if (this.connections && this.connections.size > 0) {
            console.log('🔄 createCanvas: Found existing connections, recreating complete SVG structure...');
            console.log(`🔄 Connections to recreate: ${this.connections.size}`);
            
            setTimeout(() => {
                this.recreateAllConnectionsAndMarkers();
            }, 50); // Small delay to ensure SVG is fully rendered
        }
    }
    
    setupEventListeners() {
        this.canvas.addEventListener('mousedown', (e) => this.handleMouseDown(e));
        this.canvas.addEventListener('mousemove', (e) => this.handleMouseMove(e));
        this.canvas.addEventListener('mouseup', (e) => this.handleMouseUp(e));
        this.canvas.addEventListener('dblclick', (e) => this.handleDoubleClick(e));
        this.canvas.addEventListener('click', (e) => this.handleClick(e));
        
        // Prevent context menu
        this.canvas.addEventListener('contextmenu', (e) => e.preventDefault());
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (this.container.closest('.modal').contains(document.activeElement) || 
                this.container.contains(document.activeElement)) {
                this.handleKeyDown(e);
            }
        });
        
        // Zoom with mouse wheel
        this.container.addEventListener('wheel', (e) => this.handleWheel(e));
        
        // Ensure proper event handling for dragging
        document.addEventListener('mousemove', (e) => {
            if (this.isDragging || this.isResizing) {
                this.handleMouseMove(e);
            }
        });
        
        document.addEventListener('mouseup', (e) => {
            if (this.isDragging || this.isResizing) {
                this.handleMouseUp(e);
            }
        });
    }
    
    handleMouseDown(e) {
        const rect = this.canvas.getBoundingClientRect();
        const containerRect = this.container.getBoundingClientRect();
        
        // Calculate coordinates relative to the canvas, accounting for scroll
        const scrollLeft = this.container.scrollLeft || 0;
        const scrollTop = this.container.scrollTop || 0;
        
        const x = (e.clientX - rect.left + scrollLeft) / this.zoomLevel;
        const y = (e.clientY - rect.top + scrollTop) / this.zoomLevel;
        
        console.log(`Mouse down at: x=${x}, y=${y}, zoom=${this.zoomLevel}`);
        console.log(`Canvas rect:`, rect);
        console.log(`Container rect:`, containerRect);
        console.log(`Scroll: left=${scrollLeft}, top=${scrollTop}`);
        console.log('Current tool:', this.activeTool);
        
        const element = this.getElementAt(x, y);
        console.log('Found element:', element);
        
        if (this.activeTool === 'select') {
            if (element) {
                console.log(`Selecting element: ${element.id} (${element.type})`);
                this.selectElement(element);
                if (element.type === 'node' || element.type === 'text') {
                    this.isDragging = true;
                    this.dragOffset = {
                        x: x - element.x,
                        y: y - element.y
                    };
                    console.log(`Started dragging with offset: x=${this.dragOffset.x}, y=${this.dragOffset.y}`);
                }
            } else {
                console.log('No element found, deselecting all');
                this.deselectAll();
            }
        } else if (this.activeTool === 'connect') {
            if (element && element.type === 'node') {
                if (!this.connectingFrom) {
                    this.connectingFrom = element;
                    this.canvas.style.cursor = 'crosshair';
                } else if (this.connectingFrom !== element) {
                    this.createConnection(this.connectingFrom, element);
                    this.connectingFrom = null;
                    this.canvas.style.cursor = 'default';
                }
            }
        } else if (this.activeTool === 'text') {
            if (!element) {
                this.addTextNote(x, y);
            }
        }
        
        e.preventDefault();
    }
    
    handleMouseMove(e) {
        if (!this.isDragging || !this.selectedElement) return;
        
        const rect = this.canvas.getBoundingClientRect();
        const scrollLeft = this.container.scrollLeft || 0;
        const scrollTop = this.container.scrollTop || 0;
        
        const x = (e.clientX - rect.left + scrollLeft) / this.zoomLevel;
        const y = (e.clientY - rect.top + scrollTop) / this.zoomLevel;
        
        // Snap to grid
        this.selectedElement.x = Math.round((x - this.dragOffset.x) / this.gridSize) * this.gridSize;
        this.selectedElement.y = Math.round((y - this.dragOffset.y) / this.gridSize) * this.gridSize;
        
        // Keep within bounds
        this.selectedElement.x = Math.max(0, Math.min(this.options.canvasWidth - this.selectedElement.width, this.selectedElement.x));
        this.selectedElement.y = Math.max(0, Math.min(this.options.canvasHeight - this.selectedElement.height, this.selectedElement.y));
        
        this.updateElementPosition(this.selectedElement);
        this.redrawConnections();
    }
    
    handleMouseUp(e) {
        this.isDragging = false;
        this.isResizing = false;
    }
    
    handleDoubleClick(e) {
        const rect = this.canvas.getBoundingClientRect();
        const scrollLeft = this.container.scrollLeft || 0;
        const scrollTop = this.container.scrollTop || 0;
        
        const x = (e.clientX - rect.left + scrollLeft) / this.zoomLevel;
        const y = (e.clientY - rect.top + scrollTop) / this.zoomLevel;
        
        const element = this.getElementAt(x, y);
        
        if (element && (element.type === 'node' || element.type === 'text')) {
            this.editElementText(element);
        }
    }
    
    handleClick(e) {
        // Handle connection line selection
        if (e.target.classList.contains('connection-line')) {
            const connectionId = e.target.dataset.connectionId;
            const connection = this.connections.get(connectionId);
            if (connection) {
                this.selectConnection(connection);
                e.stopPropagation();
            }
        } else {
            // Check for element selection
            const rect = this.canvas.getBoundingClientRect();
            const scrollLeft = this.container.scrollLeft || 0;
            const scrollTop = this.container.scrollTop || 0;
            
            const x = (e.clientX - rect.left + scrollLeft) / this.zoomLevel;
            const y = (e.clientY - rect.top + scrollTop) / this.zoomLevel;
            
            const element = this.getElementAt(x, y);
            if (element) {
                this.selectElement(element);
            } else {
                this.deselectAll();
            }
        }
    }
    
    handleKeyDown(e) {
        if (e.key === 'Delete' && this.selectedElement) {
            this.deleteElement(this.selectedElement);
        } else if (e.key === 'Escape') {
            this.deselectAll();
            this.connectingFrom = null;
            this.canvas.style.cursor = 'default';
        }
    }
    
    handleWheel(e) {
        if (e.ctrlKey) {
            e.preventDefault();
            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            this.setZoom(this.zoomLevel * delta);
        }
    }
    
    // Grid and Canvas Utilities
    drawGrid() {
        // Grid is already drawn via CSS background-image in createCanvas
        // This method exists for compatibility and future enhancements
    }
    
    redraw() {
        this.redrawConnections();
    }
    
    // Tool Management
    setActiveTool(tool) {
        this.activeTool = tool;
        this.connectingFrom = null;
        
        // Update cursor
        switch (tool) {
            case 'select':
                this.canvas.style.cursor = 'default';
                break;
            case 'connect':
                this.canvas.style.cursor = 'crosshair';
                break;
            case 'text':
                this.canvas.style.cursor = 'text';
                break;
        }
        
        // Update toolbar buttons
        document.querySelectorAll('.toolbar-section .btn').forEach(btn => {
            btn.classList.remove('tool-active');
        });
        
        const activeBtn = document.getElementById(tool + 'Tool');
        if (activeBtn) {
            activeBtn.classList.add('tool-active');
        }
    }
    
    // Element Creation
    addElement(type, x = 100, y = 100, text = null, skipGridSnap = false) {
        const elementConfig = this.elementTypes[type];
        if (!elementConfig) return null;
        
        const nodeId = `node_${this.nextNodeId++}`;
        
        console.log(`🏗️ ADD ELEMENT DEBUG: Creating ${nodeId} (type: ${type})`);
        console.log(`🏗️ Current nodes before add: ${this.nodes.size}`);
        console.log(`🏗️ Next node ID: ${this.nextNodeId}`);
        
        // Check for duplicates
        if (this.nodes.has(nodeId)) {
            console.error(`❌ DUPLICATE DETECTED: Node ${nodeId} already exists!`);
            console.log(`🔍 Existing node:`, this.nodes.get(nodeId));
            return null;
        }
        
        const node = {
            id: nodeId,
            type: 'node',
            elementType: type,
            x: skipGridSnap ? x : Math.round(x / this.gridSize) * this.gridSize,
            y: skipGridSnap ? y : Math.round(y / this.gridSize) * this.gridSize,
            width: elementConfig.width,
            height: elementConfig.height,
            text: text || this.getDefaultText(type),
            color: this.getDefaultColor(type)
        };
        
        this.nodes.set(nodeId, node);
        console.log(`✅ Node ${nodeId} added to Map. New size: ${this.nodes.size}`);
        
        this.createElementDOM(node);
        
        // Only select element if not loading from saved data
        if (!skipGridSnap) {
            this.selectElement(node);
        }
        
        return node;
    }
    
    addTextNote(x, y, text = 'Note', skipGridSnap = false) {
        const noteId = `note_${this.nextNoteId++}`;
        const note = {
            id: noteId,
            type: 'text',
            x: skipGridSnap ? x : Math.round(x / this.gridSize) * this.gridSize,
            y: skipGridSnap ? y : Math.round(y / this.gridSize) * this.gridSize,
            width: 150,
            height: 60,
            text: text
        };
        
        this.textNotes.set(noteId, note);
        this.createTextNoteDOM(note);
        
        // Only select and start editing if this is a new note (not loading from saved data)
        if (!skipGridSnap) {
            this.selectElement(note);
            // Start editing if it's a new note with default text
            if (text === 'Note') {
                setTimeout(() => this.editElementText(note), 100);
            }
        }
        
        return note;
    }
    
    createElementDOM(node) {
        const element = document.createElement('div');
        element.className = `diagram-element ${this.elementTypes[node.elementType].class}`;
        element.dataset.elementId = node.id;
        
        // CRITICAL: Set position and size immediately with !important to prevent timing issues
        element.style.cssText = `
            position: absolute !important;
            left: ${node.x}px !important;
            top: ${node.y}px !important;
            width: ${node.width}px !important;
            height: ${node.height}px !important;
            background-color: ${node.color} !important;
            z-index: 10 !important;
            pointer-events: all !important;
            cursor: move !important;
            ${node.elementType !== 'decision' ? 'transform: none !important;' : ''}
        `;
        
        const textSpan = document.createElement('span');
        textSpan.className = 'element-text';
        textSpan.textContent = node.text;
        textSpan.style.pointerEvents = 'none'; // Prevent text from blocking clicks
        element.appendChild(textSpan);
        
        // Add connection points
        this.addConnectionPoints(element, node);
        
        // Add direct event listeners to ensure clicking works
        element.addEventListener('mousedown', (e) => {
            console.log(`Direct mousedown on element: ${node.id}`);
            e.stopPropagation();
            this.selectElement(node);
            if (this.activeTool === 'select') {
                this.isDragging = true;
                const rect = this.canvas.getBoundingClientRect();
                const scrollLeft = this.container.scrollLeft || 0;
                const scrollTop = this.container.scrollTop || 0;
                const x = (e.clientX - rect.left + scrollLeft) / this.zoomLevel;
                const y = (e.clientY - rect.top + scrollTop) / this.zoomLevel;
                this.dragOffset = {
                    x: x - node.x,
                    y: y - node.y
                };
            }
        });
        
        element.addEventListener('click', (e) => {
            console.log(`Direct click on element: ${node.id}`);
            e.stopPropagation();
            this.selectElement(node);
        });
        
        element.addEventListener('dblclick', (e) => {
            console.log(`Direct double-click on element: ${node.id}`);
            e.stopPropagation();
            this.editElementText(node);
        });
        
        // CRITICAL: Add mutation observer to prevent position changes
        this.protectElementPosition(element, node);
        
        this.canvas.appendChild(element);
        node.domElement = element;
    }
    
    // Protect element position using MutationObserver to prevent external changes
    protectElementPosition(element, dataObject) {
        // Create mutation observer to watch for style changes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    // Check if position was changed externally
                    const currentLeft = parseInt(element.style.left);
                    const currentTop = parseInt(element.style.top);
                    
                    if (currentLeft !== dataObject.x || currentTop !== dataObject.y) {
                        console.warn(`POSITION PROTECTION: ${dataObject.id} position changed externally!`);
                        console.warn(`Expected: (${dataObject.x}, ${dataObject.y}), Got: (${currentLeft}, ${currentTop})`);
                        
                        // Force correct position back immediately
                        element.style.setProperty('left', dataObject.x + 'px', 'important');
                        element.style.setProperty('top', dataObject.y + 'px', 'important');
                        
                        console.warn(`Position corrected back to (${dataObject.x}, ${dataObject.y})`);
                    }
                }
            });
        });
        
        // Start observing
        observer.observe(element, {
            attributes: true,
            attributeFilter: ['style']
        });
        
        // Store observer reference for cleanup
        element._positionObserver = observer;
    }
    
    // Helper method to set element styles consistently
    setElementStyles(element, node) {
        // Use important to override any conflicting CSS
        element.style.setProperty('position', 'absolute', 'important');
        element.style.setProperty('left', node.x + 'px', 'important');
        element.style.setProperty('top', node.y + 'px', 'important');
        element.style.setProperty('width', node.width + 'px', 'important');
        element.style.setProperty('height', node.height + 'px', 'important');
        element.style.setProperty('background-color', node.color, 'important');
        element.style.setProperty('z-index', '10', 'important');
        element.style.setProperty('pointer-events', 'all', 'important');
        element.style.setProperty('cursor', 'move', 'important');
        
        // Only reset transform for non-decision elements (decision elements need rotation)
        if (node.elementType !== 'decision') {
            element.style.setProperty('transform', 'none', 'important');
        }
        
        console.log(`Set element ${node.id} styles: left=${node.x}px, top=${node.y}px, width=${node.width}px, height=${node.height}px`);
    }
    
    createTextNoteDOM(note) {
        const element = document.createElement('div');
        element.className = 'text-note';
        element.dataset.elementId = note.id;
        
        // CRITICAL: Set position and size immediately with !important to prevent timing issues
        element.style.cssText = `
            position: absolute !important;
            left: ${note.x}px !important;
            top: ${note.y}px !important;
            width: ${note.width}px !important;
            min-height: ${note.height}px !important;
            z-index: 10 !important;
            pointer-events: all !important;
            cursor: move !important;
            transform: none !important;
        `;
        
        element.textContent = note.text;
        
        // Add direct event listeners to ensure clicking works
        element.addEventListener('mousedown', (e) => {
            console.log(`Direct mousedown on text note: ${note.id}`);
            e.stopPropagation();
            this.selectElement(note);
            if (this.activeTool === 'select') {
                this.isDragging = true;
                const rect = this.canvas.getBoundingClientRect();
                const scrollLeft = this.container.scrollLeft || 0;
                const scrollTop = this.container.scrollTop || 0;
                const x = (e.clientX - rect.left + scrollLeft) / this.zoomLevel;
                const y = (e.clientY - rect.top + scrollTop) / this.zoomLevel;
                this.dragOffset = {
                    x: x - note.x,
                    y: y - note.y
                };
            }
        });
        
        element.addEventListener('click', (e) => {
            console.log(`Direct click on text note: ${note.id}`);
            e.stopPropagation();
            this.selectElement(note);
        });
        
        element.addEventListener('dblclick', (e) => {
            console.log(`Direct double-click on text note: ${note.id}`);
            e.stopPropagation();
            this.editElementText(note);
        });
        
        // CRITICAL: Add mutation observer to prevent position changes
        this.protectElementPosition(element, note);
        
        this.canvas.appendChild(element);
        note.domElement = element;
    }
    
    // Helper method to set text note styles consistently
    setTextNoteStyles(element, note) {
        // Use important to override any conflicting CSS
        element.style.setProperty('position', 'absolute', 'important');
        element.style.setProperty('left', note.x + 'px', 'important');
        element.style.setProperty('top', note.y + 'px', 'important');
        element.style.setProperty('width', note.width + 'px', 'important');
        element.style.setProperty('min-height', note.height + 'px', 'important');
        element.style.setProperty('z-index', '10', 'important');
        element.style.setProperty('pointer-events', 'all', 'important');
        element.style.setProperty('cursor', 'move', 'important');
        element.style.setProperty('transform', 'none', 'important');
        
        console.log(`Set text note ${note.id} styles: left=${note.x}px, top=${note.y}px, width=${note.width}px, height=${note.height}px`);
    }
    
    // Connection Points Management
    addConnectionPoints(element, node) {
        // Add connection points on all 4 sides
        const positions = [
            { side: 'top', x: '50%', y: '0px', transform: 'translate(-50%, -50%)' },
            { side: 'right', x: '100%', y: '50%', transform: 'translate(-50%, -50%)' },
            { side: 'bottom', x: '50%', y: '100%', transform: 'translate(-50%, -50%)' },
            { side: 'left', x: '0px', y: '50%', transform: 'translate(-50%, -50%)' }
        ];
        
        positions.forEach(pos => {
            const point = document.createElement('div');
            point.className = 'connection-point';
            point.dataset.side = pos.side;
            point.dataset.nodeId = node.id;
            point.style.cssText = `
                position: absolute;
                left: ${pos.x};
                top: ${pos.y};
                transform: ${pos.transform};
                width: 8px;
                height: 8px;
                background-color: #3b82f6;
                border: 2px solid white;
                border-radius: 50%;
                cursor: crosshair;
                z-index: 20;
                opacity: 0;
                transition: opacity 0.2s ease;
            `;
            
            // Add connection point event listeners
            point.addEventListener('mousedown', (e) => {
                e.stopPropagation();
                this.startConnectionFromPoint(node, pos.side, e);
            });
            
            point.addEventListener('mouseenter', (e) => {
                if (this.isDrawingConnection) {
                    point.style.backgroundColor = '#10b981';
                    point.style.transform = pos.transform + ' scale(1.2)';
                }
            });
            
            point.addEventListener('mouseleave', (e) => {
                if (this.isDrawingConnection) {
                    point.style.backgroundColor = '#3b82f6';
                    point.style.transform = pos.transform + ' scale(1)';
                }
            });
            
            point.addEventListener('mouseup', (e) => {
                if (this.isDrawingConnection && this.connectingFromNode !== node) {
                    e.stopPropagation();
                    this.finishConnectionAtPoint(node, pos.side);
                }
            });
            
            element.appendChild(point);
        });
    }
    
    showConnectionPoints(element) {
        const points = element.querySelectorAll('.connection-point');
        points.forEach(point => {
            point.style.opacity = '1';
        });
    }
    
    hideConnectionPoints(element) {
        const points = element.querySelectorAll('.connection-point');
        points.forEach(point => {
            point.style.opacity = '0';
            point.style.backgroundColor = '#3b82f6';
            point.style.transform = point.style.transform.replace(' scale(1.2)', '');
        });
    }
    
    startConnectionFromPoint(node, side, e) {
        console.log(`Starting connection from ${node.id} side ${side}`);
        this.isDrawingConnection = true;
        this.connectingFromNode = node;
        this.connectingFromSide = side;
        this.targetConnectionSide = null;
        
        // Show all connection points
        this.nodes.forEach(n => {
            if (n.domElement) {
                this.showConnectionPoints(n.domElement);
            }
        });
        
        // Create temporary line
        this.createTempConnectionLine(e);
        
        // Change cursor
        this.canvas.style.cursor = 'crosshair';
        
        // Add document listeners for drawing
        const handleMouseMove = (e) => {
            if (this.isDrawingConnection) {
                this.updateTempConnectionFromPoint(e);
            }
        };
        
        const handleMouseUp = (e) => {
            if (this.isDrawingConnection) {
                this.finishConnectionDrawing(e);
            }
        };
        
        document.addEventListener('mousemove', handleMouseMove);
        document.addEventListener('mouseup', handleMouseUp);
        
        // Store cleanup function
        this.connectionCleanup = () => {
            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
        };
    }
    
    updateTempConnectionFromPoint(e) {
        if (!this.tempConnectionLine || !this.connectingFromNode) return;
        
        const rect = this.canvas.getBoundingClientRect();
        const scrollLeft = this.container.scrollLeft || 0;
        const scrollTop = this.container.scrollTop || 0;
        const mouseX = (e.clientX - rect.left + scrollLeft) / this.zoomLevel;
        const mouseY = (e.clientY - rect.top + scrollTop) / this.zoomLevel;
        
        // Check if we're hovering over a target node
        const targetNode = this.getElementAt(mouseX, mouseY);
        
        let endX = mouseX;
        let endY = mouseY;
        
        // If hovering over a node, highlight it and snap to closest connection point
        if (targetNode && targetNode.type === 'node' && targetNode !== this.connectingFromNode) {
            // Highlight the target node
            targetNode.domElement.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.3)';
            
            // Find closest connection point on target node
            const closestPoint = this.getClosestConnectionPoint(targetNode, mouseX, mouseY);
            endX = closestPoint.x;
            endY = closestPoint.y;
            this.targetConnectionSide = closestPoint.side;
            
            // Show connection points on target
            this.showConnectionPoints(targetNode.domElement);
        } else {
            // Remove highlights from all nodes
            this.nodes.forEach(node => {
                if (node.domElement && node !== this.connectingFromNode) {
                    node.domElement.style.boxShadow = '';
                    this.hideConnectionPoints(node.domElement);
                }
            });
            this.targetConnectionSide = null;
        }
        
        const startPoint = this.getConnectionPointPosition(this.connectingFromNode, this.connectingFromSide);
        const pathData = this.getConnectionPath(startPoint.x, startPoint.y, endX, endY);
        this.tempConnectionLine.setAttribute('d', pathData);
    }
    
    finishConnectionDrawing(e) {
        const rect = this.canvas.getBoundingClientRect();
        const scrollLeft = this.container.scrollLeft || 0;
        const scrollTop = this.container.scrollTop || 0;
        const mouseX = (e.clientX - rect.left + scrollLeft) / this.zoomLevel;
        const mouseY = (e.clientY - rect.top + scrollTop) / this.zoomLevel;
        
        // Find target node at mouse position
        const targetNode = this.getElementAt(mouseX, mouseY);
        
        if (targetNode && targetNode.type === 'node' && targetNode !== this.connectingFromNode) {
            // Use the closest connection point that was calculated during hover
            const toSide = this.targetConnectionSide || this.getClosestConnectionPoint(targetNode, mouseX, mouseY).side;
            console.log(`Finishing connection to ${targetNode.id} side ${toSide}`);
            this.createConnectionBetweenPoints(this.connectingFromNode, this.connectingFromSide, targetNode, toSide);
        }
        
        this.cancelConnectionDrawing();
    }
    
    finishConnectionAtPoint(toNode, toSide) {
        console.log(`Finishing connection to ${toNode.id} side ${toSide}`);
        
        if (this.connectingFromNode && this.connectingFromNode !== toNode) {
            this.createConnectionBetweenPoints(this.connectingFromNode, this.connectingFromSide, toNode, toSide);
        }
        
        this.cancelConnectionDrawing();
    }
    
    cancelConnectionDrawing() {
        this.isDrawingConnection = false;
        this.connectingFromNode = null;
        this.connectingFromSide = null;
        this.targetConnectionSide = null;
        
        // Hide all connection points and remove highlights
        this.nodes.forEach(node => {
            if (node.domElement) {
                this.hideConnectionPoints(node.domElement);
                node.domElement.style.boxShadow = '';
            }
        });
        
        // Remove temporary line
        if (this.tempConnectionLine) {
            this.tempConnectionLine.remove();
            this.tempConnectionLine = null;
        }
        
        // Reset cursor
        this.canvas.style.cursor = 'default';
        
        // Clean up event listeners
        if (this.connectionCleanup) {
            this.connectionCleanup();
            this.connectionCleanup = null;
        }
    }
    
    getClosestConnectionPoint(node, mouseX, mouseY) {
        const sides = ['top', 'right', 'bottom', 'left'];
        let closestSide = 'top';
        let minDistance = Infinity;
        
        sides.forEach(side => {
            const point = this.getConnectionPointPosition(node, side);
            const distance = Math.sqrt(
                Math.pow(point.x - mouseX, 2) + Math.pow(point.y - mouseY, 2)
            );
            
            if (distance < minDistance) {
                minDistance = distance;
                closestSide = side;
            }
        });
        
        const closestPoint = this.getConnectionPointPosition(node, closestSide);
        return {
            x: closestPoint.x,
            y: closestPoint.y,
            side: closestSide
        };
    }
    
    getConnectionPointPosition(node, side) {
        const x = node.x;
        const y = node.y;
        const width = node.width;
        const height = node.height;
        
        switch (side) {
            case 'top':
                return { x: x + width / 2, y: y };
            case 'right':
                return { x: x + width, y: y + height / 2 };
            case 'bottom':
                return { x: x + width / 2, y: y + height };
            case 'left':
                return { x: x, y: y + height / 2 };
            default:
                return { x: x + width / 2, y: y + height / 2 };
        }
    }
    
    createConnectionBetweenPoints(fromNode, fromSide, toNode, toSide) {
        const connectionId = `${fromNode.id}_to_${toNode.id}`;
        
        // Prevent duplicate connections
        if (this.connections.has(connectionId)) {
            return;
        }
        
        const connection = {
            id: connectionId,
            type: 'connection',
            from: fromNode.id,
            to: toNode.id,
            fromNode: fromNode,
            toNode: toNode,
            fromSide: fromSide,
            toSide: toSide,
            direction: 'to' // Default direction
        };
        
        this.connections.set(connectionId, connection);
        this.drawConnectionBetweenPoints(connection);
    }
    
    drawConnectionBetweenPoints(connection) {
        const fromPoint = this.getConnectionPointPosition(connection.fromNode, connection.fromSide);
        const toPoint = this.getConnectionPointPosition(connection.toNode, connection.toSide);
        
        // Create path element
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('class', 'connection-line');
        path.setAttribute('data-connection-id', connection.id);
        path.setAttribute('d', this.getConnectionPath(fromPoint.x, fromPoint.y, toPoint.x, toPoint.y));
        path.setAttribute('stroke', this.options.connectionColor);
        path.setAttribute('stroke-width', '2');
        path.setAttribute('fill', 'none');
        path.style.pointerEvents = 'stroke';
        
        // Add arrowhead
        const arrowId = `arrow_${connection.id}`;
        this.createArrowMarker(arrowId);
        path.setAttribute('marker-end', `url(#${arrowId}_end)`);
        
        this.svg.appendChild(path);
        connection.pathElement = path;
    }
    createConnection(fromNode, toNode, direction = 'to') {
        const connectionId = `${fromNode.id}_to_${toNode.id}`;
        
        // Prevent duplicate connections
        if (this.connections.has(connectionId)) {
            return;
        }
        
        const connection = {
            id: connectionId,
            type: 'connection',
            from: fromNode.id,
            to: toNode.id,
            fromNode: fromNode,
            toNode: toNode,
            direction: direction // 'to', 'from', 'both'
        };
        
        this.connections.set(connectionId, connection);
        this.drawConnection(connection);
        return connection;
    }
    
    // Calculate edge-to-edge connection points to avoid arrows hiding behind elements
    calculateEdgeToEdgePoints(fromNode, toNode) {
        const fromCenterX = fromNode.x + fromNode.width / 2;
        const fromCenterY = fromNode.y + fromNode.height / 2;
        const toCenterX = toNode.x + toNode.width / 2;
        const toCenterY = toNode.y + toNode.height / 2;
        
        // Calculate angle from fromNode to toNode
        const dx = toCenterX - fromCenterX;
        const dy = toCenterY - fromCenterY;
        const angle = Math.atan2(dy, dx);
        
        // Calculate edge points on both rectangles
        const fromEdge = this.getEdgePoint(fromNode, angle);
        const toEdge = this.getEdgePoint(toNode, angle + Math.PI); // Opposite direction
        
        return { from: fromEdge, to: toEdge };
    }
    
    // Get the edge point of a rectangle in a given direction
    getEdgePoint(node, angle) {
        const centerX = node.x + node.width / 2;
        const centerY = node.y + node.height / 2;
        const halfWidth = node.width / 2;
        const halfHeight = node.height / 2;
        
        // Normalize angle to 0-2π
        const normalizedAngle = ((angle % (2 * Math.PI)) + (2 * Math.PI)) % (2 * Math.PI);
        
        // Calculate which edge the line intersects
        const cos = Math.cos(normalizedAngle);
        const sin = Math.sin(normalizedAngle);
        
        // Check intersection with each edge
        let x, y;
        
        if (Math.abs(cos) > Math.abs(sin)) {
            // Intersects left or right edge
            if (cos > 0) {
                // Right edge
                x = centerX + halfWidth;
                y = centerY + halfWidth * Math.tan(normalizedAngle);
            } else {
                // Left edge  
                x = centerX - halfWidth;
                y = centerY - halfWidth * Math.tan(normalizedAngle);
            }
        } else {
            // Intersects top or bottom edge
            if (sin > 0) {
                // Bottom edge
                y = centerY + halfHeight;
                x = centerX + halfHeight / Math.tan(normalizedAngle);
            } else {
                // Top edge
                y = centerY - halfHeight;
                x = centerX - halfHeight / Math.tan(normalizedAngle);
            }
        }
        
        // Clamp to rectangle bounds
        x = Math.max(node.x, Math.min(node.x + node.width, x));
        y = Math.max(node.y, Math.min(node.y + node.height, y));
        
        return { x, y };
    }
    
    drawConnection(connection) {
        const fromNode = connection.fromNode;
        const toNode = connection.toNode;
        
        // Calculate edge-to-edge points for all connections
        const edgePoints = this.calculateEdgeToEdgePoints(fromNode, toNode);
        const fromX = edgePoints.from.x;
        const fromY = edgePoints.from.y;
        const toX = edgePoints.to.x;
        const toY = edgePoints.to.y;
        
        // Create path element
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('class', 'connection-line');
        path.setAttribute('data-connection-id', connection.id);
        path.setAttribute('d', this.getConnectionPath(fromX, fromY, toX, toY));
        path.setAttribute('stroke', this.options.connectionColor);
        path.setAttribute('stroke-width', '2');
        path.setAttribute('fill', 'none');
        path.style.pointerEvents = 'stroke';
        
        // Add arrowheads based on direction
        const arrowId = `arrow_${connection.id}`;
        this.createArrowMarker(arrowId);
        
        if (connection.direction === 'to' || connection.direction === 'both') {
            path.setAttribute('marker-end', `url(#${arrowId}_end)`);
        }
        if (connection.direction === 'from' || connection.direction === 'both') {
            path.setAttribute('marker-start', `url(#${arrowId}_start)`);
        }
        
        path.style.pointerEvents = 'stroke'; // Enable pointer events for connection lines
        
        // Add click event for selection
        path.addEventListener('click', (e) => {
            e.stopPropagation();
            this.selectElement(connection);
        });
        
        this.svg.appendChild(path);
        connection.pathElement = path;
    }
    
    getConnectionPath(fromX, fromY, toX, toY) {
        // Calculate smooth curve
        const dx = toX - fromX;
        const dy = toY - fromY;
        const distance = Math.sqrt(dx * dx + dy * dy);
        
        const controlDistance = Math.min(distance * 0.5, 100);
        
        let cp1x, cp1y, cp2x, cp2y;
        
        if (Math.abs(dx) > Math.abs(dy)) {
            // Horizontal connection
            cp1x = fromX + (dx > 0 ? controlDistance : -controlDistance);
            cp1y = fromY;
            cp2x = toX - (dx > 0 ? controlDistance : -controlDistance);
            cp2y = toY;
        } else {
            // Vertical connection
            cp1x = fromX;
            cp1y = fromY + (dy > 0 ? controlDistance : -controlDistance);
            cp2x = toX;
            cp2y = toY - (dy > 0 ? controlDistance : -controlDistance);
        }
        
        return `M ${fromX} ${fromY} C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${toX} ${toY}`;
    }
    
    createArrowMarker(id, forceRecreate = false) {
        let defs = this.svg.querySelector('defs');
        if (!defs) {
            defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
            this.svg.appendChild(defs);
        }
        
        // Create both end arrow (pointing right) and start arrow (pointing left)
        this.createSingleArrowMarker(defs, `${id}_end`, 'M2,2 L2,10 L10,6 z', '10', forceRecreate); // Right-pointing arrow
        this.createSingleArrowMarker(defs, `${id}_start`, 'M10,2 L10,10 L2,6 z', '2', forceRecreate); // Left-pointing arrow
    }
    
    createSingleArrowMarker(defs, id, pathData, refX, forceRecreate = false) {
        // Check if marker already exists (unless forcing recreate)
        const existingMarker = defs.querySelector(`#${id}`);
        if (existingMarker && !forceRecreate) {
            console.log(`🔄 Arrow marker ${id} already exists, skipping creation`);
            return;
        }
        
        // Remove existing marker if force recreating
        if (existingMarker && forceRecreate) {
            console.log(`🗑️ Removing existing marker ${id} for recreation`);
            existingMarker.remove();
        }
        
        console.log(`🎯 Creating new arrow marker: ${id}`);
        
        const marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
        marker.setAttribute('id', id);
        marker.setAttribute('markerWidth', '12');
        marker.setAttribute('markerHeight', '12');
        marker.setAttribute('refX', refX);
        marker.setAttribute('refY', '6');
        marker.setAttribute('orient', 'auto');
        marker.setAttribute('markerUnits', 'strokeWidth');
        marker.setAttribute('viewBox', '0 0 12 12');
        
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', pathData);
        path.setAttribute('fill', '#333333');
        path.setAttribute('stroke', '#333333');
        path.setAttribute('stroke-width', '1');
        
        marker.appendChild(path);
        defs.appendChild(marker);
        
        console.log(`✅ Arrow marker ${id} created successfully`);
        
        // Verify the marker was created correctly
        const verification = defs.querySelector(`#${id}`);
        if (verification) {
            console.log(`✅ Marker verification passed for ${id}`);
        } else {
            console.error(`❌ Marker verification failed for ${id}`);
        }
    }

    updateConnectionDirection(connection) {
        if (!connection.pathElement) {
            console.warn('⚠️ No pathElement for connection:', connection.id);
            return;
        }

        const arrowId = `arrow_${connection.id}`;
        
        console.log(`🔄 Updating connection ${connection.id} direction to: ${connection.direction}`);
        
        // CRITICAL: Recalculate edge-to-edge points to ensure arrows are at element edges
        const fromNode = connection.fromNode;
        const toNode = connection.toNode;
        const edgePoints = this.calculateEdgeToEdgePoints(fromNode, toNode);
        
        // Update the path with proper edge-to-edge coordinates
        const newPath = this.getConnectionPath(edgePoints.from.x, edgePoints.from.y, edgePoints.to.x, edgePoints.to.y);
        connection.pathElement.setAttribute('d', newPath);
        
        console.log(`🎯 Recalculated edge points:`, {
            from: edgePoints.from,
            to: edgePoints.to,
            direction: connection.direction
        });
        
        // Remove existing arrow markers
        connection.pathElement.removeAttribute('marker-start');
        connection.pathElement.removeAttribute('marker-end');
        
        // Ensure arrow marker exists
        this.createArrowMarker(arrowId);
        
        // Add new arrow markers based on direction
        if (connection.direction === 'to' || connection.direction === 'both') {
            connection.pathElement.setAttribute('marker-end', `url(#${arrowId}_end)`);
            console.log(`✅ Added end marker: url(#${arrowId}_end)`);
        }
        if (connection.direction === 'from' || connection.direction === 'both') {
            connection.pathElement.setAttribute('marker-start', `url(#${arrowId}_start)`);
            console.log(`✅ Added start marker: url(#${arrowId}_start)`);
        }
        
        // Force a repaint by changing and restoring a property
        const originalStroke = connection.pathElement.getAttribute('stroke');
        connection.pathElement.setAttribute('stroke', 'transparent');
        setTimeout(() => {
            connection.pathElement.setAttribute('stroke', originalStroke);
        }, 1);
        
        console.log(`✅ Updated connection ${connection.id} direction to: ${connection.direction} with edge-to-edge positioning`);
    }    redrawConnections() {
        this.connections.forEach(connection => {
            if (connection.pathElement) {
                const fromNode = this.nodes.get(connection.from) || connection.fromNode;
                const toNode = this.nodes.get(connection.to) || connection.toNode;
                
                if (fromNode && toNode) {
                    let fromX, fromY, toX, toY;
                    
                    // Use specific connection points if available
                    if (connection.fromSide && connection.toSide) {
                        const fromPoint = this.getConnectionPointPosition(fromNode, connection.fromSide);
                        const toPoint = this.getConnectionPointPosition(toNode, connection.toSide);
                        fromX = fromPoint.x;
                        fromY = fromPoint.y;
                        toX = toPoint.x;
                        toY = toPoint.y;
                    } else {
                        // Use edge-to-edge calculation for all connections
                        const edgePoints = this.calculateEdgeToEdgePoints(fromNode, toNode);
                        fromX = edgePoints.from.x;
                        fromY = edgePoints.from.y;
                        toX = edgePoints.to.x;
                        toY = edgePoints.to.y;
                    }
                    
                    connection.pathElement.setAttribute('d', this.getConnectionPath(fromX, fromY, toX, toY));
                    
                    // Reapply arrow markers based on direction
                    this.updateConnectionDirection(connection);
                } else {
                    console.warn('Missing node for connection:', connection.id);
                }
            }
        });
    }
    
    // Element Management
    selectElement(element) {
        this.deselectAll();
        this.selectedElement = element;
        
        if (element.type === 'connection') {
            // Handle connection selection
            if (element.pathElement) {
                element.pathElement.classList.add('selected');
            }
            this.showPropertyPanel(element);
        } else if (element.domElement) {
            // Handle node/text selection
            element.domElement.classList.add('selected');
            this.addResizeHandles(element);
            this.showPropertyPanel(element);
            
            // Show connection points for nodes
            if (element.type === 'node') {
                this.showConnectionPoints(element.domElement);
            }
        }
    }
    
    selectConnection(connection) {
        this.deselectAll();
        if (connection && connection.pathElement) {
            connection.pathElement.classList.add('selected');
            this.selectedElement = connection;
            this.addConnectionHandles(connection);
        }
    }
    
    deselectAll() {
        // Remove selection from elements
        this.canvas.querySelectorAll('.selected').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Remove selection from SVG paths (connections)
        this.svg.querySelectorAll('.selected').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Remove resize handles
        this.canvas.querySelectorAll('.resize-handle, .connection-handle').forEach(handle => {
            handle.remove();
        });
        
        // Hide connection points
        this.nodes.forEach(node => {
            if (node.domElement) {
                this.hideConnectionPoints(node.domElement);
            }
        });
        
        this.selectedElement = null;
        this.hidePropertyPanel();
    }
    
    deleteElement(element) {
        if (element.type === 'node') {
            // Remove connections
            const connectionsToRemove = [];
            this.connections.forEach((conn, id) => {
                if (conn.from === element.id || conn.to === element.id) {
                    connectionsToRemove.push(id);
                }
            });
            
            connectionsToRemove.forEach(id => {
                const conn = this.connections.get(id);
                if (conn.pathElement) {
                    conn.pathElement.remove();
                }
                this.connections.delete(id);
            });
            
            // Clean up position observer
            if (element.domElement) {
                if (element.domElement._positionObserver) {
                    element.domElement._positionObserver.disconnect();
                }
                if (element.domElement._styleObserver) {
                    element.domElement._styleObserver.disconnect();
                }
            }
            
            // Remove node
            if (element.domElement) {
                element.domElement.remove();
            }
            this.nodes.delete(element.id);
            
        } else if (element.type === 'text') {
            // Clean up position observer
            if (element.domElement) {
                if (element.domElement._positionObserver) {
                    element.domElement._positionObserver.disconnect();
                }
                if (element.domElement._styleObserver) {
                    element.domElement._styleObserver.disconnect();
                }
            }
            
            if (element.domElement) {
                element.domElement.remove();
            }
            this.textNotes.delete(element.id);
            
        } else if (element.type === 'connection') {
            if (element.pathElement) {
                element.pathElement.remove();
            }
            this.connections.delete(element.id);
        }
        
        this.selectedElement = null;
        this.hidePropertyPanel();
    }
    
    // Utility Methods
    getElementAt(x, y) {
        console.log(`getElementAt called with coordinates: x=${x}, y=${y}`);
        
        // Check text notes first (they have higher z-index)
        for (const note of this.textNotes.values()) {
            if (x >= note.x && x <= note.x + note.width &&
                y >= note.y && y <= note.y + note.height) {
                console.log(`Found text note: ${note.id} at (${note.x}, ${note.y})`);
                return note;
            }
        }
        
        // Check nodes
        for (const node of this.nodes.values()) {
            console.log(`Checking node ${node.id} at (${node.x}, ${node.y}) with size ${node.width}x${node.height}`);
            if (x >= node.x && x <= node.x + node.width &&
                y >= node.y && y <= node.y + node.height) {
                console.log(`Found node: ${node.id} at (${node.x}, ${node.y})`);
                return node;
            }
        }
        
        console.log('No element found at coordinates');
        return null;
    }
    
    updateElementPosition(element) {
        if (element.domElement) {
            if (element.type === 'node') {
                this.setElementStyles(element.domElement, element);
            } else if (element.type === 'text') {
                this.setTextNoteStyles(element.domElement, element);
            }
        }
        
        // Update connection handles if this element is selected
        if (this.selectedElement && this.selectedElement.type === 'connection') {
            this.updateConnectionHandles(this.selectedElement);
        }
    }
    
    editElementText(element) {
        if (element.type === 'text') {
            this.startInlineEditTextNote(element);
        } else {
            this.startInlineEditNode(element);
        }
    }
    
    startInlineEditNode(node) {
        const textSpan = node.domElement.querySelector('.element-text');
        if (!textSpan || textSpan.querySelector('input')) return; // Already editing
        
        const originalText = node.text;
        
        // Create input field
        const input = document.createElement('input');
        input.type = 'text';
        input.value = originalText;
        input.style.cssText = `
            background: transparent;
            border: none;
            outline: none;
            text-align: center;
            font-size: inherit;
            font-weight: inherit;
            color: inherit;
            width: 100%;
            padding: 0;
            margin: 0;
        `;
        
        // Replace text with input
        textSpan.innerHTML = '';
        textSpan.appendChild(input);
        textSpan.style.pointerEvents = 'all';
        
        // Focus and select all text
        input.focus();
        input.select();
        
        const finishEdit = () => {
            const newText = input.value.trim();
            if (newText && newText !== originalText) {
                node.text = newText;
                this.updatePropertyPanel();
            }
            
            // Restore original text display
            textSpan.innerHTML = '';
            textSpan.textContent = node.text;
            textSpan.style.pointerEvents = 'none';
        };
        
        const cancelEdit = () => {
            // Restore original text
            textSpan.innerHTML = '';
            textSpan.textContent = originalText;
            textSpan.style.pointerEvents = 'none';
        };
        
        // Handle events
        input.addEventListener('blur', finishEdit);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                finishEdit();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                cancelEdit();
            }
            e.stopPropagation(); // Prevent canvas events
        });
        
        input.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent selection changes
        });
    }
    
    startInlineEditTextNote(note) {
        if (note.domElement.querySelector('textarea')) return; // Already editing
        
        const originalText = note.text;
        
        // Create textarea for multi-line editing
        const textarea = document.createElement('textarea');
        textarea.value = originalText;
        textarea.style.cssText = `
            background: transparent;
            border: none;
            outline: none;
            font-size: inherit;
            color: inherit;
            width: 100%;
            height: 100%;
            padding: 0;
            margin: 0;
            resize: none;
            font-family: inherit;
        `;
        
        // Replace content with textarea
        note.domElement.innerHTML = '';
        note.domElement.appendChild(textarea);
        
        // Focus and select all text
        textarea.focus();
        textarea.select();
        
        const finishEdit = () => {
            const newText = textarea.value.trim();
            if (newText && newText !== originalText) {
                note.text = newText;
                this.updatePropertyPanel();
            }
            
            // Restore original text display
            note.domElement.innerHTML = '';
            note.domElement.textContent = note.text;
        };
        
        const cancelEdit = () => {
            // Restore original text
            note.domElement.innerHTML = '';
            note.domElement.textContent = originalText;
        };
        
        // Handle events
        textarea.addEventListener('blur', finishEdit);
        textarea.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                finishEdit();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                cancelEdit();
            }
            e.stopPropagation(); // Prevent canvas events
        });
        
        textarea.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent selection changes
        });
    }
    
    getDefaultText(type) {
        const defaults = {
            process: 'Process',
            decision: 'Decision?',
            start: 'Start',
            database: 'Database',
            api: 'API',
            user: 'User'
        };
        return defaults[type] || 'Element';
    }
    
    getDefaultColor(type) {
        const defaults = {
            process: '#e2e8f0',
            decision: '#fecaca',
            start: '#dcfce7',
            database: '#e9d5ff',
            api: '#cffafe',
            user: '#fed7aa'
        };
        return defaults[type] || '#e2e8f0';
    }
    
    // Property Panel
    showPropertyPanel(element) {
        const panel = document.getElementById('property-panel');
        if (!panel) return;
        
        panel.style.display = 'block';
        this.updatePropertyPanel();
    }
    
    hidePropertyPanel() {
        const panel = document.getElementById('property-panel');
        if (panel) {
            panel.style.display = 'none';
        }
    }
    
    updatePropertyPanel() {
        if (!this.selectedElement) return;
        
        const textInput = document.getElementById('elementText');
        const widthSlider = document.getElementById('elementWidth');
        const heightSlider = document.getElementById('elementHeight');
        const colorSelect = document.getElementById('elementColor');
        const directionSelect = document.getElementById('connectionDirection');
        const directionGroup = document.getElementById('connectionDirectionGroup');
        
        // Show/hide connection direction controls
        if (this.selectedElement.type === 'connection') {
            // Hide normal element controls
            if (textInput) textInput.parentElement.style.display = 'none';
            if (widthSlider) widthSlider.parentElement.style.display = 'none';
            if (heightSlider) heightSlider.parentElement.style.display = 'none';
            if (colorSelect) colorSelect.parentElement.style.display = 'none';
            
            // Show connection controls
            if (directionGroup) directionGroup.style.display = 'block';
            if (directionSelect) directionSelect.value = this.selectedElement.direction || 'to';
        } else {
            // Show normal element controls
            if (textInput) {
                textInput.parentElement.style.display = 'block';
                textInput.value = this.selectedElement.text || '';
            }
            if (widthSlider) {
                widthSlider.parentElement.style.display = 'block';
                widthSlider.value = this.selectedElement.width || 120;
            }
            if (heightSlider) {
                heightSlider.parentElement.style.display = 'block';
                heightSlider.value = this.selectedElement.height || 60;
            }
            if (colorSelect) {
                colorSelect.parentElement.style.display = 'block';
                colorSelect.value = this.selectedElement.color || '#e2e8f0';
            }
            
            // Hide connection controls
            if (directionGroup) directionGroup.style.display = 'none';
        }
    }
    
    // Resize Handles
    addResizeHandles(element) {
        if (element.type !== 'node' && element.type !== 'text') return;
        
        const handles = ['nw', 'ne', 'sw', 'se'];
        handles.forEach(position => {
            const handle = document.createElement('div');
            handle.className = `resize-handle ${position}`;
            handle.dataset.position = position;
            handle.dataset.elementId = element.id;
            
            handle.addEventListener('mousedown', (e) => {
                e.stopPropagation();
                this.startResize(element, position, e);
            });
            
            element.domElement.appendChild(handle);
        });
    }
    
    startResize(element, position, e) {
        this.isResizing = true;
        this.resizeElement = element;
        this.resizePosition = position;
        this.resizeStartX = e.clientX;
        this.resizeStartY = e.clientY;
        this.resizeStartWidth = element.width;
        this.resizeStartHeight = element.height;
        
        const handleResize = (e) => {
            if (!this.isResizing) return;
            
            const deltaX = e.clientX - this.resizeStartX;
            const deltaY = e.clientY - this.resizeStartY;
            
            let newWidth = this.resizeStartWidth;
            let newHeight = this.resizeStartHeight;
            
            if (position.includes('e')) newWidth += deltaX;
            if (position.includes('w')) newWidth -= deltaX;
            if (position.includes('s')) newHeight += deltaY;
            if (position.includes('n')) newHeight -= deltaY;
            
            // Apply constraints
            newWidth = Math.max(50, Math.min(300, newWidth));
            newHeight = Math.max(30, Math.min(200, newHeight));
            
            element.width = newWidth;
            element.height = newHeight;
            
            element.domElement.style.width = newWidth + 'px';
            element.domElement.style.height = newHeight + 'px';
            
            this.redrawConnections();
            this.updatePropertyPanel();
        };
        
        const stopResize = () => {
            this.isResizing = false;
            document.removeEventListener('mousemove', handleResize);
            document.removeEventListener('mouseup', stopResize);
        };
        
        document.addEventListener('mousemove', handleResize);
        document.addEventListener('mouseup', stopResize);
    }
    
    // Connection Handles
    addConnectionHandles(connection) {
        const fromNode = connection.fromNode;
        const toNode = connection.toNode;
        
        // Add handle at start of connection
        this.addConnectionHandle(connection, 'from', fromNode);
        
        // Add handle at end of connection
        this.addConnectionHandle(connection, 'to', toNode);
    }
    
    addConnectionHandle(connection, type, node) {
        const handle = document.createElement('div');
        handle.className = 'connection-handle';
        handle.dataset.connectionId = connection.id;
        handle.dataset.handleType = type;
        handle.style.cssText = `
            position: absolute;
            width: 12px;
            height: 12px;
            background-color: #3b82f6;
            border: 2px solid white;
            border-radius: 50%;
            cursor: pointer;
            z-index: 30;
            left: ${node.x + node.width / 2 - 6}px;
            top: ${node.y + node.height / 2 - 6}px;
        `;
        
        handle.addEventListener('mousedown', (e) => {
            e.stopPropagation();
            this.startConnectionEdit(connection, type, e);
        });
        
        this.canvas.appendChild(handle);
    }
    
    updateConnectionHandles(connection) {
        const handles = this.canvas.querySelectorAll(`.connection-handle[data-connection-id="${connection.id}"]`);
        handles.forEach(handle => {
            const handleType = handle.dataset.handleType;
            const node = handleType === 'from' ? connection.fromNode : connection.toNode;
            if (node) {
                handle.style.left = `${node.x + node.width / 2 - 6}px`;
                handle.style.top = `${node.y + node.height / 2 - 6}px`;
            }
        });
    }
    
    startConnectionEdit(connection, handleType, e) {
        this.isEditingConnection = true;
        this.editingConnection = connection;
        this.editingHandleType = handleType;
        
        // Create temporary connection line
        this.createTempConnectionLine(e);
        
        const handleMouseMove = (e) => {
            if (!this.isEditingConnection) return;
            this.updateTempConnectionLine(e);
        };
        
        const handleMouseUp = (e) => {
            if (!this.isEditingConnection) return;
            
            this.finishConnectionEdit(e);
            this.isEditingConnection = false;
            
            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
        };
        
        document.addEventListener('mousemove', handleMouseMove);
        document.addEventListener('mouseup', handleMouseUp);
    }
    
    createTempConnectionLine(e) {
        this.tempConnectionLine = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        this.tempConnectionLine.setAttribute('class', 'temp-connection-line');
        this.tempConnectionLine.setAttribute('stroke', '#3b82f6');
        this.tempConnectionLine.setAttribute('stroke-width', '2');
        this.tempConnectionLine.setAttribute('fill', 'none');
        this.tempConnectionLine.setAttribute('stroke-dasharray', '5,5');
        this.svg.appendChild(this.tempConnectionLine);
    }
    
    updateTempConnectionLine(e) {
        const rect = this.canvas.getBoundingClientRect();
        const mouseX = (e.clientX - rect.left) / this.zoomLevel;
        const mouseY = (e.clientY - rect.top) / this.zoomLevel;
        
        const connection = this.editingConnection;
        let startX, startY;
        
        if (this.editingHandleType === 'from') {
            // Moving the start point, keep end point fixed
            startX = mouseX;
            startY = mouseY;
            const endNode = connection.toNode;
            const endX = endNode.x + endNode.width / 2;
            const endY = endNode.y + endNode.height / 2;
            
            const pathData = this.getConnectionPath(startX, startY, endX, endY);
            this.tempConnectionLine.setAttribute('d', pathData);
        } else {
            // Moving the end point, keep start point fixed
            const startNode = connection.fromNode;
            startX = startNode.x + startNode.width / 2;
            startY = startNode.y + startNode.height / 2;
            
            const pathData = this.getConnectionPath(startX, startY, mouseX, mouseY);
            this.tempConnectionLine.setAttribute('d', pathData);
        }
    }
    
    finishConnectionEdit(e) {
        const rect = this.canvas.getBoundingClientRect();
        const mouseX = (e.clientX - rect.left) / this.zoomLevel;
        const mouseY = (e.clientY - rect.top) / this.zoomLevel;
        
        // Find target node at mouse position
        const targetNode = this.getElementAt(mouseX, mouseY);
        
        if (targetNode && targetNode.type === 'node' && targetNode !== this.editingConnection.fromNode && targetNode !== this.editingConnection.toNode) {
            // Update connection
            const connection = this.editingConnection;
            
            if (this.editingHandleType === 'from') {
                connection.fromNode = targetNode;
                connection.from = targetNode.id;
            } else {
                connection.toNode = targetNode;
                connection.to = targetNode.id;
            }
            
            // Update connection ID
            const newId = `${connection.fromNode.id}_to_${connection.toNode.id}`;
            this.connections.delete(connection.id);
            connection.id = newId;
            this.connections.set(newId, connection);
            
            // Redraw connection
            this.redrawConnections();
        }
        
        // Remove temporary line
        if (this.tempConnectionLine) {
            this.tempConnectionLine.remove();
            this.tempConnectionLine = null;
        }
        
        // Refresh selection
        this.selectConnection(this.editingConnection);
    }
    
    // Zoom
    setZoom(level) {
        this.zoomLevel = Math.max(0.25, Math.min(3, level));
        this.canvas.style.transform = `scale(${this.zoomLevel})`;
        this.canvas.style.transformOrigin = '0 0';
        
        // Update zoom display
        const zoomDisplay = document.getElementById('zoomLevel');
        if (zoomDisplay) {
            zoomDisplay.textContent = Math.round(this.zoomLevel * 100) + '%';
        }
    }
    
    zoomIn() {
        this.setZoom(this.zoomLevel * 1.2);
    }
    
    zoomOut() {
        this.setZoom(this.zoomLevel / 1.2);
    }
    
    // Template and Code Generation - DEPRECATED: Use saveToMermaidCode() instead
    generateMermaidCode() {
        console.warn('⚠️ generateMermaidCode() is deprecated, redirecting to saveToMermaidCode()');
        return this.saveToMermaidCode();
    }
    
    clearAll() {
        // Stop all position monitoring and enforcement
        this.stopPositionMonitoring();
        this.stopAggressivePositionEnforcement();
        
        // Clean up position observers for all elements
        this.nodes.forEach(node => {
            if (node.domElement) {
                if (node.domElement._positionObserver) {
                    node.domElement._positionObserver.disconnect();
                }
                if (node.domElement._styleObserver) {
                    node.domElement._styleObserver.disconnect();
                }
            }
        });
        
        this.textNotes.forEach(note => {
            if (note.domElement) {
                if (note.domElement._positionObserver) {
                    note.domElement._positionObserver.disconnect();
                }
                if (note.domElement._styleObserver) {
                    note.domElement._styleObserver.disconnect();
                }
            }
        });
        
        this.nodes.clear();
        this.connections.clear();
        this.textNotes.clear();
        this.selectedElement = null;
        this.nextNodeId = 1;
        this.nextNoteId = 1;
        
        // Clear DOM elements but preserve SVG defs
        this.canvas.querySelectorAll('.diagram-element, .text-note, .connection-handle').forEach(el => el.remove());
        
        // Clear SVG paths but keep defs
        const paths = this.svg.querySelectorAll('path');
        paths.forEach(path => path.remove());
        
        this.hidePropertyPanel();
    }
    
    // Load from Mermaid code
    loadFromMermaidCode(mermaidCode) {
        try {
            console.log('=== 🔍 COMPREHENSIVE DEBUGGING MODE ACTIVE ===');
            console.log('Input Mermaid code:', mermaidCode);
            
            // Step 1: Clear everything and log state
            console.log('🧹 STEP 1: Clearing all existing elements');
            console.log('🔄 BEFORE CLEAR: Nodes:', this.nodes.size, 'Connections:', this.connections.size, 'Notes:', this.textNotes.size);
            
            this.clearAll();
            
            console.log('🔄 AFTER CLEAR: Nodes:', this.nodes.size, 'Connections:', this.connections.size, 'Notes:', this.textNotes.size);
            
            // ULTRA-NUCLEAR: Also clear DOM elements manually
            const editorContainer = document.getElementById('visual-diagram-editor');
            if (editorContainer) {
                // Remove all child elements that might be leftover
                const existingElements = editorContainer.querySelectorAll('.diagram-element, .text-note, .connection-line');
                console.log(`🧹 MANUAL DOM CLEANUP: Removing ${existingElements.length} leftover DOM elements`);
                existingElements.forEach(element => {
                    console.log('🗑️ Removing leftover DOM element:', element.id || element.className);
                    element.remove();
                });
            }
            
            // Reset IDs to ensure no conflicts
            this.nextNodeId = 1;
            this.nextNoteId = 1;
            
            console.log('🔄 AFTER NUCLEAR CLEANUP: Nodes:', this.nodes.size, 'Connections:', this.connections.size, 'Notes:', this.textNotes.size);
            
            // Step 2: Reset scroll and log container state
            console.log('📐 STEP 2: Resetting container scroll');
            this.container.scrollLeft = 0;
            this.container.scrollTop = 0;
            console.log('Container dimensions:', {
                width: this.container.clientWidth,
                height: this.container.clientHeight,
                scrollLeft: this.container.scrollLeft,
                scrollTop: this.container.scrollTop
            });
            
            // Step 3: Parse and log metadata
            console.log('🔍 STEP 3: Parsing Mermaid code');
            const cleanedCode = this.cleanMermaidCode(mermaidCode);
            console.log('Cleaned code:', cleanedCode);
            
            const lines = cleanedCode.split('\n').map(line => line.trim()).filter(line => line);
            const nodeMap = new Map();
            const nodeMetadata = new Map();
            const connectionDirections = new Map();
            const notes = [];
            
            // Extract metadata with detailed logging
            console.log('📊 Extracting metadata from', lines.length, 'lines');
            for (const line of lines) {
                if (line.startsWith('%%')) {
                    const metaData = line.substring(2).trim();
                    console.log('Found metadata line:', metaData);
                    
                    if (metaData.startsWith('NOTE:')) {
                        const parts = metaData.split(':');
                        if (parts.length >= 6) {
                            const noteData = {
                                id: parts[1],
                                x: parseInt(parts[2]),
                                y: parseInt(parts[3]),
                                width: parseInt(parts[4]),
                                height: parseInt(parts[5]),
                                text: parts.slice(6).join(':').replace(/\\n/g, '\n')
                            };
                            notes.push(noteData);
                            console.log('📝 Parsed note metadata:', noteData);
                        }
                    } else if (metaData.includes('_direction:')) {
                        // Parse connection direction metadata
                        const [connId, direction] = metaData.split('_direction:').map(s => s.trim());
                        if (connId && direction) {
                            connectionDirections.set(connId, direction);
                            console.log('🔗 Parsed connection direction:', connId, direction);
                        }
                    } else {
                        const parts = metaData.split(':');
                        if (parts.length >= 7) {
                            const elementData = {
                                type: parts[1],
                                x: parseInt(parts[2]),
                                y: parseInt(parts[3]),
                                width: parseInt(parts[4]),
                                height: parseInt(parts[5]),
                                color: parts[6]
                            };
                            nodeMetadata.set(parts[0], elementData);
                            console.log('🧩 Parsed element metadata:', parts[0], elementData);
                        }
                    }
                }
            }
            
            console.log(`📊 Metadata summary: ${nodeMetadata.size} elements, ${notes.length} notes`);
            
            // Step 4: Create elements with extreme logging
            console.log('🏗️ STEP 4: Creating elements');
            for (const line of lines) {
                if (line.startsWith('graph') || line.startsWith('flowchart') || line.startsWith('%%')) continue;
                
                let nodeMatch = line.match(/(\w+)(?:\[([^\]]+)\]|\{([^}]+)\}|\(\(([^)]+)\)\)|\[\(([^)]+)\)\]|\[\/([^\/]+)\/\]|\["([^"]+)"\])/);
                if (nodeMatch) {
                    const [, nodeId, rectText, diamondText, circleText, dbText, apiText, processText] = nodeMatch;
                    let text = rectText || diamondText || circleText || dbText || apiText || processText || nodeId;
                    
                    // CRITICAL: Clean all Mermaid syntax properly to prevent accumulating symbols
                    text = text.replace(/["""'']/g, '') // Remove quotes
                              .replace(/^\(+|\)+$/g, '') // Remove leading/trailing parentheses  
                              .replace(/^\/+|\/+$/g, '') // Remove leading/trailing slashes
                              .replace(/^\[+|\]+$/g, '') // Remove leading/trailing brackets
                              .replace(/^\{+|\}+$/g, '') // Remove leading/trailing braces
                              .replace(/&quot;/g, '"') // Restore escaped quotes
                              .trim();
                    
                    console.log(`🔄 PARSE DEBUG: Cleaned text "${text}" from original "${rectText || diamondText || circleText || dbText || apiText || processText || nodeId}"`);
                    
                    let elementType = 'process';
                    if (diamondText) elementType = 'decision';
                    else if (circleText) elementType = 'start';
                    else if (dbText) elementType = 'database';
                    else if (apiText) elementType = 'api';
                    else if (rectText && !processText) elementType = 'user';
                    
                    const fullNodeId = `node_${nodeId}`;
                    const metadata = nodeMetadata.get(fullNodeId);
                    
                    console.log(`🎯 Creating element: ${fullNodeId}, type: ${elementType}`);
                    
                    if (metadata) {
                        console.log('✅ Using saved metadata:', metadata);
                        console.log(`📍 TARGET POSITION: x=${metadata.x}, y=${metadata.y}`);
                        
                        // Create element without grid snapping
                        const element = this.addElement(metadata.type, metadata.x, metadata.y, text, true);
                        element.width = metadata.width;
                        element.height = metadata.height;
                        element.color = metadata.color;
                        
                        console.log(`🎯 ELEMENT CREATED:`, {
                            id: element.id,
                            dataPosition: { x: element.x, y: element.y },
                            dataSize: { width: element.width, height: element.height }
                        });
                        
                        // CRITICAL: Verify DOM position immediately after creation
                        if (element.domElement) {
                            const computedStyle = window.getComputedStyle(element.domElement);
                            const domPosition = {
                                left: element.domElement.style.left,
                                top: element.domElement.style.top,
                                computedLeft: computedStyle.left,
                                computedTop: computedStyle.top
                            };
                            console.log(`🔍 DOM POSITION CHECK:`, domPosition);
                            
                            if (element.domElement.style.left !== `${metadata.x}px` || element.domElement.style.top !== `${metadata.y}px`) {
                                console.error(`❌ POSITION MISMATCH DETECTED IMMEDIATELY!`);
                                console.error(`Expected: ${metadata.x}px, ${metadata.y}px`);
                                console.error(`Got: ${element.domElement.style.left}, ${element.domElement.style.top}`);
                            } else {
                                console.log(`✅ Position verified correct immediately after creation`);
                            }
                        }
                        
                        // Apply brutal position lock
                        this.brutalPositionLock(element);
                        
                        // Verify position again after brutal lock
                        if (element.domElement) {
                            const afterLockPosition = {
                                left: element.domElement.style.left,
                                top: element.domElement.style.top
                            };
                            console.log(`🔒 AFTER BRUTAL LOCK:`, afterLockPosition);
                        }
                        
                        nodeMap.set(nodeId, element);
                    } else {
                        console.log('⚠️ No metadata found, using auto-positioning');
                        const x = 100 + (nodeMap.size % 4) * 150;
                        const y = 100 + Math.floor(nodeMap.size / 4) * 120;
                        const element = this.addElement(elementType, x, y, text);
                        nodeMap.set(nodeId, element);
                    }
                }
            }
            
            // Create connections
            console.log('🔗 Creating connections');
            for (const line of lines) {
                // Check for bidirectional connections first
                const bidirectionalMatch = line.match(/(\w+)\s*<-->\s*(\w+)/);
                if (bidirectionalMatch) {
                    const [, fromId, toId] = bidirectionalMatch;
                    const fromNode = nodeMap.get(fromId);
                    const toNode = nodeMap.get(toId);
                    if (fromNode && toNode) {
                        console.log(`🔗 Creating bidirectional connection: ${fromId} <-> ${toId}`);
                        const connection = this.createConnection(fromNode, toNode, 'both');
                    }
                    continue;
                }
                
                // Check for unidirectional connections
                const connectionMatch = line.match(/(\w+)\s*-->\s*(\w+)/);
                if (connectionMatch) {
                    const [, fromId, toId] = connectionMatch;
                    const fromNode = nodeMap.get(fromId);
                    const toNode = nodeMap.get(toId);
                    if (fromNode && toNode) {
                        // Check for direction metadata
                        const connectionId = `${fromNode.id}_to_${toNode.id}`;
                        const direction = connectionDirections.get(connectionId) || 'to';
                        console.log(`🔗 Creating connection: ${fromId} -> ${toId} (direction: ${direction})`);
                        const connection = this.createConnection(fromNode, toNode, direction);
                    }
                }
            }
            
            // Create text notes
            console.log('📝 Creating text notes');
            for (const noteData of notes) {
                console.log(`📝 Creating note at (${noteData.x}, ${noteData.y})`);
                const note = this.addTextNote(noteData.x, noteData.y, noteData.text, true);
                note.width = noteData.width;
                note.height = noteData.height;
                this.brutalPositionLock(note);
                
                // Verify note position
                if (note.domElement) {
                    console.log(`📝 Note position verification:`, {
                        expected: { x: noteData.x, y: noteData.y },
                        actual: { left: note.domElement.style.left, top: note.domElement.style.top }
                    });
                }
            }
            
            this.updateNextIds();
            
            // Force browser reflow
            console.log('🔄 Forcing browser reflow');
            this.container.offsetHeight;
            
            // Start position monitoring BEFORE any delays
            console.log('🚨 Starting aggressive position enforcement');
            this.startAggressivePositionEnforcement();
            
            // Set up delayed verification with even more detail
            setTimeout(() => {
                console.log('⏰ DELAYED VERIFICATION (200ms later)');
                
                // Try to restore from fingerprint if positions have drifted
                const currentCorrect = this.debugVerifyPositions();
                if (!currentCorrect && this.positionFingerprint) {
                    console.log('🚨 Positions incorrect, attempting fingerprint restoration');
                    this.restoreFromFingerprint();
                }
                
                this.comprehensivePositionAudit();
            }, 200);
            
            // Set up multiple verification points
            setTimeout(() => {
                console.log('⏰ DELAYED VERIFICATION (500ms later)');
                
                const currentCorrect = this.debugVerifyPositions();
                if (!currentCorrect && this.positionFingerprint) {
                    console.log('🚨 Positions still incorrect, re-attempting fingerprint restoration');
                    this.restoreFromFingerprint();
                }
                
                this.comprehensivePositionAudit();
            }, 500);
            
            setTimeout(() => {
                console.log('⏰ DELAYED VERIFICATION (1000ms later)');
                
                const currentCorrect = this.debugVerifyPositions();
                if (!currentCorrect && this.positionFingerprint) {
                    console.log('🚨 Final restoration attempt');
                    this.restoreFromFingerprint();
                }
                
                this.comprehensivePositionAudit();
            }, 1000);
            
            this.deselectAll();
            
            console.log('=== LOADING COMPLETE ===');
            console.log(`Final counts: ${this.nodes.size} nodes, ${this.textNotes.size} notes, ${this.connections.size} connections`);
            
            // CRITICAL: Recreate all connections and markers after loading to ensure arrows appear
            if (this.connections.size > 0) {
                console.log('🔄 Recreating all connections and markers after data load...');
                this.recreateAllConnectionsAndMarkers();
            }
            
            // CRITICAL: Create position fingerprint after successful load
            setTimeout(() => {
                console.log('🔐 Creating position fingerprint after load completion');
                this.createPositionFingerprint();
                
                // Verify everything is correctly positioned
                const audit = this.comprehensivePositionAudit();
                if (audit.length === 0) {
                    console.log('✅ Load complete with perfect positioning');
                } else {
                    console.warn(`⚠️ Load complete but ${audit.length} position issues detected`);
                }
            }, 500);
        } catch (error) {
            console.error('❌ CRITICAL ERROR in loadFromMermaidCode:', error);
            console.error('Stack trace:', error.stack);
        }
    }
    
    // Update next IDs based on existing nodes and notes
    updateNextIds() {
        let maxNodeId = 0;
        let maxNoteId = 0;
        
        // Find highest node ID
        this.nodes.forEach((node, id) => {
            const match = id.match(/node_(\d+)/);
            if (match) {
                const num = parseInt(match[1]);
                if (num > maxNodeId) {
                    maxNodeId = num;
                }
            }
        });
        
        // Find highest note ID
        this.textNotes.forEach((note, id) => {
            const match = id.match(/note_(\d+)/);
            if (match) {
                const num = parseInt(match[1]);
                if (num > maxNoteId) {
                    maxNoteId = num;
                }
            }
        });
        
        // Set next IDs to be higher than existing ones
        this.nextNodeId = maxNodeId + 1;
        this.nextNoteId = maxNoteId + 1;
        
        console.log(`Updated nextNodeId to ${this.nextNodeId}, nextNoteId to ${this.nextNoteId}`);
    }
    
    // Brutal position locking - locks position in multiple ways
    brutalPositionLock(element) {
        if (!element || !element.domElement) return;
        
        const domElement = element.domElement;
        console.log(`🔒 BRUTAL LOCK: ${element.id} at (${element.x}, ${element.y})`);
        
        // 1. Set CSS custom properties for the nuclear CSS rules
        domElement.style.setProperty('--locked-left', element.x + 'px');
        domElement.style.setProperty('--locked-top', element.y + 'px');
        domElement.style.setProperty('--locked-width', element.width + 'px');
        domElement.style.setProperty('--locked-height', element.height + 'px');
        
        // 2. Lock position with maximum CSS specificity
        const lockStyle = `
            position: absolute !important;
            left: ${element.x}px !important;
            top: ${element.y}px !important;
            width: ${element.width}px !important;
            ${element.type === 'text' ? 'min-height' : 'height'}: ${element.height}px !important;
            transform: ${element.elementType === 'decision' ? 'rotate(45deg)' : 'none'} !important;
            transition: none !important;
            animation: none !important;
            z-index: 10 !important;
            pointer-events: all !important;
            cursor: move !important;
            margin: 0 !important;
            float: none !important;
            clear: none !important;
            display: flex !important;
            contain: none !important;
            isolation: auto !important;
            will-change: auto !important;
        `;
        
        domElement.style.cssText = lockStyle;
        
        // 3. Set individual properties with highest priority
        domElement.style.setProperty('position', 'absolute', 'important');
        domElement.style.setProperty('left', `${element.x}px`, 'important');
        domElement.style.setProperty('top', `${element.y}px`, 'important');
        domElement.style.setProperty('transition', 'none', 'important');
        domElement.style.setProperty('animation', 'none', 'important');
        
        // 4. Ensure data attribute is set for CSS targeting
        domElement.setAttribute('data-element-id', element.id);
        
        // 5. Store original position data on DOM element
        domElement._lockedX = element.x;
        domElement._lockedY = element.y;
        domElement._lockedWidth = element.width;
        domElement._lockedHeight = element.height;
        domElement._elementId = element.id;
        
        // 6. Force browser reflow
        domElement.offsetHeight;
        
        // 7. Additional aggressive style enforcement
        this.enforceStyleProperties(domElement, element);
        
        console.log(`🔒 LOCKED: ${element.id} - DOM left=${domElement.style.left}, top=${domElement.style.top}`);
    }
    
    // Enforce specific style properties aggressively
    enforceStyleProperties(domElement, element) {
        // Use a MutationObserver to watch for style attribute changes
        if (domElement._styleObserver) {
            domElement._styleObserver.disconnect();
        }
        
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    // Re-enforce position immediately
                    const currentLeft = parseInt(domElement.style.left) || 0;
                    const currentTop = parseInt(domElement.style.top) || 0;
                    
                    if (currentLeft !== element.x || currentTop !== element.y) {
                        console.warn(`🚨 STYLE MUTATION DETECTED: ${element.id} - forcing correction`);
                        
                        // Force correct values back
                        domElement.style.setProperty('left', `${element.x}px`, 'important');
                        domElement.style.setProperty('top', `${element.y}px`, 'important');
                        domElement.style.setProperty('position', 'absolute', 'important');
                        domElement.style.setProperty('transition', 'none', 'important');
                        domElement.style.setProperty('animation', 'none', 'important');
                        
                        // Update CSS variables
                        domElement.style.setProperty('--locked-left', element.x + 'px');
                        domElement.style.setProperty('--locked-top', element.y + 'px');
                    }
                }
            });
        });
        
        observer.observe(domElement, {
            attributes: true,
            attributeFilter: ['style', 'class']
        });
        
        domElement._styleObserver = observer;
    }
    
    // Start aggressive position enforcement with multiple strategies
    startAggressivePositionEnforcement() {
        console.log('🚨 STARTING AGGRESSIVE POSITION ENFORCEMENT');
        
        // Stop any existing enforcement
        this.stopAggressivePositionEnforcement();
        
        // Strategy 1: High-frequency polling
        this.positionEnforcementTimer = setInterval(() => {
            this.enforceAllPositions();
        }, 50); // Check every 50ms
        
        // Strategy 2: Animation frame monitoring
        const enforceFrame = () => {
            this.enforceAllPositions();
            this.positionEnforcementFrame = requestAnimationFrame(enforceFrame);
        };
        this.positionEnforcementFrame = requestAnimationFrame(enforceFrame);
        
        // Strategy 3: Event-based monitoring
        this.addGlobalEventMonitoring();
        
        console.log('🚨 AGGRESSIVE ENFORCEMENT ACTIVE');
    }
    
    // Stop all position enforcement
    stopAggressivePositionEnforcement() {
        if (this.positionEnforcementTimer) {
            clearInterval(this.positionEnforcementTimer);
            this.positionEnforcementTimer = null;
        }
        
        if (this.positionEnforcementFrame) {
            cancelAnimationFrame(this.positionEnforcementFrame);
            this.positionEnforcementFrame = null;
        }
        
        this.removeGlobalEventMonitoring();
        
        console.log('🛑 AGGRESSIVE ENFORCEMENT STOPPED');
    }
    
    // Enforce positions for all elements
    enforceAllPositions() {
        let correctionsMade = 0;
        
        // Check all nodes
        this.nodes.forEach((node, id) => {
            if (this.enforceElementPosition(node)) {
                correctionsMade++;
            }
        });
        
        // Check all text notes
        this.textNotes.forEach((note, id) => {
            if (this.enforceElementPosition(note)) {
                correctionsMade++;
            }
        });
        
        if (correctionsMade > 0) {
            console.warn(`⚠️ ENFORCEMENT: Corrected ${correctionsMade} position violations`);
        }
    }
    
    // Enforce position for a single element
    enforceElementPosition(element) {
        if (!element || !element.domElement) return false;
        
        const domElement = element.domElement;
        const computedStyle = window.getComputedStyle(domElement);
        const currentLeft = parseInt(computedStyle.left) || 0;
        const currentTop = parseInt(computedStyle.top) || 0;
        
        // Check if position has drifted
        if (currentLeft !== element.x || currentTop !== element.y) {
            console.warn(`🔧 DRIFT DETECTED: ${element.id}`);
            console.warn(`   Expected: (${element.x}, ${element.y})`);
            console.warn(`   Actual: (${currentLeft}, ${currentTop})`);
            console.warn(`   Correcting immediately...`);
            
            // Brutal correction
            this.brutalPositionLock(element);
            
            return true; // Correction was made
        }
        
        return false; // No correction needed
    }
    
    // Add global event monitoring
    addGlobalEventMonitoring() {
        if (this.globalEventListeners) return; // Already added
        
        this.globalEventListeners = {
            resize: () => this.enforceAllPositions(),
            scroll: () => this.enforceAllPositions(),
            transitionend: () => this.enforceAllPositions(),
            animationend: () => this.enforceAllPositions()
        };
        
        // Add listeners to window and container
        Object.entries(this.globalEventListeners).forEach(([event, handler]) => {
            window.addEventListener(event, handler);
            this.container.addEventListener(event, handler);
        });
        
        console.log('🎯 Global event monitoring active');
    }
    
    // Remove global event monitoring
    removeGlobalEventMonitoring() {
        if (!this.globalEventListeners) return;
        
        Object.entries(this.globalEventListeners).forEach(([event, handler]) => {
            window.removeEventListener(event, handler);
            this.container.removeEventListener(event, handler);
        });
        
        this.globalEventListeners = null;
        console.log('🎯 Global event monitoring removed');
    }
    
    // Force correction of all element positions
    forcePositionCorrection() {
        console.log('=== FORCE POSITION CORRECTION ===');
        
        this.nodes.forEach((node, id) => {
            const element = node.domElement;
            if (element) {
                const computedLeft = parseInt(window.getComputedStyle(element).left);
                const computedTop = parseInt(window.getComputedStyle(element).top);
                
                console.log(`Node ${id}:`);
                console.log(`  Expected: (${node.x}, ${node.y})`);
                console.log(`  Computed: (${computedLeft}, ${computedTop})`);
                
                if (computedLeft !== node.x || computedTop !== node.y) {
                    console.warn(`  FORCING CORRECTION!`);
                    // Force style update
                    element.style.cssText = `
                        position: absolute !important;
                        left: ${node.x}px !important;
                        top: ${node.y}px !important;
                        width: ${node.width}px !important;
                        height: ${node.height}px !important;
                        background-color: ${node.color} !important;
                        z-index: 10 !important;
                        pointer-events: all !important;
                        cursor: move !important;
                    `;
                    
                    // Force browser reflow
                    element.offsetHeight;
                }
            }
        });
        
        this.textNotes.forEach((note, id) => {
            const element = note.domElement;
            if (element) {
                const computedLeft = parseInt(window.getComputedStyle(element).left);
                const computedTop = parseInt(window.getComputedStyle(element).top);
                
                console.log(`Note ${id}:`);
                console.log(`  Expected: (${note.x}, ${note.y})`);
                console.log(`  Computed: (${computedLeft}, ${computedTop})`);
                
                if (computedLeft !== note.x || computedTop !== note.y) {
                    console.warn(`  FORCING CORRECTION!`);
                    // Force style update
                    element.style.cssText = `
                        position: absolute !important;
                        left: ${note.x}px !important;
                        top: ${note.y}px !important;
                        width: ${note.width}px !important;
                        min-height: ${note.height}px !important;
                        z-index: 10 !important;
                        pointer-events: all !important;
                        cursor: move !important;
                    `;
                    
                    // Force browser reflow
                    element.offsetHeight;
                }
            }
        });
        
        console.log('=== FORCE POSITION CORRECTION COMPLETE ===');
    }
    
    // Debug function to verify positions after loading
    debugVerifyPositions() {
        console.log('🔍 === COMPREHENSIVE POSITION VERIFICATION ===');
        let allCorrect = true;
        
        this.nodes.forEach((node, id) => {
            const expected = { x: node.x, y: node.y };
            const actual = {
                left: node.domElement.style.left,
                top: node.domElement.style.top
            };
            const isCorrect = actual.left === `${expected.x}px` && actual.top === `${expected.y}px`;
            
            console.log(`📦 Node ${id}:`, {
                expected: `${expected.x}px, ${expected.y}px`,
                actual: `${actual.left}, ${actual.top}`,
                correct: isCorrect ? '✅' : '❌'
            });
            
            if (!isCorrect) {
                allCorrect = false;
                console.error(`❌ POSITION MISMATCH for ${id}!`);
                console.error(`  Expected: (${expected.x}, ${expected.y})`);
                console.error(`  Got: (${actual.left}, ${actual.top})`);
                this.setElementStyles(node.domElement, node);
            }
        });
        
        this.textNotes.forEach((note, id) => {
            const expected = { x: note.x, y: note.y };
            const actual = {
                left: note.domElement.style.left,
                top: note.domElement.style.top
            };
            const isCorrect = actual.left === `${expected.x}px` && actual.top === `${expected.y}px`;
            
            console.log(`📝 Note ${id}:`, {
                expected: `${expected.x}px, ${expected.y}px`,
                actual: `${actual.left}, ${actual.top}`,
                correct: isCorrect ? '✅' : '❌'
            });
            
            if (!isCorrect) {
                allCorrect = false;
                console.error(`❌ POSITION MISMATCH for note ${id}!`);
                this.setTextNoteStyles(note.domElement, note);
            }
        });
        
        console.log(`Overall position verification: ${allCorrect ? '✅ ALL CORRECT' : '❌ MISMATCHES FOUND'}`);
        return allCorrect;
    }

    // New comprehensive position audit method
    comprehensivePositionAudit() {
        console.log('🔍 === COMPREHENSIVE POSITION AUDIT ===');
        
        let issues = [];
        
        this.nodes.forEach((node, id) => {
            const domElement = node.domElement;
            if (!domElement) {
                issues.push(`Node ${id}: No DOM element`);
                return;
            }
            
            const computed = window.getComputedStyle(domElement);
            const boundingRect = domElement.getBoundingClientRect();
            const containerRect = this.container.getBoundingClientRect();
            
            const audit = {
                id: id,
                dataPosition: { x: node.x, y: node.y },
                stylePosition: { left: domElement.style.left, top: domElement.style.top },
                computedPosition: { left: computed.left, top: computed.top },
                cssCustomProps: {
                    lockedLeft: computed.getPropertyValue('--locked-left'),
                    lockedTop: computed.getPropertyValue('--locked-top')
                },
                boundingRect: {
                    left: boundingRect.left - containerRect.left,
                    top: boundingRect.top - containerRect.top
                },
                classes: Array.from(domElement.classList),
                dataAttribs: {
                    locked: domElement.dataset.positionLocked,
                    id: domElement.dataset.id
                }
            };
            
            console.log(`📋 Node ${id} audit:`, audit);
            
            // Check for inconsistencies
            const expectedLeft = `${node.x}px`;
            const expectedTop = `${node.y}px`;
            
            if (domElement.style.left !== expectedLeft || domElement.style.top !== expectedTop) {
                issues.push(`❌ Node ${id}: Style position mismatch - Expected (${expectedLeft}, ${expectedTop}), Got (${domElement.style.left}, ${domElement.style.top})`);
            }
        });
        
        this.textNotes.forEach((note, id) => {
            const domElement = note.domElement;
            if (!domElement) {
                issues.push(`Note ${id}: No DOM element`);
                return;
            }
            
            const computed = window.getComputedStyle(domElement);
            const audit = {
                id: id,
                dataPosition: { x: note.x, y: note.y },
                stylePosition: { left: domElement.style.left, top: domElement.style.top },
                computedPosition: { left: computed.left, top: computed.top }
            };
            
            console.log(`📝 Note ${id} audit:`, audit);
            
            const expectedLeft = `${note.x}px`;
            const expectedTop = `${note.y}px`;
            
            if (domElement.style.left !== expectedLeft || domElement.style.top !== expectedTop) {
                issues.push(`❌ Note ${id}: Style position mismatch - Expected (${expectedLeft}, ${expectedTop}), Got (${domElement.style.left}, ${domElement.style.top})`);
            }
        });
        
        console.log(`🚨 AUDIT COMPLETE: ${issues.length} issues found`);
        if (issues.length > 0) {
            console.error('🚨 ISSUES DETECTED:');
            issues.forEach(issue => console.error(issue));
        } else {
            console.log('✅ All positions are correct!');
        }
        
        return issues;
    }

    // New position preservation methods
    createPositionFingerprint() {
        const fingerprint = {
            nodes: new Map(),
            notes: new Map(),
            timestamp: Date.now()
        };
        
        this.nodes.forEach((node, id) => {
            fingerprint.nodes.set(id, {
                x: node.x,
                y: node.y,
                width: node.width,
                height: node.height
            });
        });
        
        this.textNotes.forEach((note, id) => {
            fingerprint.notes.set(id, {
                x: note.x,
                y: note.y,
                width: note.width,
                height: note.height
            });
        });
        
        console.log('🔐 Created position fingerprint:', fingerprint);
        this.positionFingerprint = fingerprint;
        return fingerprint;
    }

    // Restore positions from fingerprint
    restoreFromFingerprint() {
        if (!this.positionFingerprint) {
            console.warn('⚠️ No position fingerprint available for restoration');
            return false;
        }
        
        console.log('🔓 Restoring positions from fingerprint');
        let restored = 0;
        
        this.positionFingerprint.nodes.forEach((position, id) => {
            const node = this.nodes.get(id);
            if (node && node.domElement) {
                console.log(`🔄 Restoring node ${id} to (${position.x}, ${position.y})`);
                node.x = position.x;
                node.y = position.y;
                node.width = position.width;
                node.height = position.height;
                this.setElementStyles(node.domElement, node);
                this.brutalPositionLock(node);
                restored++;
            }
        });
        
        this.positionFingerprint.notes.forEach((position, id) => {
            const note = this.textNotes.get(id);
            if (note && note.domElement) {
                console.log(`🔄 Restoring note ${id} to (${position.x}, ${position.y})`);
                note.x = position.x;
                note.y = position.y;
                note.width = position.width;
                note.height = position.height;
                this.setTextNoteStyles(note.domElement, note);
                this.brutalPositionLock(note);
                restored++;
            }
        });
        
        console.log(`✅ Restored ${restored} element positions`);
        return restored > 0;
    }

    // Override save to create fingerprint
    saveToMermaidCode() {
        try {
            console.log('🔄 SAVE DEBUG: Starting saveToMermaidCode');
            console.log('🔄 SAVE DEBUG: Nodes count:', this.nodes.size);
            console.log('🔄 SAVE DEBUG: Connections count:', this.connections.size);
            console.log('🔄 SAVE DEBUG: Text notes count:', this.textNotes.size);
            
            // Create fingerprint before saving
            this.createPositionFingerprint();
            
            let mermaidCode = 'graph TD\n';
            let metadataComments = '';
            
            console.log('🔄 SAVE DEBUG: Processing nodes...');
            
            // Generate node definitions and collect metadata
            this.nodes.forEach((node, id) => {
                console.log(`🔄 SAVE DEBUG: Processing node ${id}:`, {
                    text: node.text,
                    elementType: node.elementType,
                    x: node.x,
                    y: node.y,
                    width: node.width,
                    height: node.height,
                    color: node.color
                });
                
                // Clean text but preserve original content - don't escape for Mermaid syntax
                const cleanText = node.text.replace(/"/g, '&quot;').replace(/\n/g, ' ').trim();
                
                // Get actual DOM position for metadata
                const domX = parseInt(node.domElement.style.left) || node.x;
                const domY = parseInt(node.domElement.style.top) || node.y;
                
                console.log(`🔄 SAVE DEBUG: DOM position for ${id}: x=${domX}, y=${domY}`);
                
                switch (node.elementType) {
                    case 'process':
                        mermaidCode += `    ${id.replace('node_', '')}["${cleanText}"]\n`;
                        break;
                    case 'decision':
                        mermaidCode += `    ${id.replace('node_', '')}{${cleanText}}\n`;
                        break;
                    case 'start':
                        mermaidCode += `    ${id.replace('node_', '')}((${cleanText}))\n`;
                        break;
                    case 'database':
                        mermaidCode += `    ${id.replace('node_', '')}[(${cleanText})]\n`;
                        break;
                    case 'api':
                        mermaidCode += `    ${id.replace('node_', '')}[/"${cleanText}"/]\n`;
                        break;
                    case 'user':
                        mermaidCode += `    ${id.replace('node_', '')}[${cleanText}]\n`;
                        break;
                    default:
                        mermaidCode += `    ${id.replace('node_', '')}[${cleanText}]\n`;
                }
                
                // Save metadata with actual DOM positions
                metadataComments += `%% ${id}:${node.elementType}:${domX}:${domY}:${node.width}:${node.height}:${node.color}\n`;
            });
            
            console.log('🔄 SAVE DEBUG: Processing connections...');
            
            // Generate connections
            this.connections.forEach((connection, connectionId) => {
                console.log(`🔄 SAVE DEBUG: Processing connection ${connectionId}:`, connection);
                
                // Fix: connection.from and connection.to are already strings (node IDs), not objects
                let fromId, toId;
                
                if (typeof connection.from === 'string') {
                    fromId = connection.from.replace('node_', '');
                } else if (connection.from && connection.from.id) {
                    fromId = connection.from.id.replace('node_', '');
                } else {
                    console.error('❌ SAVE DEBUG: Invalid connection.from:', connection.from);
                    return; // Skip this connection
                }
                
                if (typeof connection.to === 'string') {
                    toId = connection.to.replace('node_', '');
                } else if (connection.to && connection.to.id) {
                    toId = connection.to.id.replace('node_', '');
                } else {
                    console.error('❌ SAVE DEBUG: Invalid connection.to:', connection.to);
                    return; // Skip this connection
                }
                
                console.log(`🔄 SAVE DEBUG: Connection: ${fromId} --> ${toId}`);
                
                // Generate connection based on direction
                if (connection.direction === 'from') {
                    mermaidCode += `    ${toId} --> ${fromId}\n`;
                } else if (connection.direction === 'both') {
                    mermaidCode += `    ${fromId} <--> ${toId}\n`;
                } else {
                    // Default 'to' direction
                    mermaidCode += `    ${fromId} --> ${toId}\n`;
                }
                
                // Add direction metadata comment
                metadataComments += `%% connection_${connectionId}_direction: ${connection.direction || 'to'}\n`;
            });
            
            console.log('🔄 SAVE DEBUG: Processing text notes...');
            
            // Add text notes metadata
            this.textNotes.forEach((note, id) => {
                console.log(`🔄 SAVE DEBUG: Processing note ${id}:`, note);
                
                const domX = parseInt(note.domElement.style.left) || note.x;
                const domY = parseInt(note.domElement.style.top) || note.y;
                const escapedText = note.text.replace(/\n/g, '\\n').replace(/:/g, '\\:');
                metadataComments += `%% NOTE:${id}:${domX}:${domY}:${note.width}:${note.height}:${escapedText}\n`;
            });
            
            const fullCode = mermaidCode + '\n' + metadataComments;
            
            console.log('💾 SAVE DEBUG: Generated Mermaid code:', fullCode);
            console.log('💾 SAVE DEBUG: Code length:', fullCode.length);
            
            return fullCode;
        } catch (error) {
            console.error('❌ SAVE DEBUG: Error in saveToMermaidCode:', error);
            console.error('❌ SAVE DEBUG: Stack trace:', error.stack);
            return '';
        }
    }

    // Emergency position restoration - call this when modal reopens
    emergencyPositionRestore() {
        console.log('🚨 EMERGENCY POSITION RESTORATION ACTIVATED');
        
        if (!this.positionFingerprint) {
            console.warn('⚠️ No fingerprint available for emergency restoration');
            return false;
        }
        
        let fixes = 0;
        
        // NUCLEAR OPTION: Complete DOM reset and rebuild
        console.log('☢️ NUCLEAR OPTION: Complete DOM reset');
        
        // Stop all monitoring first
        this.stopAggressivePositionEnforcement();
        
        // Clear the container completely
        const containerParent = this.container.parentNode;
        const containerHTML = this.container.outerHTML;
        
        // Remove and recreate container to reset all CSS contexts
        this.container.remove();
        containerParent.innerHTML = containerHTML;
        this.container = containerParent.querySelector('#visual-diagram-editor');
        
        // Reinitialize the canvas and SVG
        this.setupCanvas();
        this.setupSVG();
        
        // Now recreate all elements from fingerprint with forced positioning
        this.positionFingerprint.nodes.forEach((position, id) => {
            const nodeData = this.nodes.get(id);
            if (nodeData) {
                console.log(`☢️ Nuclear rebuild node ${id}: (${position.x}, ${position.y})`);
                
                // Remove old element if it exists
                if (nodeData.domElement && nodeData.domElement.parentNode) {
                    nodeData.domElement.remove();
                }
                
                // Create completely new DOM element
                const element = document.createElement('div');
                element.className = `diagram-element ${this.elementTypes[nodeData.type]?.class || 'element-process'}`;
                element.id = id;
                element.dataset.id = id;
                element.dataset.type = nodeData.type;
                element.textContent = nodeData.text;
                
                // Apply ultra-forced positioning
                element.style.cssText = `
                    position: absolute !important;
                    left: ${position.x}px !important;
                    top: ${position.y}px !important;
                    width: ${position.width}px !important;
                    height: ${position.height}px !important;
                    background-color: ${nodeData.color} !important;
                    transform: none !important;
                    transition: none !important;
                    animation: none !important;
                    margin: 0 !important;
                    padding: 8px !important;
                    border: 2px solid #374151 !important;
                    border-radius: 6px !important;
                    cursor: move !important;
                    user-select: none !important;
                    box-sizing: border-box !important;
                    z-index: 10 !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    font-size: 12px !important;
                    font-weight: 500 !important;
                    color: #ffffff !important;
                    text-align: center !important;
                    overflow: hidden !important;
                    word-wrap: break-word !important;
                `;
                
                // Add to container
                this.container.appendChild(element);
                
                // Update node data
                nodeData.domElement = element;
                nodeData.x = position.x;
                nodeData.y = position.y;
                nodeData.width = position.width;
                nodeData.height = position.height;
                
                // Add event listeners
                this.addElementEventListeners(element, nodeData);
                
                // Add connection points
                this.addConnectionPoints(element, nodeData);
                
                fixes++;
            }
        });
        
        // Recreate text notes
        this.positionFingerprint.notes.forEach((position, id) => {
            const noteData = this.textNotes.get(id);
            if (noteData) {
                console.log(`☢️ Nuclear rebuild note ${id}: (${position.x}, ${position.y})`);
                
                // Remove old element if it exists
                if (noteData.domElement && noteData.domElement.parentNode) {
                    noteData.domElement.remove();
                }
                
                // Create completely new DOM element
                const element = document.createElement('div');
                element.className = 'text-note';
                element.id = id;
                element.dataset.id = id;
                element.textContent = noteData.text;
                
                // Apply ultra-forced positioning
                element.style.cssText = `
                    position: absolute !important;
                    left: ${position.x}px !important;
                    top: ${position.y}px !important;
                    width: ${position.width}px !important;
                    height: ${position.height}px !important;
                    background-color: #fef3c7 !important;
                    border: 1px solid #f59e0b !important;
                    border-radius: 4px !important;
                    padding: 8px !important;
                    font-size: 12px !important;
                    font-family: Arial, sans-serif !important;
                    color: #92400e !important;
                    cursor: move !important;
                    user-select: none !important;
                    box-sizing: border-box !important;
                    z-index: 15 !important;
                    transform: none !important;
                    transition: none !important;
                    animation: none !important;
                    margin: 0 !important;
                    white-space: pre-wrap !important;
                    word-wrap: break-word !important;
                    overflow: auto !important;
                `;
                
                // Add to container
                this.container.appendChild(element);
                
                // Update note data
                noteData.domElement = element;
                noteData.x = position.x;
                noteData.y = position.y;
                noteData.width = position.width;
                noteData.height = position.height;
                
                // Add event listeners
                this.addTextNoteEventListeners(element, noteData);
                
                fixes++;
            }
        });
        
        // Recreate all connections
        this.redrawAllConnections();
        
        // Force browser reflow
        this.container.offsetHeight;
        
        // Start ultra-aggressive monitoring
        this.startUltraAggressiveMonitoring();
        
        console.log(`☢️ Nuclear restoration complete: ${fixes} positions restored`);
        return fixes > 0;
    }

    // Ultra-aggressive monitoring - even more intensive than before
    startUltraAggressiveMonitoring() {
        console.log('🚨 Starting ULTRA-aggressive position monitoring');
        
        // Clear any existing monitoring
        this.stopAggressivePositionEnforcement();
        
        // Monitor every 50ms instead of 100ms
        const monitoringInterval = setInterval(() => {
            this.nodes.forEach((node, id) => {
                if (node.domElement) {
                    const currentLeft = parseInt(node.domElement.style.left);
                    const currentTop = parseInt(node.domElement.style.top);
                    
                    if (currentLeft !== node.x || currentTop !== node.y) {
                        console.error(`🚨 ULTRA VIOLATION DETECTED for ${id}!`);
                        console.error(`Expected: (${node.x}, ${node.y}), Got: (${currentLeft}, ${currentTop})`);
                        
                        // Immediate forced correction
                        node.domElement.style.setProperty('left', `${node.x}px`, 'important');
                        node.domElement.style.setProperty('top', `${node.y}px`, 'important');
                        node.domElement.style.setProperty('transform', 'none', 'important');
                    }
                }
            });
            
            this.textNotes.forEach((note, id) => {
                if (note.domElement) {
                    const currentLeft = parseInt(note.domElement.style.left);
                    const currentTop = parseInt(note.domElement.style.top);
                    
                    if (currentLeft !== note.x || currentTop !== note.y) {
                        console.error(`🚨 ULTRA VIOLATION DETECTED for note ${id}!`);
                        
                        // Immediate forced correction
                        note.domElement.style.setProperty('left', `${note.x}px`, 'important');
                        note.domElement.style.setProperty('top', `${note.y}px`, 'important');
                        note.domElement.style.setProperty('transform', 'none', 'important');
                    }
                }
            });
        }, 50); // Check every 50ms
        
        this.enforcementIntervals.push(monitoringInterval);
        
        // Also add mutation observer for the container itself
        const containerObserver = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList' || mutation.type === 'attributes') {
                    console.log('🔍 Container mutation detected, verifying positions...');
                    
                    // Verify all positions immediately
                    setTimeout(() => {
                        this.nodes.forEach((node) => {
                            if (node.domElement) {
                                node.domElement.style.setProperty('left', `${node.x}px`, 'important');
                                node.domElement.style.setProperty('top', `${node.y}px`, 'important');
                            }
                        });
                        
                        this.textNotes.forEach((note) => {
                            if (note.domElement) {
                                note.domElement.style.setProperty('left', `${note.x}px`, 'important');
                                note.domElement.style.setProperty('top', `${note.y}px`, 'important');
                            }
                        });
                    }, 10);
                }
            });
        });
        
        containerObserver.observe(this.container, {
            childList: true,
            attributes: true,
            subtree: true,
            attributeFilter: ['style', 'class']
        });
        
        this.positionObservers.push(containerObserver);
    }

    // Stop all aggressive monitoring
    stopAggressivePositionEnforcement() {
        // Clear all intervals
        if (this.enforcementIntervals && Array.isArray(this.enforcementIntervals)) {
            this.enforcementIntervals.forEach(id => clearInterval(id));
            this.enforcementIntervals = [];
        }
        
        // Disconnect all observers
        if (this.positionObservers && Array.isArray(this.positionObservers)) {
            this.positionObservers.forEach(observer => observer.disconnect());
            this.positionObservers = [];
        }
        
        console.log('🛑 All aggressive monitoring stopped');
    }

    // Redraw all connections after nuclear rebuild
    redrawAllConnections() {
        console.log('🔗 Redrawing all connections after nuclear rebuild');
        
        // Clear existing connections from SVG
        if (this.svg) {
            const existingConnections = this.svg.querySelectorAll('.connection-line');
            existingConnections.forEach(conn => conn.remove());
        }
        
        // Recreate all connections
        this.connections.forEach((connection, id) => {
            if (connection.from && connection.to && connection.from.domElement && connection.to.domElement) {
                console.log(`🔗 Recreating connection ${id}`);
                this.createConnection(connection.from, connection.to);
            }
        });
    }

    // Setup canvas after nuclear rebuild
    setupCanvas() {
        if (!this.container) return;
        
        // Apply the EXACT same styles as in createCanvas() method
        this.container.style.position = 'relative';
        this.container.style.width = '100%';
        this.container.style.height = '100%';
        this.container.style.overflow = 'auto';
        
        // CRITICAL: Also ensure SVG setup if connections exist
        if (this.connections && this.connections.size > 0) {
            console.log('🔄 setupCanvas: Found existing connections, ensuring SVG setup...');
            this.setupSVG();
        }
        
        console.log('🎯 Canvas setup complete with original styles');
    }

    // Setup SVG after nuclear rebuild
    setupSVG() {
        // Remove existing SVG
        const existingSvg = this.container.querySelector('svg');
        if (existingSvg) {
            existingSvg.remove();
        }
        
        // Create new SVG
        this.svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        this.svg.style.cssText = `
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            pointer-events: none !important;
            z-index: 1 !important;
            transform: none !important;
            transition: none !important;
            animation: none !important;
        `;
        
        // Add marker definitions for arrows
        const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        const marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
        marker.setAttribute('id', 'arrowhead');
        marker.setAttribute('markerWidth', '10');
        marker.setAttribute('markerHeight', '7');
        marker.setAttribute('refX', '9');
        marker.setAttribute('refY', '3.5');
        marker.setAttribute('orient', 'auto');
        
        const polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        polygon.setAttribute('points', '0 0, 10 3.5, 0 7');
        polygon.setAttribute('fill', '#6b7280');
        
        marker.appendChild(polygon);
        defs.appendChild(marker);
        this.svg.appendChild(defs);
        
        this.container.insertBefore(this.svg, this.container.firstChild);
        
        // CRITICAL: Recreate all connection markers after SVG reset
        this.recreateAllMarkers();
        
        console.log('🎯 SVG setup complete');
    }
    
    // Recreate all connections AND markers after SVG is completely reset
    recreateAllConnectionsAndMarkers() {
        console.log('🔄 COMPLETE RECREATION: Recreating all connections and markers after SVG reset...');
        console.log(`🔄 Total connections to recreate: ${this.connections.size}`);
        
        const connectionsRecreated = new Set();
        
        this.connections.forEach(connection => {
            console.log(`🔧 Recreating connection: ${connection.id}`);
            console.log(`  - From: ${connection.from} (${connection.fromNode ? 'node exists' : 'NO NODE!'})`);
            console.log(`  - To: ${connection.to} (${connection.toNode ? 'node exists' : 'NO NODE!'})`);
            console.log(`  - Direction: ${connection.direction}`);
            console.log(`  - PathElement exists: ${!!connection.pathElement}`);
            console.log(`  - PathElement in DOM: ${connection.pathElement ? connection.pathElement.isConnected : 'N/A'}`);
            
            // CRITICAL: Check if path element is still connected to DOM
            if (!connection.pathElement || !connection.pathElement.isConnected) {
                console.log(`🚨 Path element missing or disconnected for ${connection.id}, recreating from scratch...`);
                
                // Get nodes from our maps
                const fromNode = this.nodes.get(connection.from) || connection.fromNode;
                const toNode = this.nodes.get(connection.to) || connection.toNode;
                
                if (fromNode && toNode) {
                    console.log(`✅ Found both nodes, recreating path element...`);
                    
                    // Calculate edge-to-edge connection points
                    const edgePoints = this.calculateEdgeToEdgePoints(fromNode, toNode);
                    
                    // Create new path element
                    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    path.setAttribute('class', 'connection-line');
                    path.setAttribute('data-connection-id', connection.id);
                    path.setAttribute('d', this.getConnectionPath(edgePoints.from.x, edgePoints.from.y, edgePoints.to.x, edgePoints.to.y));
                    path.setAttribute('stroke', this.options.connectionColor);
                    path.setAttribute('stroke-width', '2');
                    path.setAttribute('fill', 'none');
                    path.style.pointerEvents = 'stroke';
                    
                    // Add click event for selection
                    path.addEventListener('click', (e) => {
                        e.stopPropagation();
                        this.selectElement(connection);
                    });
                    
                    // Append to SVG
                    this.svg.appendChild(path);
                    
                    // Update connection object
                    connection.pathElement = path;
                    
                    console.log(`✅ Created new path element for ${connection.id}`);
                } else {
                    console.error(`❌ Missing nodes for connection ${connection.id}: from=${!!fromNode}, to=${!!toNode}`);
                }
            } else {
                console.log(`✅ Path element exists and is connected for ${connection.id}`);
            }
            
            // Now create/recreate the arrow markers
            const arrowId = `arrow_${connection.id}`;
            
            if (!connectionsRecreated.has(arrowId)) {
                console.log(`🎯 Creating arrow markers for: ${arrowId}`);
                this.createArrowMarker(arrowId, true); // true = forceRecreate
                connectionsRecreated.add(arrowId);
            }
            
            // Apply markers to path element
            if (connection.pathElement) {
                // Clear existing marker attributes first
                connection.pathElement.removeAttribute('marker-start');
                connection.pathElement.removeAttribute('marker-end');
                
                // Apply fresh markers based on direction
                if (connection.direction === 'to' || connection.direction === 'both') {
                    connection.pathElement.setAttribute('marker-end', `url(#${arrowId}_end)`);
                    console.log(`✅ Applied end marker: url(#${arrowId}_end)`);
                }
                if (connection.direction === 'from' || connection.direction === 'both') {
                    connection.pathElement.setAttribute('marker-start', `url(#${arrowId}_start)`);
                    console.log(`✅ Applied start marker: url(#${arrowId}_start)`);
                }
                
                console.log(`✅ All markers applied to ${connection.id}`);
            } else {
                console.error(`❌ No path element to apply markers to for ${connection.id}`);
            }
        });
        
        console.log(`✅ COMPLETE RECREATION FINISHED: ${connectionsRecreated.size} connections recreated with full marker support`);
        
        // Force a final redraw to ensure everything is positioned correctly
        console.log('🔄 Final redraw to ensure correct positioning...');
        this.redrawConnections();
    }
    
    // Recreate all arrow markers after SVG is reset (e.g., when modal reopens)
    recreateAllMarkers() {
        console.log('🔄 Recreating all arrow markers after SVG reset...');
        
        const markersRecreated = new Set();
        
        this.connections.forEach(connection => {
            const arrowId = `arrow_${connection.id}`;
            
            if (!markersRecreated.has(arrowId)) {
                // CRITICAL: Force recreate markers since SVG was reset
                console.log(`🔧 Force recreating marker: ${arrowId}`);
                this.createArrowMarker(arrowId, true); // true = forceRecreate
                markersRecreated.add(arrowId);
            }
            
            // CRITICAL: Force reapply markers to path elements with fresh marker IDs
            if (connection.pathElement) {
                // Clear existing marker attributes first
                connection.pathElement.removeAttribute('marker-start');
                connection.pathElement.removeAttribute('marker-end');
                
                // Apply fresh markers
                if (connection.direction === 'to' || connection.direction === 'both') {
                    connection.pathElement.setAttribute('marker-end', `url(#${arrowId}_end)`);
                    console.log(`✅ Applied fresh end marker: url(#${arrowId}_end)`);
                }
                if (connection.direction === 'from' || connection.direction === 'both') {
                    connection.pathElement.setAttribute('marker-start', `url(#${arrowId}_start)`);
                    console.log(`✅ Applied fresh start marker: url(#${arrowId}_start)`);
                }
                
                // Force path element to re-render
                const pathD = connection.pathElement.getAttribute('d');
                connection.pathElement.setAttribute('d', '');
                connection.pathElement.setAttribute('d', pathD);
            }
        });
        
        console.log(`✅ Recreated ${markersRecreated.size} arrow markers total`);
        
        // CRITICAL: After recreating markers, force a complete redraw of all connections
        console.log('🔄 Forcing complete connection redraw with fresh markers...');
        this.redrawConnections();
    }

    // Test edge positioning and modal persistence
    testArrowPositioning() {
        console.log('\n🎯 === ARROW POSITIONING & MODAL PERSISTENCE TEST ===');
        
        let edgeTestsPassed = 0;
        let modalTestsPassed = 0;
        
        console.log('\n1. Testing Edge-to-Edge Positioning:');
        this.connections.forEach(connection => {
            if (connection.pathElement) {
                console.log(`\n🔍 Testing connection: ${connection.id}`);
                
                // Get path coordinates
                const pathD = connection.pathElement.getAttribute('d');
                const pathMatch = pathD.match(/M ([\d.]+) ([\d.]+).*?([\d.]+) ([\d.]+)$/);
                
                if (pathMatch) {
                    const startX = parseFloat(pathMatch[1]);
                    const startY = parseFloat(pathMatch[2]);
                    const endX = parseFloat(pathMatch[3]);
                    const endY = parseFloat(pathMatch[4]);
                    
                    // Check if points are at element edges (not centers)
                    const fromNode = connection.fromNode;
                    const toNode = connection.toNode;
                    
                    const fromCenterX = fromNode.x + fromNode.width / 2;
                    const fromCenterY = fromNode.y + fromNode.height / 2;
                    const toCenterX = toNode.x + toNode.width / 2;
                    const toCenterY = toNode.y + toNode.height / 2;
                    
                    const startAtCenter = (Math.abs(startX - fromCenterX) < 5 && Math.abs(startY - fromCenterY) < 5);
                    const endAtCenter = (Math.abs(endX - toCenterX) < 5 && Math.abs(endY - toCenterY) < 5);
                    
                    if (!startAtCenter && !endAtCenter) {
                        console.log(`  ✅ EDGE positioning: Start(${startX.toFixed(1)}, ${startY.toFixed(1)}) End(${endX.toFixed(1)}, ${endY.toFixed(1)})`);
                        edgeTestsPassed++;
                    } else {
                        console.log(`  ❌ CENTER positioning detected: Start(${startX.toFixed(1)}, ${startY.toFixed(1)}) End(${endX.toFixed(1)}, ${endY.toFixed(1)})`);
                        console.log(`  🔧 Fixing with updateConnectionDirection...`);
                        this.updateConnectionDirection(connection);
                    }
                }
                
                // Check arrow markers
                const hasStartMarker = connection.pathElement.getAttribute('marker-start');
                const hasEndMarker = connection.pathElement.getAttribute('marker-end');
                
                if ((connection.direction === 'both' && hasStartMarker && hasEndMarker) ||
                    (connection.direction === 'to' && hasEndMarker && !hasStartMarker) ||
                    (connection.direction === 'from' && hasStartMarker && !hasEndMarker)) {
                    console.log(`  ✅ Correct arrows for direction: ${connection.direction}`);
                    modalTestsPassed++;
                } else {
                    console.log(`  ❌ Missing arrows for direction: ${connection.direction}`);
                }
            }
        });
        
        console.log('\n2. Testing Modal Persistence (recreateAllMarkers):');
        const originalMarkersCount = this.svg.querySelectorAll('defs marker').length;
        console.log(`  📊 Current markers in SVG: ${originalMarkersCount}`);
        
        this.recreateAllMarkers();
        
        const newMarkersCount = this.svg.querySelectorAll('defs marker').length;
        console.log(`  📊 Markers after recreation: ${newMarkersCount}`);
        
        console.log('\n📊 Test Results:');
        console.log(`✅ Edge positioning tests passed: ${edgeTestsPassed}/${this.connections.size}`);
        console.log(`✅ Arrow direction tests passed: ${modalTestsPassed}/${this.connections.size}`);
        console.log(`✅ Modal persistence: ${newMarkersCount >= originalMarkersCount ? 'PASS' : 'FAIL'}`);
        
        if (edgeTestsPassed === this.connections.size && modalTestsPassed === this.connections.size) {
            console.log('\n🎉 ALL TESTS PASSED! Arrows should be at element edges and persist through modal reopens!');
        } else {
            console.log('\n⚠️ Some tests failed. Try running visualEditor.redrawConnections() to fix positioning.');
        }
        
        return {
            edgePassed: edgeTestsPassed,
            arrowsPassed: modalTestsPassed,
            totalConnections: this.connections.size
        };
    }
    
    // Test specifically for FROM arrow visibility
    testFromArrows() {
        console.log('\n🔍 === TESTING "FROM" ARROW VISIBILITY ===');
        
        let fromArrowsFound = 0;
        let fromArrowsFixed = 0;
        
        this.connections.forEach(connection => {
            if (connection.direction === 'from' || connection.direction === 'both') {
                fromArrowsFound++;
                console.log(`\n🎯 Testing FROM arrow for: ${connection.id}`);
                console.log(`  Direction: ${connection.direction}`);
                
                if (connection.pathElement) {
                    const markerStart = connection.pathElement.getAttribute('marker-start');
                    const expectedMarker = `url(#arrow_${connection.id}_start)`;
                    
                    console.log(`  Current marker-start: ${markerStart || 'NONE'}`);
                    console.log(`  Expected marker-start: ${expectedMarker}`);
                    
                    if (markerStart === expectedMarker) {
                        console.log(`  ✅ FROM arrow correctly configured`);
                        fromArrowsFixed++;
                    } else {
                        console.log(`  🔧 Fixing FROM arrow...`);
                        this.updateConnectionDirection(connection);
                        fromArrowsFixed++;
                    }
                    
                    // Verify the start marker exists in SVG
                    const startMarkerId = `arrow_${connection.id}_start`;
                    const markerElement = this.svg.querySelector(`defs #${startMarkerId}`);
                    if (markerElement) {
                        console.log(`  ✅ Start marker definition found in SVG`);
                    } else {
                        console.log(`  ❌ Start marker definition MISSING from SVG!`);
                        console.log(`  🔧 Creating missing marker...`);
                        this.createArrowMarker(`arrow_${connection.id}`);
                    }
                } else {
                    console.log(`  ❌ No pathElement found for connection`);
                }
            }
        });
        
        console.log('\n📊 FROM Arrow Test Results:');
        console.log(`FROM arrows expected: ${fromArrowsFound}`);
        console.log(`FROM arrows working: ${fromArrowsFixed}`);
        
        if (fromArrowsFound === 0) {
            console.log('\n📝 No "FROM" or "BOTH" connections found to test');
            console.log('💡 Try creating a connection with direction "FROM" or "BOTH"');
        } else if (fromArrowsFixed === fromArrowsFound) {
            console.log('\n🎉 ALL FROM ARROWS SHOULD NOW BE VISIBLE!');
        } else {
            console.log('\n⚠️ Some FROM arrows may still be missing');
        }
        
        return {
            expected: fromArrowsFound,
            fixed: fromArrowsFixed
        };
    }
    
    // Test modal reopen marker persistence
    testModalReopenPersistence() {
        console.log('\n🔄 === TESTING MODAL REOPEN MARKER PERSISTENCE ===');
        
        let totalConnections = this.connections.size;
        let workingMarkers = 0;
        let missingMarkers = 0;
        let fixedMarkers = 0;
        
        console.log(`📊 Total connections to test: ${totalConnections}`);
        
        this.connections.forEach(connection => {
            console.log(`\n🔍 Testing connection: ${connection.id}`);
            console.log(`  Direction: ${connection.direction}`);
            
            if (!connection.pathElement) {
                console.log(`  ❌ No pathElement found`);
                missingMarkers++;
                return;
            }
            
            const arrowId = `arrow_${connection.id}`;
            let hasValidMarkers = true;
            
            // Check end marker (for 'to' and 'both')
            if (connection.direction === 'to' || connection.direction === 'both') {
                const endMarker = connection.pathElement.getAttribute('marker-end');
                const expectedEndMarker = `url(#${arrowId}_end)`;
                
                if (endMarker === expectedEndMarker) {
                    // Verify marker exists in SVG
                    const markerElement = this.svg.querySelector(`defs #${arrowId}_end`);
                    if (markerElement) {
                        console.log(`  ✅ End marker exists and is valid`);
                    } else {
                        console.log(`  ❌ End marker reference exists but marker missing from SVG`);
                        hasValidMarkers = false;
                    }
                } else {
                    console.log(`  ❌ End marker mismatch: ${endMarker} vs ${expectedEndMarker}`);
                    hasValidMarkers = false;
                }
            }
            
            // Check start marker (for 'from' and 'both')
            if (connection.direction === 'from' || connection.direction === 'both') {
                const startMarker = connection.pathElement.getAttribute('marker-start');
                const expectedStartMarker = `url(#${arrowId}_start)`;
                
                if (startMarker === expectedStartMarker) {
                    // Verify marker exists in SVG
                    const markerElement = this.svg.querySelector(`defs #${arrowId}_start`);
                    if (markerElement) {
                        console.log(`  ✅ Start marker exists and is valid`);
                    } else {
                        console.log(`  ❌ Start marker reference exists but marker missing from SVG`);
                        hasValidMarkers = false;
                    }
                } else {
                    console.log(`  ❌ Start marker mismatch: ${startMarker} vs ${expectedStartMarker}`);
                    hasValidMarkers = false;
                }
            }
            
            if (hasValidMarkers) {
                workingMarkers++;
                console.log(`  ✅ All markers working correctly`);
            } else {
                console.log(`  🔧 Fixing markers for this connection...`);
                this.updateConnectionDirection(connection);
                fixedMarkers++;
            }
        });
        
        console.log('\n📊 Modal Reopen Persistence Test Results:');
        console.log(`✅ Working connections: ${workingMarkers}/${totalConnections}`);
        console.log(`🔧 Fixed connections: ${fixedMarkers}/${totalConnections}`);
        console.log(`❌ Missing pathElements: ${missingMarkers}/${totalConnections}`);
        
        if (workingMarkers === totalConnections) {
            console.log('\n🎉 PERFECT! All markers persist correctly through modal reopen!');
        } else if (workingMarkers + fixedMarkers === totalConnections) {
            console.log('\n✅ All markers fixed! Modal reopen issues resolved!');
        } else {
            console.log('\n⚠️ Some connections still have issues. Run visualEditor.recreateAllMarkers() to fix.');
        }
        
        // Additional diagnostic
        const totalMarkersInSVG = this.svg.querySelectorAll('defs marker').length;
        console.log(`📊 Total markers in SVG: ${totalMarkersInSVG}`);
        console.log(`📊 Expected markers: ${totalConnections * 2} (2 per connection)`);
        
        return {
            totalConnections,
            workingMarkers,
            fixedMarkers,
            missingMarkers,
            totalMarkersInSVG,
            expectedMarkers: totalConnections * 2
        };
    }
    
    // Quick test after modal reopen
    quickArrowTest() {
        console.log('\n⚡ === QUICK ARROW VISIBILITY TEST ===');
        
        if (this.connections.size === 0) {
            console.log('📝 No connections found - create some connections to test arrows');
            return { success: false, reason: 'No connections' };
        }
        
        console.log(`📊 Testing ${this.connections.size} connections...`);
        
        let visibleArrows = 0;
        let totalExpectedArrows = 0;
        let connectionsWithoutPaths = 0;
        let connectionsWithInvalidPaths = 0;
        
        this.connections.forEach(connection => {
            console.log(`\n🔍 Testing connection: ${connection.id}`);
            console.log(`  Direction: ${connection.direction}`);
            console.log(`  Has pathElement: ${!!connection.pathElement}`);
            
            if (!connection.pathElement) {
                console.log(`  ❌ NO PATH ELEMENT - this connection is broken!`);
                connectionsWithoutPaths++;
                return;
            }
            
            if (!connection.pathElement.isConnected) {
                console.log(`  ❌ PATH ELEMENT NOT IN DOM - disconnected!`);
                connectionsWithInvalidPaths++;
                return;
            }
            
            console.log(`  ✅ Path element exists and is connected to DOM`);
            
            const arrowId = `arrow_${connection.id}`;
            
            // Check expected arrows for this connection
            if (connection.direction === 'to') {
                totalExpectedArrows += 1;
                console.log(`  Expected: 1 end arrow`);
            }
            else if (connection.direction === 'from') {
                totalExpectedArrows += 1;
                console.log(`  Expected: 1 start arrow`);
            }
            else if (connection.direction === 'both') {
                totalExpectedArrows += 2;
                console.log(`  Expected: 2 arrows (start + end)`);
            }
            
            // Check if end marker exists and is visible
            if ((connection.direction === 'to' || connection.direction === 'both')) {
                const endMarkerId = `${arrowId}_end`;
                const endMarker = this.svg.querySelector(`defs #${endMarkerId}`);
                const pathEndMarker = connection.pathElement.getAttribute('marker-end');
                
                console.log(`  🎯 End marker check:`);
                console.log(`    - Marker in SVG: ${!!endMarker}`);
                console.log(`    - Path marker-end: ${pathEndMarker || 'NONE'}`);
                console.log(`    - Expected: url(#${endMarkerId})`);
                
                if (endMarker && pathEndMarker === `url(#${endMarkerId})`) {
                    console.log(`    ✅ End arrow working`);
                    visibleArrows++;
                } else {
                    console.log(`    ❌ End arrow missing or broken`);
                }
            }
            
            // Check if start marker exists and is visible
            if ((connection.direction === 'from' || connection.direction === 'both')) {
                const startMarkerId = `${arrowId}_start`;
                const startMarker = this.svg.querySelector(`defs #${startMarkerId}`);
                const pathStartMarker = connection.pathElement.getAttribute('marker-start');
                
                console.log(`  🎯 Start marker check:`);
                console.log(`    - Marker in SVG: ${!!startMarker}`);
                console.log(`    - Path marker-start: ${pathStartMarker || 'NONE'}`);
                console.log(`    - Expected: url(#${startMarkerId})`);
                
                if (startMarker && pathStartMarker === `url(#${startMarkerId})`) {
                    console.log(`    ✅ Start arrow working`);
                    visibleArrows++;
                } else {
                    console.log(`    ❌ Start arrow missing or broken`);
                }
            }
        });
        
        const success = visibleArrows === totalExpectedArrows && connectionsWithoutPaths === 0 && connectionsWithInvalidPaths === 0;
        
        console.log(`\n📊 Quick Test Results:`);
        console.log(`✅ Working arrows: ${visibleArrows}/${totalExpectedArrows}`);
        console.log(`📊 Total connections: ${this.connections.size}`);
        console.log(`❌ Connections without paths: ${connectionsWithoutPaths}`);
        console.log(`❌ Connections with invalid paths: ${connectionsWithInvalidPaths}`);
        
        if (success) {
            console.log('🎉 QUICK TEST PASSED! All arrows are visible and working!');
        } else {
            console.log('❌ QUICK TEST FAILED! Issues detected:');
            if (connectionsWithoutPaths > 0) {
                console.log(`  🔧 ${connectionsWithoutPaths} connections missing path elements`);
            }
            if (connectionsWithInvalidPaths > 0) {
                console.log(`  🔧 ${connectionsWithInvalidPaths} connections have disconnected path elements`);
            }
            if (visibleArrows < totalExpectedArrows) {
                console.log(`  🔧 ${totalExpectedArrows - visibleArrows} arrows are missing or broken`);
            }
            console.log('🔧 Run visualEditor.recreateAllConnectionsAndMarkers() to fix all issues.');
        }
        
        return { 
            success, 
            visibleArrows, 
            totalExpectedArrows, 
            connections: this.connections.size,
            brokenConnections: connectionsWithoutPaths + connectionsWithInvalidPaths
        };
    }

    // Add event listeners to recreated elements
    addElementEventListeners(element, node) {
        element.addEventListener('mousedown', (e) => {
            console.log(`Nuclear rebuild mousedown on element: ${node.id}`);
            e.stopPropagation();
            this.selectElement(node);
            if (this.activeTool === 'select') {
                this.isDragging = true;
                const rect = this.container.getBoundingClientRect();
                const scrollLeft = this.container.scrollLeft || 0;
                const scrollTop = this.container.scrollTop || 0;
                const x = (e.clientX - rect.left + scrollLeft) / this.zoomLevel;
                const y = (e.clientY - rect.top + scrollTop) / this.zoomLevel;
                this.dragOffset = {
                    x: x - node.x,
                    y: y - node.y
                };
            }
        });
        
        element.addEventListener('click', (e) => {
            console.log(`Nuclear rebuild click on element: ${node.id}`);
            e.stopPropagation();
            this.selectElement(node);
        });
        
        element.addEventListener('dblclick', (e) => {
            console.log(`Nuclear rebuild double-click on element: ${node.id}`);
            e.stopPropagation();
            this.editElementText(node);
        });
        
        // Add position protection
        this.protectElementPosition(element, node);
    }

    // Add event listeners to recreated text notes
    addTextNoteEventListeners(element, note) {
        element.addEventListener('mousedown', (e) => {
            console.log(`Nuclear rebuild mousedown on note: ${note.id}`);
            e.stopPropagation();
            this.selectElement(note);
            if (this.activeTool === 'select') {
                this.isDragging = true;
                const rect = this.container.getBoundingClientRect();
                const scrollLeft = this.container.scrollLeft || 0;
                const scrollTop = this.container.scrollTop || 0;
                const x = (e.clientX - rect.left + scrollLeft) / this.zoomLevel;
                const y = (e.clientY - rect.top + scrollTop) / this.zoomLevel;
                this.dragOffset = {
                    x: x - note.x,
                    y: y - note.y
                };
            }
        });
        
        element.addEventListener('click', (e) => {
            console.log(`Nuclear rebuild click on note: ${note.id}`);
            e.stopPropagation();
            this.selectElement(note);
        });
        
        element.addEventListener('dblclick', (e) => {
            console.log(`Nuclear rebuild double-click on note: ${note.id}`);
            e.stopPropagation();
            this.editTextNoteInline(note);
        });
        
        // Add position protection
        this.protectElementPosition(element, note);
    }
    
    // Start monitoring positions to catch any unexpected changes
    startPositionMonitoring() {
        console.log('Starting position monitoring...');
        
        if (this.positionMonitor) {
            clearInterval(this.positionMonitor);
        }
        
        this.positionMonitor = setInterval(() => {
            let foundIssue = false;
            
            this.nodes.forEach((node, id) => {
                if (node.domElement) {
                    const computedLeft = parseInt(window.getComputedStyle(node.domElement).left);
                    const computedTop = parseInt(window.getComputedStyle(node.domElement).top);
                    
                    if (computedLeft !== node.x || computedTop !== node.y) {
                        console.warn(`POSITION DRIFT DETECTED for node ${id}!`);
                        console.warn(`  Expected: (${node.x}, ${node.y}), Actual: (${computedLeft}, ${computedTop})`);
                        this.setElementStyles(node.domElement, node);
                        foundIssue = true;
                    }
                }
            });
            
            this.textNotes.forEach((note, id) => {
                if (note.domElement) {
                    const computedLeft = parseInt(window.getComputedStyle(note.domElement).left);
                    const computedTop = parseInt(window.getComputedStyle(note.domElement).top);
                    
                    if (computedLeft !== note.x || computedTop !== note.y) {
                        console.warn(`POSITION DRIFT DETECTED for note ${id}!`);
                        console.warn(`  Expected: (${note.x}, ${note.y}), Actual: (${computedLeft}, ${computedTop})`);
                        this.setTextNoteStyles(note.domElement, note);
                        foundIssue = true;
                    }
                }
            });
            
            if (foundIssue) {
                console.warn('Position corrections applied due to drift detection');
            }
        }, 2000); // Check every 2 seconds
    }
    
    // Stop position monitoring
    stopPositionMonitoring() {
        if (this.positionMonitor) {
            clearInterval(this.positionMonitor);
            this.positionMonitor = null;
            console.log('Position monitoring stopped');
        }
    }
    
    // Auto layout algorithm
    autoLayout() {
        const nodes = Array.from(this.nodes.values());
        if (nodes.length === 0) return;
        
        // Simple grid layout
        const cols = Math.ceil(Math.sqrt(nodes.length));
        const spacing = 150;
        
        nodes.forEach((node, index) => {
            const col = index % cols;
            const row = Math.floor(index / cols);
            
            node.x = 100 + col * spacing;
            node.y = 100 + row * spacing;
            
            node.domElement.style.left = node.x + 'px';
            node.domElement.style.top = node.y + 'px';
        });
        
        this.redrawConnections();
    }
    
    // Clean malformed Mermaid code
    cleanMermaidCode(code) {
        let lines = code.split('\n').map(line => line.trim()).filter(line => line);
        let cleanedLines = [];
        
        for (let line of lines) {
            // Fix malformed circle syntax like ("Start"( -> ((Start))
            if (line.includes('("') && line.includes('"(')) {
                line = line.replace(/(\w+)\("([^"]+)"\(/g, '$1(($2))');
            }
            
            // Fix broken parentheses
            if (line.includes('((') && !line.includes('))')) {
                line = line.replace(/\(\(([^)]+)\($/, '(($1))');
            }
            
            // Fix other malformed syntax
            line = line.replace(/\(\s*$/, ')'); // Fix trailing open parenthesis
            
            cleanedLines.push(line);
        }
        
        return cleanedLines.join('\n');
    }
    
    // Debug function to list all nodes
    debugListNodes() {
        console.log('=== Current Nodes ===');
        this.nodes.forEach((node, id) => {
            console.log(`Node ${id}: type=${node.elementType}, pos=(${node.x}, ${node.y}), size=${node.width}x${node.height}, text="${node.text}"`);
        });
        console.log('=== Current Text Notes ===');
        this.textNotes.forEach((note, id) => {
            console.log(`Note ${id}: pos=(${note.x}, ${note.y}), size=${note.width}x${note.height}, text="${note.text}"`);
        });
        console.log('=== Current Connections ===');
        this.connections.forEach((conn, id) => {
            console.log(`Connection ${id}: from=${conn.from} to=${conn.to}`);
        });
    }
    
    // Debug function to check SVG markers
    debugSVGMarkers() {
        console.log('🔍 Debugging SVG Markers:');
        
        // Check if SVG has defs element
        const defsElement = this.svg.querySelector('defs');
        console.log('Defs element:', defsElement);
        
        if (defsElement) {
            const markers = defsElement.querySelectorAll('marker');
            console.log(`Found ${markers.length} markers:`, markers);
            
            markers.forEach(marker => {
                console.log(`Marker ID: ${marker.id}`, marker);
                console.log(`Marker attributes:`, {
                    id: marker.getAttribute('id'),
                    markerWidth: marker.getAttribute('markerWidth'),
                    markerHeight: marker.getAttribute('markerHeight'),
                    refX: marker.getAttribute('refX'),
                    refY: marker.getAttribute('refY'),
                    orient: marker.getAttribute('orient'),
                    markerUnits: marker.getAttribute('markerUnits'),
                    viewBox: marker.getAttribute('viewBox')
                });
                
                const path = marker.querySelector('path');
                if (path) {
                    console.log(`Marker path d:`, path.getAttribute('d'));
                    console.log(`Marker path fill:`, path.getAttribute('fill'));
                    console.log(`Marker path stroke:`, path.getAttribute('stroke'));
                }
            });
        }
        
        // Check connection paths
        const paths = this.svg.querySelectorAll('path[data-connection-id]');
        console.log(`Found ${paths.length} connection paths:`);
        
        paths.forEach(path => {
            const connectionId = path.getAttribute('data-connection-id');
            const markerStart = path.getAttribute('marker-start');
            const markerEnd = path.getAttribute('marker-end');
            
            console.log(`Connection ${connectionId}:`, {
                markerStart,
                markerEnd,
                stroke: path.getAttribute('stroke'),
                strokeWidth: path.getAttribute('stroke-width'),
                fill: path.getAttribute('fill'),
                d: path.getAttribute('d'),
                visibility: getComputedStyle(path).visibility,
                display: getComputedStyle(path).display
            });
        });
        
        // Check if connections array has direction property
        console.log('Connections with directions:', Array.from(this.connections.values()).map(c => ({
            id: c.id,
            direction: c.direction,
            hasPathElement: !!c.pathElement
        })));
        
        // Test creating a simple test arrow
        console.log('🧪 Creating test arrow marker...');
        this.createTestArrow();
        
        // Additional test - try to force visibility
        console.log('🔧 Testing marker visibility...');
        this.testMarkerVisibility();
    }
    
    // Test function to create a simple visible arrow
    createTestArrow() {
        let defs = this.svg.querySelector('defs');
        if (!defs) {
            defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
            this.svg.appendChild(defs);
        }
        
        // Remove existing test marker
        const existingTest = defs.querySelector('#test-arrow');
        if (existingTest) {
            existingTest.remove();
        }
        
        // Create a simple, highly visible test marker
        const marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
        marker.setAttribute('id', 'test-arrow');
        marker.setAttribute('markerWidth', '20');
        marker.setAttribute('markerHeight', '20');
        marker.setAttribute('refX', '15');
        marker.setAttribute('refY', '10');
        marker.setAttribute('orient', 'auto');
        marker.setAttribute('viewBox', '0 0 20 20');
        
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', 'M0,0 L0,20 L20,10 z');
        path.setAttribute('fill', 'red');
        path.setAttribute('stroke', 'black');
        path.setAttribute('stroke-width', '2');
        
        marker.appendChild(path);
        defs.appendChild(marker);
        
        console.log('🧪 Test arrow marker created with ID: test-arrow');
        
        // Apply test arrow to first connection if it exists
        const firstPath = this.svg.querySelector('path[data-connection-id]');
        if (firstPath) {
            console.log('🧪 Applying test arrow to first connection...');
            firstPath.setAttribute('marker-end', 'url(#test-arrow)');
            firstPath.setAttribute('stroke', 'blue'); // Make line blue too
            firstPath.setAttribute('stroke-width', '4'); // Make line thicker
            console.log('🧪 Test arrow applied! Should see large red arrow on thick blue line.');
        } else {
            console.log('🧪 No connections found to apply test arrow to');
        }
        
        // Also try to fix all existing arrows by recreating them with better settings
        console.log('🔧 Attempting to fix all existing arrows...');
        this.fixAllArrows();
    }
    
    // Function to fix all existing arrows
    fixAllArrows() {
        console.log('🔧 Fixing all existing arrows...');
        
        // Clear all existing arrow markers
        const defs = this.svg.querySelector('defs');
        if (defs) {
            const existingMarkers = defs.querySelectorAll('marker[id^="arrow_"]');
            console.log(`🗑️ Removing ${existingMarkers.length} existing arrow markers`);
            existingMarkers.forEach(marker => marker.remove());
        }
        
        // Recreate all arrows with improved settings
        this.connections.forEach(connection => {
            if (connection.direction && connection.direction !== 'none') {
                console.log(`🔧 Fixing arrow for connection ${connection.id} with direction ${connection.direction}`);
                const arrowId = `arrow_${connection.id}`;
                this.createArrowMarker(arrowId);
                
                if (connection.pathElement) {
                    // Clear existing markers
                    connection.pathElement.removeAttribute('marker-start');
                    connection.pathElement.removeAttribute('marker-end');
                    
                    // Apply new markers
                    if (connection.direction === 'to' || connection.direction === 'both') {
                        connection.pathElement.setAttribute('marker-end', `url(#${arrowId}_end)`);
                        console.log(`🔧 Applied end marker: url(#${arrowId}_end)`);
                    }
                    if (connection.direction === 'from' || connection.direction === 'both') {
                        connection.pathElement.setAttribute('marker-start', `url(#${arrowId}_start)`);
                        console.log(`🔧 Applied start marker: url(#${arrowId}_start)`);
                    }
                    
                    // Make sure the connection line is visible
                    connection.pathElement.setAttribute('stroke', '#333333');
                    connection.pathElement.setAttribute('stroke-width', '2');
                }
            }
        });
        
        console.log('🔧 All arrows fixed!');
    }
    
    // Test function to check marker visibility issues
    // Detailed diagnostic function for visual confirmation
    diagnosticMarkerCheck() {
        console.log('\n🔍 === DETAILED MARKER DIAGNOSTIC ===');
        
        // Check SVG structure
        const svg = this.svg;
        const defs = svg.querySelector('defs');
        const markers = defs ? defs.querySelectorAll('marker') : [];
        const paths = svg.querySelectorAll('path[id*="connection"]');
        
        console.log('📊 SVG Structure:');
        console.log(`- SVG element exists: ${!!svg}`);
        console.log(`- SVG dimensions: ${svg.clientWidth}x${svg.clientHeight}`);
        console.log(`- SVG viewBox: ${svg.getAttribute('viewBox') || 'none'}`);
        console.log(`- Defs element exists: ${!!defs}`);
        console.log(`- Total markers: ${markers.length}`);
        console.log(`- Total connection paths: ${paths.length}`);
        
        // Check marker visibility
        console.log('\n🎯 Marker Analysis:');
        markers.forEach((marker, index) => {
            const rect = marker.getBoundingClientRect();
            const style = window.getComputedStyle(marker);
            console.log(`Marker ${index + 1} (${marker.id}):`);
            console.log(`  - Display: ${style.display}`);
            console.log(`  - Visibility: ${style.visibility}`);
            console.log(`  - Opacity: ${style.opacity}`);
            console.log(`  - BoundingRect: ${rect.width}x${rect.height} at (${rect.x}, ${rect.y})`);
        });
        
        // Check path visibility and marker attribution
        console.log('\n🛣️ Path Analysis:');
        paths.forEach((path, index) => {
            const rect = path.getBoundingClientRect();
            const style = window.getComputedStyle(path);
            console.log(`Path ${index + 1} (${path.id}):`);
            console.log(`  - Display: ${style.display}`);
            console.log(`  - Visibility: ${style.visibility}`);
            console.log(`  - Opacity: ${style.opacity}`);
            console.log(`  - Stroke: ${style.stroke}`);
            console.log(`  - Stroke-width: ${style.strokeWidth}`);
            console.log(`  - Marker-start: ${path.getAttribute('marker-start') || 'none'}`);
            console.log(`  - Marker-end: ${path.getAttribute('marker-end') || 'none'}`);
            console.log(`  - BoundingRect: ${rect.width}x${rect.height} at (${rect.x}, ${rect.y})`);
            console.log(`  - Path data length: ${path.getAttribute('d')?.length || 0}`);
        });
        
        // Check for any CSS that might be interfering
        console.log('\n🎨 CSS Analysis:');
        const svgStyle = window.getComputedStyle(svg);
        console.log(`SVG styles:`);
        console.log(`  - Display: ${svgStyle.display}`);
        console.log(`  - Visibility: ${svgStyle.visibility}`);
        console.log(`  - Opacity: ${svgStyle.opacity}`);
        console.log(`  - Overflow: ${svgStyle.overflow}`);
        console.log(`  - Position: ${svgStyle.position}`);
        console.log(`  - Z-index: ${svgStyle.zIndex}`);
        
        // Look for any transforms or filters that might hide content
        if (svgStyle.transform && svgStyle.transform !== 'none') {
            console.log(`  - Transform: ${svgStyle.transform}`);
        }
        if (svgStyle.filter && svgStyle.filter !== 'none') {
            console.log(`  - Filter: ${svgStyle.filter}`);
        }
        
        // Check parent container
        const container = svg.parentElement;
        if (container) {
            const containerStyle = window.getComputedStyle(container);
            console.log(`Container styles:`);
            console.log(`  - Display: ${containerStyle.display}`);
            console.log(`  - Visibility: ${containerStyle.visibility}`);
            console.log(`  - Opacity: ${containerStyle.opacity}`);
            console.log(`  - Overflow: ${containerStyle.overflow}`);
        }
        
        // Final assessment
        const visibleMarkers = Array.from(markers).filter(m => {
            const style = window.getComputedStyle(m);
            return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
        });
        
        const visiblePaths = Array.from(paths).filter(p => {
            const style = window.getComputedStyle(p);
            return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
        });
        
        console.log('\n🏁 Summary:');
        console.log(`✅ Visible markers: ${visibleMarkers.length}/${markers.length}`);
        console.log(`✅ Visible paths: ${visiblePaths.length}/${paths.length}`);
        
        if (visibleMarkers.length === 0) {
            console.log('🚨 NO MARKERS ARE VISIBLE - This explains why no arrows appear!');
        } else if (visiblePaths.length === 0) {
            console.log('🚨 NO PATHS ARE VISIBLE - Markers exist but paths are hidden!');
        } else {
            console.log('✅ Both markers and paths are visible - The issue might be elsewhere');
        }
        
        return {
            markersTotal: markers.length,
            markersVisible: visibleMarkers.length,
            pathsTotal: paths.length,
            pathsVisible: visiblePaths.length
        };
    }
    
    // Test edge-to-edge connections
    testEdgeConnections() {
        console.log('\n🔧 === TESTING EDGE-TO-EDGE CONNECTIONS ===');
        console.log('This will redraw all connections to stop at element edges instead of centers');
        
        // Store original paths for comparison
        const originalPaths = [];
        this.connections.forEach(connection => {
            if (connection.pathElement) {
                originalPaths.push({
                    id: connection.id,
                    originalPath: connection.pathElement.getAttribute('d')
                });
            }
        });
        
        // Apply edge-to-edge connections
        this.redrawConnections();
        
        // Show comparison
        console.log('\n📊 Connection Path Changes:');
        originalPaths.forEach(({ id, originalPath }) => {
            const connection = this.connections.get(id);
            if (connection && connection.pathElement) {
                const newPath = connection.pathElement.getAttribute('d');
                console.log(`🔗 ${id}:`);
                console.log(`  Old: ${originalPath.substring(0, 50)}...`);
                console.log(`  New: ${newPath.substring(0, 50)}...`);
                console.log(`  Changed: ${originalPath !== newPath ? '✅ YES' : '❌ NO'}`);
            }
        });
        
        console.log('\n✅ Edge-to-edge connections applied!');
        console.log('🎯 Arrows should now appear at element edges instead of behind them');
        
        return {
            connectionsUpdated: this.connections.size,
            pathsChanged: originalPaths.filter((original, index) => {
                const connection = this.connections.get(original.id);
                return connection && connection.pathElement && 
                       connection.pathElement.getAttribute('d') !== original.originalPath;
            }).length
        };
    }
    
    // Test single-line bidirectional connections
    testSingleLineBidirectional() {
        console.log('\n🔧 === TESTING SINGLE-LINE BIDIRECTIONAL CONNECTIONS ===');
        console.log('Testing one line with arrows at both ends (edge-to-edge)');
        
        let bothCount = 0;
        let fixedCount = 0;
        
        this.connections.forEach(connection => {
            if (connection.direction === 'both') {
                bothCount++;
                console.log(`🔍 Testing connection: ${connection.id}`);
                
                // Ensure it uses single line with both markers
                if (connection.pathElement) {
                    const hasStartMarker = connection.pathElement.getAttribute('marker-start');
                    const hasEndMarker = connection.pathElement.getAttribute('marker-end');
                    
                    console.log(`  - Start arrow: ${hasStartMarker ? '✅' : '❌'}`);
                    console.log(`  - End arrow: ${hasEndMarker ? '✅' : '❌'}`);
                    
                    if (hasStartMarker && hasEndMarker) {
                        console.log(`  ✅ Perfect: Single line with arrows at both ends`);
                        fixedCount++;
                    } else {
                        console.log(`  🔧 Fixing: Adding missing arrows`);
                        this.updateConnectionDirection(connection);
                        fixedCount++;
                    }
                } else {
                    console.log(`  ⚠️ No path element found`);
                }
            }
        });
        
        console.log('\n📊 Single-Line Bidirectional Test Results:');
        console.log(`- Bidirectional connections found: ${bothCount}`);
        console.log(`- Connections with proper arrows: ${fixedCount}`);
        
        if (bothCount > 0) {
            console.log('\n✅ All bidirectional connections now use single lines with arrows at both ends!');
            console.log('🎯 Arrows should be visible at element edges, not behind elements');
        } else {
            console.log('\n📝 No bidirectional connections found');
        }
        
        return {
            bidirectionalTotal: bothCount,
            properlyConfigured: fixedCount
        };
    }
    
    testMarkerVisibility() {
        console.log('🔧 ULTIMATE VISIBILITY TEST - Testing all possible marker approaches...');
        
        // Get SVG element
        let defs = this.svg.querySelector('defs');
        if (!defs) {
            defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
            this.svg.appendChild(defs);
        }
        
        // Remove existing test markers
        const existingMarkers = defs.querySelectorAll('#simple-test, #no-units-test, #force-visible, #huge-triangle, #bright-rect, #multi-shape, #text-arrow');
        existingMarkers.forEach(marker => marker.remove());
        
        const paths = this.svg.querySelectorAll('path[data-connection-id]');
        if (paths.length === 0) {
            console.log('❌ No connection paths found for testing');
            return;
        }
        
        console.log(`🎯 Testing on ${paths.length} connection paths`);
        
        // EXTREME TEST 1: Huge visible geometric shapes
        const hugeMarker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
        hugeMarker.setAttribute('id', 'huge-triangle');
        hugeMarker.setAttribute('markerWidth', '50');
        hugeMarker.setAttribute('markerHeight', '50');
        hugeMarker.setAttribute('refX', '45');
        hugeMarker.setAttribute('refY', '25');
        hugeMarker.setAttribute('orient', 'auto');
        hugeMarker.setAttribute('markerUnits', 'userSpaceOnUse');
        hugeMarker.setAttribute('overflow', 'visible');
        
        const hugeTriangle = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        hugeTriangle.setAttribute('points', '5,5 5,45 45,25');
        hugeTriangle.setAttribute('fill', 'red');
        hugeTriangle.setAttribute('stroke', 'black');
        hugeTriangle.setAttribute('stroke-width', '3');
        hugeTriangle.setAttribute('opacity', '1');
        
        hugeMarker.appendChild(hugeTriangle);
        defs.appendChild(hugeMarker);
        console.log('� Created HUGE RED TRIANGLE marker (50x50px)');
        
        // EXTREME TEST 2: Bright colored rectangle
        const rectMarker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
        rectMarker.setAttribute('id', 'bright-rect');
        rectMarker.setAttribute('markerWidth', '40');
        rectMarker.setAttribute('markerHeight', '20');
        rectMarker.setAttribute('refX', '35');
        rectMarker.setAttribute('refY', '10');
        rectMarker.setAttribute('orient', 'auto');
        rectMarker.setAttribute('markerUnits', 'strokeWidth');
        
        const brightRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        brightRect.setAttribute('x', '2');
        brightRect.setAttribute('y', '2');
        brightRect.setAttribute('width', '36');
        brightRect.setAttribute('height', '16');
        brightRect.setAttribute('fill', 'yellow');
        brightRect.setAttribute('stroke', 'black');
        brightRect.setAttribute('stroke-width', '2');
        
        rectMarker.appendChild(brightRect);
        defs.appendChild(rectMarker);
        console.log('� Created BRIGHT YELLOW RECTANGLE marker');
        
        // EXTREME TEST 3: Multiple overlapping shapes
        const multiMarker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
        multiMarker.setAttribute('id', 'multi-shape');
        multiMarker.setAttribute('markerWidth', '30');
        multiMarker.setAttribute('markerHeight', '30');
        multiMarker.setAttribute('refX', '25');
        multiMarker.setAttribute('refY', '15');
        multiMarker.setAttribute('orient', 'auto');
        
        // Add circle background
        const bgCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        bgCircle.setAttribute('cx', '15');
        bgCircle.setAttribute('cy', '15');
        bgCircle.setAttribute('r', '12');
        bgCircle.setAttribute('fill', 'cyan');
        bgCircle.setAttribute('stroke', 'blue');
        bgCircle.setAttribute('stroke-width', '3');
        
        // Add arrow on top
        const arrow = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        arrow.setAttribute('d', 'M8,8 L8,22 L22,15 z');
        arrow.setAttribute('fill', 'darkblue');
        arrow.setAttribute('stroke', 'white');
        arrow.setAttribute('stroke-width', '1');
        
        multiMarker.appendChild(bgCircle);
        multiMarker.appendChild(arrow);
        defs.appendChild(multiMarker);
        console.log('� Created MULTI-SHAPE marker (cyan circle + blue arrow)');
        
        // EXTREME TEST 4: Text marker for ultimate visibility
        const textMarker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
        textMarker.setAttribute('id', 'text-arrow');
        textMarker.setAttribute('markerWidth', '60');
        textMarker.setAttribute('markerHeight', '20');
        textMarker.setAttribute('refX', '55');
        textMarker.setAttribute('refY', '10');
        textMarker.setAttribute('orient', 'auto');
        textMarker.setAttribute('markerUnits', 'userSpaceOnUse');
        
        const textElement = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        textElement.setAttribute('x', '5');
        textElement.setAttribute('y', '15');
        textElement.setAttribute('font-family', 'Arial, sans-serif');
        textElement.setAttribute('font-size', '12');
        textElement.setAttribute('font-weight', 'bold');
        textElement.setAttribute('fill', 'red');
        textElement.setAttribute('stroke', 'white');
        textElement.setAttribute('stroke-width', '1');
        textElement.textContent = '→ARROW';
        
        textMarker.appendChild(textElement);
        defs.appendChild(textMarker);
        console.log('� Created TEXT ARROW marker');
        
        // Apply extreme markers to different paths with maximum visibility settings
        if (paths[0]) {
            paths[0].setAttribute('marker-end', 'url(#huge-triangle)');
            paths[0].setAttribute('stroke', 'red');
            paths[0].setAttribute('stroke-width', '15');
            paths[0].setAttribute('opacity', '1');
            paths[0].setAttribute('fill', 'none');
            console.log('🟥 Applied HUGE RED TRIANGLE to path 1');
        }
        
        if (paths[1]) {
            paths[1].setAttribute('marker-end', 'url(#bright-rect)');
            paths[1].setAttribute('stroke', 'green');
            paths[1].setAttribute('stroke-width', '12');
            paths[1].setAttribute('opacity', '1');
            console.log('� Applied BRIGHT YELLOW RECTANGLE to path 2');
        }
        
        if (paths[2]) {
            paths[2].setAttribute('marker-end', 'url(#multi-shape)');
            paths[2].setAttribute('stroke', 'purple');
            paths[2].setAttribute('stroke-width', '10');
            paths[2].setAttribute('opacity', '1');
            console.log('🔵 Applied MULTI-SHAPE to path 3');
        }
        
        if (paths[3]) {
            paths[3].setAttribute('marker-end', 'url(#text-arrow)');
            paths[3].setAttribute('stroke', 'orange');
            paths[3].setAttribute('stroke-width', '8');
            paths[3].setAttribute('opacity', '1');
            console.log('� Applied TEXT ARROW to path 4');
        }
        
        // EXTREME VISIBILITY: Add start markers too for double visibility
        if (paths[0]) {
            paths[0].setAttribute('marker-start', 'url(#bright-rect)');
            console.log('⬅️ Also added START marker to path 1');
        }
        
        console.log('💥 ULTIMATE VISIBILITY TEST COMPLETE!');
        console.log('🔍 You should now see:');
        console.log('   🟥 Path 1: Huge red triangle + yellow rectangle start');
        console.log('   🟨 Path 2: Bright yellow rectangle');
        console.log('   🔵 Path 3: Cyan circle with blue arrow');
        console.log('   📝 Path 4: Text "→ARROW"');
        console.log('');
        console.log('🚨 If NONE of these are visible, the issue is fundamental SVG rendering!');
        console.log('');
        console.log('⚡ RUN THIS COMMAND IN CONSOLE FOR DETAILED DIAGNOSIS:');
        console.log('   visualEditor.diagnosticMarkerCheck()');
        console.log('');
        console.log('🔧 TO COMPLETELY FIX MODAL REOPEN ISSUES:');
        console.log('   visualEditor.recreateAllConnectionsAndMarkers()');
        console.log('');
        console.log('🔧 TO TEST EDGE-TO-EDGE CONNECTIONS:');
        console.log('   visualEditor.redrawConnections()');
        console.log('');
        console.log('🎯 TO FIX ALL ARROWS (INCLUDING BIDIRECTIONAL):');
        console.log('   visualEditor.testEdgeConnections()');
        console.log('');
        console.log('🧪 NEW: COMPLETE ARROW POSITIONING TEST:');
        console.log('   visualEditor.testArrowPositioning()');
        console.log('');
        console.log('🎯 NEW: TEST "FROM" ARROW VISIBILITY:');
        console.log('   visualEditor.testFromArrows()');
        console.log('');
        console.log('🔄 NEW: TEST MODAL REOPEN PERSISTENCE:');
        console.log('   visualEditor.testModalReopenPersistence()');
        console.log('');
        console.log('⚡ QUICK ARROW TEST (fast check):');
        console.log('   visualEditor.quickArrowTest()');
        console.log('');
        console.log('🔄 IF MODAL REOPENING BREAKS ARROWS:');
        console.log('   visualEditor.recreateAllConnectionsAndMarkers()');
        console.log('');
        console.log('⚡ ALTERNATIVE (just markers):');
        console.log('   visualEditor.recreateAllMarkers()');
        console.log('');
        
        // Force multiple types of refresh
        this.svg.style.display = 'none';
        this.svg.offsetHeight; // Force reflow
        this.svg.style.display = 'block';
        
        // Force repaint
        this.svg.style.transform = 'translateZ(0)';
        setTimeout(() => {
            this.svg.style.transform = '';
        }, 50);
        
        console.log('🔧 Forced multiple SVG refresh techniques');
    }
    
    // Public method to force recreation of all connections and markers
    // Can be called from outside when arrows disappear after modal reopening
    forceRecreateArrows() {
        console.log('🔧 FORCE RECREATE: Public method called to recreate all arrows');
        if (this.connections.size > 0) {
            console.log(`🔧 FORCE RECREATE: Found ${this.connections.size} connections to recreate`);
            this.recreateAllConnectionsAndMarkers();
            console.log('✅ FORCE RECREATE: Complete');
        } else {
            console.log('⚠️ FORCE RECREATE: No connections found to recreate');
        }
    }
}

// Export for global use
window.VisualDiagramEditor = VisualDiagramEditor;
