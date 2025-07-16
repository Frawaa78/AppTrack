<?php
// public/shared/activity_tracker.php
// Inkluder denne komponenten i app_form.php og app_view.php

if (!isset($application_id) || !isset($_SESSION['user_id'])) {
    return;
}

$user_role = $_SESSION['role'] ?? 'viewer';
?>

<div class="activity-tracker">
    <div class="activity-header">
        <h5>Activity Tracker</h5>
        <div class="activity-filters">
            <button id="filter-work-notes-only" class="btn btn-outline-secondary filter-btn">
                Work Notes Only
            </button>
            <?php if ($user_role === 'admin'): ?>
                <button id="filter-show-hidden" class="btn btn-outline-secondary filter-btn">
                    Show Hidden
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($user_role === 'editor' || $user_role === 'admin'): ?>
        <div class="work-notes-form">
            <form id="work-notes-form" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="work-note-text" class="form-label">Work Notes</label>
                    <textarea 
                        class="form-control" 
                        id="work-note-text" 
                        name="note" 
                        rows="3" 
                        placeholder="Add a comment, update, or note about this application..."
                        required></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <label for="work-note-type" class="form-label">Type</label>
                        <select class="form-select" id="work-note-type" name="type">
                            <option value="comment">Comment</option>
                            <option value="change">Change</option>
                            <option value="problem">Problem</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="work-note-priority" class="form-label">Priority</label>
                        <select class="form-select" id="work-note-priority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-3">
                    <label class="form-label">Attachment (Optional)</label>
                    <div id="file-upload-area" class="file-upload-area">
                        <p class="mb-1">Click to upload or drag and drop</p>
                        <small class="text-muted">
                            Supported: Images, PDF, Office docs, Text files, Archives (Max 10MB)
                        </small>
                    </div>
                    <input type="file" id="work-note-file" name="attachment" style="display: none;">
                    <div id="file-info" class="file-info"></div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        Add Work Note
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
    
    <div class="activity-feed" id="activity-feed-container">
        <!-- Activity feed will be loaded here via JavaScript -->
        <div class="text-center text-muted p-4">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            Loading activities...
        </div>
    </div>
    
    <div class="load-more-container" id="load-more-container" style="display: none;">
        <button class="btn btn-outline-primary load-more-btn" id="load-more-btn">
            Load More Activities
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize activity tracker
    const activityTracker = new ActivityTracker(<?php echo $application_id; ?>, '<?php echo $user_role; ?>');
});
</script>
