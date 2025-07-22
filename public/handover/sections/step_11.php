<?php
// public/handover/sections/step_11.php - Economics
?>

<div class="alert alert-info">
    <h6><i class="fas fa-dollar-sign"></i> Economics</h6>
    <p class="mb-0">Document cost elements for current and next year including licenses, maintenance, and operational costs.</p>
</div>

<div class="row">
    <div class="col-12">
        <!-- Current Year Costs -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Cost Elements for Current Year</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addCurrentCost()">
                    <i class="fas fa-plus"></i> Add cost element
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="currentCostsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 8%;">ID</th>
                            <th style="width: 30%;">Functionality</th>
                            <th style="width: 25%;">Responsible</th>
                            <th style="width: 32%;">Description</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="currentCostsTableBody">
                        <?php
                        $existing_current_costs = [];
                        if (isset($existing_data['current_year_costs'])) {
                            $existing_current_costs = json_decode($existing_data['current_year_costs'], true) ?: [];
                        }
                        
                        if (empty($existing_current_costs)) {
                            $cost_types = ['Licenses', 'SW subscription', 'Implementation', 'Support & Maintenance'];
                            foreach ($cost_types as $index => $type) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='current_year_costs[$index][id]' placeholder='".($index+1)."'></td>";
                                echo "<td><input type='text' class='form-control' name='current_year_costs[$index][functionality]' value='$type' placeholder='Functionality'></td>";
                                echo "<td><input type='text' class='form-control' name='current_year_costs[$index][responsible]' placeholder='Department/Team'></td>";
                                echo "<td><input type='text' class='form-control' name='current_year_costs[$index][description]' placeholder='Cost details and amount'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            foreach ($existing_current_costs as $index => $cost) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='current_year_costs[$index][id]' value='".htmlspecialchars($cost['id'] ?? '')."' placeholder='ID'></td>";
                                echo "<td><input type='text' class='form-control' name='current_year_costs[$index][functionality]' value='".htmlspecialchars($cost['functionality'] ?? '')."' placeholder='Functionality'></td>";
                                echo "<td><input type='text' class='form-control' name='current_year_costs[$index][responsible]' value='".htmlspecialchars($cost['responsible'] ?? '')."' placeholder='Responsible'></td>";
                                echo "<td><input type='text' class='form-control' name='current_year_costs[$index][description]' value='".htmlspecialchars($cost['description'] ?? '')."' placeholder='Description'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Next Year Costs -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Cost Elements for Next Full Calendar Year</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addNextCost()">
                    <i class="fas fa-plus"></i> Add cost element
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="nextCostsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 8%;">ID</th>
                            <th style="width: 30%;">Functionality</th>
                            <th style="width: 25%;">Responsible</th>
                            <th style="width: 32%;">Description</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="nextCostsTableBody">
                        <?php
                        $existing_next_costs = [];
                        if (isset($existing_data['next_year_costs'])) {
                            $existing_next_costs = json_decode($existing_data['next_year_costs'], true) ?: [];
                        }
                        
                        if (empty($existing_next_costs)) {
                            $next_cost_types = ['Annual licenses', 'Maintenance & Support', 'Operations', 'Enhancements'];
                            foreach ($next_cost_types as $index => $type) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='next_year_costs[$index][id]' placeholder='".($index+1)."'></td>";
                                echo "<td><input type='text' class='form-control' name='next_year_costs[$index][functionality]' value='$type' placeholder='Functionality'></td>";
                                echo "<td><input type='text' class='form-control' name='next_year_costs[$index][responsible]' placeholder='Department/Team'></td>";
                                echo "<td><input type='text' class='form-control' name='next_year_costs[$index][description]' placeholder='Projected cost details'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            foreach ($existing_next_costs as $index => $cost) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='next_year_costs[$index][id]' value='".htmlspecialchars($cost['id'] ?? '')."' placeholder='ID'></td>";
                                echo "<td><input type='text' class='form-control' name='next_year_costs[$index][functionality]' value='".htmlspecialchars($cost['functionality'] ?? '')."' placeholder='Functionality'></td>";
                                echo "<td><input type='text' class='form-control' name='next_year_costs[$index][responsible]' value='".htmlspecialchars($cost['responsible'] ?? '')."' placeholder='Responsible'></td>";
                                echo "<td><input type='text' class='form-control' name='next_year_costs[$index][description]' value='".htmlspecialchars($cost['description'] ?? '')."' placeholder='Description'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Budget Summary -->
        <div class="mb-4">
            <h6>Budget Summary</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="total_current_budget" id="totalCurrentBudget" placeholder="Total current year budget" value="<?php echo htmlspecialchars($existing_data['total_current_budget'] ?? ''); ?>">
                        <label for="totalCurrentBudget">Total current year budget</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="total_next_budget" id="totalNextBudget" placeholder="Total next year budget" value="<?php echo htmlspecialchars($existing_data['total_next_budget'] ?? ''); ?>">
                        <label for="totalNextBudget">Total next year budget</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-light">
            <h6><i class="fas fa-info-circle"></i> Help text</h6>
            <ul class="mb-0">
                <li><strong>Current year:</strong> Include all remaining costs for this calendar year</li>
                <li><strong>Next year:</strong> Project all costs for the full next calendar year</li>
                <li>Include licenses, maintenance, operations, and enhancement costs</li>
                <li>Specify which department or budget center is responsible for each cost</li>
            </ul>
        </div>
    </div>
</div>

<script>
let currentCostIndex = <?php echo count($existing_current_costs ?? [1, 2, 3, 4]); ?>;
let nextCostIndex = <?php echo count($existing_next_costs ?? [1, 2, 3, 4]); ?>;

function addCurrentCost() {
    const tableBody = document.getElementById('currentCostsTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='text' class='form-control' name='current_year_costs[${currentCostIndex}][id]' placeholder='ID'></td>
        <td><input type='text' class='form-control' name='current_year_costs[${currentCostIndex}][functionality]' placeholder='Functionality'></td>
        <td><input type='text' class='form-control' name='current_year_costs[${currentCostIndex}][responsible]' placeholder='Responsible'></td>
        <td><input type='text' class='form-control' name='current_year_costs[${currentCostIndex}][description]' placeholder='Description'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    currentCostIndex++;
}

function addNextCost() {
    const tableBody = document.getElementById('nextCostsTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='text' class='form-control' name='next_year_costs[${nextCostIndex}][id]' placeholder='ID'></td>
        <td><input type='text' class='form-control' name='next_year_costs[${nextCostIndex}][functionality]' placeholder='Functionality'></td>
        <td><input type='text' class='form-control' name='next_year_costs[${nextCostIndex}][responsible]' placeholder='Responsible'></td>
        <td><input type='text' class='form-control' name='next_year_costs[${nextCostIndex}][description]' placeholder='Description'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    nextCostIndex++;
}

function removeRow(button) {
    if (confirm('Are you sure you want to remove this cost element?')) {
        button.closest('tr').remove();
    }
}
</script>
