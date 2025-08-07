<!-- Application Header -->
<div class="app-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="javascript:history.back()" class="header-action-btn me-3" title="Go back to previous page">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <h1 class="app-title" style="margin: 0;">
                DataMap: <?php echo htmlspecialchars($application['name'] ?? 'Unknown Application'); ?>
                <span class="status-badge <?php echo strtolower($application['status'] ?? 'unknown'); ?>">
                    <?php echo htmlspecialchars($application['status'] ?? 'Unknown'); ?>
                </span>
            </h1>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button onclick="saveDiagram()" class="btn btn-primary">
                <i class="fas fa-save"></i> Save
            </button>
        </div>
    </div>
</div>
