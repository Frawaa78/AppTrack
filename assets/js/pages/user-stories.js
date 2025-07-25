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
        this.needsReload = false; // Track if we need to reload after editing
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadFormOptions();
        
        // Check URL parameters for initial filters
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('show_mine_only') === 'true') {
            this.currentFilters.show_mine_only = true;
            document.getElementById('showMineOnly').checked = true;
        }
        
        // Store application_id if present and set it as a filter
        this.fromAppId = urlParams.get('application_id');
        if (this.fromAppId) {
            this.currentFilters.application_id = this.fromAppId;
        }
        
        this.loadStories();
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

        // Inline editing event delegation
        document.addEventListener('click', (e) => {
            if (e.target.closest('.editable-field') && !e.target.closest('.editable-field').classList.contains('editing')) {
                this.startInlineEdit(e.target.closest('.editable-field'));
            }
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
        
        // Set the selected value if we have an application_id from URL
        if (this.fromAppId) {
            select.value = this.fromAppId;
        }
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
                this.renderStories();
            } else {
                this.showError('Failed to load user stories: ' + result.error);
            }
        } catch (error) {
            console.error('Error loading stories:', error);
            this.showError('Failed to load user stories');
        }
    }
    
    renderStories() {
        const tbody = document.getElementById('storiesTableBody');
        
        if (this.stories.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center empty-state">
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
                        <span class="story-role"><span style="color: #0D8ABC; font-weight: bold;">As a</span> ${this.escapeHtml(story.role)}</span><br>
                        <small class="text-muted"><span style="color: #0D8ABC; font-weight: bold;">I want to</span> ${this.truncateText(story.want_to, 60)}</small>
                    </div>
                </td>
                <td>
                    <div class="editable-application editable-field" 
                         data-story-id="${story.id}" 
                         data-field="application_ids"
                         data-current-value="${story.application_id || ''}"
                         title="Click to edit applications">
                        ${this.renderApplicationBadges(story.application_name, story.application_id)}
                    </div>
                </td>
                <td>
                    <div class="editable-priority editable-field" 
                         data-story-id="${story.id}" 
                         data-field="priority"
                         data-current-value="${story.priority}"
                         title="Click to edit priority">
                        <span class="badge priority-badge priority-${story.priority.toLowerCase()}">
                            ${story.priority}
                        </span>
                    </div>
                </td>
                <td>
                    <div class="editable-status editable-field" 
                         data-story-id="${story.id}" 
                         data-field="status"
                         data-current-value="${story.status}"
                         title="Click to edit status">
                        <span class="badge status-badge status-${story.status}">
                            ${this.formatStatus(story.status)}
                        </span>
                    </div>
                </td>
                <td>
                    <div class="action-buttons">
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
    }    applyFilters() {
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
    
    renderApplicationBadges(applicationNames, applicationIds) {
        if (!applicationNames) {
            return '<span class="text-muted">-</span>';
        }
        
        // Split application names and IDs
        const names = applicationNames.split(', ');
        
        return names.map((name) => {
            const badgeClass = 'badge bg-light text-dark border me-1 mb-1';
            return `<span class="${badgeClass}">${this.escapeHtml(name.trim())}</span>`;
        }).join('');
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

    // Inline editing methods
    async startInlineEdit(fieldElement) {
        const storyId = fieldElement.dataset.storyId;
        const field = fieldElement.dataset.field;
        const currentValue = fieldElement.dataset.currentValue;

        // Prevent multiple edits at once - but allow same field to be re-edited
        const existingEditor = document.querySelector('.editing');
        if (existingEditor && existingEditor !== fieldElement) {
            this.cancelInlineEdit(existingEditor, false); // Don't reload on cancel
        }

        fieldElement.classList.add('editing');

        if (field === 'application_ids') {
            await this.createApplicationEditor(fieldElement, storyId, currentValue);
        } else if (field === 'priority') {
            this.createPriorityEditor(fieldElement, storyId, currentValue);
        } else if (field === 'status') {
            this.createStatusEditor(fieldElement, storyId, currentValue);
        }
    }

    async createApplicationEditor(fieldElement, storyId, currentValue) {
        // Create popover with Choices.js multiselect
        await this.createApplicationPopover(fieldElement, storyId, currentValue);
    }

    createPriorityEditor(fieldElement, storyId, currentValue) {
        const selectElement = document.createElement('select');
        selectElement.className = 'form-select inline-edit-select';
        
        const priorities = ['Low', 'Medium', 'High', 'Critical'];
        priorities.forEach(priority => {
            const option = document.createElement('option');
            option.value = priority;
            option.textContent = priority;
            option.selected = priority === currentValue;
            selectElement.appendChild(option);
        });

        fieldElement.innerHTML = '';
        fieldElement.appendChild(selectElement);

        let hasChanged = false;
        const saveEdit = async () => {
            if (!hasChanged || fieldElement.classList.contains('saving')) return;
            hasChanged = false;
            await this.saveInlineEdit(storyId, 'priority', selectElement.value);
            // Close the editor after saving for priority (single selection)
            this.cancelInlineEdit(fieldElement);
        };

        // Save immediately on change
        selectElement.addEventListener('change', () => {
            hasChanged = true;
            saveEdit();
        });
        
        // Handle keyboard events
        selectElement.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                hasChanged = true;
                saveEdit();
            } else if (e.key === 'Escape') {
                this.cancelInlineEdit(fieldElement);
            }
        });

        // Handle clicks outside to cancel editing
        const handleClickOutside = (e) => {
            if (!fieldElement.contains(e.target)) {
                this.cancelInlineEdit(fieldElement);
            }
        };
        
        // Store the handler so we can remove it later
        fieldElement._clickOutsideHandler = handleClickOutside;
        
        setTimeout(() => {
            document.addEventListener('click', handleClickOutside);
        }, 100);

        selectElement.focus();
    }

    createStatusEditor(fieldElement, storyId, currentValue) {
        const selectElement = document.createElement('select');
        selectElement.className = 'form-select inline-edit-select';
        
        const statuses = [
            { value: 'backlog', label: 'Backlog' },
            { value: 'in_progress', label: 'In Progress' },
            { value: 'review', label: 'Review' },
            { value: 'done', label: 'Done' },
            { value: 'cancelled', label: 'Cancelled' }
        ];

        statuses.forEach(status => {
            const option = document.createElement('option');
            option.value = status.value;
            option.textContent = status.label;
            option.selected = status.value === currentValue;
            selectElement.appendChild(option);
        });

        fieldElement.innerHTML = '';
        fieldElement.appendChild(selectElement);

        let hasChanged = false;
        const saveEdit = async () => {
            if (!hasChanged || fieldElement.classList.contains('saving')) return;
            hasChanged = false;
            await this.saveInlineEdit(storyId, 'status', selectElement.value);
            // Close the editor after saving for status (single selection)
            this.cancelInlineEdit(fieldElement);
        };

        // Save immediately on change
        selectElement.addEventListener('change', () => {
            hasChanged = true;
            saveEdit();
        });
        
        // Handle keyboard events
        selectElement.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                hasChanged = true;
                saveEdit();
            } else if (e.key === 'Escape') {
                this.cancelInlineEdit(fieldElement);
            }
        });

        // Handle clicks outside to cancel editing
        const handleClickOutside = (e) => {
            if (!fieldElement.contains(e.target)) {
                this.cancelInlineEdit(fieldElement);
            }
        };
        
        // Store the handler so we can remove it later
        fieldElement._clickOutsideHandler = handleClickOutside;
        
        setTimeout(() => {
            document.addEventListener('click', handleClickOutside);
        }, 100);

        selectElement.focus();
    }

    async saveInlineEdit(storyId, field, value) {
        try {
            // Handle array values for application_ids
            let requestData = {};
            if (field === 'application_ids' && Array.isArray(value)) {
                requestData['application_id'] = value.join(',');
            } else {
                requestData[field] = value;
            }

            const response = await fetch(`api/user_stories/update_story.php?id=${storyId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            });

            const result = await response.json();

            if (result.success) {
                // Show success message if available
                if (typeof this.showSuccess === 'function') {
                    this.showSuccess('Story updated successfully');
                }
                
                // Don't reload immediately - let user continue editing
                // Mark that we need to reload when editing is complete
                this.needsReload = true;
            } else {
                this.showError('Failed to update: ' + result.error);
            }
        } catch (error) {
            console.error('Error updating story:', error);
            this.showError('Failed to update story');
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    cancelInlineEdit(fieldElement, shouldReload = true) {
        fieldElement.classList.remove('editing', 'saving');
        
        // Remove any existing click outside handlers
        if (fieldElement._clickOutsideHandler) {
            document.removeEventListener('click', fieldElement._clickOutsideHandler);
            fieldElement._clickOutsideHandler = null;
        }
        
        // Destroy any active Choices.js instances
        const choicesElement = fieldElement.querySelector('.choices');
        if (choicesElement && choicesElement._choices) {
            choicesElement._choices.destroy();
        }
        
        // Only reload if explicitly requested and there were changes
        if (shouldReload && this.needsReload) {
            this.needsReload = false;
            this.loadStories();
        } else if (shouldReload) {
            // Just restore the original content if no changes were made
            this.loadStories();
        }
    }

    async createApplicationPopover(fieldElement, storyId, currentValue) {
        // Create select element for Choices.js
        const selectElement = document.createElement('select');
        selectElement.multiple = true;
        selectElement.className = 'form-select inline-edit-applications';
        selectElement.style.width = '300px'; // Fixed width for popover
        
        // Get current selection
        const currentIds = currentValue ? currentValue.split(',').map(id => id.trim()) : [];
        
        fieldElement.innerHTML = '';
        fieldElement.appendChild(selectElement);
        
        // Initialize Choices.js with autocomplete
        const choices = new Choices(selectElement, {
            removeItemButton: true,
            searchEnabled: true,
            searchPlaceholderValue: 'Search applications...',
            placeholder: true,
            placeholderValue: 'Select applications',
            loadingText: 'Loading...',
            noResultsText: 'No applications found',
            noChoicesText: 'No applications to choose from',
            itemSelectText: 'Press to select',
            maxItemCount: 10,
            searchResultLimit: 20,
            shouldSort: false
        });

        // Add CSS classes after initialization
        const choicesContainer = fieldElement.querySelector('.choices');
        if (choicesContainer) {
            choicesContainer.classList.add('inline-edit-choices', 'inline-edit-applications');
            // Store Choices instance for cleanup
            choicesContainer._choices = choices;
        }

        // Load initial data and set current values after initialization
        this.loadApplicationChoices(choices, currentIds);

        // Handle search with autocomplete
        let searchTimeout;
        selectElement.addEventListener('search', (event) => {
            const query = event.detail.value;
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.searchApplicationChoices(choices, query, currentIds);
            }, 300);
        });

        // Save on change
        selectElement.addEventListener('change', async () => {
            const selectedValues = choices.getValue().map(item => item.value);
            await this.saveInlineEdit(storyId, 'application_ids', selectedValues);
        });

        // Handle escape key to cancel
        selectElement.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.cancelInlineEdit(fieldElement);
            }
        });

        // Handle clicks outside to cancel editing
        const handleClickOutside = (e) => {
            if (!fieldElement.contains(e.target)) {
                this.cancelInlineEdit(fieldElement);
            }
        };
        
        // Store the handler so we can remove it later
        fieldElement._clickOutsideHandler = handleClickOutside;
        
        // Add click outside listener after a short delay to avoid immediate triggering
        setTimeout(() => {
            document.addEventListener('click', handleClickOutside);
        }, 100);

        // Focus the search input
        setTimeout(() => {
            const searchInput = fieldElement.querySelector('.choices__input--cloned');
            if (searchInput) {
                searchInput.focus();
            }
        }, 100);
    }

    async loadApplicationChoices(choices, currentIds = []) {
        try {
            // Load current applications first
            if (currentIds.length > 0) {
                const currentApps = await this.loadApplicationsByIds(currentIds);
                currentApps.forEach(app => {
                    choices.setChoices([{
                        value: app.value,
                        label: app.label,
                        selected: true
                    }], 'value', 'label', false);
                });
            }

            // Load additional applications for search - use a common search term or get recent ones
            const response = await fetch('api/search_applications.php?q=a&limit=20'); // Search for 'a' to get many results
            const data = await response.json();
            
            if (!data.error && Array.isArray(data)) {
                const newChoices = data
                    .filter(app => !currentIds.includes(app.value))
                    .map(app => ({
                        value: app.value,
                        label: app.label,
                        selected: false
                    }));
                
                choices.setChoices(newChoices, 'value', 'label', false);
            }
        } catch (error) {
            console.error('Error loading application choices:', error);
        }
    }

    async searchApplicationChoices(choices, query, currentIds = []) {
        try {
            // If query is too short, just load some default results
            let searchQuery = query;
            if (query.length < 2) {
                searchQuery = 'a'; // Use 'a' to get many results when query is short
            }
            
            const response = await fetch(`api/search_applications.php?q=${encodeURIComponent(searchQuery)}&limit=20`);
            const data = await response.json();
            
            if (!data.error && Array.isArray(data)) {
                // Clear existing choices except selected ones
                choices.clearChoices();
                
                // Add search results
                const searchChoices = data.map(app => ({
                    value: app.value,
                    label: app.label,
                    selected: currentIds.includes(app.value)
                }));
                
                choices.setChoices(searchChoices, 'value', 'label', true);
            }
        } catch (error) {
            console.error('Error searching applications:', error);
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    async loadApplicationsByIds(ids) {
        const response = await fetch(`api/get_applications_by_ids.php?ids=${encodeURIComponent(ids.join(','))}`);
        if (!response.ok) throw new Error('Failed to load applications');
        const data = await response.json();
        // Convert API response format to expected format
        return data.map(app => ({
            value: app.id,
            label: app.short_description
        }));
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.userStoriesDashboard = new UserStoriesDashboard();
});
