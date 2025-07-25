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
        this.freeLines = new Map(); // New: for free-form lines
        this.selectedElement = null;
        this.activeTool = 'select'; // 'select', 'connect', 'text', 'line'
        this.connectingFrom = null;
        this.isDragging = false;
        this.isResizing = false;
        this.isDrawingConnection = false;
        this.isDrawingLine = false; // New: for line drawing state
        this.connectingFromNode = null;
        this.connectingFromSide = null;
        this.connectionCleanup = null;
        this.currentLine = null; // New: current line being drawn
        this.nextLineId = 1; // New: line ID counter
        this.activeLines = new Map(); // Store active curved lines
        this.selectedLine = null; // Currently selected line for editing
        this.lineDragHandle = null; // Handle for curve adjustment
        this.justFinishedLine = null; // Track recently finished line to prevent immediate deselection
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
            user: { width: 100, height: 80, class: 'element-user' },
            data: { width: 120, height: 60, class: 'element-data' }
        };
        
        this.init();
    }
    
    init() {
        this.createCanvas();
        this.setupEventListeners();
        this.updateMouseMoveForLineDrawing(); // Enable line drawing
        this.drawGrid();
        this.setActiveTool('select');
        
        // Make test function available globally for debugging
        window.testDragHandles = () => this.testDragHandles();
        console.log('🔧 Debug: testDragHandles() function available in console');
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
            // Check if the modal is open and we're in diagram editing context
            const modal = this.container.closest('.modal');
            const isModalOpen = modal && modal.style.display !== 'none';
            
            console.log('🔑 Keydown event detected:', e.key);
            console.log('🔑 Modal open:', isModalOpen);
            console.log('🔑 Active element:', document.activeElement.tagName, document.activeElement.className);
            
            if (isModalOpen) {
                console.log('🔑 Calling handleKeyDown');
                this.handleKeyDown(e);
            } else {
                console.log('🔑 Modal not open, ignoring keydown');
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
        
        if (this.activeTool === 'line') {
            // Handle line drawing tool
            this.handleLineToolMouseDown(e, x, y);
            return;
        } else if (this.activeTool === 'select') {
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
            }
            // Don't deselect here - let handleClick handle connection selection and deselection
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
        // Handle connection line selection (old straight connections)
        if (e.target.classList.contains('connection-line')) {
            const connectionId = e.target.dataset.connectionId;
            const connection = this.connections.get(connectionId);
            if (connection) {
                this.selectConnection(connection);
                e.stopPropagation();
            }
            return;
        }
        
        // Handle curved line selection (new SVG paths)
        if (e.target.tagName === 'path' && e.target.id.startsWith('line_')) {
            const lineId = e.target.id;
            const line = this.activeLines.get(lineId);
            if (line) {
                this.selectLine(line);
                e.stopPropagation();
            }
            return;
        }
        
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
            // Don't deselect if we just finished drawing a line
            if (!this.justFinishedLine) {
                this.deselectAll();
            }
        }
    }
    
    handleKeyDown(e) {
        console.log('🔑 Key pressed:', e.key);
        console.log('🔑 Selected element:', this.selectedElement ? this.selectedElement.id : 'none');
        console.log('🔑 Selected line:', this.selectedLine ? this.selectedLine.id : 'none');
        
        if (e.key === 'Delete') {
            if (this.selectedElement) {
                console.log('🗑️ Deleting selected element:', this.selectedElement.id);
                this.deleteElement(this.selectedElement);
            } else if (this.selectedLine) {
                console.log('🗑️ Deleting selected line:', this.selectedLine.id);
                this.deleteSelectedLine();
            } else {
                console.log('⚠️ Delete pressed but nothing selected');
            }
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
    
    // ===== CURVED LINE SYSTEM =====
    
    // Test function to verify drag handles work
    testDragHandles() {
        console.log('🧪 Testing drag handles...');
        
        // Check if we have any active lines
        if (this.activeLines.size === 0) {
            console.log('⚠️ No active lines to test');
            return;
        }
        
        // Get first line
        const firstLine = Array.from(this.activeLines.values())[0];
        console.log('🔍 Testing line:', firstLine.id);
        
        // Check if handles exist in DOM
        const controlHandle = document.getElementById(firstLine.id + '_handle');
        const startHandle = document.getElementById(firstLine.id + '_start_handle');
        const endHandle = document.getElementById(firstLine.id + '_end_handle');
        
        console.log('🔍 Handle existence check:');
        console.log('  - Control handle:', !!controlHandle);
        console.log('  - Start handle:', !!startHandle);
        console.log('  - End handle:', !!endHandle);
        
        if (controlHandle) {
            console.log('🔍 Control handle details:');
            console.log('  - ID:', controlHandle.id);
            console.log('  - CX:', controlHandle.getAttribute('cx'));
            console.log('  - CY:', controlHandle.getAttribute('cy'));
            console.log('  - Cursor style:', controlHandle.style.cursor);
            console.log('  - Pointer events:', controlHandle.style.pointerEvents);
            
            // Try to trigger a click programmatically to test event listeners
            console.log('🧪 Simulating click on control handle...');
            controlHandle.dispatchEvent(new MouseEvent('mousedown', {
                bubbles: true,
                cancelable: true,
                clientX: 100,
                clientY: 100
            }));
        }
    }
    
    createCurvedLine(startX, startY, endX, endY) {
        const lineId = `line_${this.nextLineId++}`;
        
        // Calculate default control point for curve (midpoint offset)
        const midX = (startX + endX) / 2;
        const midY = (startY + endY) / 2;
        
        // Offset control point perpendicular to the line
        const dx = endX - startX;
        const dy = endY - startY;
        const length = Math.sqrt(dx * dx + dy * dy);
        const perpX = -dy / length * 50; // 50px offset
        const perpY = dx / length * 50;
        
        const controlX = midX + perpX;
        const controlY = midY + perpY;
        
        const line = {
            id: lineId,
            startX,
            startY,
            endX,
            endY,
            controlX,
            controlY,
            selected: false
        };
        
        this.activeLines.set(lineId, line);
        this.renderCurvedLine(line);
        
        return line;
    }
    
    renderCurvedLine(line) {
        console.log('🎨 renderCurvedLine called for:', line.id, 'selected:', line.selected);
        
        // Remove existing line if it exists
        const existingPath = document.getElementById(line.id);
        if (existingPath) {
            existingPath.remove();
        }
        
        // Remove existing handles if they exist
        const existingHandle = document.getElementById(line.id + '_handle');
        if (existingHandle) {
            existingHandle.remove();
        }
        
        const existingStartHandle = document.getElementById(line.id + '_start_handle');
        if (existingStartHandle) {
            existingStartHandle.remove();
        }
        
        const existingEndHandle = document.getElementById(line.id + '_end_handle');
        if (existingEndHandle) {
            existingEndHandle.remove();
        }
        
        // Create curved SVG path
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.id = line.id;
        path.setAttribute('d', `M ${line.startX} ${line.startY} Q ${line.controlX} ${line.controlY} ${line.endX} ${line.endY}`);
        path.setAttribute('stroke', line.selected ? '#3b82f6' : '#6b7280');
        path.setAttribute('stroke-width', line.selected ? '3' : '2');
        path.setAttribute('fill', 'none');
        path.style.cursor = 'pointer';
        path.style.pointerEvents = 'stroke'; // Make sure the stroke is clickable
        
        // Add arrow markers based on direction (ensure line has direction)
        if (!line.direction) {
            line.direction = 'to'; // Default fallback
            console.log(`⚠️ Line ${line.id} had no direction, set to default 'to'`);
        }
        
        // Use unique arrow ID with timestamp to bypass browser cache (same as regular connections)
        const uniqueArrowId = `curved_arrow_${line.id}_${Date.now()}`;
        this.createArrowMarker(uniqueArrowId, true); // Force recreate markers
        
        console.log(`🎯 renderCurvedLine: Setting markers for ${line.id} with direction: ${line.direction}`);
        console.log(`🎯 Using unique arrow ID: ${uniqueArrowId}`);
        
        // Clear existing markers first
        path.removeAttribute('marker-start');
        path.removeAttribute('marker-end');
        
        if (line.direction === 'to' || line.direction === 'both') {
            path.setAttribute('marker-end', `url(#${uniqueArrowId}_end)`);
            console.log(`  - Added marker-end: url(#${uniqueArrowId}_end)`);
        }
        if (line.direction === 'from' || line.direction === 'both') {
            path.setAttribute('marker-start', `url(#${uniqueArrowId}_start)`);
            console.log(`  - Added marker-start: url(#${uniqueArrowId}_start)`);
        }
        
        // Add click handler for line selection
        path.addEventListener('click', (e) => {
            console.log('🔍 Path clicked:', line.id);
            e.stopPropagation();
            this.selectLine(line);
        });
        
        this.svg.appendChild(path);
        
        // Create drag handle at control point if line is selected
        if (line.selected) {
            this.createLineDragHandle(line);
            this.createLineEndpointHandles(line);
        }
    }
    
    createLineDragHandle(line) {
        const handle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        handle.id = line.id + '_handle';
        handle.setAttribute('cx', line.controlX);
        handle.setAttribute('cy', line.controlY);
        handle.setAttribute('r', '6');
        handle.setAttribute('fill', '#3b82f6'); // Blue for control point
        handle.setAttribute('stroke', '#ffffff');
        handle.setAttribute('stroke-width', '2');
        handle.style.cursor = 'move';
        handle.style.pointerEvents = 'all'; // Ensure it receives mouse events
        handle.style.zIndex = '1000'; // Bring to front
        
        let isDragging = false;
        
        handle.addEventListener('mousedown', (e) => {
            console.log('🔍 Control handle mousedown triggered for:', line.id);
            console.log('🔍 Event details:', {
                clientX: e.clientX,
                clientY: e.clientY,
                target: e.target.id,
                button: e.button
            });
            e.preventDefault();
            e.stopPropagation();
            isDragging = true;
            
            const handleMouseMove = (e) => {
                if (!isDragging) return;
                
                const rect = this.canvas.getBoundingClientRect();
                const scrollLeft = this.canvas.scrollLeft || 0;
                const scrollTop = this.canvas.scrollTop || 0;
                const x = (e.clientX - rect.left + scrollLeft) / this.zoomLevel;
                const y = (e.clientY - rect.top + scrollTop) / this.zoomLevel;
                
                console.log('🔍 Control handle dragging to:', x, y);
                
                // Update control point
                line.controlX = x;
                line.controlY = y;
                
                // Re-render line
                this.renderCurvedLine(line);
            };
            
            const handleMouseUp = () => {
                console.log('🔍 Control handle drag ended');
                isDragging = false;
                document.removeEventListener('mousemove', handleMouseMove);
                document.removeEventListener('mouseup', handleMouseUp);
            };
            
            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', handleMouseUp);
        });
        
        this.svg.appendChild(handle);
        console.log('🔍 Control handle appended to SVG, ID:', handle.id);
        console.log('🔍 Handle position:', handle.getAttribute('cx'), handle.getAttribute('cy'));
        console.log('🔍 Handle in DOM:', document.getElementById(handle.id) !== null);
    }
    
    createLineEndpointHandles(line) {
        // Create start point handle
        const startHandle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        startHandle.id = line.id + '_start_handle';
        startHandle.setAttribute('cx', line.startX);
        startHandle.setAttribute('cy', line.startY);
        startHandle.setAttribute('r', '5');
        startHandle.setAttribute('fill', '#10b981'); // Green for start
        startHandle.setAttribute('stroke', '#ffffff');
        startHandle.setAttribute('stroke-width', '2');
        startHandle.style.cursor = 'move';
        startHandle.style.pointerEvents = 'all'; // Ensure it receives mouse events
        startHandle.style.zIndex = '1000'; // Bring to front
        
        let isDraggingStart = false;
        
        startHandle.addEventListener('mousedown', (e) => {
            console.log('🔍 Start handle mousedown triggered for:', line.id);
            console.log('🔍 Event details:', {
                clientX: e.clientX,
                clientY: e.clientY,
                target: e.target.id,
                button: e.button
            });
            e.preventDefault();
            e.stopPropagation();
            isDraggingStart = true;
            
            const handleMouseMove = (e) => {
                if (!isDraggingStart) return;
                
                const rect = this.canvas.getBoundingClientRect();
                const scrollLeft = this.canvas.scrollLeft || 0;
                const scrollTop = this.canvas.scrollTop || 0;
                const x = (e.clientX - rect.left + scrollLeft) / this.zoomLevel;
                const y = (e.clientY - rect.top + scrollTop) / this.zoomLevel;
                
                console.log('🔍 Start handle dragging to:', x, y);
                
                // Update start point
                line.startX = x;
                line.startY = y;
                
                // Re-render line
                this.renderCurvedLine(line);
            };
            
            const handleMouseUp = () => {
                console.log('🔍 Start handle drag ended');
                isDraggingStart = false;
                document.removeEventListener('mousemove', handleMouseMove);
                document.removeEventListener('mouseup', handleMouseUp);
            };
            
            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', handleMouseUp);
        });
        
        this.svg.appendChild(startHandle);
        console.log('🔍 Start handle appended to SVG, ID:', startHandle.id);
        
        // Create end point handle
        const endHandle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        endHandle.id = line.id + '_end_handle';
        endHandle.setAttribute('cx', line.endX);
        endHandle.setAttribute('cy', line.endY);
        endHandle.setAttribute('r', '5');
        endHandle.setAttribute('fill', '#ef4444'); // Red for end
        endHandle.setAttribute('stroke', '#ffffff');
        endHandle.setAttribute('stroke-width', '2');
        endHandle.style.cursor = 'move';
        endHandle.style.pointerEvents = 'all'; // Ensure it receives mouse events
        endHandle.style.zIndex = '1000'; // Bring to front
        
        let isDraggingEnd = false;
        
        endHandle.addEventListener('mousedown', (e) => {
            console.log('🔍 End handle mousedown triggered for:', line.id);
            console.log('🔍 Event details:', {
                clientX: e.clientX,
                clientY: e.clientY,
                target: e.target.id,
                button: e.button
            });
            e.preventDefault();
            e.stopPropagation();
            isDraggingEnd = true;
            
            const handleMouseMove = (e) => {
                if (!isDraggingEnd) return;
                
                const rect = this.canvas.getBoundingClientRect();
                const scrollLeft = this.canvas.scrollLeft || 0;
                const scrollTop = this.canvas.scrollTop || 0;
                const x = (e.clientX - rect.left + scrollLeft) / this.zoomLevel;
                const y = (e.clientY - rect.top + scrollTop) / this.zoomLevel;
                
                console.log('🔍 End handle dragging to:', x, y);
                
                // Update end point
                line.endX = x;
                line.endY = y;
                
                // Re-render line
                this.renderCurvedLine(line);
            };
            
            const handleMouseUp = () => {
                console.log('🔍 End handle drag ended');
                isDraggingEnd = false;
                document.removeEventListener('mousemove', handleMouseMove);
                document.removeEventListener('mouseup', handleMouseUp);
            };
            
            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', handleMouseUp);
        });
        
        this.svg.appendChild(endHandle);
        console.log('🔍 End handle appended to SVG, ID:', endHandle.id);
    }
    
    selectLine(line) {
        console.log('🎯 selectLine called for:', line.id);
        
        // Deselect all elements first
        this.deselectAll();
        
        // Deselect previous line
        if (this.selectedLine && this.selectedLine !== line) {
            console.log('🔄 Deselecting previous line:', this.selectedLine.id);
            this.selectedLine.selected = false;
            this.renderCurvedLine(this.selectedLine);
        }
        
        // Select new line
        this.selectedLine = line;
        line.selected = true;
        
        // Set as selectedElement for property panel compatibility
        // IMPORTANT: Don't use spread operator - we need the same reference!
        line.type = 'curved-connection'; // Temporarily add type for property panel
        this.selectedElement = line;
        
        console.log('✅ Line selected, calling renderCurvedLine');
        this.renderCurvedLine(line);
        
        // Show property panel
        this.showPropertyPanel(this.selectedElement);
        
        console.log('✅ Line selected and property panel should be visible:', line.id);
    }
    
    deselectAllLines() {
        if (this.selectedLine) {
            this.selectedLine.selected = false;
            this.renderCurvedLine(this.selectedLine);
            this.selectedLine = null;
        }
    }
    
    deleteSelectedLine() {
        console.log('🗑️ deleteSelectedLine called');
        console.log('🗑️ this.selectedLine:', this.selectedLine);
        
        if (!this.selectedLine) {
            console.log('❌ No selected line to delete');
            return;
        }
        
        const line = this.selectedLine;
        console.log('🗑️ Deleting curved line:', line.id);
        
        // Remove SVG elements
        const path = document.getElementById(line.id);
        if (path) {
            path.remove();
            console.log('✅ Removed path element:', line.id);
        } else {
            console.log('⚠️ Path element not found:', line.id);
        }
        
        const handle = document.getElementById(line.id + '_handle');
        if (handle) {
            handle.remove();
            console.log('✅ Removed handle element');
        }
        
        const startHandle = document.getElementById(line.id + '_start_handle');
        if (startHandle) {
            startHandle.remove();
            console.log('✅ Removed start handle element');
        }
        
        const endHandle = document.getElementById(line.id + '_end_handle');
        if (endHandle) {
            endHandle.remove();
            console.log('✅ Removed end handle element');
        }
        
        // Remove from collection
        this.activeLines.delete(line.id);
        console.log('✅ Removed from activeLines collection');
        
        // Clear selection
        this.selectedLine = null;
        this.selectedElement = null; // Also clear selectedElement since curved lines set this too
        
        // Hide property panel
        this.hidePropertyPanel();
        
        // Save the updated diagram
        this.saveToMermaidCode();
        
        console.log('✅ Deleted curved line:', line.id);
    }
    
    // ===== END CURVED LINE SYSTEM =====

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
            case 'line':
                this.canvas.style.cursor = 'crosshair';
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
        
        console.log('🔧 Active tool set to:', tool);
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
            z-index: 10 !important;
            pointer-events: all !important;
            cursor: move !important;
            ${node.elementType !== 'decision' ? 'transform: none !important;' : ''}
        `;
        
        // Apply custom background color if different from default
        const defaultColor = this.getDefaultColor(node.elementType);
        if (node.color && node.color !== defaultColor) {
            element.style.backgroundColor = node.color;
        }
        
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
        element.style.setProperty('z-index', '10', 'important');
        element.style.setProperty('pointer-events', 'all', 'important');
        element.style.setProperty('cursor', 'move', 'important');
        
        // Only set background color if it's different from default (allow CSS classes to handle default colors)
        const defaultColor = this.getDefaultColor(node.elementType);
        if (node.color && node.color !== defaultColor) {
            element.style.setProperty('background-color', node.color, 'important');
        }
        
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
        
        // Auto-switch back to select tool after finishing connection
        this.setActiveTool('select');
        console.log('🔄 Automatically switched to select tool after finishing connection drawing');
    }
    
    finishConnectionAtPoint(toNode, toSide) {
        console.log(`Finishing connection to ${toNode.id} side ${toSide}`);
        
        if (this.connectingFromNode && this.connectingFromNode !== toNode) {
            this.createConnectionBetweenPoints(this.connectingFromNode, this.connectingFromSide, toNode, toSide);
        }
        
        this.cancelConnectionDrawing();
        
        // Auto-switch back to select tool after finishing connection
        this.setActiveTool('select');
        console.log('🔄 Automatically switched to select tool after finishing connection');
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
        // Skip if already updating to prevent infinite loops
        if (connection._isUpdatingDirection) {
            console.log('⏸️ Skipping direction update - already in progress for:', connection.id);
            return;
        }
        
        connection._isUpdatingDirection = true;
        
        try {
            console.log(`🔄 Updating connection ${connection.id} direction to: ${connection.direction}`);
            
            // Find the path element - either from pathElement property or by ID
            let pathElement = connection.pathElement;
            if (!pathElement) {
                pathElement = document.getElementById(`path_${connection.id}`);
                if (!pathElement) {
                    console.warn('⚠️ No path element found for connection:', connection.id);
                    return;
                }
            }

            const arrowId = `arrow_${connection.id}`;
            
            // Get node references - handle both structures
            let fromNode = connection.fromNode;
            let toNode = connection.toNode;
            
            if (!fromNode || !toNode) {
                // Try to get from nodes collection using from/to IDs
                fromNode = this.nodes.get(connection.from);
                toNode = this.nodes.get(connection.to);
            }
            
            if (!fromNode || !toNode) {
                console.warn('⚠️ Missing nodes for connection:', connection.id);
                return;
            }
            
            // CRITICAL: Recalculate edge-to-edge points to ensure arrows are at element edges
            let edgePoints = this.calculateEdgeToEdgePoints(fromNode, toNode);
            
            // IMPORTANT: If direction is 'from', we need to swap the path direction
            // so the visual path matches the conceptual direction
            if (connection.direction === 'from') {
                // Swap the edge points so path goes from toNode to fromNode
                const temp = edgePoints.from;
                edgePoints.from = edgePoints.to;
                edgePoints.to = temp;
                console.log(`🔄 SWAPPED path direction for 'from' - now goes from ${toNode.id} to ${fromNode.id}`);
            }
            
            console.log(`🎯 Final edge points for direction '${connection.direction}':`, {
                from: edgePoints.from,
                to: edgePoints.to,
                conceptualDirection: connection.direction
            });
            
            // CREATE UNIQUE MARKER ID to bypass SVG cache
            const timestamp = Date.now();
            const uniqueArrowId = `arrow_${connection.id}_${timestamp}`;
            console.log(`🆔 Creating unique marker ID: ${uniqueArrowId}`);
            
            // AGGRESSIVE APPROACH: Remove the old path and create a new one
            const parentElement = pathElement.parentNode;
            const wasSelected = pathElement.classList.contains('selected');
            const pathId = pathElement.id;
            const pathClass = pathElement.getAttribute('class') || '';
            const pathStroke = pathElement.getAttribute('stroke') || '#333333';
            const pathStrokeWidth = pathElement.getAttribute('stroke-width') || '2';
            const pathFill = pathElement.getAttribute('fill') || 'none';
            const connectionId = pathElement.getAttribute('data-connection-id');
            
            // Remove the old path completely
            pathElement.remove();
            console.log(`🗑️ Completely removed old path element for ${connection.id}`);
            
            // Create a brand new path element
            const newPathElement = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            newPathElement.setAttribute('id', pathId);
            newPathElement.setAttribute('class', pathClass);
            newPathElement.setAttribute('stroke', pathStroke);
            newPathElement.setAttribute('stroke-width', pathStrokeWidth);
            newPathElement.setAttribute('fill', pathFill);
            
            // CRITICAL: Restore the connection attributes needed for click detection
            if (connectionId) {
                newPathElement.setAttribute('data-connection-id', connectionId);
            }
            
            // Ensure proper pointer events for click detection
            newPathElement.style.pointerEvents = 'stroke';
            
            // Set the path data
            const newPath = this.getConnectionPath(edgePoints.from.x, edgePoints.from.y, edgePoints.to.x, edgePoints.to.y);
            newPathElement.setAttribute('d', newPath);
            
            // CRITICAL: Explicitly clear ALL marker attributes before setting new ones
            newPathElement.removeAttribute('marker-start');
            newPathElement.removeAttribute('marker-end');
            newPathElement.removeAttribute('marker-mid');
            console.log(`🧹 Cleared all marker attributes from new path element`);
            
            // AGGRESSIVE: Remove existing markers to prevent cache issues
            const existingEndMarker = document.getElementById(`${arrowId}_end`);
            const existingStartMarker = document.getElementById(`${arrowId}_start`);
            if (existingEndMarker) {
                existingEndMarker.remove();
                console.log(`🗑️ Removed existing end marker: ${arrowId}_end`);
            }
            if (existingStartMarker) {
                existingStartMarker.remove();
                console.log(`🗑️ Removed existing start marker: ${arrowId}_start`);
            }
            
            // Create new unique markers
            this.createArrowMarker(uniqueArrowId, true); // Force recreate markers
            
            // Apply direction-specific markers to the NEW path element using UNIQUE IDs
            // Note: After swapping path direction for 'from', we always use marker-end for the arrow
            if (connection.direction === 'to') {
                newPathElement.setAttribute('marker-end', `url(#${uniqueArrowId}_end)`);
                console.log(`✅ NEW PATH: Added end marker for 'to' direction: url(#${uniqueArrowId}_end)`);
            } else if (connection.direction === 'from') {
                // Path is now swapped, so we still use marker-end (which is now at the target)
                newPathElement.setAttribute('marker-end', `url(#${uniqueArrowId}_end)`);
                console.log(`✅ NEW PATH: Added end marker for 'from' direction (swapped path): url(#${uniqueArrowId}_end)`);
            } else if (connection.direction === 'both') {
                newPathElement.setAttribute('marker-end', `url(#${uniqueArrowId}_end)`);
                newPathElement.setAttribute('marker-start', `url(#${uniqueArrowId}_start)`);
                console.log(`✅ NEW PATH: Added both markers for 'both' direction: end=${uniqueArrowId}_end, start=${uniqueArrowId}_start`);
            }
            
            // Add the new path to the SVG
            parentElement.appendChild(newPathElement);
            
            // Restore selection state if it was selected
            if (wasSelected) {
                newPathElement.classList.add('selected');
            }
            
            // Update the connection object to reference the new path element
            connection.pathElement = newPathElement;
            
            // Update in connections collection
            if (this.connections.has(connection.id)) {
                const storedConnection = this.connections.get(connection.id);
                storedConnection.direction = connection.direction;
                storedConnection.pathElement = newPathElement;
                console.log(`🔄 Updated direction and path reference in connections collection for ${connection.id}`);
            }
            
            // Update in activeLines collection if it exists there
            if (this.activeLines.has(connection.id)) {
                const storedConnection = this.activeLines.get(connection.id);
                storedConnection.direction = connection.direction;
                storedConnection.pathElement = newPathElement; // Update reference
                console.log(`🔄 Updated direction and path reference in activeLines collection for ${connection.id}`);
            }
            
            console.log(`✅ COMPLETELY RECREATED connection ${connection.id} with direction: ${connection.direction}`);
            
            // FORCE browser reflow to ensure immediate visual update
            newPathElement.getBoundingClientRect();
            console.log(`🔄 Forced browser reflow for immediate visual update`);
        } finally {
            connection._isUpdatingDirection = false;
        }
    }    redrawConnections() {
        // Skip redraw if we're already in the middle of one to prevent infinite loops
        if (this._isRedrawingConnections) {
            console.log('⏸️ Skipping redrawConnections - already in progress');
            return;
        }
        
        this._isRedrawingConnections = true;
        
        try {
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
                        
                        // Only update the path, don't recreate the entire connection unless needed
                        const newPath = this.getConnectionPath(fromX, fromY, toX, toY);
                        const currentPath = connection.pathElement.getAttribute('d');
                        
                        if (newPath !== currentPath) {
                            connection.pathElement.setAttribute('d', newPath);
                        }
                        
                        // Only update direction if it actually changed to prevent infinite loops
                        const currentDirection = connection.direction || 'to';
                        if (connection._lastDirection !== currentDirection) {
                            this.updateConnectionDirection(connection);
                            connection._lastDirection = currentDirection;
                        }
                    } else {
                        console.warn('Missing node for connection:', connection.id);
                    }
                }
            });
        } finally {
            this._isRedrawingConnections = false;
        }
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
        console.log('🔗 selectConnection called with:', connection);
        console.log('🔗 Connection type:', connection.type);
        
        this.deselectAll();
        if (connection && connection.pathElement) {
            connection.pathElement.classList.add('selected');
            this.selectedElement = connection;
            this.addConnectionHandles(connection);
            
            // Show property panel for the connection
            this.showPropertyPanel(connection);
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
        
        // Deselect curved lines
        this.deselectAllLines();
        
        // Remove resize handles safely without affecting element positions
        this.canvas.querySelectorAll('.resize-handle, .connection-handle').forEach(handle => {
            const parentElement = handle.parentElement;
            
            // Store parent's current position before removing handle
            if (parentElement) {
                const currentLeft = parentElement.style.left;
                const currentTop = parentElement.style.top;
                
                // Remove the handle
                handle.remove();
                
                // Restore position if it was changed (shouldn't happen, but just in case)
                if (parentElement.style.left !== currentLeft || parentElement.style.top !== currentTop) {
                    console.log('🔧 Position changed during handle removal, restoring:', {
                        before: {left: currentLeft, top: currentTop},
                        after: {left: parentElement.style.left, top: parentElement.style.top}
                    });
                    parentElement.style.left = currentLeft;
                    parentElement.style.top = currentTop;
                }
            } else {
                // Handle doesn't have a parent (e.g., connection handles in SVG)
                handle.remove();
            }
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
        } else if (element.type === 'curved-connection') {
            console.log('🗑️ deleteElement: Handling curved connection deletion:', element.id);
            // For curved connections, use the dedicated deleteSelectedLine method
            this.selectedLine = element; // Set the selectedLine to match
            this.deleteSelectedLine();
            return; // deleteSelectedLine handles clearing selections and saving
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
            
            // Switch back to select tool after finishing text editing
            this.setActiveTool('select');
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
            
            // Switch back to select tool after finishing text editing
            this.setActiveTool('select');
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
            user: 'User',
            data: 'Data'
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
            user: '#fed7aa',
            data: '#f8d7da'
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
        const colorSelect = document.getElementById('elementColor');
        const directionSelect = document.getElementById('connectionDirection');
        const directionGroup = document.getElementById('connectionDirectionGroup');
        
        // Show/hide connection direction controls
        if (this.selectedElement.type === 'connection' || this.selectedElement.type === 'curved-connection') {
            // Hide normal element controls
            if (textInput) textInput.parentElement.style.display = 'none';
            if (colorSelect) colorSelect.parentElement.style.display = 'none';
            
            // Show connection controls
            if (directionGroup) directionGroup.style.display = 'block';
            if (directionSelect) directionSelect.value = this.selectedElement.direction || 'to';
            
            // Add a label or indicator for curved connections
            if (this.selectedElement.type === 'curved-connection') {
                console.log('📊 Property panel updated for curved connection:', this.selectedElement.id);
            }
        } else {
            // Show normal element controls
            if (textInput) {
                textInput.parentElement.style.display = 'block';
                textInput.value = this.selectedElement.text || '';
            }
            if (colorSelect) {
                colorSelect.parentElement.style.display = 'block';
                colorSelect.value = this.selectedElement.color || '#e2e8f0';
            }
            
            // Hide connection controls
            if (directionGroup) directionGroup.style.display = 'none';
        }
    }
    
    // Update selected element properties
    updateSelectedElement(properties) {
        console.log('🔧 updateSelectedElement called with:', properties);
        
        if (!this.selectedElement) {
            console.warn('⚠️ No selected element to update');
            return;
        }
        
        const element = this.selectedElement;
        console.log('🎯 Updating element:', element.id, 'type:', element.type);
        
        // Handle different element types
        if (element.type === 'connection') {
            console.log('🔗 Handling connection type update');
            // Update connection properties
            if (properties.direction && properties.direction !== element.direction) {
                console.log(`🔄 Updating connection direction from ${element.direction} to ${properties.direction}`);
                element.direction = properties.direction;
                this.updateConnectionDirection(element);
            }
        } else if (element.type === 'curved-connection') {
            // Update curved line properties (similar to connection)
            if (properties.direction && properties.direction !== element.direction) {
                console.log(`🔄 Updating curved line direction from ${element.direction} to ${properties.direction}`);
                console.log(`🔍 Element before update:`, element);
                console.log(`🔍 activeLines has this line:`, this.activeLines.has(element.id));
                
                element.direction = properties.direction;
                
                // Also update in activeLines collection if it exists there
                if (this.activeLines.has(element.id)) {
                    const storedLine = this.activeLines.get(element.id);
                    storedLine.direction = properties.direction;
                    console.log(`🔄 Updated direction in activeLines collection for ${element.id}`);
                }
                
                this.updateCurvedLineDirection(element);
                
                console.log(`🔍 Element after update:`, element);
            }
        } else {
            // Update node/text element properties
            if (properties.text !== undefined && element.text !== properties.text) {
                element.text = properties.text;
                if (element.domElement) {
                    const textSpan = element.domElement.querySelector('.element-text');
                    if (textSpan) textSpan.textContent = properties.text;
                }
            }
            
            if (properties.width && element.width !== properties.width) {
                element.width = properties.width;
                if (element.domElement) {
                    element.domElement.style.width = properties.width + 'px';
                }
            }
            
            if (properties.height && element.height !== properties.height) {
                element.height = properties.height;
                if (element.domElement) {
                    element.domElement.style.height = properties.height + 'px';
                }
            }
            
            if (properties.backgroundColor && element.color !== properties.backgroundColor) {
                element.color = properties.backgroundColor;
                if (element.domElement) {
                    element.domElement.style.backgroundColor = properties.backgroundColor;
                }
            }
            
            // Redraw connections if element size changed
            if (properties.width || properties.height) {
                this.redrawConnections();
            }
        }
        
        console.log('✅ Element updated successfully');
    }
    
    // Update curved line direction
    updateCurvedLineDirection(line) {
        console.log(`🔄 updateCurvedLineDirection called for ${line.id}:`);
        console.log(`  - Current direction: ${line.direction}`);
        console.log(`  - Line object:`, line);
        
        const path = document.getElementById(line.id);
        if (!path) {
            console.warn('⚠️ No path element found for curved line:', line.id);
            return;
        }
        
        console.log(`  - Path element found: ${path.id}`);
        console.log(`  - Current marker-start: ${path.getAttribute('marker-start')}`);
        console.log(`  - Current marker-end: ${path.getAttribute('marker-end')}`);
        
        // Remove existing arrow markers
        path.removeAttribute('marker-start');
        path.removeAttribute('marker-end');
        console.log(`  - Removed existing markers`);
        
        // Update direction in activeLines collection too
        if (this.activeLines.has(line.id)) {
            const storedLine = this.activeLines.get(line.id);
            storedLine.direction = line.direction;
            console.log(`  - Updated direction in activeLines collection`);
        }
        
        // Create unique arrow markers for this line (with timestamp to bypass cache)
        const uniqueArrowId = `curved_arrow_${line.id}_${Date.now()}`;
        this.createArrowMarker(uniqueArrowId, true); // Force recreate markers
        
        // Add new arrow markers based on direction
        if (line.direction === 'to' || line.direction === 'both') {
            path.setAttribute('marker-end', `url(#${uniqueArrowId}_end)`);
            console.log(`✅ Added end marker to curved line: url(#${uniqueArrowId}_end)`);
        }
        if (line.direction === 'from' || line.direction === 'both') {
            path.setAttribute('marker-start', `url(#${uniqueArrowId}_start)`);
            console.log(`✅ Added start marker to curved line: url(#${uniqueArrowId}_start)`);
        }
        
        // Force DOM update
        setTimeout(() => {
            console.log(`🔄 Force refresh markers for ${line.id}`);
            const currentStart = path.getAttribute('marker-start');
            const currentEnd = path.getAttribute('marker-end');
            console.log(`  - After timeout - marker-start: ${currentStart}, marker-end: ${currentEnd}`);
        }, 10);
        
        console.log(`✅ Updated curved line ${line.id} direction to: ${line.direction}`);
        console.log(`  - Final marker-start: ${path.getAttribute('marker-start')}`);
        console.log(`  - Final marker-end: ${path.getAttribute('marker-end')}`);
    }
    
    // Resize Handles
    addResizeHandles(element) {
        if (element.type !== 'node' && element.type !== 'text') return;
        
        console.log('📎 Adding resize handles to:', {
            element: element.id,
            currentPos: {x: element.x, y: element.y},
            domPos: {left: element.domElement.style.left, top: element.domElement.style.top}
        });
        
        const handles = ['nw', 'ne', 'sw', 'se'];
        handles.forEach(position => {
            const handle = document.createElement('div');
            handle.className = `resize-handle ${position}`;
            handle.dataset.position = position;
            handle.dataset.elementId = element.id;
            
            // Enhanced styling for better visibility and usability
            handle.style.cssText = `
                position: absolute;
                width: 10px;
                height: 10px;
                background: #3b82f6;
                border: 2px solid #ffffff;
                border-radius: 50%;
                cursor: ${this.getResizeCursor(position)};
                z-index: 1000;
                pointer-events: all;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
                opacity: 0.9;
                transition: all 0.15s ease;
                ${this.getResizeHandlePosition(position)}
            `;
            
            // Hover effects
            handle.addEventListener('mouseover', () => {
                handle.style.transform = 'scale(1.2)';
                handle.style.opacity = '1';
                handle.style.background = '#2563eb';
            });
            
            handle.addEventListener('mouseout', () => {
                handle.style.transform = 'scale(1)';
                handle.style.opacity = '0.9';
                handle.style.background = '#3b82f6';
            });
            
            handle.addEventListener('mousedown', (e) => {
                e.stopPropagation();
                this.startResize(element, position, e);
            });
            
            element.domElement.appendChild(handle);
        });
        
        console.log('✅ Resize handles added, final position:', {
            element: element.id,
            finalPos: {x: element.x, y: element.y},
            domPos: {left: element.domElement.style.left, top: element.domElement.style.top}
        });
    }
    
    getResizeCursor(position) {
        const cursors = {
            'nw': 'nw-resize',
            'ne': 'ne-resize',
            'sw': 'sw-resize',
            'se': 'se-resize'
        };
        return cursors[position] || 'pointer';
    }
    
    getResizeHandlePosition(position) {
        const positions = {
            'nw': 'top: -6px; left: -6px;',
            'ne': 'top: -6px; right: -6px;',
            'sw': 'bottom: -6px; left: -6px;',
            'se': 'bottom: -6px; right: -6px;'
        };
        return positions[position] || '';
    }
    
    startResize(element, position, e) {
        // If already resizing another element, stop it first
        if (this.isResizing && this.resizeElement && this.resizeElement !== element) {
            console.log('⚠️ Interrupting previous resize operation on:', this.resizeElement.id);
            this.isResizing = false;
            // Remove any existing event listeners
            document.removeEventListener('mousemove', this.currentHandleResize);
            document.removeEventListener('mouseup', this.currentStopResize);
        }
        
        this.isResizing = true;
        this.resizeElement = element;
        this.resizePosition = position;
        this.resizeStartX = e.clientX;
        this.resizeStartY = e.clientY;
        
        // Store original values from both element data and DOM styles
        this.resizeStartWidth = element.width;
        this.resizeStartHeight = element.height;
        this.resizeStartLeft = element.x;
        this.resizeStartTop = element.y;
        
        // Also get current DOM position as backup
        const computedStyle = window.getComputedStyle(element.domElement);
        const domLeft = parseInt(computedStyle.left) || element.x;
        const domTop = parseInt(computedStyle.top) || element.y;
        
        console.log('🎯 Starting resize:', {
            element: element.id,
            position,
            startSize: {width: element.width, height: element.height},
            startPos: {x: element.x, y: element.y},
            domPos: {left: domLeft, top: domTop},
            positionMismatch: domLeft !== element.x || domTop !== element.y
        });
        
        // Use DOM position if there's a mismatch (this might be the source of the problem)
        if (domLeft !== element.x || domTop !== element.y) {
            console.warn('⚠️ Position mismatch detected! Using DOM position as source of truth');
            this.resizeStartLeft = domLeft;
            this.resizeStartTop = domTop;
            element.x = domLeft;
            element.y = domTop;
        }
        
        // Throttle redraw connections to prevent excessive calls
        let redrawThrottleTimeout = null;
        
        const handleResize = (e) => {
            if (!this.isResizing) return;
            
            const deltaX = e.clientX - this.resizeStartX;
            const deltaY = e.clientY - this.resizeStartY;
            
            let newWidth = this.resizeStartWidth;
            let newHeight = this.resizeStartHeight;
            let newX = this.resizeStartLeft;
            let newY = this.resizeStartTop;
            
            // Calculate new dimensions based on resize handle position
            if (position.includes('e')) {
                newWidth = this.resizeStartWidth + deltaX;
            }
            if (position.includes('w')) {
                newWidth = this.resizeStartWidth - deltaX;
                newX = this.resizeStartLeft + deltaX;
            }
            if (position.includes('s')) {
                newHeight = this.resizeStartHeight + deltaY;
            }
            if (position.includes('n')) {
                newHeight = this.resizeStartHeight - deltaY;
                newY = this.resizeStartTop + deltaY;
            }
            
            // Apply size constraints - much more reasonable limits
            const minWidth = element.type === 'text' ? 50 : 60;
            const minHeight = element.type === 'text' ? 20 : 40;
            const maxWidth = 400;
            const maxHeight = 300;
            
            newWidth = Math.max(minWidth, Math.min(maxWidth, newWidth));
            newHeight = Math.max(minHeight, Math.min(maxHeight, newHeight));
            
            // For west/north resizes, adjust position if size was constrained
            if (position.includes('w') && newWidth === minWidth) {
                newX = this.resizeStartLeft + (this.resizeStartWidth - minWidth);
            }
            if (position.includes('n') && newHeight === minHeight) {
                newY = this.resizeStartTop + (this.resizeStartHeight - minHeight);
            }
            
            // Only snap to grid for position if we're actually moving the element (west/north resize)
            if (position.includes('w') || position.includes('n')) {
                newX = Math.round(newX / this.gridSize) * this.gridSize;
                newY = Math.round(newY / this.gridSize) * this.gridSize;
            }
            
            // Update element properties
            element.width = newWidth;
            element.height = newHeight;
            element.x = newX;
            element.y = newY;
            
            console.log('📏 Resizing:', {
                element: element.id,
                oldPos: {x: this.resizeStartLeft, y: this.resizeStartTop},
                newPos: {x: newX, y: newY},
                positionChanged: newX !== this.resizeStartLeft || newY !== this.resizeStartTop,
                delta: {x: newX - this.resizeStartLeft, y: newY - this.resizeStartTop}
            });
            
            // Update DOM element
            element.domElement.style.width = newWidth + 'px';
            element.domElement.style.height = newHeight + 'px';
            element.domElement.style.left = newX + 'px';
            element.domElement.style.top = newY + 'px';
            
            // Throttle connection redrawing to prevent excessive calls
            if (element.type === 'node' && redrawThrottleTimeout === null) {
                redrawThrottleTimeout = setTimeout(() => {
                    this.redrawConnections();
                    redrawThrottleTimeout = null;
                }, 16); // ~60fps throttling
            }
        };
        
        const stopResize = () => {
            if (!this.isResizing) return;
            
            this.isResizing = false;
            
            console.log('✅ Resize completed:', {
                element: this.resizeElement.id,
                finalSize: {width: this.resizeElement.width, height: this.resizeElement.height},
                finalPos: {x: this.resizeElement.x, y: this.resizeElement.y}
            });
            
            // Final connection redraw to ensure everything is up to date
            if (element.type === 'node') {
                this.redrawConnections();
            }
            
            // Clear throttle timeout
            if (redrawThrottleTimeout) {
                clearTimeout(redrawThrottleTimeout);
                redrawThrottleTimeout = null;
            }
            
            document.removeEventListener('mousemove', handleResize);
            document.removeEventListener('mouseup', stopResize);
            
            // Clean up resize variables and function references
            this.resizeElement = null;
            this.resizePosition = null;
            this.currentHandleResize = null;
            this.currentStopResize = null;
        };
        
        // Store function references for cleanup
        this.currentHandleResize = handleResize;
        this.currentStopResize = stopResize;
        
        document.addEventListener('mousemove', handleResize);
        document.addEventListener('mouseup', stopResize);
        
        e.preventDefault();
        e.stopPropagation();
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
        this.freeLines.clear(); // Clear free-form lines
        this.selectedElement = null;
        this.nextNodeId = 1;
        this.nextNoteId = 1;
        this.nextLineId = 1; // Reset line counter
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
            const curvedLines = []; // Store curved lines data
            
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
                    } else if (metaData.startsWith('CURVED_LINE:')) {
                        const parts = metaData.split(':');
                        if (parts.length >= 8) {
                            const curvedLineData = {
                                id: parts[1],
                                startX: parseFloat(parts[2]),
                                startY: parseFloat(parts[3]),
                                endX: parseFloat(parts[4]),
                                endY: parseFloat(parts[5]),
                                controlX: parseFloat(parts[6]),
                                controlY: parseFloat(parts[7]),
                                direction: parts[8] || 'to'
                            };
                            curvedLines.push(curvedLineData);
                            console.log('🎨 Parsed curved line metadata:', curvedLineData);
                        }
                    } else if (metaData.includes('_direction:')) {
                        // Parse connection direction metadata
                        const [connId, direction] = metaData.split('_direction:').map(s => s.trim());
                        if (connId && direction) {
                            // Remove "connection_" prefix if present to match lookup format
                            const cleanConnId = connId.startsWith('connection_') ? connId.substring('connection_'.length) : connId;
                            connectionDirections.set(cleanConnId, direction);
                            console.log('🔗 Parsed connection direction:', cleanConnId, direction);
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
            
            // Recreate curved lines
            console.log('🎨 Recreating curved lines');
            for (const curvedLineData of curvedLines) {
                console.log(`🎨 Recreating curved line: ${curvedLineData.id}`);
                
                const line = this.createCurvedLine(
                    curvedLineData.startX,
                    curvedLineData.startY,
                    curvedLineData.endX,
                    curvedLineData.endY
                );
                
                // Update the line with saved data
                line.controlX = curvedLineData.controlX;
                line.controlY = curvedLineData.controlY;
                line.direction = curvedLineData.direction;
                
                // Re-render with correct data
                this.renderCurvedLine(line);
                
                console.log(`✅ Recreated curved line: ${line.id} with direction: ${line.direction}`);
            }
            
            console.log('=== LOADING COMPLETE ===');
            console.log(`Final counts: ${this.nodes.size} nodes, ${this.textNotes.size} notes, ${this.connections.size} connections, ${this.activeLines.size} curved lines`);
            
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
        
        // DEBUG: Check if element is actually in DOM
        const inDOM = document.contains(domElement);
        const parentElement = domElement.parentElement;
        const computedBefore = window.getComputedStyle(domElement);
        console.log(`🔍 DOM STATUS:`, {
            inDOM,
            parentElement: parentElement?.tagName || 'none',
            parentId: parentElement?.id || 'none',
            parentClass: parentElement?.className || 'none',
            currentLeft: computedBefore.left,
            currentTop: computedBefore.top,
            display: computedBefore.display,
            position: computedBefore.position,
            elementInContainer: this.container?.contains(domElement) || 'container not found',
            containerExists: !!this.container,
            containerInDOM: this.container ? document.body.contains(this.container) : 'no container'
        });
        
        // CRITICAL FIX: If element is detached from DOM, reattach it
        if (!parentElement || !inDOM) {
            console.log('🚨 DETACHED ELEMENT DETECTED - REATTACHING');
            if (this.container && document.body.contains(this.container)) {
                this.container.appendChild(domElement);
                console.log('✅ Element reattached to container');
            } else {
                console.log('❌ Container not available for reattachment');
                return; // Don't try to position a detached element
            }
        }
        
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
        
        // 8. NUCLEAR OPTION: If computed styles don't match visual position, use transform
        const afterStyles = window.getComputedStyle(domElement);
        const clientRect = domElement.getBoundingClientRect();
        const containerRect = this.container.getBoundingClientRect();
        
        // Calculate where the element actually is relative to container
        const actualX = clientRect.left - containerRect.left;
        const actualY = clientRect.top - containerRect.top;
        const expectedX = element.x;
        const expectedY = element.y;
        
        // If there's a significant difference, use transform to force correct position
        const deltaX = expectedX - actualX;
        const deltaY = expectedY - actualY;
        
        console.log(`🎯 POSITION ANALYSIS:`, {
            expected: `(${expectedX}, ${expectedY})`,
            actual: `(${actualX}, ${actualY})`,
            delta: `(${deltaX}, ${deltaY})`,
            threshold: 'abs > 1px'
        });
        
        if (Math.abs(deltaX) > 1 || Math.abs(deltaY) > 1) {
            console.log(`🚨 POSITION MISMATCH DETECTED - Using transform override`);
            console.log(`   Expected: (${expectedX}, ${expectedY})`);
            console.log(`   Actual: (${actualX}, ${actualY})`);
            console.log(`   Delta: (${deltaX}, ${deltaY})`);
            
            // For decision elements, combine rotation with translation
            let transformValue;
            if (element.elementType === 'decision') {
                transformValue = `rotate(45deg) translate(${deltaX}px, ${deltaY}px)`;
                console.log(`✅ Applied decision transform: ${transformValue}`);
            } else {
                transformValue = `translate(${deltaX}px, ${deltaY}px)`;
                console.log(`✅ Applied transform: ${transformValue}`);
            }
            
            // Use transform to force correct visual position
            domElement.style.setProperty('transform', transformValue, 'important');
            domElement.style.setProperty('will-change', 'transform', 'important');
            
            // Force immediate update
            domElement.offsetHeight;
        } else {
            // For decision elements, preserve the rotation while removing position transform
            if (element.elementType === 'decision') {
                if (domElement.style.transform && domElement.style.transform.includes('translate')) {
                    // Keep rotation, remove only translate
                    domElement.style.setProperty('transform', 'rotate(45deg)', 'important');
                    console.log(`✅ Preserved decision rotation, removed position transform`);
                } else if (!domElement.style.transform || domElement.style.transform === 'none') {
                    // Ensure decision elements have rotation
                    domElement.style.setProperty('transform', 'rotate(45deg)', 'important');
                    console.log(`✅ Applied decision rotation`);
                }
            } else {
                // Remove transform for non-decision elements if position is correct
                if (domElement.style.transform && domElement.style.transform !== 'none') {
                    domElement.style.removeProperty('transform');
                    domElement.style.removeProperty('will-change');
                    console.log(`✅ Removed transform - position is correct`);
                }
            }
        }
        
        // DEBUG: Verify the styles were actually applied
        const computedAfter = window.getComputedStyle(domElement);
        
        // Check for conflicting CSS rules
        const allStyleSheets = Array.from(document.styleSheets);
        let conflictingRules = [];
        
        try {
            allStyleSheets.forEach(sheet => {
                try {
                    Array.from(sheet.cssRules || sheet.rules || []).forEach(rule => {
                        if (rule.selectorText && domElement.matches && domElement.matches(rule.selectorText)) {
                            const ruleStyle = rule.style;
                            if (ruleStyle.left || ruleStyle.top || ruleStyle.position) {
                                conflictingRules.push({
                                    selector: rule.selectorText,
                                    left: ruleStyle.left,
                                    top: ruleStyle.top,
                                    position: ruleStyle.position,
                                    important: {
                                        left: ruleStyle.getPropertyPriority('left'),
                                        top: ruleStyle.getPropertyPriority('top'),
                                        position: ruleStyle.getPropertyPriority('position')
                                    }
                                });
                            }
                        }
                    });
                } catch (e) {
                    // Cross-origin or other access issues
                }
            });
        } catch (e) {
            console.log('🚫 Could not analyze stylesheets:', e.message);
        }
        
        console.log(`🔒 AFTER LOCK:`, {
            elementId: element.id,
            styleLeft: domElement.style.left,
            styleTop: domElement.style.top,
            computedLeft: computedAfter.left,
            computedTop: computedAfter.top,
            position: computedAfter.position,
            display: computedAfter.display,
            transform: computedAfter.transform,
            offsetParent: domElement.offsetParent?.tagName,
            clientRect: domElement.getBoundingClientRect(),
            stylePriorities: {
                leftPriority: domElement.style.getPropertyPriority('left'),
                topPriority: domElement.style.getPropertyPriority('top'),
                positionPriority: domElement.style.getPropertyPriority('position')
            },
            conflictingRules: conflictingRules.length > 0 ? conflictingRules : 'none'
        });
        
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
                    // Force style update (don't override background-color to allow CSS classes)
                    const defaultColor = this.getDefaultColor(node.elementType);
                    const backgroundStyle = (node.color && node.color !== defaultColor) ? 
                        `background-color: ${node.color} !important;` : '';
                    
                    element.style.cssText = `
                        position: absolute !important;
                        left: ${node.x}px !important;
                        top: ${node.y}px !important;
                        width: ${node.width}px !important;
                        height: ${node.height}px !important;
                        ${backgroundStyle}
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
                    case 'data':
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
            
            console.log('🔄 SAVE DEBUG: Processing curved lines (activeLines)...');
            console.log('🔄 SAVE DEBUG: ActiveLines count:', this.activeLines.size);
            
            // Add curved lines metadata
            this.activeLines.forEach((line, id) => {
                console.log(`🔄 SAVE DEBUG: Processing curved line ${id}:`, line);
                
                // Save curved line data as metadata comment
                metadataComments += `%% CURVED_LINE:${id}:${line.startX}:${line.startY}:${line.endX}:${line.endY}:${line.controlX}:${line.controlY}:${line.direction || 'to'}\n`;
                
                console.log(`✅ SAVE DEBUG: Added curved line metadata for ${id}`);
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
                
                // Apply ultra-forced positioning (don't override background-color to allow CSS classes)
                const defaultColor = this.getDefaultColor(nodeData.type);
                const backgroundStyle = (nodeData.color && nodeData.color !== defaultColor) ? 
                    `background-color: ${nodeData.color} !important;` : '';
                
                element.style.cssText = `
                    position: absolute !important;
                    left: ${position.x}px !important;
                    top: ${position.y}px !important;
                    width: ${position.width}px !important;
                    height: ${position.height}px !important;
                    ${backgroundStyle}
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

    // Line Tool Methods
    handleLineToolMouseDown(e, x, y) {
        if (this.isDrawingLine) {
            // If already drawing, finish the current line (priority over everything else)
            console.log('🖊️ Finishing line drawing on click');
            this.finishLineDrawing(x, y);
            e.preventDefault();
            e.stopPropagation();
            return;
        }
        
        // Only check for existing elements when NOT drawing
        const clickedElement = this.getElementAt(x, y);
        if (clickedElement) {
            // Clicked on an element - select it instead of drawing line
            console.log('🎯 Clicked on element, switching to select tool');
            this.selectElement(clickedElement);
            this.setActiveTool('select'); // Switch back to select tool
            return;
        }
        
        // Check if we clicked on an existing curved line
        if (e.target.tagName === 'path' && e.target.id.startsWith('line_')) {
            // Clicked on a curved line - don't start drawing
            console.log('🎯 Clicked on existing curved line');
            return;
        }
        
        // Deselect any currently selected elements before starting new line
        this.deselectAll();
        
        // Start drawing a new line
        console.log('🖊️ Starting new line drawing');
        this.startLineDrawing(x, y);
        e.preventDefault();
        e.stopPropagation();
    }

    startLineDrawing(x, y) {
        console.log('🖊️ Starting curved line drawing at:', x, y);
        
        this.isDrawingLine = true;
        
        // Create a new curved line object
        const lineId = `line_${this.nextLineId++}`;
        const line = {
            id: lineId,
            type: 'curved_line',
            startX: x,
            startY: y,
            endX: x,
            endY: y,
            controlX: x, // Will be updated during drawing
            controlY: y,
            selected: false,
            direction: 'to' // Default direction
        };
        
        // Store current line
        this.currentLine = line;
        
        console.log('✅ Curved line drawing started:', lineId);
    }

    finishLineDrawing(x, y) {
        if (!this.currentLine) {
            console.log('❌ finishLineDrawing called but no currentLine exists');
            return;
        }
        
        console.log('🖊️ Finishing curved line drawing at:', x, y);
        console.log('📍 Current line before finish:', this.currentLine);
        
        // Update final position
        this.currentLine.endX = x;
        this.currentLine.endY = y;
        
        // Finalize control point
        const midX = (this.currentLine.startX + x) / 2;
        const midY = (this.currentLine.startY + y) / 2;
        
        const dx = x - this.currentLine.startX;
        const dy = y - this.currentLine.startY;
        const length = Math.sqrt(dx * dx + dy * dy);
        
        if (length > 0) {
            const perpX = -dy / length * 50; // Final curve amount
            const perpY = dx / length * 50;
            
            this.currentLine.controlX = midX + perpX;
            this.currentLine.controlY = midY + perpY;
        }
        
        // Add to active lines collection
        this.activeLines.set(this.currentLine.id, this.currentLine);
        
        // Render final curved line
        this.renderCurvedLine(this.currentLine);
        
        // Clear drawing state
        const finishedLine = this.currentLine;
        this.isDrawingLine = false;
        this.currentLine = null;
        
        // Auto-switch back to select tool after finishing line drawing (with small delay)
        setTimeout(() => {
            console.log('🔄 Before tool switch - current tool:', this.activeTool);
            this.setActiveTool('select');
            console.log('🔄 After tool switch - current tool:', this.activeTool);
            console.log('🔄 Automatically switched to select tool after finishing line');
        }, 100); // Small delay to ensure UI state is consistent
        
        // Track the finished line to prevent immediate deselection
        this.justFinishedLine = finishedLine;
        
        // Auto-select the newly created line (with a slight delay to avoid immediate deselection)
        setTimeout(() => {
            console.log('🎯 Auto-selecting newly created line (delayed):', finishedLine.id);
            this.selectLine(finishedLine);
            
            // Clear the "just finished" flag after a short delay
            setTimeout(() => {
                this.justFinishedLine = null;
            }, 100);
        }, 10);
        
        console.log('✅ Curved line drawing finished:', finishedLine.id);
    }

    createLineElement(line) {
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('class', 'free-line');
        path.setAttribute('data-line-id', line.id);
        path.setAttribute('stroke', '#6b7280');
        path.setAttribute('stroke-width', '2');
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke-linecap', 'round');
        path.style.pointerEvents = 'stroke';
        path.style.cursor = 'pointer';
        
        // Add click handler for selection
        path.addEventListener('click', (e) => {
            e.stopPropagation();
            this.selectElement(line);
        });
        
        this.svg.appendChild(path);
        line.pathElement = path;
        
        // Initial path (just a point)
        this.updateLineElement(line);
    }

    updateLineElement(line) {
        if (!line.pathElement) return;
        
        const pathData = `M ${line.startX} ${line.startY} L ${line.endX} ${line.endY}`;
        line.pathElement.setAttribute('d', pathData);
    }

    checkLineAttachment(line) {
        // Check if line endpoints are near any elements
        const attachmentRadius = 15; // pixels
        
        // Check start point
        const startElement = this.getElementNear(line.startX, line.startY, attachmentRadius);
        if (startElement && startElement.type === 'node') {
            line.startAttachment = startElement.id;
            console.log('📎 Line attached to start element:', startElement.id);
        }
        
        // Check end point  
        const endElement = this.getElementNear(line.endX, line.endY, attachmentRadius);
        if (endElement && endElement.type === 'node') {
            line.endAttachment = endElement.id;
            console.log('📎 Line attached to end element:', endElement.id);
        }
    }

    getElementNear(x, y, radius) {
        for (const [id, element] of this.nodes) {
            const centerX = element.x + element.width / 2;
            const centerY = element.y + element.height / 2;
            const distance = Math.sqrt(Math.pow(x - centerX, 2) + Math.pow(y - centerY, 2));
            
            if (distance <= radius) {
                return element;
            }
        }
        return null;
    }

    // Update handleMouseMove to handle line drawing
    updateMouseMoveForLineDrawing() {
        const originalHandleMouseMove = this.handleMouseMove.bind(this);
        
        this.handleMouseMove = (e) => {
            if (this.isDrawingLine && this.currentLine) {
                const rect = this.canvas.getBoundingClientRect();
                const scrollLeft = this.container.scrollLeft || 0;
                const scrollTop = this.container.scrollTop || 0;
                
                const x = (e.clientX - rect.left + scrollLeft) / this.zoomLevel;
                const y = (e.clientY - rect.top + scrollTop) / this.zoomLevel;
                
                // Update current line end position
                this.currentLine.endX = x;
                this.currentLine.endY = y;
                
                // Recalculate control point for smooth curve
                const midX = (this.currentLine.startX + x) / 2;
                const midY = (this.currentLine.startY + y) / 2;
                
                const dx = x - this.currentLine.startX;
                const dy = y - this.currentLine.startY;
                const length = Math.sqrt(dx * dx + dy * dy);
                
                if (length > 0) {
                    const perpX = -dy / length * 30; // Smaller curve during drawing
                    const perpY = dx / length * 30;
                    
                    this.currentLine.controlX = midX + perpX;
                    this.currentLine.controlY = midY + perpY;
                }
                
                this.renderCurvedLine(this.currentLine);
            } else {
                originalHandleMouseMove(e);
            }
        };
    }
}

// Export for global use
window.VisualDiagramEditor = VisualDiagramEditor;
