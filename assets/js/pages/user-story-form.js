// User Story Form JavaScript
// assets/js/pages/user-story-form.js

class UserStoryForm {
    constructor() {
        this.isEditMode = document.getElementById('storyId') !== null;
        this.storyId = this.isEditMode ? document.getElementById('storyId').value : null;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.updatePreview();
    }
    
    bindEvents() {
        // Form submission
        document.getElementById('userStoryForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitForm();
        });
        
        // Real-time preview update
        ['role', 'wantTo', 'soThat'].forEach(fieldId => {
            document.getElementById(fieldId).addEventListener('input', () => {
                this.updatePreview();
            });
        });
        
        // Jira import button
        document.getElementById('jiraImportBtn').addEventListener('click', () => {
            this.showJiraImportDialog();
        });
        
        // Auto-populate fields based on title
        document.getElementById('title').addEventListener('blur', () => {
            this.suggestFieldsFromTitle();
        });
    }
    
    updatePreview() {
        const role = document.getElementById('role').value.trim();
        const wantTo = document.getElementById('wantTo').value.trim();
        const soThat = document.getElementById('soThat').value.trim();
        
        // Create or update preview
        let preview = document.getElementById('storyPreview');
        if (!preview) {
            preview = document.createElement('div');
            preview.id = 'storyPreview';
            preview.className = 'user-story-preview';
            
            const card = document.querySelector('.card-body .card');
            if (card) {
                card.appendChild(preview);
            }
        }
        
        if (role || wantTo || soThat) {
            preview.innerHTML = `
                <h6><i class="bi bi-eye"></i> Story Preview:</h6>
                <p><strong>As a</strong> ${role || '[role]'}</p>
                <p><strong>I want to</strong> ${wantTo || '[capability]'}</p>
                <p><strong>So that</strong> ${soThat || '[benefit]'}</p>
            `;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
    }
    
    async submitForm() {
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
        
        try {
            const formData = this.collectFormData();
            const url = this.isEditMode 
                ? `api/user_stories/update_story.php?id=${this.storyId}`
                : 'api/user_stories/create_story.php';
            
            const method = this.isEditMode ? 'PUT' : 'POST';
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            // Check if response is ok
            if (!response.ok) {
                // Try to get error text instead of JSON
                const errorText = await response.text();
                console.error('Server error response:', errorText);
                throw new Error(`Server error: ${response.status} - ${errorText}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(result.message || 'User story saved successfully');
                
                // Redirect after a short delay
                setTimeout(() => {
                    if (this.isEditMode) {
                        window.location.href = `user_story_view.php?id=${this.storyId}`;
                    } else {
                        window.location.href = `user_story_view.php?id=${result.data.id}`;
                    }
                }, 1500);
            } else {
                this.showError(result.error || 'Failed to save user story');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
            
        } catch (error) {
            console.error('Error submitting form:', error);
            this.showError('Failed to save user story');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }
    
    collectFormData() {
        const formData = {};
        
        // Text inputs
        const textFields = [
            'title', 'jira_id', 'role', 'want_to', 'so_that',
            'jira_url', 'sharepoint_url', 'tags', 'category'
        ];
        
        textFields.forEach(field => {
            const element = document.getElementById(field.replace('_', ''));
            if (element) {
                formData[field] = element.value.trim() || null;
            }
        });
        
        // Special mappings for camelCase IDs
        formData.want_to = document.getElementById('wantTo').value.trim() || null;
        formData.so_that = document.getElementById('soThat').value.trim() || null;
        formData.jira_id = document.getElementById('jiraId').value.trim() || null;
        formData.jira_url = document.getElementById('jiraUrl').value.trim() || null;
        formData.sharepoint_url = document.getElementById('sharepointUrl').value.trim() || null;
        
        // Handle multiple application IDs
        const applicationSelect = document.getElementById('applicationIds');
        if (applicationSelect) {
            const selectedApplications = Array.from(applicationSelect.selectedOptions).map(option => parseInt(option.value));
            formData.application_ids = selectedApplications.length > 0 ? selectedApplications : null;
        } else {
            formData.application_ids = null;
        }
        
        // Select fields
        formData.priority = document.getElementById('priority').value;
        formData.status = document.getElementById('status').value;
        
        // Handle multiple application IDs - keep backward compatibility
        if (formData.application_ids && formData.application_ids.length > 0) {
            // For now, take the first selected application for backward compatibility
            formData.application_id = formData.application_ids[0];
        } else {
            formData.application_id = null;
        }
        
        // Source tracking
        formData.source = 'manual';
        formData.manual_entry = true;
        
        return formData;
    }
    
    suggestFieldsFromTitle() {
        const title = document.getElementById('title').value.trim().toLowerCase();
        
        // Simple AI-like suggestions based on keywords
        if (title.includes('dashboard')) {
            if (!document.getElementById('role').value) {
                document.getElementById('role').value = 'Operations Engineer';
            }
            if (!document.getElementById('category').value) {
                document.getElementById('category').value = 'Monitoring';
            }
        }
        
        if (title.includes('mobile') || title.includes('app')) {
            if (!document.getElementById('role').value) {
                document.getElementById('role').value = 'Field Technician';
            }
            if (!document.getElementById('tags').value) {
                document.getElementById('tags').value = 'mobile, app';
            }
        }
        
        if (title.includes('report') || title.includes('analytics')) {
            if (!document.getElementById('role').value) {
                document.getElementById('role').value = 'Business Analyst';
            }
            if (!document.getElementById('category').value) {
                document.getElementById('category').value = 'Reporting';
            }
        }
        
        this.updatePreview();
    }
    
    showJiraImportDialog() {
        // For now, show a simple prompt - this can be enhanced later
        const jiraUrl = prompt('Enter Jira URL or issue key:');
        if (jiraUrl) {
            this.importFromJira(jiraUrl);
        }
    }
    
    async importFromJira(jiraInput) {
        // This is a placeholder for future Jira integration
        // For now, we'll just extract the issue key if it's a URL
        let jiraId = jiraInput;
        
        if (jiraInput.includes('/browse/')) {
            jiraId = jiraInput.split('/browse/')[1];
        }
        
        if (jiraId) {
            document.getElementById('jiraId').value = jiraId;
            document.getElementById('jiraUrl').value = jiraInput.startsWith('http') ? jiraInput : '';
            
            // Set source as Jira import
            document.querySelector('input[name="source"]') && 
                (document.querySelector('input[name="source"]').value = 'jira_import');
            
            this.showSuccess('Jira information added. Please fill in the story details manually.');
        }
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
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.userStoryForm = new UserStoryForm();
});
