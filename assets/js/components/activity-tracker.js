// assets/js/components/activity-tracker.js

class ActivityTracker {
    constructor(applicationId, userRole, readOnly = false) {
        this.applicationId = applicationId;
        this.userRole = userRole;
        this.readOnly = readOnly;
        this.currentFilters = {
            show_work_notes_only: true, // Default til true (switch på)
            show_hidden: false
        };
        this.pagination = {
            offset: 0,
            limit: 5,
            hasMore: false,
            total: 0
        };
        this.activities = []; // Store all loaded activities
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.initializeSwitchStates(); // Set initial switch states
        this.loadActivityFeed(true); // true = reset pagination
    }
    
    initializeSwitchStates() {
        // Set initial state for work notes switch (checked = true)
        const workNotesSwitch = document.getElementById('filter-work-notes-only');
        if (workNotesSwitch) {
            workNotesSwitch.checked = this.currentFilters.show_work_notes_only;
        }
        
        // Set initial state for show hidden switch (checked = false)
        const showHiddenSwitch = document.getElementById('filter-show-hidden');
        if (showHiddenSwitch) {
            showHiddenSwitch.checked = this.currentFilters.show_hidden;
        }
    }
    
    bindEvents() {
        // Skip most event binding in read-only mode
        if (this.readOnly) {
            // Only bind load more button for read-only mode
            document.getElementById('load-more-btn')?.addEventListener('click', () => {
                this.loadActivityFeed(false); // false = append to existing
            });
            return;
        }
        
        // Full event binding for editable mode
        // Filter switches
        document.getElementById('filter-work-notes-only')?.addEventListener('change', (e) => {
            this.currentFilters.show_work_notes_only = e.target.checked;
            this.resetPagination();
            this.loadActivityFeed(true);
        });
        
        document.getElementById('filter-show-hidden')?.addEventListener('change', (e) => {
            this.currentFilters.show_hidden = e.target.checked;
            this.resetPagination();
            this.loadActivityFeed(true);
        });
        
        // Work notes form
        document.getElementById('work-notes-form')?.addEventListener('submit', (e) => {
            this.handleWorkNoteSubmit(e);
        });
        
        // Load more button
        document.getElementById('load-more-btn')?.addEventListener('click', () => {
            this.loadActivityFeed(false); // false = append to existing
        });
        
        // File upload info display
        this.setupFileUpload();
        
        // Admin controls
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('hide-activity-btn')) {
                this.hideActivity(e.target.dataset.type, e.target.dataset.id);
            }
            if (e.target.classList.contains('show-activity-btn')) {
                this.showActivity(e.target.dataset.type, e.target.dataset.id);
            }
            if (e.target.classList.contains('delete-attachment-btn') || e.target.closest('.delete-attachment-btn')) {
                const btn = e.target.classList.contains('delete-attachment-btn') ? e.target : e.target.closest('.delete-attachment-btn');
                this.deleteAttachment(btn.dataset.workNoteId);
            }
        });
    }
    
    setupFileUpload() {
        const fileInput = document.getElementById('work-note-file');
        const fileInfo = document.getElementById('file-info');
        
        if (!fileInput || !fileInfo) return;
        
        // File selection
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.updateFileInfo(e.target.files[0]);
            } else {
                fileInfo.innerHTML = '';
            }
        });
    }
    
    updateFileInfo(file) {
        const fileInfo = document.getElementById('file-info');
        if (fileInfo) {
            const sizeFormatted = this.formatFileSize(file.size);
            fileInfo.innerHTML = `
                <strong>Selected:</strong> ${file.name} (${sizeFormatted})
            `;
        }
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    resetPagination() {
        this.pagination.offset = 0;
        this.activities = [];
    }
    
    async loadActivityFeed(reset = false) {
        if (reset) {
            this.resetPagination();
        }
        
        try {
            const response = await fetch('api/get_activity_feed.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    application_id: this.applicationId,
                    filters: this.currentFilters,
                    limit: this.pagination.limit,
                    offset: this.pagination.offset
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Update pagination info
                this.pagination.total = data.pagination.total;
                this.pagination.hasMore = data.pagination.has_more;
                
                if (reset) {
                    // Replace activities
                    this.activities = data.activities;
                } else {
                    // Append new activities
                    this.activities = [...this.activities, ...data.activities];
                }
                
                // Update offset for next load
                this.pagination.offset = this.activities.length;
                
                this.renderActivityFeed();
                this.updateLoadMoreButton();
            } else {
                console.error('API Error:', data.error);
                this.showErrorInContainer(data.error);
            }
        } catch (error) {
            console.error('Error loading activity feed:', error);
            this.showErrorInContainer('Failed to load activities: ' + error.message);
        }
    }
    
    showErrorInContainer(message) {
        const container = document.getElementById('activity-feed-container');
        if (container) {
            container.innerHTML = `
                <div class="text-center text-danger p-4">
                    <i class="bi bi-exclamation-triangle"></i>
                    <p>${message}</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                        Retry
                    </button>
                </div>
            `;
        }
    }
    
    renderActivityFeed() {
        const container = document.getElementById('activity-feed-container');
        if (!container) return;
        
        if (this.activities.length === 0) {
            container.innerHTML = '<div class="text-center text-muted p-4">No activities found</div>';
            return;
        }
        
        const html = this.activities.map(activity => this.renderActivityItem(activity)).join('');
        container.innerHTML = html;
    }
    
    updateLoadMoreButton() {
        const loadMoreContainer = document.getElementById('load-more-container');
        const loadMoreBtn = document.getElementById('load-more-btn');
        
        if (loadMoreContainer && loadMoreBtn) {
            if (this.pagination.hasMore) {
                loadMoreContainer.style.display = 'block';
                const remaining = this.pagination.total - this.activities.length;
                loadMoreBtn.textContent = `Load More Activities (${remaining} remaining)`;
            } else {
                loadMoreContainer.style.display = 'none';
            }
        }
    }
    
    renderActivityItem(activity) {
        const isHidden = !activity.is_visible;
        const typeClass = activity.activity_type === 'work_note' ? 'work-note' : 'audit-log';
        const priorityClass = activity.priority ? `priority-${activity.priority}` : '';
        const hiddenClass = isHidden ? 'hidden' : '';
        
        // Add specific type class for border color
        let activityTypeClass = '';
        if (activity.activity_type === 'work_note') {
            activityTypeClass = `activity-type-${activity.type || 'comment'}`;
        } else {
            activityTypeClass = 'activity-type-field-change';
        }
        
        let contentHtml = '';
        
        if (activity.activity_type === 'work_note') {
            contentHtml = `
                <div class="activity-header-row">
                    <span class="activity-user">${this.escapeHtml(activity.user_display_name || activity.user_email || 'Unknown')}</span>
                </div>
                <div class="activity-type-badge type-${activity.type}">
                    ${activity.type || 'comment'}
                </div>
                <div class="activity-content mt-2">
                    ${this.escapeHtmlWithLineBreaks(activity.content)}
                </div>
                ${this.renderAttachment(activity)}
                <div class="activity-footer">
                    <span class="activity-time">${this.formatDateTime(activity.created_at)}</span>
                </div>
            `;
        } else {
            contentHtml = `
                <div class="activity-header-row">
                    <span class="activity-user">${this.escapeHtml(activity.user_display_name || activity.user_email || 'System')}</span>
                </div>
                <div class="activity-type-badge type-field-change">
                    Field Change
                </div>
                <div class="activity-content mt-2">
                    ${this.escapeHtmlWithLineBreaks(activity.content)}
                </div>
                <div class="activity-footer">
                    <span class="activity-time">${this.formatDateTime(activity.created_at)}</span>
                </div>
            `;
        }
        
        const adminControls = (!this.readOnly && this.userRole === 'admin') ? this.renderAdminControls(activity) : '';
        
        return `
            <div class="activity-item ${typeClass} ${priorityClass} ${hiddenClass} ${activityTypeClass}" data-id="${activity.id}">
                ${adminControls}
                ${contentHtml}
            </div>
        `;
    }
    
    renderAttachment(activity) {
        if (!activity.attachment_filename) {
            return '';
        }
        
        const sizeFormatted = this.formatFileSize(activity.attachment_size || 0);
        
        // Only show delete button for admins and if not in read-only mode
        const deleteButton = (!this.readOnly && this.userRole === 'admin') ? 
            `<button class="btn btn-sm btn-outline-danger ms-2 delete-attachment-btn" 
                     data-work-note-id="${activity.id}" 
                     title="Delete attachment">
                <i class="bi bi-trash"></i>
            </button>` : '';
        
        return `
            <div class="activity-attachment">
                <div class="d-flex align-items-center">
                    <a href="api/download_attachment.php?id=${activity.id}" 
                       class="attachment-link" 
                       target="_blank">
                        📎 ${this.escapeHtml(activity.attachment_filename)} (${sizeFormatted})
                    </a>
                    ${deleteButton}
                </div>
            </div>
        `;
    }
    
    renderAdminControls(activity) {
        const isHidden = !activity.is_visible;
        
        return `
            <div class="admin-controls">
                ${isHidden ? 
                    `<button class="show-activity-btn" data-type="${activity.activity_type}" data-id="${activity.id}">Show</button>` :
                    `<button class="hide-activity-btn" data-type="${activity.activity_type}" data-id="${activity.id}">Hide</button>`
                }
            </div>
        `;
    }
    
    async handleWorkNoteSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        formData.append('application_id', this.applicationId);
        
        try {
            const response = await fetch('api/add_work_note.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                form.reset();
                document.getElementById('file-info').innerHTML = '';
                // Reset pagination and reload feed after adding work note
                this.resetPagination();
                await this.loadActivityFeed(true);
                this.showSuccessMessage('Work note added successfully');
            } else {
                this.showErrorMessage(data.error || 'Error adding work note');
            }
        } catch (error) {
            this.showErrorMessage('Error submitting work note');
        }
    }
    
    async hideActivity(type, id) {
        if (!confirm('Are you sure you want to hide this activity?')) {
            return;
        }
        
        try {
            const response = await fetch('api/hide_activity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    activity_type: type,
                    activity_id: id
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Reset pagination and reload feed after hiding activity
                this.resetPagination();
                this.loadActivityFeed(true);
            } else {
                this.showErrorMessage(data.error || 'Error hiding activity');
            }
        } catch (error) {
            console.error('Error hiding activity:', error);
        }
    }
    
    async showActivity(type, id) {
        try {
            const response = await fetch('api/show_activity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    activity_type: type,
                    activity_id: id
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Reset pagination and reload feed after showing activity
                this.resetPagination();
                this.loadActivityFeed(true);
            } else {
                this.showErrorMessage(data.error || 'Error showing activity');
            }
        } catch (error) {
            console.error('Error showing activity:', error);
        }
    }
    
    async deleteAttachment(workNoteId) {
        if (!confirm('Are you sure you want to delete this attachment? This action cannot be undone.')) {
            return;
        }
        
        try {
            const response = await fetch('api/delete_attachment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    work_note_id: workNoteId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Reset pagination and reload feed after deleting attachment
                this.resetPagination();
                this.loadActivityFeed(true);
                this.showSuccessMessage('Attachment deleted successfully');
            } else {
                this.showErrorMessage(data.error || 'Error deleting attachment');
            }
        } catch (error) {
            console.error('Error deleting attachment:', error);
            this.showErrorMessage('Error deleting attachment');
        }
    }
    
    formatDateTime(dateString) {
        const date = new Date(dateString);
        
        // Day names in English
        const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        const dayName = dayNames[date.getDay()];
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');
        
        return `${dayName} - ${day}.${month}.${year} @ ${hours}:${minutes}:${seconds}`;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    escapeHtmlWithLineBreaks(text) {
        if (!text) return '';
        // First escape HTML entities
        const escaped = this.escapeHtml(text);
        // Then convert line breaks to <br> tags
        return escaped.replace(/\n/g, '<br>');
    }
    
    showSuccessMessage(message) {
        // Implementation for toast or alert
        // Using browser alert for now, could be replaced with toast library
        alert(message);
    }
    
    showErrorMessage(message) {
        // Implementation for toast or alert
        console.error('Error:', message);
        alert(message);
    }
}
