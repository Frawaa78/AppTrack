<?php
// public/handover/sections/step_1.php - Definitions
?>

<div class="alert alert-info">
    <h6><i class="fas fa-info-circle"></i> About this section</h6>
    <p class="mb-0">This is an informational section that defines key terms and abbreviations used in the handover document. No input required.</p>
</div>

<div class="row">
    <div class="col-12">
        <h6>Definitions and abbreviations</h6>
        
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th style="width: 20%;">Term/Abbreviation</th>
                        <th>Definition</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Operations</strong></td>
                        <td>IT Operations responsible for operation and maintenance of IT systems</td>
                    </tr>
                    <tr>
                        <td><strong>PM</strong></td>
                        <td>Project Manager</td>
                    </tr>
                    <tr>
                        <td><strong>RM</strong></td>
                        <td>Release Manager - Responsible for product releases</td>
                    </tr>
                    <tr>
                        <td><strong>AM</strong></td>
                        <td>Application Manager - Application administrator</td>
                    </tr>
                    <tr>
                        <td><strong>MoM</strong></td>
                        <td>Minutes of Meeting</td>
                    </tr>
                    <tr>
                        <td><strong>SLA</strong></td>
                        <td>Service Level Agreement</td>
                    </tr>
                    <tr>
                        <td><strong>CMDB</strong></td>
                        <td>Configuration Management Database</td>
                    </tr>
                    <tr>
                        <td><strong>KB</strong></td>
                        <td>Knowledge Base</td>
                    </tr>
                    <tr>
                        <td><strong>MFA</strong></td>
                        <td>Multi Factor Authentication</td>
                    </tr>
                    <tr>
                        <td><strong>BMS</strong></td>
                        <td>Business Management System</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="alert alert-light mt-3">
            <h6><i class="fas fa-lightbulb"></i> Tips</h6>
            <p class="mb-0">This list can be expanded during the handover process if new terms are introduced. Make sure all participants understand the terminology before proceeding.</p>
        </div>
    </div>
</div>

<!-- Hidden input to mark this section as "visited" -->
<input type="hidden" name="definitions_completed" value="1">

<script>
// Auto-advance to next step after 5 seconds (optional)
document.addEventListener('DOMContentLoaded', function() {
    // Mark as completed immediately since this is just informational
    setTimeout(function() {
        document.getElementById('saveBtn').click();
    }, 1000);
});
</script>
