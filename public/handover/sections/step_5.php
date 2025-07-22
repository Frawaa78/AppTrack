<?php
// public/handover/sections/step_5.php - Deliverables
?>

<div class="alert alert-info">
    <h6><i class="fas fa-box"></i> Deliverables</h6>
    <p class="mb-0">Document all deliverables including functionality, contracts, documentation, user access, and training materials.</p>
</div>

<div class="row">
    <div class="col-12">
        <!-- Functionality -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Functionality</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addFunctionality()">
                    <i class="fas fa-plus"></i> Add function
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="functionalityTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 30%;">Function</th>
                            <th style="width: 55%;">Description</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="functionalityTableBody">
                        <?php
                        $existing_functionality = [];
                        if (isset($existing_data['functionality'])) {
                            $existing_functionality = json_decode($existing_data['functionality'], true) ?: [];
                        }
                        
                        if (empty($existing_functionality)) {
                            echo "<tr>";
                            echo "<td><input type='text' class='form-control' name='functionality[0][id]' placeholder='1'></td>";
                            echo "<td><input type='text' class='form-control' name='functionality[0][function]' placeholder='Main application functionality'></td>";
                            echo "<td><input type='text' class='form-control' name='functionality[0][description]' placeholder='Detailed description of the function'></td>";
                            echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                            echo "</tr>";
                        } else {
                            foreach ($existing_functionality as $index => $item) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='functionality[$index][id]' value='".htmlspecialchars($item['id'] ?? '')."' placeholder='ID'></td>";
                                echo "<td><input type='text' class='form-control' name='functionality[$index][function]' value='".htmlspecialchars($item['function'] ?? '')."' placeholder='Function'></td>";
                                echo "<td><input type='text' class='form-control' name='functionality[$index][description]' value='".htmlspecialchars($item['description'] ?? '')."' placeholder='Description'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Contracts -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Contracts</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addContract()">
                    <i class="fas fa-plus"></i> Add contract
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="contractsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 25%;">Contract Type</th>
                            <th style="width: 60%;">Link to Contract or Description</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="contractsTableBody">
                        <?php
                        $existing_contracts = [];
                        if (isset($existing_data['contracts'])) {
                            $existing_contracts = json_decode($existing_data['contracts'], true) ?: [];
                        }
                        
                        if (empty($existing_contracts)) {
                            $contract_types = ['Contract', 'Service and maintenance', 'Licenses'];
                            foreach ($contract_types as $index => $type) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='contracts[$index][id]' placeholder='".($index+1)."'></td>";
                                echo "<td><select class='form-select' name='contracts[$index][type]'>";
                                echo "<option value='$type' selected>$type</option>";
                                foreach ($contract_types as $option) {
                                    if ($option !== $type) echo "<option value='$option'>$option</option>";
                                }
                                echo "<option value='Other'>Other</option>";
                                echo "</select></td>";
                                echo "<td><input type='text' class='form-control' name='contracts[$index][description]' placeholder='Link or description'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            foreach ($existing_contracts as $index => $contract) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='contracts[$index][id]' value='".htmlspecialchars($contract['id'] ?? '')."' placeholder='ID'></td>";
                                echo "<td><select class='form-select' name='contracts[$index][type]'>";
                                $selected_type = $contract['type'] ?? '';
                                $contract_types = ['Contract', 'Service and maintenance', 'Licenses', 'Other'];
                                foreach ($contract_types as $type) {
                                    $selected = ($selected_type === $type) ? 'selected' : '';
                                    echo "<option value='$type' $selected>$type</option>";
                                }
                                echo "</select></td>";
                                echo "<td><input type='text' class='form-control' name='contracts[$index][description]' value='".htmlspecialchars($contract['description'] ?? '')."' placeholder='Link or description'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Documentation -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Documentation</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addDocumentation()">
                    <i class="fas fa-plus"></i> Add documentation
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="documentationTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 35%;">Documentation Type</th>
                            <th style="width: 50%;">Description</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="documentationTableBody">
                        <?php
                        $existing_docs = [];
                        if (isset($existing_data['documentation'])) {
                            $existing_docs = json_decode($existing_data['documentation'], true) ?: [];
                        }
                        
                        if (empty($existing_docs)) {
                            $doc_types = ['System/Operations documentation', 'User documentation', 'Technical documentation'];
                            foreach ($doc_types as $index => $type) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='documentation[$index][id]' placeholder='".($index+1)."'></td>";
                                echo "<td><input type='text' class='form-control' name='documentation[$index][type]' value='$type' placeholder='Documentation Type'></td>";
                                echo "<td><input type='text' class='form-control' name='documentation[$index][description]' placeholder='Location, format, and access details'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            foreach ($existing_docs as $index => $doc) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='documentation[$index][id]' value='".htmlspecialchars($doc['id'] ?? '')."' placeholder='ID'></td>";
                                echo "<td><input type='text' class='form-control' name='documentation[$index][type]' value='".htmlspecialchars($doc['type'] ?? '')."' placeholder='Documentation Type'></td>";
                                echo "<td><input type='text' class='form-control' name='documentation[$index][description]' value='".htmlspecialchars($doc['description'] ?? '')."' placeholder='Description'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="alert alert-light">
            <h6><i class="fas fa-info-circle"></i> Help text</h6>
            <ul class="mb-0">
                <li><strong>Functionality:</strong> Describe all key features and capabilities delivered</li>
                <li><strong>Contracts:</strong> Include main contracts, service agreements, and license information</li>
                <li><strong>Documentation:</strong> List all relevant documentation with locations and access information</li>
                <li>Include cost estimates for project backlog items where applicable</li>
            </ul>
        </div>
    </div>
</div>

<script>
let functionalityIndex = <?php echo count($existing_functionality ?? [1]); ?>;
let contractIndex = <?php echo count($existing_contracts ?? [1, 2, 3]); ?>;
let documentationIndex = <?php echo count($existing_docs ?? [1, 2, 3]); ?>;

function addFunctionality() {
    const tableBody = document.getElementById('functionalityTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='text' class='form-control' name='functionality[${functionalityIndex}][id]' placeholder='ID'></td>
        <td><input type='text' class='form-control' name='functionality[${functionalityIndex}][function]' placeholder='Function'></td>
        <td><input type='text' class='form-control' name='functionality[${functionalityIndex}][description]' placeholder='Description'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    functionalityIndex++;
}

function addContract() {
    const tableBody = document.getElementById('contractsTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='text' class='form-control' name='contracts[${contractIndex}][id]' placeholder='ID'></td>
        <td><select class='form-select' name='contracts[${contractIndex}][type]'>
            <option value=''>Select type...</option>
            <option value='Contract'>Contract</option>
            <option value='Service and maintenance'>Service and maintenance</option>
            <option value='Licenses'>Licenses</option>
            <option value='Other'>Other</option>
        </select></td>
        <td><input type='text' class='form-control' name='contracts[${contractIndex}][description]' placeholder='Link or description'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    contractIndex++;
}

function addDocumentation() {
    const tableBody = document.getElementById('documentationTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='text' class='form-control' name='documentation[${documentationIndex}][id]' placeholder='ID'></td>
        <td><input type='text' class='form-control' name='documentation[${documentationIndex}][type]' placeholder='Documentation Type'></td>
        <td><input type='text' class='form-control' name='documentation[${documentationIndex}][description]' placeholder='Description'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    documentationIndex++;
}

function removeRow(button) {
    if (confirm('Are you sure you want to remove this item?')) {
        button.closest('tr').remove();
    }
}
</script>
