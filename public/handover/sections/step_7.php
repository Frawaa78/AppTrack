<?php
// public/handover/sections/step_7.php - Release Management
?>

<div class="alert alert-info">
    <h6><i class="fas fa-rocket"></i> Release Management</h6>
    <p class="mb-0">Document release-related information including frequency, costs, vendor involvement, and deployment procedures.</p>
</div>

<div class="row">
    <div class="col-12">
        <!-- Release Related Items -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Release Related Items</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addReleaseItem()">
                    <i class="fas fa-plus"></i> Add item
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="releaseTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 35%;">Release Item</th>
                            <th style="width: 50%;">Description</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="releaseTableBody">
                        <?php
                        $existing_release = [];
                        if (isset($existing_data['release_items'])) {
                            $existing_release = json_decode($existing_data['release_items'], true) ?: [];
                        }
                        
                        if (empty($existing_release)) {
                            $release_items = [
                                'Release frequency',
                                'Release cost',
                                'Vendor involvement',
                                'Business involvement',
                                'Deployment procedure',
                                'Rollback procedure',
                                'Release windows',
                                'Change approval process'
                            ];
                            foreach ($release_items as $index => $item) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='release_items[$index][id]' placeholder='".($index+1)."'></td>";
                                echo "<td><select class='form-select' name='release_items[$index][item]'>";
                                echo "<option value='$item' selected>$item</option>";
                                foreach ($release_items as $option) {
                                    if ($option !== $item) echo "<option value='$option'>$option</option>";
                                }
                                echo "<option value='Other'>Other</option>";
                                echo "</select></td>";
                                echo "<td><input type='text' class='form-control' name='release_items[$index][description]' placeholder='Provide details for this release item'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            foreach ($existing_release as $index => $item) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='release_items[$index][id]' value='".htmlspecialchars($item['id'] ?? '')."' placeholder='ID'></td>";
                                echo "<td><input type='text' class='form-control' name='release_items[$index][item]' value='".htmlspecialchars($item['item'] ?? '')."' placeholder='Release Item'></td>";
                                echo "<td><input type='text' class='form-control' name='release_items[$index][description]' value='".htmlspecialchars($item['description'] ?? '')."' placeholder='Description'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Release Schedule -->
        <div class="mb-4">
            <h6>Release Schedule</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <select class="form-select" name="release_frequency" id="releaseFrequency">
                            <option value="">Select frequency...</option>
                            <option value="Weekly" <?php echo ($existing_data['release_frequency'] ?? '') === 'Weekly' ? 'selected' : ''; ?>>Weekly</option>
                            <option value="Bi-weekly" <?php echo ($existing_data['release_frequency'] ?? '') === 'Bi-weekly' ? 'selected' : ''; ?>>Bi-weekly</option>
                            <option value="Monthly" <?php echo ($existing_data['release_frequency'] ?? '') === 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
                            <option value="Quarterly" <?php echo ($existing_data['release_frequency'] ?? '') === 'Quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                            <option value="As needed" <?php echo ($existing_data['release_frequency'] ?? '') === 'As needed' ? 'selected' : ''; ?>>As needed</option>
                            <option value="Other" <?php echo ($existing_data['release_frequency'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <label for="releaseFrequency">Release frequency</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="release_window" id="releaseWindow" placeholder="Release window" value="<?php echo htmlspecialchars($existing_data['release_window'] ?? ''); ?>">
                        <label for="releaseWindow">Preferred release window</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deployment Details -->
        <div class="mb-4">
            <h6>Deployment Details</h6>
            <div class="form-floating mb-3">
                <textarea class="form-control" name="deployment_procedure" id="deploymentProcedure" style="height: 100px" placeholder="Describe deployment steps..."><?php echo htmlspecialchars($existing_data['deployment_procedure'] ?? ''); ?></textarea>
                <label for="deploymentProcedure">Deployment procedure</label>
            </div>
            
            <div class="form-floating mb-3">
                <textarea class="form-control" name="rollback_procedure" id="rollbackProcedure" style="height: 80px" placeholder="Describe rollback steps..."><?php echo htmlspecialchars($existing_data['rollback_procedure'] ?? ''); ?></textarea>
                <label for="rollbackProcedure">Rollback procedure</label>
            </div>
        </div>

        <!-- Release Stakeholders -->
        <div class="mb-4">
            <h6>Release Stakeholders</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="release_manager" id="releaseManager" placeholder="Release manager" value="<?php echo htmlspecialchars($existing_data['release_manager'] ?? ''); ?>">
                        <label for="releaseManager">Release manager</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="technical_lead" id="technicalLead" placeholder="Technical lead" value="<?php echo htmlspecialchars($existing_data['technical_lead'] ?? ''); ?>">
                        <label for="technicalLead">Technical lead</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="business_approver" id="businessApprover" placeholder="Business approver" value="<?php echo htmlspecialchars($existing_data['business_approver'] ?? ''); ?>">
                        <label for="businessApprover">Business approver</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-light">
            <h6><i class="fas fa-info-circle"></i> Help text</h6>
            <ul class="mb-0">
                <li><strong>Release frequency:</strong> How often releases occur (planned schedule)</li>
                <li><strong>Cost:</strong> Estimated cost per release or annual release budget</li>
                <li><strong>Vendor involvement:</strong> External vendor participation in releases</li>
                <li><strong>Business involvement:</strong> Business stakeholder requirements for releases</li>
                <li>Include emergency release procedures if different from standard</li>
                <li>Document any special requirements for production deployments</li>
            </ul>
        </div>
    </div>
</div>

<script>
let releaseIndex = <?php echo count($existing_release ?? [1, 2, 3, 4, 5, 6, 7, 8]); ?>;

function addReleaseItem() {
    const tableBody = document.getElementById('releaseTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='text' class='form-control' name='release_items[${releaseIndex}][id]' placeholder='ID'></td>
        <td><select class='form-select' name='release_items[${releaseIndex}][item]'>
            <option value=''>Select item...</option>
            <option value='Release frequency'>Release frequency</option>
            <option value='Release cost'>Release cost</option>
            <option value='Vendor involvement'>Vendor involvement</option>
            <option value='Business involvement'>Business involvement</option>
            <option value='Deployment procedure'>Deployment procedure</option>
            <option value='Rollback procedure'>Rollback procedure</option>
            <option value='Release windows'>Release windows</option>
            <option value='Change approval process'>Change approval process</option>
            <option value='Other'>Other</option>
        </select></td>
        <td><input type='text' class='form-control' name='release_items[${releaseIndex}][description]' placeholder='Description'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    releaseIndex++;
}

function removeRow(button) {
    if (confirm('Are you sure you want to remove this item?')) {
        button.closest('tr').remove();
    }
}
</script>
