<?php
// public/handover/sections/step_15.php - Review & Export
?>

<div class="alert alert-info">
    <h6><i class="fas fa-check-double"></i> Review & Export</h6>
    <p class="mb-0">Final review of all handover documentation and export options.</p>
</div>

<div class="row">
    <div class="col-12">
        <!-- Completion Summary -->
        <div class="mb-4">
            <h6>Handover Completion Summary</h6>
            <div class="row">
                <?php
                $total_steps = 15;
                $completed_steps = 0;
                
                // Calculate completion based on existing data
                for ($i = 1; $i <= $total_steps; $i++) {
                    // This is simplified - in reality you'd check specific fields for each step
                    if (isset($existing_data["step_{$i}_completed"]) && $existing_data["step_{$i}_completed"]) {
                        $completed_steps++;
                    }
                }
                
                $completion_percentage = round(($completed_steps / $total_steps) * 100);
                ?>
                <div class="col-md-4">
                    <div class="card border-left-primary">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Steps Completed</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $completed_steps; ?> / <?php echo $total_steps; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-left-success">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Progress</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $completion_percentage; ?>%</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-left-warning">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Status</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                if ($completion_percentage >= 100) {
                                    echo '<i class="fas fa-check-circle text-success"></i> Complete';
                                } elseif ($completion_percentage >= 75) {
                                    echo '<i class="fas fa-clock text-warning"></i> Nearly Complete';
                                } else {
                                    echo '<i class="fas fa-hourglass-half text-info"></i> In Progress';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Document Review Checklist -->
        <div class="mb-4">
            <h6>Final Review Checklist</h6>
            <div class="card">
                <div class="card-body">
                    <?php
                    $checklist_items = [
                        'definitions_complete' => 'All definitions and terminology documented',
                        'participants_complete' => 'All participants and roles identified',
                        'contacts_complete' => 'Contact information verified and current',
                        'support_complete' => 'Support models and SLAs defined',
                        'deliverables_complete' => 'All deliverables documented and verified',
                        'testing_complete' => 'Testing procedures and results documented',
                        'release_complete' => 'Release management processes defined',
                        'technical_complete' => 'Technical documentation complete',
                        'risk_complete' => 'All risks identified and mitigation plans in place',
                        'security_complete' => 'Security requirements and controls documented',
                        'economics_complete' => 'Budget and cost elements documented',
                        'data_complete' => 'Data storage and management procedures defined',
                        'signatures_complete' => 'All required signatures obtained',
                        'meetings_complete' => 'Meeting minutes and decisions documented',
                        'export_ready' => 'Document ready for final export'
                    ];
                    
                    foreach ($checklist_items as $key => $description) {
                        $checked = isset($existing_data[$key]) && $existing_data[$key] ? 'checked' : '';
                        echo "<div class='form-check mb-2'>";
                        echo "<input class='form-check-input' type='checkbox' name='$key' id='$key' value='1' $checked>";
                        echo "<label class='form-check-label' for='$key'>$description</label>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Quality Assurance -->
        <div class="mb-4">
            <h6>Quality Assurance</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="reviewed_by" id="reviewedBy" placeholder="Reviewed by" value="<?php echo htmlspecialchars($existing_data['reviewed_by'] ?? ''); ?>">
                        <label for="reviewedBy">Reviewed by</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="datetime-local" class="form-control" name="review_date" id="reviewDate" value="<?php echo htmlspecialchars($existing_data['review_date'] ?? ''); ?>">
                        <label for="reviewDate">Review date</label>
                    </div>
                </div>
            </div>
            <div class="form-floating mb-3">
                <textarea class="form-control" name="review_comments" id="reviewComments" style="height: 100px;" placeholder="Review comments and feedback"><?php echo htmlspecialchars($existing_data['review_comments'] ?? ''); ?></textarea>
                <label for="reviewComments">Review comments</label>
            </div>
        </div>

        <!-- Export Options -->
        <div class="mb-4">
            <h6>Export Options</h6>
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-primary">Document Formats</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="export_pdf" id="exportPdf" value="1" checked>
                                <label class="form-check-label" for="exportPdf">
                                    <i class="fas fa-file-pdf text-danger"></i> PDF Document
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="export_word" id="exportWord" value="1">
                                <label class="form-check-label" for="exportWord">
                                    <i class="fas fa-file-word text-primary"></i> Word Document
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="export_excel" id="exportExcel" value="1">
                                <label class="form-check-label" for="exportExcel">
                                    <i class="fas fa-file-excel text-success"></i> Excel Spreadsheet (Tables only)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary">Digital Options</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="export_html" id="exportHtml" value="1">
                                <label class="form-check-label" for="exportHtml">
                                    <i class="fas fa-globe text-info"></i> HTML Web Page
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="export_json" id="exportJson" value="1">
                                <label class="form-check-label" for="exportJson">
                                    <i class="fas fa-code text-warning"></i> JSON Data Export
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="email_stakeholders" id="emailStakeholders" value="1">
                                <label class="form-check-label" for="emailStakeholders">
                                    <i class="fas fa-envelope text-secondary"></i> Email to Stakeholders
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="document_title" id="documentTitle" placeholder="Custom document title" value="<?php echo htmlspecialchars($existing_data['document_title'] ?? ''); ?>">
                                <label for="documentTitle">Custom document title</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Actions -->
        <div class="mb-4">
            <h6>Export Actions</h6>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <button type="button" class="btn btn-outline-primary w-100" onclick="previewDocument()">
                        <i class="fas fa-eye"></i> Preview Document
                    </button>
                </div>
                <div class="col-md-4 mb-3">
                    <button type="button" class="btn btn-primary w-100" onclick="generateDocument()">
                        <i class="fas fa-download"></i> Generate & Download
                    </button>
                </div>
                <div class="col-md-4 mb-3">
                    <button type="button" class="btn btn-success w-100" onclick="finalizeHandover()">
                        <i class="fas fa-check"></i> Finalize Handover
                    </button>
                </div>
            </div>
        </div>

        <!-- Final Notes -->
        <div class="mb-4">
            <h6>Final Notes</h6>
            <div class="form-floating mb-3">
                <textarea class="form-control" name="final_notes" id="finalNotes" style="height: 120px;" placeholder="Any final notes or comments about the handover process"><?php echo htmlspecialchars($existing_data['final_notes'] ?? ''); ?></textarea>
                <label for="finalNotes">Final notes and comments</label>
            </div>
        </div>

        <div class="alert alert-success">
            <h6><i class="fas fa-info-circle"></i> Completion Guidelines</h6>
            <ul class="mb-0">
                <li><strong>Review:</strong> Ensure all previous steps are completed and verified</li>
                <li><strong>Quality check:</strong> Have another team member review the documentation</li>
                <li><strong>Export:</strong> Generate documents in required formats for archival</li>
                <li><strong>Signatures:</strong> Ensure all required signatures are obtained before finalizing</li>
                <li>Once finalized, the handover document becomes the official record</li>
            </ul>
        </div>
    </div>
</div>

<script>
function previewDocument() {
    // Save current data first
    const formData = new FormData(document.getElementById('wizardForm'));
    
    fetch('../api/handover/save_data.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Open preview in new window
            const previewUrl = `preview.php?document_id=<?php echo $document_id; ?>`;
            window.open(previewUrl, '_blank', 'width=1200,height=800,scrollbars=yes');
        } else {
            alert('Please save your changes first before previewing.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving data for preview.');
    });
}

function generateDocument() {
    // Check if all required steps are completed
    const checkedItems = document.querySelectorAll('input[type="checkbox"]:checked').length;
    const totalItems = document.querySelectorAll('input[type="checkbox"]').length;
    
    if (checkedItems < totalItems * 0.8) {
        if (!confirm('Not all items are checked. Continue with document generation?')) {
            return;
        }
    }
    
    // Get selected export formats
    const exportFormats = [];
    if (document.getElementById('exportPdf').checked) exportFormats.push('pdf');
    if (document.getElementById('exportWord').checked) exportFormats.push('word');
    if (document.getElementById('exportExcel').checked) exportFormats.push('excel');
    if (document.getElementById('exportHtml').checked) exportFormats.push('html');
    if (document.getElementById('exportJson').checked) exportFormats.push('json');
    
    if (exportFormats.length === 0) {
        alert('Please select at least one export format.');
        return;
    }
    
    // Save current data first
    const formData = new FormData(document.getElementById('wizardForm'));
    formData.append('generate_export', '1');
    formData.append('export_formats', JSON.stringify(exportFormats));
    
    fetch('../api/handover/save_data.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Trigger download
            window.location.href = `export.php?document_id=<?php echo $document_id; ?>&formats=${exportFormats.join(',')}`;
        } else {
            alert('Error generating documents: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error generating documents.');
    });
}

function finalizeHandover() {
    if (!confirm('Are you sure you want to finalize this handover? This action cannot be undone and will lock the document for editing.')) {
        return;
    }
    
    // Check completion percentage
    const checkedItems = document.querySelectorAll('input[type="checkbox"]:checked').length;
    const totalItems = document.querySelectorAll('input[type="checkbox"]').length;
    const completionRate = (checkedItems / totalItems) * 100;
    
    if (completionRate < 90) {
        alert(`Completion rate is only ${Math.round(completionRate)}%. Please complete more items before finalizing.`);
        return;
    }
    
    // Save and finalize
    const formData = new FormData(document.getElementById('wizardForm'));
    formData.append('finalize_handover', '1');
    formData.append('finalization_date', new Date().toISOString());
    
    fetch('../api/handover/save_data.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Handover has been successfully finalized!');
            window.location.href = '../index.php';
        } else {
            alert('Error finalizing handover: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error finalizing handover.');
    });
}

// Update progress indicator
document.addEventListener('change', function(e) {
    if (e.target.type === 'checkbox') {
        updateProgress();
    }
});

function updateProgress() {
    const checkedItems = document.querySelectorAll('input[type="checkbox"]:checked').length;
    const totalItems = document.querySelectorAll('input[type="checkbox"]').length;
    const percentage = Math.round((checkedItems / totalItems) * 100);
    
    // Update progress indicators if they exist
    const progressElements = document.querySelectorAll('.progress-percentage');
    progressElements.forEach(el => {
        el.textContent = percentage + '%';
    });
}

// Auto-set review date when reviewer is entered
document.getElementById('reviewedBy').addEventListener('change', function() {
    if (this.value && !document.getElementById('reviewDate').value) {
        const now = new Date();
        document.getElementById('reviewDate').value = now.toISOString().slice(0, 16);
    }
});

// Initialize
updateProgress();
</script>
