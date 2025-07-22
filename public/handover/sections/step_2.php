<?php
// public/handover/sections/step_2.php - Participants of Handover
?>

<div class="alert alert-info">
    <h6><i class="fas fa-users"></i> Handover participants</h6>
    <p class="mb-0">Register everyone who should participate in the handover meeting. Actual attendance will be logged later in "Meeting Minutes".</p>
</div>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6>Participants</h6>
            <button type="button" class="btn btn-sm btn-primary" onclick="addParticipant()">
                <i class="fas fa-plus"></i> Add participant
            </button>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered" id="participantsTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 25%;">Role</th>
                        <th style="width: 30%;">Name/Function</th>
                        <th style="width: 25%;">Organization</th>
                        <th style="width: 15%;">Contact info</th>
                        <th style="width: 5%;">Actions</th>
                    </tr>
                </thead>
                <tbody id="participantsTableBody">
                    <?php
                    // Pre-populate with standard roles
                    $standard_roles = [
                        'Project Leader',
                        'Release Manager', 
                        'Application Manager',
                        'System Owner'
                    ];
                    
                    // Get existing participants
                    $existing_participants = [];
                    if (isset($existing_data['participants'])) {
                        $existing_participants = json_decode($existing_data['participants'], true) ?: [];
                    }
                    
                    // If no existing data, pre-populate with standard roles
                    if (empty($existing_participants)) {
                        foreach ($standard_roles as $index => $role) {
                            echo "<tr>";
                            echo "<td><input type='text' class='form-control' name='participants[$index][role]' value='$role' placeholder='Role'></td>";
                            echo "<td><input type='text' class='form-control' name='participants[$index][name]' placeholder='Name/Function'></td>";
                            echo "<td><input type='text' class='form-control' name='participants[$index][organization]' placeholder='Organization'></td>";
                            echo "<td><input type='text' class='form-control' name='participants[$index][contact_info]' placeholder='Email/phone'></td>";
                            echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeParticipantRow(this)'><i class='fas fa-trash'></i></button></td>";
                            echo "</tr>";
                        }
                    } else {
                        // Load existing participants
                        foreach ($existing_participants as $index => $participant) {
                            echo "<tr>";
                            echo "<td><input type='text' class='form-control' name='participants[$index][role]' value='".htmlspecialchars($participant['role'] ?? '')."' placeholder='Role'></td>";
                            echo "<td><input type='text' class='form-control' name='participants[$index][name]' value='".htmlspecialchars($participant['name'] ?? '')."' placeholder='Name/Function'></td>";
                            echo "<td><input type='text' class='form-control' name='participants[$index][organization]' value='".htmlspecialchars($participant['organization'] ?? '')."' placeholder='Organization'></td>";
                            echo "<td><input type='text' class='form-control' name='participants[$index][contact_info]' value='".htmlspecialchars($participant['contact_info'] ?? '')."' placeholder='Email/phone'></td>";
                            echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeParticipantRow(this)'><i class='fas fa-trash'></i></button></td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div class="alert alert-light mt-3">
            <h6><i class="fas fa-info-circle"></i> Help text</h6>
            <ul class="mb-0">
                <li>Add all necessary roles for the handover meeting</li>
                <li>Ensure both technical and business-related roles are represented</li>
                <li>Contact information is important for follow-up after the meeting</li>
                <li>Actual attendance is recorded later in the meeting minutes (Appendix A)</li>
            </ul>
        </div>
    </div>
</div>

<script>
let participantIndex = <?php echo count($existing_participants ?? $standard_roles); ?>;

function addParticipant() {
    const tableBody = document.getElementById('participantsTableBody');
    const row = document.createElement('tr');
    row.innerHTML = 
        '<td><input type="text" class="form-control" name="participants[' + participantIndex + '][role]" placeholder="Role"></td>' +
        '<td><input type="text" class="form-control" name="participants[' + participantIndex + '][name]" placeholder="Name/Function"></td>' +
        '<td><input type="text" class="form-control" name="participants[' + participantIndex + '][organization]" placeholder="Organization"></td>' +
        '<td><input type="text" class="form-control" name="participants[' + participantIndex + '][contact_info]" placeholder="Email/phone"></td>' +
        '<td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeParticipantRow(this)"><i class="fas fa-trash"></i></button></td>';
    tableBody.appendChild(row);
    participantIndex++;
    
    // Focus on the first input of the new row
    setTimeout(function() {
        row.querySelector('input').focus();
    }, 100);
}

function removeParticipantRow(button) {
    if (confirm('Are you sure you want to remove this participant?')) {
        const row = button.closest('tr');
        row.remove();
        
        // Simple reindexing - just update the global counter
        updateParticipantCounter();
    }
}

function updateParticipantCounter() {
    const rows = document.querySelectorAll('#participantsTableBody tr');
    participantIndex = rows.length;
}

// Prevent form submission on Enter key in table inputs
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('#participantsTable input');
    inputs.forEach(function(input) {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                // Move to next input or add new row if on last input
                const nextSibling = this.parentElement.nextElementSibling;
                const nextInput = nextSibling ? nextSibling.querySelector('input') : null;
                if (nextInput) {
                    nextInput.focus();
                } else {
                    // If on last column, go to next row or add new row
                    const currentRow = this.closest('tr');
                    const nextRow = currentRow.nextElementSibling;
                    if (nextRow) {
                        nextRow.querySelector('input').focus();
                    } else {
                        addParticipant();
                        setTimeout(function() {
                            const newRow = document.getElementById('participantsTableBody').lastElementChild;
                            newRow.querySelector('input').focus();
                        }, 100);
                    }
                }
            }
        });
    });
});
</script>
