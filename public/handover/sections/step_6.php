<?php
// public/handover/sections/step_6.php - Testing
?>

<div class="alert alert-info">
    <h6><i class="fas fa-vial"></i> Testing</h6>
    <p class="mb-0">Document testing activities, test reports, and any outstanding issues or bug backlogs.</p>
</div>

<div class="row">
    <div class="col-12">
        <!-- Testing Documentation -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Testing Documentation</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addTestingDoc()">
                    <i class="fas fa-plus"></i> Add documentation
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="testingTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 35%;">Documentation Type</th>
                            <th style="width: 50%;">Description</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="testingTableBody">
                        <?php
                        $existing_testing = [];
                        if (isset($existing_data['testing_documentation'])) {
                            $existing_testing = json_decode($existing_data['testing_documentation'], true) ?: [];
                        }
                        
                        if (empty($existing_testing)) {
                            $testing_types = [
                                'Error backlog',
                                'Test report',
                                'Test plan',
                                'User acceptance testing',
                                'Performance testing',
                                'Security testing'
                            ];
                            foreach ($testing_types as $index => $type) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='testing_documentation[$index][id]' placeholder='".($index+1)."'></td>";
                                echo "<td><select class='form-select' name='testing_documentation[$index][type]'>";
                                echo "<option value='$type' selected>$type</option>";
                                foreach ($testing_types as $option) {
                                    if ($option !== $type) echo "<option value='$option'>$option</option>";
                                }
                                echo "<option value='Other'>Other</option>";
                                echo "</select></td>";
                                echo "<td><input type='text' class='form-control' name='testing_documentation[$index][description]' placeholder='Location, status, and key findings'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            foreach ($existing_testing as $index => $item) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='testing_documentation[$index][id]' value='".htmlspecialchars($item['id'] ?? '')."' placeholder='ID'></td>";
                                echo "<td><input type='text' class='form-control' name='testing_documentation[$index][type]' value='".htmlspecialchars($item['type'] ?? '')."' placeholder='Documentation Type'></td>";
                                echo "<td><input type='text' class='form-control' name='testing_documentation[$index][description]' value='".htmlspecialchars($item['description'] ?? '')."' placeholder='Description'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Test Summary -->
        <div class="mb-4">
            <h6>Test Summary</h6>
            <div class="form-floating mb-3">
                <textarea class="form-control" name="test_summary" id="testSummary" style="height: 100px" placeholder="Summarize testing activities and results..."><?php echo htmlspecialchars($existing_data['test_summary'] ?? ''); ?></textarea>
                <label for="testSummary">Overall testing summary and key outcomes</label>
            </div>
        </div>

        <!-- Outstanding Issues -->
        <div class="mb-4">
            <h6>Outstanding Issues</h6>
            <div class="form-floating mb-3">
                <textarea class="form-control" name="outstanding_issues" id="outstandingIssues" style="height: 80px" placeholder="List any known issues or bugs..."><?php echo htmlspecialchars($existing_data['outstanding_issues'] ?? ''); ?></textarea>
                <label for="outstandingIssues">Known issues, bugs, or limitations</label>
            </div>
        </div>

        <!-- Test Environment Details -->
        <div class="mb-4">
            <h6>Test Environment Details</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="test_environment" id="testEnvironment" placeholder="Test environment" value="<?php echo htmlspecialchars($existing_data['test_environment'] ?? ''); ?>">
                        <label for="testEnvironment">Test environment used</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="test_data" id="testData" placeholder="Test data details" value="<?php echo htmlspecialchars($existing_data['test_data'] ?? ''); ?>">
                        <label for="testData">Test data used</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-light">
            <h6><i class="fas fa-info-circle"></i> Help text</h6>
            <ul class="mb-0">
                <li><strong>Error backlog:</strong> List of known bugs and their severity/priority</li>
                <li><strong>Test report:</strong> Summary of test execution and results</li>
                <li><strong>Outstanding issues:</strong> Any unresolved problems that operations should be aware of</li>
                <li>Include information about test environments and data used for testing</li>
                <li>Mention any special testing considerations or limitations</li>
            </ul>
        </div>
    </div>
</div>

<script>
let testingIndex = <?php echo count($existing_testing ?? [1, 2, 3, 4, 5, 6]); ?>;

function addTestingDoc() {
    const tableBody = document.getElementById('testingTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='text' class='form-control' name='testing_documentation[${testingIndex}][id]' placeholder='ID'></td>
        <td><select class='form-select' name='testing_documentation[${testingIndex}][type]'>
            <option value=''>Select type...</option>
            <option value='Error backlog'>Error backlog</option>
            <option value='Test report'>Test report</option>
            <option value='Test plan'>Test plan</option>
            <option value='User acceptance testing'>User acceptance testing</option>
            <option value='Performance testing'>Performance testing</option>
            <option value='Security testing'>Security testing</option>
            <option value='Other'>Other</option>
        </select></td>
        <td><input type='text' class='form-control' name='testing_documentation[${testingIndex}][description]' placeholder='Description'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    testingIndex++;
}

function removeRow(button) {
    if (confirm('Are you sure you want to remove this item?')) {
        button.closest('tr').remove();
    }
}
</script>
