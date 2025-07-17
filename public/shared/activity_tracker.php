<?php
// public/shared/activity_tracker.php
// Inkluder denne komponenten i app_form.php og app_view.php

if (!isset($application_id) || !isset($_SESSION['user_id'])) {
    return;
}

$user_role = $_SESSION['user_role'] ?? 'viewer';
?>

<div class="activity-tracker">
    <div class="activity-header">
        <h5>Activity Tracker</h5>
        <div class="activity-content-wrapper">
            <div class="activity-filters">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="filter-work-notes-only" checked>
                    <label class="form-check-label" for="filter-work-notes-only">
                        Show only Work notes
                    </label>
                </div>
                <?php if ($user_role === 'admin'): ?>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="filter-show-hidden">
                        <label class="form-check-label" for="filter-show-hidden">
                            Show hidden
                        </label>
                    </div>
                <?php endif; ?>
            </div>
            
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize activity tracker
    const activityTracker = new ActivityTracker(<?php echo $application_id; ?>, '<?php echo $user_role; ?>');
});
</script>
