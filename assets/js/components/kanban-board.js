// Kanban Board Component
class KanbanBoard {
    constructor(containerId) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.data = null;
        this.loading = false;
    }

    // Initialize the kanban board
    async init() {
        if (!this.container) {
            console.error('Kanban container not found:', this.containerId);
            return;
        }

        await this.loadData();
        this.initializeDragAndDrop();
    }

    // Fetch kanban data from API
    async loadData(showMineOnly = false) {
        this.loading = true;
        this.showLoading();

        try {
            const url = showMineOnly ? 'api/kanban_data.php?show_mine_only=true' : 'api/kanban_data.php';
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('API Response:', result); // Debug logging

            if (result.success) {
                this.data = result;
                this.render(); // Re-render with new data
            } else {
                throw new Error(result.error || 'Failed to load kanban data');
            }
        } catch (error) {
            console.error('Error loading kanban data:', error);
            console.error('Error details:', error.message);
            this.showError('Failed to load applications. Please refresh the page.');
        } finally {
            this.loading = false;
        }
    }

    // Fetch kanban data with filter
    async loadDataWithFilter(showMineOnly = false) {
        // Delegate to loadData method
        return this.loadData(showMineOnly);
    }

    // Render the complete kanban board
    render() {
        if (!this.data) {
            this.showError('No data available');
            return;
        }

        console.log('Rendering kanban with data:', this.data); // Debug logging

        this.container.innerHTML = `
            ${this.renderBoard()}
        `;
    }

    // Render statistics bar
    renderStats() {
        const { phase_counts, total_applications } = this.data;

        return `
            <div class="kanban-stats" style="background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); margin-bottom: 1.5rem;">
                <div class="kanban-stats-content" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem;">
                    <div class="kanban-stats-item" data-phase="Need" style="text-align: center; flex: 1;">
                        <div class="kanban-stats-number" style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.25rem;">${phase_counts?.Need || 0}</div>
                        <div class="kanban-stats-label" style="font-size: 0.8rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">Need</div>
                    </div>
                    <div class="kanban-stats-item" data-phase="Solution" style="text-align: center; flex: 1;">
                        <div class="kanban-stats-number" style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.25rem;">${phase_counts?.Solution || 0}</div>
                        <div class="kanban-stats-label" style="font-size: 0.8rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">Solution</div>
                    </div>
                    <div class="kanban-stats-item" data-phase="Build" style="text-align: center; flex: 1;">
                        <div class="kanban-stats-number" style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.25rem;">${phase_counts?.Build || 0}</div>
                        <div class="kanban-stats-label" style="font-size: 0.8rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">Build</div>
                    </div>
                    <div class="kanban-stats-item" data-phase="Implement" style="text-align: center; flex: 1;">
                        <div class="kanban-stats-number" style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.25rem;">${phase_counts?.Implement || 0}</div>
                        <div class="kanban-stats-label" style="font-size: 0.8rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">Implement</div>
                    </div>
                    <div class="kanban-stats-item" data-phase="Operate" style="text-align: center; flex: 1;">
                        <div class="kanban-stats-number" style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.25rem;">${phase_counts?.Operate || 0}</div>
                        <div class="kanban-stats-label" style="font-size: 0.8rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">Operate</div>
                    </div>
                    <div class="kanban-stats-item" data-phase="total" style="text-align: center; flex: 1; border-left: 2px solid #dee2e6; margin-left: 1rem; padding-left: 1rem;">
                        <div class="kanban-stats-number" style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.25rem;">${total_applications || 0}</div>
                        <div class="kanban-stats-label" style="font-size: 0.8rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">Total</div>
                    </div>
                </div>
            </div>
        `;
    }

    // Render the kanban board columns
    renderBoard() {
        const phases = ['Need', 'Solution', 'Build', 'Implement', 'Operate'];
        const maxVisibleCards = 4; // Maximum cards visible initially
        
        const columns = phases.map(phase => {
            const apps = this.data.data[phase] || [];
            const count = apps.length;
            const visibleApps = apps.slice(0, maxVisibleCards);
            const hiddenApps = apps.slice(maxVisibleCards);
            const hasHiddenCards = hiddenApps.length > 0;

            return `
                <div class="kanban-column" data-phase="${phase}" 
                     style="background-color: #F6F7FB; border-radius: 8px; padding: 1rem; border: 1px solid #E3E6E9; display: flex; flex-direction: column; ${hasHiddenCards ? 'min-height: auto;' : 'min-height: 500px;'}"
                     ondragover="window.kanbanBoard.handleDragOver(event)" 
                     ondragenter="window.kanbanBoard.handleDragEnter(event)"
                     ondragleave="window.kanbanBoard.handleDragLeave(event)"
                     ondrop="window.kanbanBoard.handleDrop(event, '${phase}')">
                    <div class="kanban-column-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #dee2e6;">
                        <h5 class="kanban-column-title" style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #495057;">${phase}</h5>
                        <span class="kanban-column-count" style="color: #6c757d; font-size: 0.875rem; font-weight: 500; background: none !important; background-color: transparent !important;">(${count})</span>
                    </div>
                    <div class="kanban-cards" style="flex: 1; display: flex; flex-direction: column; gap: 0.75rem;">
                        ${count > 0 ? this.renderVisibleCards(visibleApps, hiddenApps, phase) : this.renderEmptyState()}
                    </div>
                </div>
            `;
        }).join('');

        return `<div class="kanban-container" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; padding: 1rem 0; min-height: 600px; overflow-x: auto;">${columns}</div>`;
    }

    // Render visible cards with load more button if needed
    renderVisibleCards(visibleApps, hiddenApps, phase) {
        const visibleCardsHtml = visibleApps.map(app => this.renderCard(app)).join('');
        const hasHiddenCards = hiddenApps.length > 0;
        
        let loadMoreButton = '';
        if (hasHiddenCards) {
            loadMoreButton = `
                <div class="kanban-load-more" style="text-align: center; margin-top: 0.5rem;">
                    <button class="kanban-load-more-btn" 
                            onclick="window.kanbanBoard.showMoreCards('${phase}')"
                            style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); 
                                   border: 1px solid #dee2e6; 
                                   border-radius: 6px; 
                                   padding: 0.75rem 1rem; 
                                   cursor: pointer; 
                                   transition: all 0.2s ease; 
                                   color: #6c757d; 
                                   font-size: 0.875rem; 
                                   width: 100%; 
                                   display: flex; 
                                   align-items: center; 
                                   justify-content: center; 
                                   gap: 0.5rem;
                                   box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);"
                            onmouseover="this.style.backgroundColor='#e9ecef'; this.style.borderColor='#adb5bd'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 2px 6px rgba(0, 0, 0, 0.15)';"
                            onmouseout="this.style.backgroundColor=''; this.style.borderColor='#dee2e6'; this.style.transform='none'; this.style.boxShadow='0 1px 3px rgba(0, 0, 0, 0.1)';">
                        <i class="fas fa-chevron-down" style="font-size: 0.75rem;"></i>
                        <span style="margin-left: 0.25rem; font-weight: 500;">+${hiddenApps.length} more</span>
                    </button>
                </div>
            `;
        }
        
        return visibleCardsHtml + loadMoreButton;
    }

    // Render individual kanban card
    renderCard(app) {
        const statusBadgeClass = this.getStatusBadgeClass(app.status);
        const progressColor = app.handover_status >= 90 ? '#198754' : 
                             app.handover_status >= 50 ? '#0d6efd' : '#ffc107';

        // Status badge color mapping
        let badgeColor = '#6c757d'; // Grey default
        if (statusBadgeClass.includes('blue')) badgeColor = '#0d6efd';
        if (statusBadgeClass.includes('green')) badgeColor = '#198754';

        // Check if user can edit (will be set by PHP session data)
        const canEdit = window.userRole === 'admin' || window.userRole === 'editor';
        const draggableAttr = canEdit ? 'draggable="true"' : '';
        const dragHandlers = canEdit ? `ondragstart="window.kanbanBoard.handleDragStart(event, ${app.id})" ondragend="window.kanbanBoard.handleDragEnd(event)"` : '';

        return `
            <div class="kanban-card" data-app-id="${app.id}" ${draggableAttr} ${dragHandlers}
                 style="background-color: white; border-radius: 6px; padding: 1rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); cursor: ${canEdit ? 'grab' : 'pointer'}; transition: transform 0.2s ease, box-shadow 0.2s ease; border: 1px solid #dee2e6;"
                 onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 15px rgba(0, 0, 0, 0.15)'"
                 onmouseout="this.style.transform='none'; this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.1)'">
                <div class="kanban-card-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                    <h6 class="kanban-card-title" style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #212529; flex: 1; margin-right: 0.5rem; cursor: pointer; transition: color 0.2s ease;" 
                        onclick="event.stopPropagation(); window.location.href='app_view.php?id=${app.id}'"
                        onmouseover="this.style.color='#0d6efd'; this.style.textDecoration='underline'"
                        onmouseout="this.style.color='#212529'; this.style.textDecoration='none'">${this.escapeHtml(app.name || 'Untitled Application')}</h6>
                    <span class="kanban-status-badge" data-app-id="${app.id}" data-current-status="${app.status || 'Unknown'}" 
                          style="background-color: ${badgeColor}; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: 500; white-space: nowrap; cursor: ${canEdit ? 'pointer' : 'default'}; position: relative;"
                          ${canEdit ? `onclick="event.stopPropagation(); event.preventDefault(); window.kanbanBoard.showStatusDropdown(event, ${app.id}, '${(app.status || 'Unknown').replace(/'/g, '&#39;')}')"` : ''}>${this.escapeHtml(app.status || 'Unknown')}</span>
                </div>
                <div class="kanban-card-content" style="margin-bottom: 0.75rem;">
                    <div class="kanban-card-meta" style="display: flex; align-items: center; margin-bottom: 0.5rem; font-size: 0.8rem; color: #6c757d;">
                        <i class="fas fa-user" style="margin-right: 0.5rem;"></i>
                        ${this.escapeHtml(app.project_manager || 'Not assigned')}
                    </div>
                    <div class="kanban-card-notes" style="display: flex; align-items: center; margin-bottom: 0.75rem; font-size: 0.8rem; color: #6c757d;">
                        <i class="fas fa-sticky-note" style="margin-right: 0.5rem;"></i>
                        ${app.work_notes_count || 0} work note${(app.work_notes_count || 0) !== 1 ? 's' : ''}
                    </div>
                    <div class="kanban-progress-container" style="margin-bottom: 0.5rem;">
                        <div class="kanban-progress-bar" style="background-color: #e9ecef; border-radius: 4px; height: 6px; overflow: hidden; margin-bottom: 0.25rem;">
                            <div class="kanban-progress-fill" style="width: ${app.handover_status || 0}%; background-color: ${progressColor}; height: 100%; transition: width 0.3s ease;"></div>
                        </div>
                        <div class="kanban-progress-text" style="font-size: 0.75rem; color: #6c757d; text-align: right;">${app.handover_status || 0}% handover</div>
                    </div>
                </div>
                <div class="kanban-card-footer" style="font-size: 0.75rem; color: #adb5bd; text-align: right; border-top: 1px solid #f1f3f4; padding-top: 0.5rem;">
                    Updated: ${app.formatted_date || 'Unknown'}
                </div>
            </div>
        `;
    }

    // Render empty state for columns with no cards
    renderEmptyState() {
        return `
            <div class="kanban-empty-state" style="text-align: center; padding: 2rem 1rem; color: #adb5bd; font-size: 0.9rem;">
                <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                No applications in this phase
            </div>
        `;
    }

    // Get CSS class for status badge based on status
    getStatusBadgeClass(status) {
        const greyStatuses = ['On Hold', 'Not Started', 'Unknown'];
        const blueStatuses = ['Ongoing Work'];
        const greenStatuses = ['Completed'];

        if (greyStatuses.includes(status)) {
            return 'status-badge-grey';
        } else if (blueStatuses.includes(status)) {
            return 'status-badge-blue';
        } else if (greenStatuses.includes(status)) {
            return 'status-badge-green';
        } else {
            return 'status-badge-grey'; // Default to grey for unknown statuses
        }
    }

    // Show loading state
    showLoading() {
        if (this.container) {
            this.container.innerHTML = `
                <div class="kanban-loading" style="text-align: center; padding: 2rem; color: #6c757d;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i><br>
                    Loading applications...
                </div>
            `;
        }
    }

    // Show error state
    showError(message) {
        if (this.container) {
            this.container.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Kanban Board Error:</strong><br>
                    ${this.escapeHtml(message)}
                    <hr>
                    <details>
                        <summary>Troubleshooting Information</summary>
                        <ul>
                            <li>Check browser console for detailed error messages</li>
                            <li>Verify API endpoint is accessible: <code>api/kanban_data.php</code></li>
                            <li>Ensure you are logged in properly</li>
                            <li>Check network connectivity</li>
                        </ul>
                    </details>
                    <div class="mt-3">
                        <button class="btn btn-outline-danger" onclick="location.reload()">
                            <i class="fas fa-redo"></i> Reload Page
                        </button>
                        <button class="btn btn-outline-primary ms-2" onclick="window.dashboardControls.refreshKanban()">
                            <i class="fas fa-sync"></i> Retry API Call
                        </button>
                    </div>
                </div>
            `;
        }
    }

    // Refresh kanban data
    async refresh(showMineOnly = false) {
        console.log('REFRESH DEBUG: showMineOnly parameter =', showMineOnly);
        await this.loadData(showMineOnly);
    }

    // Utility function to escape HTML
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Get application count for specific phase
    getPhaseCount(phase) {
        return this.data ? (this.data.data[phase] || []).length : 0;
    }

    // Get total application count
    getTotalCount() {
        return this.data ? this.data.total_applications : 0;
    }

    // Initialize drag and drop functionality
    initializeDragAndDrop() {
        // Set reference to this instance for global access
        window.kanbanBoard = this;
        this.draggedElement = null;
        this.draggedAppId = null;
    }

    // Drag and drop handlers
    handleDragStart(event, appId) {
        this.draggedElement = event.target;
        this.draggedAppId = appId;
        event.target.style.opacity = '0.5';
        event.target.style.transform = 'rotate(2deg)';
        event.target.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.3)';
        event.dataTransfer.setData('text/plain', appId);
        
        // Add dragging class to body for global cursor
        document.body.classList.add('dragging');
        document.body.style.cursor = 'grabbing';
    }

    handleDragEnd(event) {
        event.target.style.opacity = '1';
        event.target.style.transform = 'none';
        event.target.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.1)';
        this.draggedElement = null;
        this.draggedAppId = null;
        
        // Remove dragging class
        document.body.classList.remove('dragging');
        document.body.style.cursor = '';
        
        // Remove any drag-over styling and placeholders
        document.querySelectorAll('.kanban-column').forEach(col => {
            col.style.backgroundColor = '#F6F7FB';
            col.style.borderColor = '#E3E6E9';
            col.style.transform = 'none';
        });
        
        // Clean up placeholders and reset card positions
        this.removePlaceholder();
    }

    handleDragOver(event) {
        event.preventDefault();
        const column = event.currentTarget;
        const cardsContainer = column.querySelector('.kanban-cards');
        
        // Style the column
        column.style.backgroundColor = '#e3f2fd';
        column.style.borderColor = '#2196f3';
        column.style.transform = 'scale(1.02)';
        column.style.transition = 'all 0.2s ease';
        
        // Remove existing placeholder
        this.removePlaceholder();
        
        // Create placeholder for insertion point
        const placeholder = document.createElement('div');
        placeholder.className = 'kanban-card-placeholder';
        placeholder.style.cssText = `
            height: 120px;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border: 2px dashed #2196f3;
            border-radius: 6px;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1976d2;
            font-weight: 500;
            font-size: 0.9rem;
            opacity: 0.8;
            animation: pulseGlow 1.5s ease-in-out infinite;
            transition: all 0.3s ease;
        `;
        placeholder.innerHTML = '<i class="fas fa-plus"></i> Drop here';
        
        // Add CSS animation for placeholder
        this.ensurePlaceholderStyles();
        
        // Insert placeholder at the top, before any cards
        const firstCard = cardsContainer.querySelector('.kanban-card');
        if (firstCard) {
            cardsContainer.insertBefore(placeholder, firstCard);
        } else {
            // If no cards, but check for load more button or empty state
            const loadMoreBtn = cardsContainer.querySelector('.kanban-load-more');
            const emptyState = cardsContainer.querySelector('.kanban-empty-state');
            
            if (loadMoreBtn) {
                cardsContainer.insertBefore(placeholder, loadMoreBtn);
            } else if (emptyState) {
                emptyState.style.display = 'none';
                cardsContainer.appendChild(placeholder);
            } else {
                cardsContainer.appendChild(placeholder);
            }
        }
        
        // Add smooth slide-down animation to existing cards (but not load more button)
        const cards = cardsContainer.querySelectorAll('.kanban-card');
        cards.forEach((card, index) => {
            card.style.transform = 'translateY(135px)'; // Height of placeholder + margin
            card.style.transition = 'transform 0.3s ease';
        });
    }

    ensurePlaceholderStyles() {
        if (!document.querySelector('#placeholder-styles')) {
            const style = document.createElement('style');
            style.id = 'placeholder-styles';
            style.textContent = `
                @keyframes pulseGlow {
                    0%, 100% {
                        box-shadow: 0 0 5px rgba(33, 150, 243, 0.3);
                        transform: scale(1);
                    }
                    50% {
                        box-shadow: 0 0 20px rgba(33, 150, 243, 0.6);
                        transform: scale(1.02);
                    }
                }
                .kanban-card-placeholder {
                    position: relative;
                }
                .kanban-card-placeholder::before {
                    content: '';
                    position: absolute;
                    top: -2px;
                    left: -2px;
                    right: -2px;
                    bottom: -2px;
                    background: linear-gradient(45deg, transparent, rgba(33, 150, 243, 0.1), transparent);
                    border-radius: 8px;
                    z-index: -1;
                }
            `;
            document.head.appendChild(style);
        }
    }

    removePlaceholder() {
        const placeholders = document.querySelectorAll('.kanban-card-placeholder');
        placeholders.forEach(placeholder => {
            const cardsContainer = placeholder.parentNode;
            placeholder.remove();
            
            // Reset card positions in this container
            const cards = cardsContainer.querySelectorAll('.kanban-card');
            cards.forEach(card => {
                card.style.transform = 'none';
                card.style.transition = 'transform 0.3s ease';
            });
            
            // Show empty state if no cards and no load more button
            const emptyState = cardsContainer.querySelector('.kanban-empty-state');
            const loadMoreBtn = cardsContainer.querySelector('.kanban-load-more');
            if (cards.length === 0 && !loadMoreBtn && emptyState) {
                emptyState.style.display = 'block';
            }
        });
    }

    handleDragEnter(event) {
        event.preventDefault();
        // DragEnter will trigger dragover, so we let dragover handle the placeholder
    }

    handleDragLeave(event) {
        const column = event.currentTarget;
        // Only reset if we're actually leaving the column (not entering a child)
        if (!column.contains(event.relatedTarget)) {
            column.style.backgroundColor = '#F6F7FB';
            column.style.borderColor = '#E3E6E9';
            column.style.transform = 'none';
            
            // Remove placeholder and reset card positions
            this.removePlaceholder();
        }
    }

    async handleDrop(event, newPhase) {
        event.preventDefault();
        const column = event.currentTarget;
        
        // Reset column styling and remove placeholder
        column.style.backgroundColor = '#F6F7FB';
        column.style.borderColor = '#E3E6E9';
        column.style.transform = 'none';
        this.removePlaceholder();

        if (!this.draggedAppId) return;

        // Show loading state on dragged element
        if (this.draggedElement) {
            this.draggedElement.style.opacity = '0.3';
            this.draggedElement.style.pointerEvents = 'none';
        }

        try {
            const response = await fetch('api/update_phase.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    app_id: this.draggedAppId,
                    phase: newPhase
                })
            });

            const result = await response.json();
            if (result.success) {
                // Show success animation
                if (this.draggedElement) {
                    this.draggedElement.style.backgroundColor = '#d4edda';
                    this.draggedElement.style.borderColor = '#28a745';
                }
                
                // Reload data after brief delay to show success state
                setTimeout(async () => {
                    // Check if "Show mine only" filter is active and preserve it during refresh
                    const showMineOnlyToggle = document.getElementById('showMineOnlyToggle');
                    const showMineOnly = showMineOnlyToggle ? showMineOnlyToggle.checked : false;
                    console.log('DRAG DROP DEBUG: showMineOnly toggle checked =', showMineOnly);
                    await this.refresh(showMineOnly);
                }, 300);
            } else {
                throw new Error(result.error || 'Failed to update phase');
            }
        } catch (error) {
            console.error('Error updating phase:', error);
            
            // Show error state
            if (this.draggedElement) {
                this.draggedElement.style.backgroundColor = '#f8d7da';
                this.draggedElement.style.borderColor = '#dc3545';
                this.draggedElement.style.opacity = '1';
                this.draggedElement.style.pointerEvents = 'auto';
                
                // Reset after 2 seconds
                setTimeout(() => {
                    if (this.draggedElement) {
                        this.draggedElement.style.backgroundColor = '';
                        this.draggedElement.style.borderColor = '';
                    }
                }, 2000);
            }
            
            alert('Failed to update application phase. Please try again.');
        }
    }

    // Status dropdown handlers
    showStatusDropdown(event, appId, currentStatus) {
        console.log('showStatusDropdown called with:', { event, appId, currentStatus }); // Debug log
        
        event.stopPropagation();
        event.preventDefault();
        
        // Remove any existing dropdown
        this.hideStatusDropdown();

        const statusOptions = [
            { value: 'Not Started', color: '#6c757d', label: 'Not Started' },
            { value: 'Ongoing Work', color: '#0d6efd', label: 'Ongoing Work' },
            { value: 'On Hold', color: '#6c757d', label: 'On Hold' },
            { value: 'Completed', color: '#198754', label: 'Completed' },
            { value: 'Unknown', color: '#6c757d', label: 'Unknown' }
        ];

        const badge = event.target.closest('.kanban-status-badge');
        if (!badge) {
            console.error('Badge not found for dropdown');
            return;
        }

        console.log('Creating dropdown for badge:', badge); // Debug log

        // Create dropdown container
        const dropdown = document.createElement('div');
        dropdown.className = 'status-dropdown';
        dropdown.style.cssText = `
            position: absolute !important;
            top: calc(100% + 4px) !important;
            right: 0 !important;
            background: white !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 6px !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
            z-index: 9999 !important;
            min-width: 140px !important;
            overflow: hidden !important;
            animation: slideDown 0.15s ease-out !important;
            display: block !important;
        `;

        // Ensure CSS animations exist
        this.ensureDropdownStyles();

        statusOptions.forEach((option, index) => {
            const item = document.createElement('div');
            item.className = 'status-dropdown-item';
            const isSelected = option.value === currentStatus;
            
            item.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 8px; height: 8px; border-radius: 50%; background-color: ${option.color};"></div>
                    <span style="font-weight: ${isSelected ? '600' : '400'};">${option.label}</span>
                </div>
                ${isSelected ? '<i class="fas fa-check" style="color: #198754; font-size: 0.7rem;"></i>' : ''}
            `;
            
            item.style.cssText = `
                padding: 0.6rem 0.8rem !important;
                cursor: pointer !important;
                font-size: 0.8rem !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                transition: all 0.15s ease !important;
                border-bottom: ${index < statusOptions.length - 1 ? '1px solid #f1f3f4' : 'none'} !important;
                color: #212529 !important;
                background-color: ${isSelected ? '#f8f9fa' : 'white'} !important;
            `;
            
            // Event handlers
            item.addEventListener('mouseenter', () => {
                if (!isSelected) {
                    item.style.backgroundColor = '#f8f9fa';
                }
            });
            
            item.addEventListener('mouseleave', () => {
                if (!isSelected) {
                    item.style.backgroundColor = 'white';
                }
            });
            
            item.addEventListener('click', (e) => {
                console.log('Dropdown item clicked:', option.value); // Debug log
                e.stopPropagation();
                e.preventDefault();
                this.updateStatus(appId, option.value);
            });
            
            dropdown.appendChild(item);
        });

        // Set badge position and append dropdown
        badge.style.position = 'relative';
        badge.appendChild(dropdown);

        console.log('Dropdown created and appended:', dropdown); // Debug log

        // Close dropdown when clicking outside
        setTimeout(() => {
            const closeHandler = (e) => {
                if (!dropdown.contains(e.target) && !badge.contains(e.target)) {
                    this.hideStatusDropdown();
                    document.removeEventListener('click', closeHandler);
                }
            };
            document.addEventListener('click', closeHandler);
        }, 100);
    }

    ensureDropdownStyles() {
        if (!document.querySelector('#dropdown-styles')) {
            const style = document.createElement('style');
            style.id = 'dropdown-styles';
            style.textContent = `
                @keyframes slideDown {
                    from {
                        opacity: 0;
                        transform: translateY(-8px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                @keyframes slideUp {
                    from {
                        opacity: 1;
                        transform: translateY(0);
                    }
                    to {
                        opacity: 0;
                        transform: translateY(-8px);
                    }
                }
                .status-dropdown {
                    display: block !important;
                }
                .status-dropdown-item:hover {
                    background-color: #f8f9fa !important;
                }
            `;
            document.head.appendChild(style);
        }
    }

    hideStatusDropdown() {
        const dropdowns = document.querySelectorAll('.status-dropdown');
        dropdowns.forEach(dropdown => {
            if (dropdown) {
                dropdown.style.animation = 'slideUp 0.15s ease-out';
                setTimeout(() => {
                    if (dropdown.parentNode) {
                        dropdown.remove();
                    }
                }, 150);
            }
        });
    }

    // Show more cards in a specific column
    showMoreCards(phase) {
        const column = document.querySelector(`[data-phase="${phase}"]`);
        if (!column) return;
        
        const cardsContainer = column.querySelector('.kanban-cards');
        const apps = this.data.data[phase] || [];
        
        // Clear current content
        cardsContainer.innerHTML = '';
        
        // Render all cards
        const allCardsHtml = apps.map(app => this.renderCard(app)).join('');
        
        // Add collapse button
        const collapseButton = `
            <div class="kanban-load-more" style="text-align: center; margin-top: 0.5rem;">
                <button class="kanban-collapse-btn" 
                        onclick="window.kanbanBoard.showLessCards('${phase}')"
                        style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); 
                               border: 1px solid #ffc107; 
                               border-radius: 6px; 
                               padding: 0.75rem 1rem; 
                               cursor: pointer; 
                               transition: all 0.2s ease; 
                               color: #856404; 
                               font-size: 0.875rem; 
                               width: 100%; 
                               display: flex; 
                               align-items: center; 
                               justify-content: center; 
                               gap: 0.5rem;
                               box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);"
                        onmouseover="this.style.backgroundColor='#ffeaa7'; this.style.borderColor='#fd7e14'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 2px 6px rgba(0, 0, 0, 0.15)';"
                        onmouseout="this.style.backgroundColor=''; this.style.borderColor='#ffc107'; this.style.transform='none'; this.style.boxShadow='0 1px 3px rgba(0, 0, 0, 0.1)';">
                    <i class="fas fa-chevron-up" style="font-size: 0.75rem;"></i>
                    <span style="margin-left: 0.25rem; font-weight: 500;">Show less</span>
                </button>
            </div>
        `;
        
        cardsContainer.innerHTML = allCardsHtml + collapseButton;
        
        // Add smooth transition
        cardsContainer.style.opacity = '0';
        cardsContainer.style.transform = 'translateY(10px)';
        setTimeout(() => {
            cardsContainer.style.transition = 'all 0.3s ease';
            cardsContainer.style.opacity = '1';
            cardsContainer.style.transform = 'translateY(0)';
        }, 10);
    }
    
    // Show less cards (collapse back to initial state)
    showLessCards(phase) {
        const column = document.querySelector(`[data-phase="${phase}"]`);
        if (!column) return;
        
        const cardsContainer = column.querySelector('.kanban-cards');
        const apps = this.data.data[phase] || [];
        const maxVisibleCards = 4;
        
        const visibleApps = apps.slice(0, maxVisibleCards);
        const hiddenApps = apps.slice(maxVisibleCards);
        
        // Add fade out transition
        cardsContainer.style.transition = 'all 0.2s ease';
        cardsContainer.style.opacity = '0';
        cardsContainer.style.transform = 'translateY(-10px)';
        
        setTimeout(() => {
            cardsContainer.innerHTML = this.renderVisibleCards(visibleApps, hiddenApps, phase);
            cardsContainer.style.opacity = '1';
            cardsContainer.style.transform = 'translateY(0)';
        }, 200);
    }

    async updateStatus(appId, newStatus) {
        this.hideStatusDropdown();

        try {
            const response = await fetch('api/update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    app_id: appId,
                    status: newStatus
                })
            });

            const result = await response.json();
            if (result.success) {
                // Check if "Show mine only" filter is active and preserve it during refresh
                const showMineOnlyToggle = document.getElementById('showMineOnlyToggle');
                const showMineOnly = showMineOnlyToggle ? showMineOnlyToggle.checked : false;
                console.log('STATUS UPDATE DEBUG: showMineOnly toggle checked =', showMineOnly);
                await this.refresh(showMineOnly);
            } else {
                throw new Error(result.error || 'Failed to update status');
            }
        } catch (error) {
            console.error('Error updating status:', error);
            alert('Failed to update application status. Please try again.');
        }
    }
}

// Export for use in other modules
window.KanbanBoard = KanbanBoard;
