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
