// assets/js/components/activity-tracker.js

class ActivityTracker {
    constructor(applicationId, userRole) {
        this.applicationId = applicationId;
        this.userRole = userRole;
        this.currentFilters = {
            show_work_notes_only: false,
            show_hidden: false
        };
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadActivityFeed();
    }
    
    bindEvents() {
        // Filter-knapper
        document.getElementById('filter-work-notes-only')?.addEventListener('click', () => {
            this.toggleFilter('show_work_notes_only');
        });
        
        document.getElementById('filter-show-hidden')?.addEventListener('click', () => {
            this.toggleFilter('show_hidden');
        });
        
        // Work notes form
        document.getElementById('work-notes-form')?.addEventListener('submit', (e) => {
            this.handleWorkNoteSubmit(e);
        });
        
        // File upload
        this.setupFileUpload();
        
        // Admin controls
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('hide-activity-btn')) {
                this.hideActivity(e.target.dataset.type, e.target.dataset.id);
            }
            if (e.target.classList.contains('show-activity-btn')) {
                this.showActivity(e.target.dataset.type, e.target.dataset.id);
            }
        });
    }
    
    setupFileUpload() {
        const fileUpload = document.getElementById('work-note-file');
        const uploadArea = document.getElementById('file-upload-area');
        const fileInfo = document.getElementById('file-info');
        
        if (!fileUpload || !uploadArea) return;
        
        // Click to upload
        uploadArea.addEventListener('click', () => fileUpload.click());
        
        // Drag & Drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileUpload.files = files;
                this.updateFileInfo(files[0]);
            }
        });
        
        // File selection
        fileUpload.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.updateFileInfo(e.target.files[0]);
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
    
    toggleFilter(filterName) {
        this.currentFilters[filterName] = !this.currentFilters[filterName];
        
        // Oppdater button state
        const button = document.getElementById(`filter-${filterName.replace('_', '-')}`);
        if (button) {
            button.classList.toggle('btn-primary', this.currentFilters[filterName]);
            button.classList.toggle('btn-outline-secondary', !this.currentFilters[filterName]);
        }
        
        this.loadActivityFeed();
    }
    
    async loadActivityFeed() {
        try {
            const response = await fetch('api/get_activity_feed.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    application_id: this.applicationId,
                    filters: this.currentFilters
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.renderActivityFeed(data.activities);
            } else {
                console.error('Error loading activity feed:', data.error);
            }
        } catch (error) {
            console.error('Error loading activity feed:', error);
        }
    }
    
    renderActivityFeed(activities) {
        const container = document.getElementById('activity-feed-container');
        if (!container) return;
        
        if (activities.length === 0) {
            container.innerHTML = '<div class="text-center text-muted p-4">No activities found</div>';
            return;
        }
        
        const html = activities.map(activity => this.renderActivityItem(activity)).join('');
        container.innerHTML = html;
    }
    
    renderActivityItem(activity) {
        const isHidden = !activity.is_visible;
        const typeClass = activity.activity_type === 'work_note' ? 'work-note' : 'audit-log';
        const priorityClass = activity.priority ? `priority-${activity.priority}` : '';
        const hiddenClass = isHidden ? 'hidden' : '';
        
        let contentHtml = '';
        
        if (activity.activity_type === 'work_note') {
            contentHtml = `
                <div class="activity-header-row">
                    <span class="activity-user">${this.escapeHtml(activity.user_email || 'Unknown')}</span>
                    <span class="activity-time">${this.formatDateTime(activity.created_at)}</span>
                </div>
                <div class="activity-type-badge type-${activity.type}">
                    ${activity.type || 'comment'}
                </div>
                <div class="activity-content mt-2">
                    ${this.escapeHtml(activity.content)}
                </div>
                ${this.renderAttachment(activity)}
            `;
        } else {
            contentHtml = `
                <div class="activity-header-row">
                    <span class="activity-user">${this.escapeHtml(activity.user_email || 'System')}</span>
                    <span class="activity-time">${this.formatDateTime(activity.created_at)}</span>
                </div>
                <div class="activity-type-badge type-change">
                    Field Change
                </div>
                <div class="activity-content mt-2">
                    ${this.escapeHtml(activity.content)}
                </div>
            `;
        }
        
        const adminControls = this.userRole === 'admin' ? this.renderAdminControls(activity) : '';
        
        return `
            <div class="activity-item ${typeClass} ${priorityClass} ${hiddenClass}" data-id="${activity.id}">
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
        
        return `
            <div class="activity-attachment">
                <a href="api/download_attachment.php?id=${activity.id}" 
                   class="attachment-link" 
                   target="_blank">
                    📎 ${this.escapeHtml(activity.attachment_filename)} (${sizeFormatted})
                </a>
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
                this.loadActivityFeed();
                this.showSuccessMessage('Work note added successfully');
            } else {
                this.showErrorMessage(data.error || 'Error adding work note');
            }
        } catch (error) {
            console.error('Error submitting work note:', error);
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
                this.loadActivityFeed();
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
                this.loadActivityFeed();
            } else {
                this.showErrorMessage(data.error || 'Error showing activity');
            }
        } catch (error) {
            console.error('Error showing activity:', error);
        }
    }
    
    formatDateTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 1) {
            return 'Just now';
        } else if (diffMins < 60) {
            return `${diffMins} minute${diffMins !== 1 ? 's' : ''} ago`;
        } else if (diffHours < 24) {
            return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
        } else if (diffDays < 7) {
            return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
        } else {
            return date.toLocaleDateString();
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    showSuccessMessage(message) {
        // Implementer toast eller alert
        console.log('Success:', message);
    }
    
    showErrorMessage(message) {
        // Implementer toast eller alert
        console.error('Error:', message);
    }
}
