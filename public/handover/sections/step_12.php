<?php
// public/handover/sections/step_12.php - Data Storage
?>

<div class="alert alert-info">
    <h6><i class="fas fa-database"></i> Data Storage</h6>
    <p class="mb-0">Document data flow, storage requirements, migration plans, and data management practices.</p>
</div>

<div class="row">
    <div class="col-12">
        <!-- Data Flow Description -->
        <div class="mb-4">
            <h6>Data Flow Overview</h6>
            <div class="form-floating mb-3">
                <textarea class="form-control" name="data_flow_description" id="dataFlowDescription" style="height: 120px;" placeholder="Describe how data flows through the system"><?php echo htmlspecialchars($existing_data['data_flow_description'] ?? ''); ?></textarea>
                <label for="dataFlowDescription">Data flow description</label>
            </div>
        </div>

        <!-- Data Sources -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Data Sources & Destinations</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addDataSource()">
                    <i class="fas fa-plus"></i> Add data source
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="dataSourcesTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 20%;">Source System</th>
                            <th style="width: 20%;">Data Type</th>
                            <th style="width: 15%;">Frequency</th>
                            <th style="width: 20%;">Storage Location</th>
                            <th style="width: 20%;">Responsible</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="dataSourcesTableBody">
                        <?php
                        $existing_data_sources = [];
                        if (isset($existing_data['data_sources'])) {
                            $existing_data_sources = json_decode($existing_data['data_sources'], true) ?: [];
                        }
                        
                        if (empty($existing_data_sources)) {
                            for ($i = 0; $i < 3; $i++) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='data_sources[$i][source_system]' placeholder='System name'></td>";
                                echo "<td><input type='text' class='form-control' name='data_sources[$i][data_type]' placeholder='Type of data'></td>";
                                echo "<td><input type='text' class='form-control' name='data_sources[$i][frequency]' placeholder='Daily/Weekly/etc'></td>";
                                echo "<td><input type='text' class='form-control' name='data_sources[$i][storage_location]' placeholder='Database/File system'></td>";
                                echo "<td><input type='text' class='form-control' name='data_sources[$i][responsible]' placeholder='Team/Department'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            foreach ($existing_data_sources as $index => $source) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='data_sources[$index][source_system]' value='".htmlspecialchars($source['source_system'] ?? '')."' placeholder='Source System'></td>";
                                echo "<td><input type='text' class='form-control' name='data_sources[$index][data_type]' value='".htmlspecialchars($source['data_type'] ?? '')."' placeholder='Data Type'></td>";
                                echo "<td><input type='text' class='form-control' name='data_sources[$index][frequency]' value='".htmlspecialchars($source['frequency'] ?? '')."' placeholder='Frequency'></td>";
                                echo "<td><input type='text' class='form-control' name='data_sources[$index][storage_location]' value='".htmlspecialchars($source['storage_location'] ?? '')."' placeholder='Storage Location'></td>";
                                echo "<td><input type='text' class='form-control' name='data_sources[$index][responsible]' value='".htmlspecialchars($source['responsible'] ?? '')."' placeholder='Responsible'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Data Redundancy & Backup -->
        <div class="mb-4">
            <h6>Data Redundancy & Backup</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="backup_frequency" id="backupFrequency" placeholder="Backup frequency" value="<?php echo htmlspecialchars($existing_data['backup_frequency'] ?? ''); ?>">
                        <label for="backupFrequency">Backup frequency</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="backup_location" id="backupLocation" placeholder="Backup location" value="<?php echo htmlspecialchars($existing_data['backup_location'] ?? ''); ?>">
                        <label for="backupLocation">Backup location</label>
                    </div>
                </div>
            </div>
            <div class="form-floating mb-3">
                <textarea class="form-control" name="redundancy_description" id="redundancyDescription" style="height: 100px;" placeholder="Describe redundancy and disaster recovery"><?php echo htmlspecialchars($existing_data['redundancy_description'] ?? ''); ?></textarea>
                <label for="redundancyDescription">Redundancy & disaster recovery</label>
            </div>
        </div>

        <!-- Standards and Migration -->
        <div class="mb-4">
            <h6>Standards & Migration</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="data_standards" id="dataStandards" placeholder="Data standards followed" value="<?php echo htmlspecialchars($existing_data['data_standards'] ?? ''); ?>">
                        <label for="dataStandards">Data standards</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="migration_plan" id="migrationPlan" placeholder="Migration plan reference" value="<?php echo htmlspecialchars($existing_data['migration_plan'] ?? ''); ?>">
                        <label for="migrationPlan">Migration plan</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Confidentiality -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Data Confidentiality & Classification</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addDataClassification()">
                    <i class="fas fa-plus"></i> Add classification
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="dataClassificationTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%;">Data Type</th>
                            <th style="width: 20%;">Classification</th>
                            <th style="width: 30%;">Protection Measures</th>
                            <th style="width: 20%;">Access Control</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="dataClassificationTableBody">
                        <?php
                        $existing_classifications = [];
                        if (isset($existing_data['data_classifications'])) {
                            $existing_classifications = json_decode($existing_data['data_classifications'], true) ?: [];
                        }
                        
                        if (empty($existing_classifications)) {
                            $classifications = ['User data', 'Business data', 'System logs'];
                            foreach ($classifications as $index => $type) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='data_classifications[$index][data_type]' value='$type' placeholder='Data type'></td>";
                                echo "<td><select class='form-select' name='data_classifications[$index][classification]'>";
                                echo "<option value=''>Select...</option>";
                                echo "<option value='Public'>Public</option>";
                                echo "<option value='Internal'>Internal</option>";
                                echo "<option value='Confidential'>Confidential</option>";
                                echo "<option value='Restricted'>Restricted</option>";
                                echo "</select></td>";
                                echo "<td><input type='text' class='form-control' name='data_classifications[$index][protection_measures]' placeholder='Encryption, access controls, etc'></td>";
                                echo "<td><input type='text' class='form-control' name='data_classifications[$index][access_control]' placeholder='Who has access'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            foreach ($existing_classifications as $index => $classification) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='data_classifications[$index][data_type]' value='".htmlspecialchars($classification['data_type'] ?? '')."' placeholder='Data Type'></td>";
                                echo "<td><select class='form-select' name='data_classifications[$index][classification]'>";
                                echo "<option value=''>Select...</option>";
                                $selected_class = $classification['classification'] ?? '';
                                foreach (['Public', 'Internal', 'Confidential', 'Restricted'] as $level) {
                                    $selected = ($selected_class === $level) ? ' selected' : '';
                                    echo "<option value='$level'$selected>$level</option>";
                                }
                                echo "</select></td>";
                                echo "<td><input type='text' class='form-control' name='data_classifications[$index][protection_measures]' value='".htmlspecialchars($classification['protection_measures'] ?? '')."' placeholder='Protection Measures'></td>";
                                echo "<td><input type='text' class='form-control' name='data_classifications[$index][access_control]' value='".htmlspecialchars($classification['access_control'] ?? '')."' placeholder='Access Control'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Archiving -->
        <div class="mb-4">
            <h6>Data Archiving & Retention</h6>
            <div class="form-floating mb-3">
                <textarea class="form-control" name="archiving_policy" id="archivingPolicy" style="height: 100px;" placeholder="Describe archiving policies and retention periods"><?php echo htmlspecialchars($existing_data['archiving_policy'] ?? ''); ?></textarea>
                <label for="archivingPolicy">Archiving policy & retention periods</label>
            </div>
        </div>

        <div class="alert alert-light">
            <h6><i class="fas fa-info-circle"></i> Help text</h6>
            <ul class="mb-0">
                <li><strong>Data flow:</strong> Describe how data moves through your system</li>
                <li><strong>Sources:</strong> Include all external systems providing data</li>
                <li><strong>Backup:</strong> Document backup frequency and locations</li>
                <li><strong>Classification:</strong> Classify data according to sensitivity levels</li>
                <li><strong>Archiving:</strong> Define retention periods and archiving procedures</li>
            </ul>
        </div>
    </div>
</div>

<script>
let dataSourceIndex = <?php echo count($existing_data_sources ?? [1, 2, 3]); ?>;
let dataClassificationIndex = <?php echo count($existing_classifications ?? [1, 2, 3]); ?>;

function addDataSource() {
    const tableBody = document.getElementById('dataSourcesTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='text' class='form-control' name='data_sources[${dataSourceIndex}][source_system]' placeholder='Source System'></td>
        <td><input type='text' class='form-control' name='data_sources[${dataSourceIndex}][data_type]' placeholder='Data Type'></td>
        <td><input type='text' class='form-control' name='data_sources[${dataSourceIndex}][frequency]' placeholder='Frequency'></td>
        <td><input type='text' class='form-control' name='data_sources[${dataSourceIndex}][storage_location]' placeholder='Storage Location'></td>
        <td><input type='text' class='form-control' name='data_sources[${dataSourceIndex}][responsible]' placeholder='Responsible'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    dataSourceIndex++;
}

function addDataClassification() {
    const tableBody = document.getElementById('dataClassificationTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='text' class='form-control' name='data_classifications[${dataClassificationIndex}][data_type]' placeholder='Data Type'></td>
        <td><select class='form-select' name='data_classifications[${dataClassificationIndex}][classification]'>
            <option value=''>Select...</option>
            <option value='Public'>Public</option>
            <option value='Internal'>Internal</option>
            <option value='Confidential'>Confidential</option>
            <option value='Restricted'>Restricted</option>
        </select></td>
        <td><input type='text' class='form-control' name='data_classifications[${dataClassificationIndex}][protection_measures]' placeholder='Protection Measures'></td>
        <td><input type='text' class='form-control' name='data_classifications[${dataClassificationIndex}][access_control]' placeholder='Access Control'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    dataClassificationIndex++;
}

function removeRow(button) {
    if (confirm('Are you sure you want to remove this item?')) {
        button.closest('tr').remove();
    }
}
</script>
