<?php
// public/handover/sections/step_10.php - Security
?>

<div class="alert alert-info">
    <h6><i class="fas fa-shield-alt"></i> Security</h6>
    <p class="mb-0">Document security measures, authentication methods, and security review outcomes.</p>
</div>

<div class="row">
    <div class="col-12">
        <!-- Multi Factor Authentication -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Multi Factor Authentication (MFA)</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addMFA()">
                    <i class="fas fa-plus"></i> Add MFA item
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="mfaTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 35%;">IT Service</th>
                            <th style="width: 50%;">Description</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="mfaTableBody">
                        <?php
                        $existing_mfa = [];
                        if (isset($existing_data['mfa_items'])) {
                            $existing_mfa = json_decode($existing_data['mfa_items'], true) ?: [];
                        }
                        
                        if (empty($existing_mfa)) {
                            echo "<tr>";
                            echo "<td><input type='text' class='form-control' name='mfa_items[0][id]' placeholder='1'></td>";
                            echo "<td><input type='text' class='form-control' name='mfa_items[0][service]' placeholder='Application login' value='Application login'></td>";
                            echo "<td><input type='text' class='form-control' name='mfa_items[0][description]' placeholder='Include any deviations from standard MFA implementation'></td>";
                            echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                            echo "</tr>";
                        } else {
                            foreach ($existing_mfa as $index => $mfa) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='mfa_items[$index][id]' value='".htmlspecialchars($mfa['id'] ?? '')."' placeholder='ID'></td>";
                                echo "<td><input type='text' class='form-control' name='mfa_items[$index][service]' value='".htmlspecialchars($mfa['service'] ?? '')."' placeholder='IT Service'></td>";
                                echo "<td><input type='text' class='form-control' name='mfa_items[$index][description]' value='".htmlspecialchars($mfa['description'] ?? '')."' placeholder='Description'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <small class="text-muted">Include any deviations from standard MFA requirements</small>
        </div>

        <!-- Service Log Collection -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Service Log Collection</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addLogItem()">
                    <i class="fas fa-plus"></i> Add log item
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="logTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 35%;">Log Item</th>
                            <th style="width: 50%;">Description</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="logTableBody">
                        <?php
                        $existing_logs = [];
                        if (isset($existing_data['log_items'])) {
                            $existing_logs = json_decode($existing_data['log_items'], true) ?: [];
                        }
                        
                        if (empty($existing_logs)) {
                            $log_types = [
                                'Application logs',
                                'Security logs',
                                'Error logs',
                                'Access logs'
                            ];
                            foreach ($log_types as $index => $log_type) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='log_items[$index][id]' placeholder='".($index+1)."'></td>";
                                echo "<td><input type='text' class='form-control' name='log_items[$index][item]' value='$log_type' placeholder='Log Item'></td>";
                                echo "<td><input type='text' class='form-control' name='log_items[$index][description]' placeholder='Location, format, and retention policy'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            foreach ($existing_logs as $index => $log) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='log_items[$index][id]' value='".htmlspecialchars($log['id'] ?? '')."' placeholder='ID'></td>";
                                echo "<td><input type='text' class='form-control' name='log_items[$index][item]' value='".htmlspecialchars($log['item'] ?? '')."' placeholder='Log Item'></td>";
                                echo "<td><input type='text' class='form-control' name='log_items[$index][description]' value='".htmlspecialchars($log['description'] ?? '')."' placeholder='Description'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Security Review -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Security Review by Security Department</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addSecurityReview()">
                    <i class="fas fa-plus"></i> Add review
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="securityReviewTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 35%;">IT Service</th>
                            <th style="width: 50%;">Description</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="securityReviewTableBody">
                        <?php
                        $existing_security_reviews = [];
                        if (isset($existing_data['security_reviews'])) {
                            $existing_security_reviews = json_decode($existing_data['security_reviews'], true) ?: [];
                        }
                        
                        if (empty($existing_security_reviews)) {
                            $security_types = [
                                'Security assessment',
                                'Penetration testing',
                                'Code review',
                                'Vulnerability scan'
                            ];
                            foreach ($security_types as $index => $type) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='security_reviews[$index][id]' placeholder='".($index+1)."'></td>";
                                echo "<td><input type='text' class='form-control' name='security_reviews[$index][service]' value='$type' placeholder='IT Service'></td>";
                                echo "<td><input type='text' class='form-control' name='security_reviews[$index][description]' placeholder='Results, findings, and recommendations'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            foreach ($existing_security_reviews as $index => $review) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='security_reviews[$index][id]' value='".htmlspecialchars($review['id'] ?? '')."' placeholder='ID'></td>";
                                echo "<td><input type='text' class='form-control' name='security_reviews[$index][service]' value='".htmlspecialchars($review['service'] ?? '')."' placeholder='IT Service'></td>";
                                echo "<td><input type='text' class='form-control' name='security_reviews[$index][description]' value='".htmlspecialchars($review['description'] ?? '')."' placeholder='Description'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Security Compliance -->
        <div class="mb-4">
            <h6>Security Compliance</h6>
            <div class="form-floating mb-3">
                <textarea class="form-control" name="security_compliance" id="securityCompliance" style="height: 80px" placeholder="Security compliance status..."><?php echo htmlspecialchars($existing_data['security_compliance'] ?? ''); ?></textarea>
                <label for="securityCompliance">Security compliance and certifications</label>
            </div>
        </div>

        <div class="alert alert-light">
            <h6><i class="fas fa-info-circle"></i> Help text</h6>
            <ul class="mb-0">
                <li><strong>MFA:</strong> Document any deviations from standard multi-factor authentication</li>
                <li><strong>Log Collection:</strong> Specify what logs are collected and where they're stored</li>
                <li><strong>Security Review:</strong> Include security testing results and recommendations</li>
                <li>Document compliance with security policies and standards</li>
            </ul>
        </div>
    </div>
</div>

<script>
let mfaIndex = <?php echo count($existing_mfa ?? [1]); ?>;
let logIndex = <?php echo count($existing_logs ?? [1, 2, 3, 4]); ?>;
let securityReviewIndex = <?php echo count($existing_security_reviews ?? [1, 2, 3, 4]); ?>;

function addMFA() {
    const tableBody = document.getElementById('mfaTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='text' class='form-control' name='mfa_items[${mfaIndex}][id]' placeholder='ID'></td>
        <td><input type='text' class='form-control' name='mfa_items[${mfaIndex}][service]' placeholder='IT Service'></td>
        <td><input type='text' class='form-control' name='mfa_items[${mfaIndex}][description]' placeholder='Description'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    mfaIndex++;
}

function addLogItem() {
    const tableBody = document.getElementById('logTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='text' class='form-control' name='log_items[${logIndex}][id]' placeholder='ID'></td>
        <td><input type='text' class='form-control' name='log_items[${logIndex}][item]' placeholder='Log Item'></td>
        <td><input type='text' class='form-control' name='log_items[${logIndex}][description]' placeholder='Description'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    logIndex++;
}

function addSecurityReview() {
    const tableBody = document.getElementById('securityReviewTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='text' class='form-control' name='security_reviews[${securityReviewIndex}][id]' placeholder='ID'></td>
        <td><input type='text' class='form-control' name='security_reviews[${securityReviewIndex}][service]' placeholder='IT Service'></td>
        <td><input type='text' class='form-control' name='security_reviews[${securityReviewIndex}][description]' placeholder='Description'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    securityReviewIndex++;
}

function removeRow(button) {
    if (confirm('Are you sure you want to remove this item?')) {
        button.closest('tr').remove();
    }
}
</script>
