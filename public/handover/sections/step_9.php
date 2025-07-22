<?php
// public/handover/sections/step_9.php - Risk
?>

<div class="alert alert-info">
    <h6><i class="fas fa-exclamation-triangle"></i> Risk</h6>
    <p class="mb-0">Identify and document risks associated with the application and its operations.</p>
</div>

<div class="row">
    <div class="col-12">
        <!-- Risk Assessment Table -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Risk Assessment</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addRisk()">
                    <i class="fas fa-plus"></i> Add risk
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="riskTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 8%;">ID</th>
                            <th style="width: 25%;">Corporate Object</th>
                            <th style="width: 25%;">Consequence</th>
                            <th style="width: 15%;">Probability</th>
                            <th style="width: 22%;">Description</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="riskTableBody">
                        <?php
                        $existing_risks = [];
                        if (isset($existing_data['risks'])) {
                            $existing_risks = json_decode($existing_data['risks'], true) ?: [];
                        }
                        
                        if (empty($existing_risks)) {
                            // Pre-populate with common risk categories
                            $common_risks = [
                                'System availability',
                                'Data security',
                                'Performance degradation',
                                'Integration failure'
                            ];
                            foreach ($common_risks as $index => $risk) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='risks[$index][id]' placeholder='".($index+1)."'></td>";
                                echo "<td><input type='text' class='form-control' name='risks[$index][object]' value='$risk' placeholder='Corporate Object'></td>";
                                echo "<td><select class='form-select' name='risks[$index][consequence]'>";
                                echo "<option value=''>Select...</option>";
                                echo "<option value='Low'>Low</option>";
                                echo "<option value='Medium'>Medium</option>";
                                echo "<option value='High'>High</option>";
                                echo "<option value='Critical'>Critical</option>";
                                echo "</select></td>";
                                echo "<td><select class='form-select' name='risks[$index][probability]'>";
                                echo "<option value=''>Select...</option>";
                                echo "<option value='Very Low'>Very Low</option>";
                                echo "<option value='Low'>Low</option>";
                                echo "<option value='Medium'>Medium</option>";
                                echo "<option value='High'>High</option>";
                                echo "<option value='Very High'>Very High</option>";
                                echo "</select></td>";
                                echo "<td><input type='text' class='form-control' name='risks[$index][description]' placeholder='Risk description and mitigation'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            foreach ($existing_risks as $index => $risk) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='risks[$index][id]' value='".htmlspecialchars($risk['id'] ?? '')."' placeholder='ID'></td>";
                                echo "<td><input type='text' class='form-control' name='risks[$index][object]' value='".htmlspecialchars($risk['object'] ?? '')."' placeholder='Corporate Object'></td>";
                                echo "<td><select class='form-select' name='risks[$index][consequence]'>";
                                echo "<option value=''>Select...</option>";
                                $consequences = ['Low', 'Medium', 'High', 'Critical'];
                                foreach ($consequences as $consequence) {
                                    $selected = ($risk['consequence'] ?? '') === $consequence ? 'selected' : '';
                                    echo "<option value='$consequence' $selected>$consequence</option>";
                                }
                                echo "</select></td>";
                                echo "<td><select class='form-select' name='risks[$index][probability]'>";
                                echo "<option value=''>Select...</option>";
                                $probabilities = ['Very Low', 'Low', 'Medium', 'High', 'Very High'];
                                foreach ($probabilities as $probability) {
                                    $selected = ($risk['probability'] ?? '') === $probability ? 'selected' : '';
                                    echo "<option value='$probability' $selected>$probability</option>";
                                }
                                echo "</select></td>";
                                echo "<td><input type='text' class='form-control' name='risks[$index][description]' value='".htmlspecialchars($risk['description'] ?? '')."' placeholder='Description'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Risk Mitigation Strategies -->
        <div class="mb-4">
            <h6>Risk Mitigation Strategies</h6>
            <div class="form-floating mb-3">
                <textarea class="form-control" name="risk_mitigation_strategies" id="riskMitigationStrategies" style="height: 100px" placeholder="Describe overall risk mitigation strategies..."><?php echo htmlspecialchars($existing_data['risk_mitigation_strategies'] ?? ''); ?></textarea>
                <label for="riskMitigationStrategies">Overall risk mitigation strategies</label>
            </div>
        </div>

        <!-- Business Continuity -->
        <div class="mb-4">
            <h6>Business Continuity</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="recovery_time_objective" id="recoveryTimeObjective" placeholder="RTO" value="<?php echo htmlspecialchars($existing_data['recovery_time_objective'] ?? ''); ?>">
                        <label for="recoveryTimeObjective">Recovery Time Objective (RTO)</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="recovery_point_objective" id="recoveryPointObjective" placeholder="RPO" value="<?php echo htmlspecialchars($existing_data['recovery_point_objective'] ?? ''); ?>">
                        <label for="recoveryPointObjective">Recovery Point Objective (RPO)</label>
                    </div>
                </div>
            </div>
            
            <div class="form-floating mb-3">
                <textarea class="form-control" name="disaster_recovery_plan" id="disasterRecoveryPlan" style="height: 80px" placeholder="Disaster recovery procedures..."><?php echo htmlspecialchars($existing_data['disaster_recovery_plan'] ?? ''); ?></textarea>
                <label for="disasterRecoveryPlan">Disaster recovery plan</label>
            </div>
        </div>

        <div class="alert alert-light">
            <h6><i class="fas fa-info-circle"></i> Help text</h6>
            <ul class="mb-0">
                <li><strong>Corporate Object:</strong> The business function or asset at risk</li>
                <li><strong>Consequence:</strong> Impact level if the risk materializes</li>
                <li><strong>Probability:</strong> Likelihood of the risk occurring</li>
                <li><strong>RTO:</strong> Maximum acceptable downtime</li>
                <li><strong>RPO:</strong> Maximum acceptable data loss</li>
                <li>Include both technical and business risks</li>
            </ul>
        </div>
    </div>
</div>

<script>
let riskIndex = <?php echo count($existing_risks ?? [1, 2, 3, 4]); ?>;

function addRisk() {
    const tableBody = document.getElementById('riskTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='text' class='form-control' name='risks[${riskIndex}][id]' placeholder='ID'></td>
        <td><input type='text' class='form-control' name='risks[${riskIndex}][object]' placeholder='Corporate Object'></td>
        <td><select class='form-select' name='risks[${riskIndex}][consequence]'>
            <option value=''>Select...</option>
            <option value='Low'>Low</option>
            <option value='Medium'>Medium</option>
            <option value='High'>High</option>
            <option value='Critical'>Critical</option>
        </select></td>
        <td><select class='form-select' name='risks[${riskIndex}][probability]'>
            <option value=''>Select...</option>
            <option value='Very Low'>Very Low</option>
            <option value='Low'>Low</option>
            <option value='Medium'>Medium</option>
            <option value='High'>High</option>
            <option value='Very High'>Very High</option>
        </select></td>
        <td><input type='text' class='form-control' name='risks[${riskIndex}][description]' placeholder='Description'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    riskIndex++;
}

function removeRow(button) {
    if (confirm('Are you sure you want to remove this risk?')) {
        button.closest('tr').remove();
    }
}
</script>
