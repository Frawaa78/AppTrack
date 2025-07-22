// Dashboard Page Logic
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize variables
    let kanbanBoard = null;
    let currentView = 'table'; // 'table' or 'kanban'
    let showMineOnly = false; // Filter state for "Show mine only"
    
    // Get view toggle buttons
    const tableViewBtn = document.getElementById('tableViewBtn');
    const kanbanViewBtn = document.getElementById('kanbanViewBtn');
    const tableContainer = document.getElementById('tableContainer');
    const kanbanContainer = document.getElementById('kanbanContainer');
    const showMineOnlyToggle = document.getElementById('showMineOnlyToggle');

    // Initialize dashboard
    init();

    function init() {
        // Check URL parameters for show_mine_only state
        const urlParams = new URLSearchParams(window.location.search);
        const showMineOnlyParam = urlParams.get('show_mine_only') === 'true';
        showMineOnly = showMineOnlyParam;
        
        // Set toggle state to match URL parameter
        if (showMineOnlyToggle) {
            showMineOnlyToggle.checked = showMineOnly;
        }
        
        // Determine current view based on button states and saved preference
        const savedView = loadViewPreference();
        if (kanbanViewBtn && kanbanViewBtn.classList.contains('active')) {
            currentView = 'kanban';
        } else if (tableViewBtn && tableViewBtn.classList.contains('active')) {
            currentView = 'table';
        } else {
            // Fallback to saved preference or default to table
            currentView = savedView;
        }
        
        // Set initial view state
        setViewState(currentView);
        
        // Add event listeners for view toggle
        if (tableViewBtn) {
            tableViewBtn.addEventListener('click', () => switchView('table'));
        }
        if (kanbanViewBtn) {
            kanbanViewBtn.addEventListener('click', () => switchView('kanban'));
        }

        // Add event listener for "Show mine only" toggle
        if (showMineOnlyToggle) {
            showMineOnlyToggle.addEventListener('change', handleShowMineOnlyToggle);
        }

        // Initialize kanban board (but keep it hidden initially)
        initializeKanbanBoard();

        // Add refresh functionality
        addRefreshHandlers();
    }

    // Switch between table and kanban views
    function switchView(view) {
        if (view === currentView) return;

        currentView = view;
        setViewState(view);

        // Analytics tracking (optional)
        if (typeof gtag !== 'undefined') {
            gtag('event', 'view_switch', {
                'event_category': 'dashboard',
                'event_label': view
            });
        }
    }

    // Set the visual state for the selected view
    function setViewState(view) {
        if (view === 'table') {
            // Show table view
            if (tableContainer) tableContainer.style.display = 'block';
            if (kanbanContainer) kanbanContainer.style.display = 'none';
            
            // Update button states
            if (tableViewBtn) {
                tableViewBtn.classList.add('active');
                tableViewBtn.setAttribute('aria-pressed', 'true');
            }
            if (kanbanViewBtn) {
                kanbanViewBtn.classList.remove('active');
                kanbanViewBtn.setAttribute('aria-pressed', 'false');
            }
        } else if (view === 'kanban') {
            // Show kanban view
            if (tableContainer) tableContainer.style.display = 'none';
            if (kanbanContainer) kanbanContainer.style.display = 'block';
            
            // Update button states
            if (tableViewBtn) {
                tableViewBtn.classList.remove('active');
                tableViewBtn.setAttribute('aria-pressed', 'false');
            }
            if (kanbanViewBtn) {
                kanbanViewBtn.classList.add('active');
                kanbanViewBtn.setAttribute('aria-pressed', 'true');
            }

            // Initialize/refresh kanban data when switching to kanban view
            if (kanbanBoard) {
                kanbanBoard.refresh(showMineOnly);
            }
        }
    }

    // Initialize the kanban board component
    function initializeKanbanBoard() {
        if (typeof KanbanBoard !== 'undefined' && kanbanContainer) {
            kanbanBoard = new KanbanBoard('kanbanContainer');
            kanbanBoard.init().catch(error => {
                console.error('Failed to initialize kanban board:', error);
                showKanbanError();
            });
        }
    }

    // Handle "Show mine only" toggle change
    function handleShowMineOnlyToggle(event) {
        showMineOnly = event.target.checked;
        
        // If kanban view is active, refresh the data
        if (currentView === 'kanban' && kanbanBoard) {
            kanbanBoard.refresh(showMineOnly);
        }
        
        // If table view is active, refresh the table
        if (currentView === 'table') {
            refreshTableView(showMineOnly);
        }
    }

    // Refresh table view with filter
    function refreshTableView(showMineOnly = false) {
        const currentUrl = new URL(window.location);
        
        // Update URL parameter
        if (showMineOnly) {
            currentUrl.searchParams.set('show_mine_only', 'true');
        } else {
            currentUrl.searchParams.delete('show_mine_only');
        }
        
        // Reload page with new parameter
        window.location.href = currentUrl.toString();
    }

    // Add refresh handlers for both views
    function addRefreshHandlers() {
        // Refresh button for table view (if exists)
        const tableRefreshBtn = document.getElementById('refreshTable');
        if (tableRefreshBtn) {
            tableRefreshBtn.addEventListener('click', function() {
                location.reload();
            });
        }

        // Refresh button for kanban view (if exists)
        const kanbanRefreshBtn = document.getElementById('refreshKanban');
        if (kanbanRefreshBtn) {
            kanbanRefreshBtn.addEventListener('click', function() {
                if (kanbanBoard) {
                    kanbanBoard.refresh();
                }
            });
        }

        // Auto-refresh every 5 minutes (optional)
        setInterval(function() {
            if (currentView === 'kanban' && kanbanBoard) {
                kanbanBoard.refresh();
            }
        }, 300000); // 5 minutes
    }

    // Show error in kanban container
    function showKanbanError() {
        if (kanbanContainer) {
            kanbanContainer.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <h4 class="alert-heading">Kanban Board Error</h4>
                    <p>Unable to load the kanban board. This could be due to:</p>
                    <ul>
                        <li>Network connectivity issues</li>
                        <li>Server problems</li>
                        <li>Missing kanban board component</li>
                    </ul>
                    <hr>
                    <p class="mb-0">
                        <button class="btn btn-outline-danger" onclick="location.reload()">
                            <i class="fas fa-redo"></i> Try Again
                        </button>
                    </p>
                </div>
            `;
        }
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Alt + T for table view
        if (e.altKey && e.key === 't') {
            e.preventDefault();
            switchView('table');
        }
        // Alt + K for kanban view
        if (e.altKey && e.key === 'k') {
            e.preventDefault();
            switchView('kanban');
        }
        // F5 or Ctrl+R for refresh current view
        if (e.key === 'F5' || (e.ctrlKey && e.key === 'r')) {
            if (currentView === 'kanban' && kanbanBoard) {
                e.preventDefault();
                kanbanBoard.loadDataWithFilter(showMineOnly);
            }
        }
    });

    // Expose functions for external use (debugging/testing)
    window.dashboardControls = {
        switchView: switchView,
        getCurrentView: () => currentView,
        refreshKanban: () => kanbanBoard ? kanbanBoard.refresh(showMineOnly) : null,
        getKanbanBoard: () => kanbanBoard,
        toggleShowMineOnly: () => {
            if (showMineOnlyToggle) {
                showMineOnlyToggle.checked = !showMineOnlyToggle.checked;
                handleShowMineOnlyToggle({target: showMineOnlyToggle});
            }
        }
    };

    // Handle browser back/forward navigation
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.view) {
            switchView(event.state.view);
        }
    });

    // Save view preference in sessionStorage
    function saveViewPreference(view) {
        try {
            sessionStorage.setItem('dashboardView', view);
        } catch (e) {
            // Ignore storage errors
        }
    }

    // Load view preference from sessionStorage
    function loadViewPreference() {
        try {
            return sessionStorage.getItem('dashboardView') || 'table';
        } catch (e) {
            return 'table';
        }
    }

    // Save preference when view changes
    const originalSwitchView = switchView;
    switchView = function(view) {
        originalSwitchView(view);
        saveViewPreference(view);
    };
});
