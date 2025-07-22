<?php
// public/handover/sections/step_4.php - Support
?>

<div class="alert alert-info">
    <h6><i class="fas fa-life-ring"></i> Support</h6>
    <p class="mb-0">Describe how IT-help should handle inquiries and incidents. Define support responsibilities and knowledge base articles.</p>
</div>

<div class="row">
    <div class="col-12">
        <!-- Support Description -->
        <div class="mb-4">
            <h6>Support Description</h6>
            <div class="form-floating">
                <textarea class="form-control" name="support_description" id="supportDescription" style="height: 100px" placeholder="Describe how IT-help should handle inquiries..."><?php echo htmlspecialchars($existing_data['support_description'] ?? ''); ?></textarea>
                <label for="supportDescription">How should IT-help handle inquiries and incidents for this application?</label>
            </div>
            <small class="text-muted">Example: Who has SLA? Who handles user support? Escalation procedures, etc.</small>
        </div>

        <!-- Service Desk and Assignment Groups -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Service Desk and Assignment Groups</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addServiceDeskItem()">
                    <i class="fas fa-plus"></i> Add item
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="serviceDeskTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 30%;">Support Item</th>
                            <th style="width: 40%;">Description</th>
                            <th style="width: 15%;">Assignment Group</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="serviceDeskTableBody">
                        <?php
                        $existing_service_desk = [];
                        if (isset($existing_data['service_desk_items'])) {
                            $existing_service_desk = json_decode($existing_data['service_desk_items'], true) ?: [];
                        }
                        
                        if (empty($existing_service_desk)) {
                            // Pre-populate with one empty row
                            echo "<tr>";
                            echo "<td><input type='text' class='form-control' name='service_desk_items[0][id]' placeholder='1'></td>";
                            echo "<td><input type='text' class='form-control' name='service_desk_items[0][item]' placeholder='User access requests'></td>";
                            echo "<td><input type='text' class='form-control' name='service_desk_items[0][description]' placeholder='How to handle user access requests'></td>";
                            echo "<td><input type='text' class='form-control' name='service_desk_items[0][group]' placeholder='IT Operations'></td>";
                            echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                            echo "</tr>";
                        } else {
                            foreach ($existing_service_desk as $index => $item) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='service_desk_items[$index][id]' value='".htmlspecialchars($item['id'] ?? '')."' placeholder='ID'></td>";
                                echo "<td><input type='text' class='form-control' name='service_desk_items[$index][item]' value='".htmlspecialchars($item['item'] ?? '')."' placeholder='Support Item'></td>";
                                echo "<td><input type='text' class='form-control' name='service_desk_items[$index][description]' value='".htmlspecialchars($item['description'] ?? '')."' placeholder='Description'></td>";
                                echo "<td><input type='text' class='form-control' name='service_desk_items[$index][group]' value='".htmlspecialchars($item['group'] ?? '')."' placeholder='Assignment Group'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Knowledge Base Articles -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Knowledge Base Articles</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addKBArticle()">
                    <i class="fas fa-plus"></i> Add article
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="kbTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 40%;">KB Article Name</th>
                            <th style="width: 45%;">Description</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="kbTableBody">
                        <?php
                        $existing_kb = [];
                        if (isset($existing_data['kb_articles'])) {
                            $existing_kb = json_decode($existing_data['kb_articles'], true) ?: [];
                        }
                        
                        if (empty($existing_kb)) {
                            // Pre-populate with standard articles
                            $standard_kb = [
                                ['name' => 'Service Desk - Application Support', 'description' => 'Internal KB for service desk staff'],
                                ['name' => 'End User Guide', 'description' => 'User-facing documentation and troubleshooting']
                            ];
                            foreach ($standard_kb as $index => $kb) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='kb_articles[$index][id]' placeholder='".($index+1)."'></td>";
                                echo "<td><input type='text' class='form-control' name='kb_articles[$index][name]' value='".$kb['name']."' placeholder='KB Article Name'></td>";
                                echo "<td><input type='text' class='form-control' name='kb_articles[$index][description]' value='".$kb['description']."' placeholder='Description'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            foreach ($existing_kb as $index => $kb) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='kb_articles[$index][id]' value='".htmlspecialchars($kb['id'] ?? '')."' placeholder='ID'></td>";
                                echo "<td><input type='text' class='form-control' name='kb_articles[$index][name]' value='".htmlspecialchars($kb['name'] ?? '')."' placeholder='KB Article Name'></td>";
                                echo "<td><input type='text' class='form-control' name='kb_articles[$index][description]' value='".htmlspecialchars($kb['description'] ?? '')."' placeholder='Description'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <small class="text-muted">Minimum one for service desk and one for end users</small>
        </div>

        <div class="alert alert-light">
            <h6><i class="fas fa-info-circle"></i> Help text</h6>
            <ul class="mb-0">
                <li>Define clear support procedures and responsibility areas</li>
                <li>Include SLA requirements and escalation procedures</li>
                <li>Ensure both internal (service desk) and external (end user) documentation exists</li>
                <li>Specify assignment groups for different types of support requests</li>
            </ul>
        </div>
    </div>
</div>

<script>
let serviceDeskIndex = <?php echo count($existing_service_desk ?? [1]); ?>;
let kbIndex = <?php echo count($existing_kb ?? [['name' => '', 'description' => ''], ['name' => '', 'description' => '']]); ?>;

function addServiceDeskItem() {
    const tableBody = document.getElementById('serviceDeskTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='text' class='form-control' name='service_desk_items[${serviceDeskIndex}][id]' placeholder='ID'></td>
        <td><input type='text' class='form-control' name='service_desk_items[${serviceDeskIndex}][item]' placeholder='Support Item'></td>
        <td><input type='text' class='form-control' name='service_desk_items[${serviceDeskIndex}][description]' placeholder='Description'></td>
        <td><input type='text' class='form-control' name='service_desk_items[${serviceDeskIndex}][group]' placeholder='Assignment Group'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    serviceDeskIndex++;
}

function addKBArticle() {
    const tableBody = document.getElementById('kbTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='text' class='form-control' name='kb_articles[${kbIndex}][id]' placeholder='ID'></td>
        <td><input type='text' class='form-control' name='kb_articles[${kbIndex}][name]' placeholder='KB Article Name'></td>
        <td><input type='text' class='form-control' name='kb_articles[${kbIndex}][description]' placeholder='Description'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    kbIndex++;
}

function removeRow(button) {
    if (confirm('Are you sure you want to remove this item?')) {
        button.closest('tr').remove();
    }
}
</script>
