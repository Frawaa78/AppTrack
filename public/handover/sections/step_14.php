<?php
// public/handover/sections/step_14.php - Meeting Minutes
?>

<div class="alert alert-info">
    <h6><i class="fas fa-clipboard-list"></i> Meeting Minutes (Appendix A)</h6>
    <p class="mb-0">Document key meetings, decisions, and action items from the handover process.</p>
</div>

<div class="row">
    <div class="col-12">
        <!-- Meeting List -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Handover Meetings</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addMeeting()">
                    <i class="fas fa-plus"></i> Add meeting
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="meetingsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 20%;">Meeting Date</th>
                            <th style="width: 25%;">Meeting Title</th>
                            <th style="width: 20%;">Participants</th>
                            <th style="width: 15%;">Type</th>
                            <th style="width: 15%;">Status</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="meetingsTableBody">
                        <?php
                        $existing_meetings = [];
                        if (isset($existing_data['meetings'])) {
                            $existing_meetings = json_decode($existing_data['meetings'], true) ?: [];
                        }
                        
                        if (empty($existing_meetings)) {
                            $meeting_types = [
                                'Kickoff Meeting',
                                'Technical Review',
                                'Security Review',
                                'Final Handover'
                            ];
                            foreach ($meeting_types as $index => $type) {
                                echo "<tr>";
                                echo "<td><input type='datetime-local' class='form-control' name='meetings[$index][date]'></td>";
                                echo "<td><input type='text' class='form-control' name='meetings[$index][title]' value='$type' placeholder='Meeting title'></td>";
                                echo "<td><input type='text' class='form-control' name='meetings[$index][participants]' placeholder='List of participants'></td>";
                                echo "<td><select class='form-select' name='meetings[$index][type]'>";
                                echo "<option value=''>Select type...</option>";
                                echo "<option value='kickoff' ".($type === 'Kickoff Meeting' ? 'selected' : '').">Kickoff</option>";
                                echo "<option value='review' ".($type === 'Technical Review' || $type === 'Security Review' ? 'selected' : '').">Review</option>";
                                echo "<option value='handover' ".($type === 'Final Handover' ? 'selected' : '').">Handover</option>";
                                echo "<option value='follow-up'>Follow-up</option>";
                                echo "</select></td>";
                                echo "<td><select class='form-select' name='meetings[$index][status]'>";
                                echo "<option value='planned'>Planned</option>";
                                echo "<option value='completed'>Completed</option>";
                                echo "<option value='cancelled'>Cancelled</option>";
                                echo "</select></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            foreach ($existing_meetings as $index => $meeting) {
                                echo "<tr>";
                                echo "<td><input type='datetime-local' class='form-control' name='meetings[$index][date]' value='".htmlspecialchars($meeting['date'] ?? '')."'></td>";
                                echo "<td><input type='text' class='form-control' name='meetings[$index][title]' value='".htmlspecialchars($meeting['title'] ?? '')."' placeholder='Meeting Title'></td>";
                                echo "<td><input type='text' class='form-control' name='meetings[$index][participants]' value='".htmlspecialchars($meeting['participants'] ?? '')."' placeholder='Participants'></td>";
                                echo "<td><select class='form-select' name='meetings[$index][type]'>";
                                echo "<option value=''>Select type...</option>";
                                $meeting_type = $meeting['type'] ?? '';
                                foreach (['kickoff' => 'Kickoff', 'review' => 'Review', 'handover' => 'Handover', 'follow-up' => 'Follow-up'] as $value => $label) {
                                    $selected = ($meeting_type === $value) ? ' selected' : '';
                                    echo "<option value='$value'$selected>$label</option>";
                                }
                                echo "</select></td>";
                                echo "<td><select class='form-select' name='meetings[$index][status]'>";
                                $meeting_status = $meeting['status'] ?? 'planned';
                                foreach (['planned' => 'Planned', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label) {
                                    $selected = ($meeting_status === $value) ? ' selected' : '';
                                    echo "<option value='$value'$selected>$label</option>";
                                }
                                echo "</select></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Key Decisions -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Key Decisions</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addDecision()">
                    <i class="fas fa-plus"></i> Add decision
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="decisionsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 15%;">Date</th>
                            <th style="width: 30%;">Decision</th>
                            <th style="width: 20%;">Decision Maker</th>
                            <th style="width: 30%;">Impact</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="decisionsTableBody">
                        <?php
                        $existing_decisions = [];
                        if (isset($existing_data['decisions'])) {
                            $existing_decisions = json_decode($existing_data['decisions'], true) ?: [];
                        }
                        
                        if (empty($existing_decisions)) {
                            for ($i = 0; $i < 3; $i++) {
                                echo "<tr>";
                                echo "<td><input type='date' class='form-control' name='decisions[$i][date]'></td>";
                                echo "<td><input type='text' class='form-control' name='decisions[$i][decision]' placeholder='Decision made'></td>";
                                echo "<td><input type='text' class='form-control' name='decisions[$i][decision_maker]' placeholder='Who made the decision'></td>";
                                echo "<td><input type='text' class='form-control' name='decisions[$i][impact]' placeholder='Impact on handover'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            foreach ($existing_decisions as $index => $decision) {
                                echo "<tr>";
                                echo "<td><input type='date' class='form-control' name='decisions[$index][date]' value='".htmlspecialchars($decision['date'] ?? '')."'></td>";
                                echo "<td><input type='text' class='form-control' name='decisions[$index][decision]' value='".htmlspecialchars($decision['decision'] ?? '')."' placeholder='Decision'></td>";
                                echo "<td><input type='text' class='form-control' name='decisions[$index][decision_maker]' value='".htmlspecialchars($decision['decision_maker'] ?? '')."' placeholder='Decision Maker'></td>";
                                echo "<td><input type='text' class='form-control' name='decisions[$index][impact]' value='".htmlspecialchars($decision['impact'] ?? '')."' placeholder='Impact'></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Action Items -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Action Items</h6>
                <button type="button" class="btn btn-sm btn-primary" onclick="addActionItem()">
                    <i class="fas fa-plus"></i> Add action item
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="actionItemsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 8%;">ID</th>
                            <th style="width: 30%;">Action Item</th>
                            <th style="width: 20%;">Assigned To</th>
                            <th style="width: 12%;">Due Date</th>
                            <th style="width: 15%;">Status</th>
                            <th style="width: 10%;">Priority</th>
                            <th style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="actionItemsTableBody">
                        <?php
                        $existing_actions = [];
                        if (isset($existing_data['action_items'])) {
                            $existing_actions = json_decode($existing_data['action_items'], true) ?: [];
                        }
                        
                        if (empty($existing_actions)) {
                            for ($i = 0; $i < 5; $i++) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='action_items[$i][id]' placeholder='".($i+1)."'></td>";
                                echo "<td><input type='text' class='form-control' name='action_items[$i][action]' placeholder='Action to be taken'></td>";
                                echo "<td><input type='text' class='form-control' name='action_items[$i][assigned_to]' placeholder='Person responsible'></td>";
                                echo "<td><input type='date' class='form-control' name='action_items[$i][due_date]'></td>";
                                echo "<td><select class='form-select' name='action_items[$i][status]'>";
                                echo "<option value='open'>Open</option>";
                                echo "<option value='in-progress'>In Progress</option>";
                                echo "<option value='completed'>Completed</option>";
                                echo "<option value='blocked'>Blocked</option>";
                                echo "</select></td>";
                                echo "<td><select class='form-select' name='action_items[$i][priority]'>";
                                echo "<option value='low'>Low</option>";
                                echo "<option value='medium' selected>Medium</option>";
                                echo "<option value='high'>High</option>";
                                echo "<option value='critical'>Critical</option>";
                                echo "</select></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        } else {
                            foreach ($existing_actions as $index => $action) {
                                echo "<tr>";
                                echo "<td><input type='text' class='form-control' name='action_items[$index][id]' value='".htmlspecialchars($action['id'] ?? '')."' placeholder='ID'></td>";
                                echo "<td><input type='text' class='form-control' name='action_items[$index][action]' value='".htmlspecialchars($action['action'] ?? '')."' placeholder='Action'></td>";
                                echo "<td><input type='text' class='form-control' name='action_items[$index][assigned_to]' value='".htmlspecialchars($action['assigned_to'] ?? '')."' placeholder='Assigned To'></td>";
                                echo "<td><input type='date' class='form-control' name='action_items[$index][due_date]' value='".htmlspecialchars($action['due_date'] ?? '')."'></td>";
                                echo "<td><select class='form-select' name='action_items[$index][status]'>";
                                $action_status = $action['status'] ?? 'open';
                                foreach (['open' => 'Open', 'in-progress' => 'In Progress', 'completed' => 'Completed', 'blocked' => 'Blocked'] as $value => $label) {
                                    $selected = ($action_status === $value) ? ' selected' : '';
                                    echo "<option value='$value'$selected>$label</option>";
                                }
                                echo "</select></td>";
                                echo "<td><select class='form-select' name='action_items[$index][priority]'>";
                                $action_priority = $action['priority'] ?? 'medium';
                                foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $value => $label) {
                                    $selected = ($action_priority === $value) ? ' selected' : '';
                                    echo "<option value='$value'$selected>$label</option>";
                                }
                                echo "</select></td>";
                                echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Meeting Summary -->
        <div class="mb-4">
            <h6>Overall Meeting Summary</h6>
            <div class="form-floating mb-3">
                <textarea class="form-control" name="meeting_summary" id="meetingSummary" style="height: 150px;" placeholder="Summarize the key outcomes of all handover meetings"><?php echo htmlspecialchars($existing_data['meeting_summary'] ?? ''); ?></textarea>
                <label for="meetingSummary">Meeting summary</label>
            </div>
        </div>

        <div class="alert alert-light">
            <h6><i class="fas fa-info-circle"></i> Help text</h6>
            <ul class="mb-0">
                <li><strong>Meetings:</strong> Record all formal handover meetings and their outcomes</li>
                <li><strong>Decisions:</strong> Document key decisions that affect the handover process</li>
                <li><strong>Action items:</strong> Track all follow-up actions with assigned owners and due dates</li>
                <li><strong>Priority levels:</strong> Critical items should be resolved before handover completion</li>
                <li>This section serves as Appendix A to the handover document</li>
            </ul>
        </div>
    </div>
</div>

<script>
let meetingIndex = <?php echo count($existing_meetings ?? [1, 2, 3, 4]); ?>;
let decisionIndex = <?php echo count($existing_decisions ?? [1, 2, 3]); ?>;
let actionItemIndex = <?php echo count($existing_actions ?? [1, 2, 3, 4, 5]); ?>;

function addMeeting() {
    const tableBody = document.getElementById('meetingsTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='datetime-local' class='form-control' name='meetings[${meetingIndex}][date]'></td>
        <td><input type='text' class='form-control' name='meetings[${meetingIndex}][title]' placeholder='Meeting Title'></td>
        <td><input type='text' class='form-control' name='meetings[${meetingIndex}][participants]' placeholder='Participants'></td>
        <td><select class='form-select' name='meetings[${meetingIndex}][type]'>
            <option value=''>Select type...</option>
            <option value='kickoff'>Kickoff</option>
            <option value='review'>Review</option>
            <option value='handover'>Handover</option>
            <option value='follow-up'>Follow-up</option>
        </select></td>
        <td><select class='form-select' name='meetings[${meetingIndex}][status]'>
            <option value='planned'>Planned</option>
            <option value='completed'>Completed</option>
            <option value='cancelled'>Cancelled</option>
        </select></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    meetingIndex++;
}

function addDecision() {
    const tableBody = document.getElementById('decisionsTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='date' class='form-control' name='decisions[${decisionIndex}][date]'></td>
        <td><input type='text' class='form-control' name='decisions[${decisionIndex}][decision]' placeholder='Decision'></td>
        <td><input type='text' class='form-control' name='decisions[${decisionIndex}][decision_maker]' placeholder='Decision Maker'></td>
        <td><input type='text' class='form-control' name='decisions[${decisionIndex}][impact]' placeholder='Impact'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    decisionIndex++;
}

function addActionItem() {
    const tableBody = document.getElementById('actionItemsTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type='text' class='form-control' name='action_items[${actionItemIndex}][id]' placeholder='ID'></td>
        <td><input type='text' class='form-control' name='action_items[${actionItemIndex}][action]' placeholder='Action'></td>
        <td><input type='text' class='form-control' name='action_items[${actionItemIndex}][assigned_to]' placeholder='Assigned To'></td>
        <td><input type='date' class='form-control' name='action_items[${actionItemIndex}][due_date]'></td>
        <td><select class='form-select' name='action_items[${actionItemIndex}][status]'>
            <option value='open'>Open</option>
            <option value='in-progress'>In Progress</option>
            <option value='completed'>Completed</option>
            <option value='blocked'>Blocked</option>
        </select></td>
        <td><select class='form-select' name='action_items[${actionItemIndex}][priority]'>
            <option value='low'>Low</option>
            <option value='medium' selected>Medium</option>
            <option value='high'>High</option>
            <option value='critical'>Critical</option>
        </select></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    actionItemIndex++;
}

function removeRow(button) {
    if (confirm('Are you sure you want to remove this item?')) {
        button.closest('tr').remove();
    }
}
</script>
