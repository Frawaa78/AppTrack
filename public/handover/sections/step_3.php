<?php
// public/handover/sections/step_3.php - Contact Points
?>

<div class="alert alert-info">
    <h6><i class="fas fa-address-book"></i> Contact points</h6>
    <p class="mb-0">Define key contacts for various roles and responsibilities. These are permanent contacts to be used after handover.</p>
</div>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6>Contact persons</h6>
            <button type="button" class="btn btn-sm btn-primary" onclick="addContact()">
                <i class="fas fa-plus"></i> Add contact
            </button>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered" id="contactsTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 25%;">Role</th>
                        <th style="width: 25%;">Company/Organization</th>
                        <th style="width: 25%;">Name</th>
                        <th style="width: 20%;">Contact info</th>
                        <th style="width: 5%;">Actions</th>
                    </tr>
                </thead>
                <tbody id="contactsTableBody">
                    <?php
                    // Pre-populate with standard contact roles
                    $standard_contacts = [
                        'System owner',
                        'System manager', 
                        'Vendor name',
                        'Support vendor',
                        'Portfolio manager',
                        'Business Analyst',
                        'Information Steward',
                        'Support contact',
                        'Super users'
                    ];
                    
                    // Get existing contacts
                    $existing_contacts = [];
                    if (isset($existing_data['contacts'])) {
                        $existing_contacts = json_decode($existing_data['contacts'], true) ?: [];
                    }
                    
                    // If no existing data, pre-populate with standard roles
                    if (empty($existing_contacts)) {
                        foreach ($standard_contacts as $index => $role) {
                            echo "<tr>";
                            echo "<td>";
                            echo "<select class='form-select' name='contacts[$index][role]'>";
                            echo "<option value='$role' selected>$role</option>";
                            foreach ($standard_contacts as $option) {
                                if ($option !== $role) {
                                    echo "<option value='$option'>$option</option>";
                                }
                            }
                            echo "<option value='custom'>Other role...</option>";
                            echo "</select>";
                            echo "<input type='text' class='form-control mt-1 d-none' name='contacts[$index][custom_role]' placeholder='Specify role'>";
                            echo "</td>";
                            echo "<td><input type='text' class='form-control' name='contacts[$index][organization]' placeholder='Company/Organization'></td>";
                            echo "<td><input type='text' class='form-control' name='contacts[$index][name]' placeholder='Name (if applicable)'></td>";
                            echo "<td><input type='text' class='form-control' name='contacts[$index][contact_info]' placeholder='Email/phone'></td>";
                            echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
                            echo "</tr>";
                        }
                    } else {
                        // Load existing contacts
                        foreach ($existing_contacts as $index => $contact) {
                            echo "<tr>";
                            echo "<td>";
                            echo "<select class='form-select' name='contacts[$index][role]'>";
                            $selected_role = $contact['role'] ?? '';
                            $is_custom = !in_array($selected_role, $standard_contacts);
                            
                            foreach ($standard_contacts as $option) {
                                $selected = ($selected_role === $option) ? 'selected' : '';
                                echo "<option value='$option' $selected>$option</option>";
                            }
                            echo "<option value='custom' " . ($is_custom ? 'selected' : '') . ">Other role...</option>";
                            echo "</select>";
                            echo "<input type='text' class='form-control mt-1 " . ($is_custom ? '' : 'd-none') . "' name='contacts[$index][custom_role]' placeholder='Specify role' value='" . ($is_custom ? htmlspecialchars($selected_role) : '') . "'>";
                            echo "</td>";
                            echo "<td><input type='text' class='form-control' name='contacts[$index][organization]' value='".htmlspecialchars($contact['organization'] ?? '')."' placeholder='Company/Organization'></td>";
                            echo "<td><input type='text' class='form-control' name='contacts[$index][name]' value='".htmlspecialchars($contact['name'] ?? '')."' placeholder='Name (if applicable)'></td>";
                            echo "<td><input type='text' class='form-control' name='contacts[$index][contact_info]' value='".htmlspecialchars($contact['contact_info'] ?? '')."' placeholder='Email/phone'></td>";
                            echo "<td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>";
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
                <li><strong>System owner:</strong> Business responsible for the system</li>
                <li><strong>System manager:</strong> Technical responsible for operations</li>
                <li><strong>Vendor/Support vendor:</strong> Supplier and support supplier</li>
                <li><strong>Information Steward:</strong> Responsible for data management</li>
                <li><strong>Super users:</strong> Advanced users who can assist others</li>
                <li>Add additional roles if necessary by selecting "Other role..."</li>
            </ul>
        </div>
    </div>
</div>

<script>
let contactIndex = <?php echo count($existing_contacts ?? $standard_contacts); ?>;

function addContact() {
    const tableBody = document.getElementById('contactsTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <select class='form-select' name='contacts[${contactIndex}][role]' onchange='toggleCustomRole(this)'>
                <option value=''>Select role...</option>
                <option value='System owner'>System owner</option>
                <option value='System manager'>System manager</option>
                <option value='Vendor name'>Vendor name</option>
                <option value='Support vendor'>Support vendor</option>
                <option value='Portfolio manager'>Portfolio manager</option>
                <option value='Business Analyst'>Business Analyst</option>
                <option value='Information Steward'>Information Steward</option>
                <option value='Support contact'>Support contact</option>
                <option value='Super users'>Super users</option>
                <option value='custom'>Other role...</option>
            </select>
            <input type='text' class='form-control mt-1 d-none' name='contacts[${contactIndex}][custom_role]' placeholder='Specify role'>
        </td>
        <td><input type='text' class='form-control' name='contacts[${contactIndex}][organization]' placeholder='Company/Organization'></td>
        <td><input type='text' class='form-control' name='contacts[${contactIndex}][name]' placeholder='Name (if applicable)'></td>
        <td><input type='text' class='form-control' name='contacts[${contactIndex}][contact_info]' placeholder='Email/phone'></td>
        <td><button type='button' class='btn btn-sm btn-outline-danger' onclick='removeRow(this)'><i class='fas fa-trash'></i></button></td>
    `;
    tableBody.appendChild(row);
    contactIndex++;
}

function removeRow(button) {
    if (confirm('Are you sure you want to remove this contact?')) {
        button.closest('tr').remove();
    }
}

function toggleCustomRole(select) {
    const customInput = select.parentElement.querySelector('input[name$="[custom_role]"]');
    if (select.value === 'custom') {
        customInput.classList.remove('d-none');
        customInput.focus();
    } else {
        customInput.classList.add('d-none');
        customInput.value = '';
    }
}

// Initialize existing dropdowns
document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('select[name$="[role]"]');
    selects.forEach(select => {
        select.addEventListener('change', function() {
            toggleCustomRole(this);
        });
    });
});
</script>
