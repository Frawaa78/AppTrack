<?php
// public/handover/sections/step_13.php - Signatures
?>

<div class="alert alert-info">
    <h6><i class="fas fa-file-signature"></i> Signatures</h6>
    <p class="mb-0">Digital signatures and approvals for the handover process completion.</p>
</div>

<div class="row">
    <div class="col-12">
        <!-- Signature Status -->
        <div class="mb-4">
            <h6>Signature Status</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="card border-left-primary">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Project Team</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $project_signed = $existing_data['project_signature_date'] ?? false;
                                echo $project_signed ? '<i class="fas fa-check-circle text-success"></i> Signed' : '<i class="fas fa-clock text-warning"></i> Pending';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-left-warning">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">IT Operations</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $ops_signed = $existing_data['ops_signature_date'] ?? false;
                                echo $ops_signed ? '<i class="fas fa-check-circle text-success"></i> Signed' : '<i class="fas fa-clock text-warning"></i> Pending';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-left-success">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Management</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $mgmt_signed = $existing_data['mgmt_signature_date'] ?? false;
                                echo $mgmt_signed ? '<i class="fas fa-check-circle text-success"></i> Signed' : '<i class="fas fa-clock text-warning"></i> Pending';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Signature List -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Required Signatures</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addSignature()">
                    <i class="fas fa-plus"></i> Add signature
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="signaturesTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 20%;">Role</th>
                            <th style="width: 25%;">Name</th>
                            <th style="width: 20%;">Department</th>
                            <th style="width: 15%;">Status</th>
                            <th style="width: 15%;">Date</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="signaturesTableBody">
                        <?php
                        $existing_signatures = [];
                        if (isset($existing_data['signatures'])) {
                            $existing_signatures = json_decode($existing_data['signatures'], true) ?: [];
                        }
                        
                        if (empty($existing_signatures)) {
                            $default_roles = [
                                'Project Manager',
                                'Technical Lead',
                                'IT Operations Manager',
                                'Application Owner',
                                'Security Officer'
                            ];
                            foreach ($default_roles as $index => $role) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='signatures[$index][role]' value='$role' placeholder='Role'></td>";
                                echo "<td><input type='text' class='form-control' name='signatures[$index][name]' placeholder='Full name'></td>";
                                echo "<td><input type='text' class='form-control' name='signatures[$index][department]' placeholder='Department'></td>";
                                echo "<td><select class='form-select' name='signatures[$index][status]'>";
                                echo "<option value='pending'>Pending</option>";
                                echo "<option value='signed'>Signed</option>";
                                echo "<option value='declined'>Declined</option>";
                                echo "</select></td>";
                                echo "<td><input type='datetime-local' class='form-control' name='signatures[$index][date]'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            foreach ($existing_signatures as $index => $signature) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='signatures[$index][role]' value='".htmlspecialchars($signature['role'] ?? '')."' placeholder='Role'></td>";
                                echo "<td><input type='text' class='form-control' name='signatures[$index][name]' value='".htmlspecialchars($signature['name'] ?? '')."' placeholder='Name'></td>";
                                echo "<td><input type='text' class='form-control' name='signatures[$index][department]' value='".htmlspecialchars($signature['department'] ?? '')."' placeholder='Department'></td>";
                                echo "<td><select class='form-select' name='signatures[$index][status]'>";
                                $status = $signature['status'] ?? 'pending';
                                foreach (['pending' => 'Pending', 'signed' => 'Signed', 'declined' => 'Declined'] as $value => $label) {
                                    $selected = ($status === $value) ? ' selected' : '';
                                    echo "<option value='$value'$selected>$label</option>";
                                }
                                echo "</select></td>";
                                echo "<td><input type='datetime-local' class='form-control' name='signatures[$index][date]' value='".htmlspecialchars($signature['date'] ?? '')."'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Signature Workflow -->
        <div class="mb-4">
            <h6>Signature Workflow</h6>
            <div class="form-floating mb-3">
                <textarea class="form-control" name="signature_workflow" id="signatureWorkflow" style="height: 100px;" placeholder="Describe the signature workflow and approval process"><?php echo htmlspecialchars($existing_data['signature_workflow'] ?? ''); ?></textarea>
                <label for="signatureWorkflow">Signature workflow description</label>
            </div>
        </div>

        <!-- Handover Completion -->
        <div class="mb-4">
            <h6>Handover Completion</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="datetime-local" class="form-control" name="handover_completion_date" id="handoverCompletionDate" value="<?php echo htmlspecialchars($existing_data['handover_completion_date'] ?? ''); ?>">
                        <label for="handoverCompletionDate">Handover completion date</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="handover_status" id="handoverStatus" placeholder="Status" value="<?php echo htmlspecialchars($existing_data['handover_status'] ?? 'In Progress'); ?>">
                        <label for="handoverStatus">Handover status</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Digital Signature Configuration -->
        <div class="mb-4">
            <h6>Digital Signature Settings</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="require_digital_signature" id="requireDigitalSignature" value="1" <?php echo isset($existing_data['require_digital_signature']) && $existing_data['require_digital_signature'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="requireDigitalSignature">
                            Require digital signatures
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="email_notifications" id="emailNotifications" value="1" <?php echo isset($existing_data['email_notifications']) && $existing_data['email_notifications'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="emailNotifications">
                            Send email notifications
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comments and Notes -->
        <div class="mb-4">
            <h6>Signature Comments</h6>
            <div class="form-floating mb-3">
                <textarea class="form-control" name="signature_comments" id="signatureComments" style="height: 120px;" placeholder="Additional comments or notes about signatures"><?php echo htmlspecialchars($existing_data['signature_comments'] ?? ''); ?></textarea>
                <label for="signatureComments">Comments and notes</label>
            </div>
        </div>

        <div class="alert alert-light">
            <h6><i class="fas fa-info-circle"></i> Help text</h6>
            <ul class="mb-0">
                <li><strong>Workflow:</strong> Define the order and requirements for signatures</li>
                <li><strong>Digital signatures:</strong> Enable for legally binding electronic signatures</li>
                <li><strong>Status tracking:</strong> Monitor signature completion status</li>
                <li><strong>Notifications:</strong> Automatic reminders for pending signatures</li>
                <li>All signatures must be completed before handover is finalized</li>
            </ul>
        </div>
    </div>
</div>

<script>
let signatureIndex = <?php echo count($existing_signatures ?? [1, 2, 3, 4, 5]); ?>;

function addSignature() {
    const tableBody = document.getElementById('signaturesTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='text' class='form-control' name='signatures[${signatureIndex}][role]' placeholder='Role'></td>
        <td><input type='text' class='form-control' name='signatures[${signatureIndex}][name]' placeholder='Name'></td>
        <td><input type='text' class='form-control' name='signatures[${signatureIndex}][department]' placeholder='Department'></td>
        <td><select class='form-select' name='signatures[${signatureIndex}][status]'>
            <option value='pending'>Pending</option>
            <option value='signed'>Signed</option>
            <option value='declined'>Declined</option>
        </select></td>
        <td><input type='datetime-local' class='form-control' name='signatures[${signatureIndex}][date]'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    signatureIndex++;
}

function removeRow(button) {
    if (confirm('Are you sure you want to remove this signature?')) {
        button.closest('tr').remove();
    }
}

// Update handover status based on signatures
document.addEventListener('change', function(e) {
    if (e.target.name && e.target.name.includes('[status]')) {
        updateHandoverStatus();
    }
});

function updateHandoverStatus() {
    const statusSelects = document.querySelectorAll('select[name*="[status]"]');
    let allSigned = true;
    let anyDeclined = false;
    
    statusSelects.forEach(select => {
        if (select.value === 'pending') allSigned = false;
        if (select.value === 'declined') anyDeclined = true;
    });
    
    const statusInput = document.getElementById('handoverStatus');
    if (anyDeclined) {
        statusInput.value = 'Declined';
    } else if (allSigned && statusSelects.length > 0) {
        statusInput.value = 'Completed';
        if (!document.getElementById('handoverCompletionDate').value) {
            document.getElementById('handoverCompletionDate').value = new Date().toISOString().slice(0, 16);
        }
    } else {
        statusInput.value = 'In Progress';
    }
}
</script>
