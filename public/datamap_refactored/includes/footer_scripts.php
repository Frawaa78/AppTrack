<!-- Footer Scripts -->

<!-- Drawflow JS -->
<script src="/assets/vendor/drawflow.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataMap Core JavaScript -->
<script src="/public/datamap_refactored/js/datamap-core.js"></script>

<script>
    // Initialize DataMap when DOM is loaded
    window.addEventListener('DOMContentLoaded', function() {
        console.log('🔧 REFACTORED DataMap Loading...');
        
        // Global variables
        window.applicationId = <?php echo isset($application_id) ? $application_id : 'null'; ?>;
        
        // Initialize the DataMap editor
        if (typeof window.DataMapCore !== 'undefined' && window.applicationId) {
            window.DataMapCore.init();
        } else {
            console.error('❌ DataMap Core not loaded or missing application ID');
        }
    });
</script>
