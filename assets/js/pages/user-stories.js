// User Stories Dashboard JavaScript
// assets/js/pages/user-stories.js

class UserStoriesDashboard {
    constructor() {
        this.stories = [];
        this.filteredStories = [];
        this.currentFilters = {
            application_id: '',
            priority: '',
            status: '',
            search: '',
            show_mine_only: false
        };
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadFormOptions();
        this.loadStories();
        
        // Check URL parameters for initial filters
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('show_mine_only') === 'true') {
            this.currentFilters.show_mine_only = true;
            document.getElementById('showMineOnly').checked = true;
        }
        
        // Store application_id if present
        this.fromAppId = urlParams.get('application_id');
    }
    
    bindEvents() {
        // Filter toggle
        document.getElementById('filterToggle').addEventListener('click', () => {
            const panel = document.getElementById('filtersPanel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        });
        
        // Filter controls
        document.getElementById('applicationFilter').addEventListener('change', (e) => {
            this.currentFilters.application_id = e.target.value;
            this.applyFilters();
        });
        
        document.getElementById('priorityFilter').addEventListener('change', (e) => {
            this.currentFilters.priority = e.target.value;
            this.applyFilters();
        });
        
        document.getElementById('statusFilter').addEventListener('change', (e) => {
            this.currentFilters.status = e.target.value;
            this.applyFilters();
        });
        
        document.getElementById('searchFilter').addEventListener('input', (e) => {
            this.currentFilters.search = e.target.value;
            this.debounce(() => this.applyFilters(), 300);
        });
        
        document.getElementById('showMineOnly').addEventListener('change', (e) => {
            this.currentFilters.show_mine_only = e.target.checked;
            this.applyFilters();
            this.updateURL();
        });
        
        // Clear filters
        document.getElementById('clearFilters').addEventListener('click', () => {
            this.clearFilters();
        });
        
        // Delete confirmation
        document.getElementById('confirmDelete').addEventListener('click', () => {
            this.executeDelete();
        });
    }
    
    async loadFormOptions() {
        try {
            const response = await fetch('api/user_stories/get_form_options.php');
            const result = await response.json();
            
            if (result.success) {
                this.populateApplicationFilter(result.data.applications);
            }
        } catch (error) {
            console.error('Error loading form options:', error);
        }
    }
    
    populateApplicationFilter(applications) {
        const select = document.getElementById('applicationFilter');
        applications.forEach(app => {
            const option = document.createElement('option');
            option.value = app.id;
            option.textContent = app.short_description;
            select.appendChild(option);
        });
    }
    
    async loadStories() {
        try {
            const params = new URLSearchParams();
            
            // Add filters to request
            Object.keys(this.currentFilters).forEach(key => {
                if (this.currentFilters[key]) {
                    params.append(key, this.currentFilters[key]);
                }
            });
            
            const response = await fetch(`api/user_stories/get_stories.php?${params.toString()}`);
            const result = await response.json();
            
            if (result.success) {
                this.stories = result.data;
                this.updateStatistics(result.statistics);
                this.renderStories();
            } else {
                this.showError('Failed to load user stories: ' + result.error);
            }
        } catch (error) {
            console.error('Error loading stories:', error);
            this.showError('Failed to load user stories');
        }
    }
    
    updateStatistics(stats) {
        document.getElementById('totalStories').textContent = stats.total_stories || 0;
        document.getElementById('backlogCount').textContent = stats.backlog_count || 0;
        document.getElementById('inProgressCount').textContent = stats.in_progress_count || 0;
        document.getElementById('reviewCount').textContent = stats.review_count || 0;
        document.getElementById('doneCount').textContent = stats.done_count || 0;
        document.getElementById('criticalCount').textContent = stats.critical_count || 0;
    }
    
    renderStories() {
        const tbody = document.getElementById('storiesTableBody');
        
        if (this.stories.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center empty-state">
                        <i class="bi bi-journal-x"></i>
                        <p>No user stories found</p>
                        <a href="user_story_form.php" class="btn btn-primary">
                            <i class="bi bi-plus"></i> Create First Story
                        </a>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = this.stories.map(story => `
            <tr>
                <td>
                    <a href="user_story_view.php?id=${story.id}${this.fromAppId ? '&from_app=1' : ''}" class="story-title">
                        ${this.escapeHtml(story.title)}
                    </a>
                    ${story.jira_id ? `<br><small class="jira-badge">JIRA: ${this.escapeHtml(story.jira_id)}</small>` : ''}
                </td>
                <td>
                    <div class="story-summary">
                        <span class="story-role">As a ${this.escapeHtml(story.role)}</span><br>
                        <small class="text-muted">${this.truncateText(story.want_to, 60)}</small>
                    </div>
                </td>
                <td>
                    ${story.application_name ? 
                        `<a href="app_view.php?id=${story.application_id}" class="text-decoration-none">
                            ${this.escapeHtml(story.application_name)}
                        </a>` : 
                        '<span class="text-muted">-</span>'
                    }
                </td>
                <td>
                    <span class="badge priority-badge priority-${story.priority.toLowerCase()}">
                        ${story.priority}
                    </span>
                </td>
                <td>
                    <span class="badge status-badge status-${story.status}">
                        ${this.formatStatus(story.status)}
                    </span>
                </td>
                <td>
                    <small>${story.created_by_name || 'Unknown'}</small>
                </td>
                <td>
                    <small>${this.formatDate(story.created_at)}</small>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="user_story_view.php?id=${story.id}${this.fromAppId ? '&from_app=1' : ''}" class="btn btn-outline-primary btn-sm" title="View">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="user_story_form.php?id=${story.id}${this.fromAppId ? '&application_id=' + this.fromAppId : ''}" class="btn btn-outline-secondary btn-sm" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-outline-danger btn-sm" title="Delete"
                                onclick="userStoriesDashboard.confirmDelete(${story.id}, '${this.escapeHtml(story.title)}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
    
    applyFilters() {
        this.loadStories(); // Reload with current filters
    }
    
    clearFilters() {
        this.currentFilters = {
            application_id: '',
            priority: '',
            status: '',
            search: '',
            show_mine_only: false
        };
        
        // Reset form controls
        document.getElementById('applicationFilter').value = '';
        document.getElementById('priorityFilter').value = '';
        document.getElementById('statusFilter').value = '';
        document.getElementById('searchFilter').value = '';
        document.getElementById('showMineOnly').checked = false;
        
        this.applyFilters();
        this.updateURL();
    }
    
    updateURL() {
        const url = new URL(window.location);
        
        // Update show_mine_only parameter
        if (this.currentFilters.show_mine_only) {
            url.searchParams.set('show_mine_only', 'true');
        } else {
            url.searchParams.delete('show_mine_only');
        }
        
        window.history.replaceState({}, '', url);
    }
    
    confirmDelete(storyId, storyTitle) {
        this.deleteStoryId = storyId;
        document.querySelector('#deleteModal .modal-body').innerHTML = 
            `Are you sure you want to delete the user story "<strong>${this.escapeHtml(storyTitle)}</strong>"? This action cannot be undone.`;
        
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }
    
    async executeDelete() {
        if (!this.deleteStoryId) return;
        
        try {
            const response = await fetch(`api/user_stories/delete_story.php?id=${this.deleteStoryId}`, {
                method: 'DELETE'
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('User story deleted successfully');
                this.loadStories(); // Reload the list
                
                // Hide modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                modal.hide();
            } else {
                this.showError('Failed to delete user story: ' + result.error);
            }
        } catch (error) {
            console.error('Error deleting story:', error);
            this.showError('Failed to delete user story');
        }
        
        this.deleteStoryId = null;
    }
    
    // Utility functions
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    truncateText(text, maxLength) {
        if (!text) return '';
        if (text.length <= maxLength) return this.escapeHtml(text);
        return this.escapeHtml(text.substring(0, maxLength)) + '...';
    }
    
    formatStatus(status) {
        const statusMap = {
            'backlog': 'Backlog',
            'in_progress': 'In Progress',
            'review': 'Review',
            'done': 'Done',
            'cancelled': 'Cancelled'
        };
        return statusMap[status] || status;
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
    
    showSuccess(message) {
        const toast = document.getElementById('successToast');
        document.getElementById('successMessage').textContent = message;
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }
    
    showError(message) {
        const toast = document.getElementById('errorToast');
        document.getElementById('errorMessage').textContent = message;
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }
    
    debounce(func, wait) {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(func, wait);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.userStoriesDashboard = new UserStoriesDashboard();
});
