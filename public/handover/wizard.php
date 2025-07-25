<?php
// public/handover/wizard.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../src/db/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); // Fixed relative path
    exit;
}

$doc_id = isset($_GET['doc_id']) ? (int)$_GET['doc_id'] : 0;
if ($doc_id <= 0) {
    header('Location: ../dashboard.php'); // Fixed relative path
    exit;
}

$db = Database::getInstance()->getConnection();

// Define sections (moved up before use)
$sections = [
    1 => ['title' => 'Definitions', 'name' => 'definitions'],
    2 => ['title' => 'Participants of Handover', 'name' => 'participants'],
    3 => ['title' => 'Contact Points', 'name' => 'contact_points'],
    4 => ['title' => 'Support', 'name' => 'support'],
    5 => ['title' => 'Deliverables', 'name' => 'deliverables'],
    6 => ['title' => 'Testing', 'name' => 'testing'],
    7 => ['title' => 'Release Management', 'name' => 'release_management'],
    8 => ['title' => 'Technical', 'name' => 'technical'],
    9 => ['title' => 'Risk', 'name' => 'risk'],
    10 => ['title' => 'Security', 'name' => 'security'],
    11 => ['title' => 'Economics', 'name' => 'economics'],
    12 => ['title' => 'Data Storage', 'name' => 'data_storage'],
    13 => ['title' => 'Signatures', 'name' => 'signatures'],
    14 => ['title' => 'Meeting Minutes', 'name' => 'meeting_minutes'],
    15 => ['title' => 'Review & Export', 'name' => 'review_export']
];

// Get handover document with application verification
$stmt = $db->prepare('SELECT hd.*, a.* FROM handover_documents hd 
                     JOIN applications a ON hd.application_id = a.id 
                     WHERE hd.id = :id');
$stmt->execute([':id' => $doc_id]);
$handover = $stmt->fetch();
if (!$handover) {
    // Document not found or not properly linked to application
    header('Location: ../dashboard.php');
    exit;
}

// Verify this handover document is properly linked to an application
if (!$handover['application_id']) {
    // This handover document is not properly linked to an application
    // Redirect to dashboard with error
    header('Location: ../dashboard.php?error=handover_not_linked');
    exit;
}

// Parse completed steps
$completed_steps = [];
if (isset($handover['completed_steps']) && !empty($handover['completed_steps'])) {
    $completed_steps = json_decode($handover['completed_steps'], true) ?: [];
}

$current_step = isset($_GET['step']) ? (int)$_GET['step'] : $handover['current_step'];
if ($current_step < 1) $current_step = 1;
if ($current_step > 15) $current_step = 15;

// Check if current step is completed (locked)
$is_step_completed = in_array($current_step, $completed_steps);

// Get which steps have data
$stmt = $db->prepare('SELECT DISTINCT section_name FROM handover_data WHERE handover_document_id = :doc_id');
$stmt->execute([':doc_id' => $doc_id]);
$sections_with_data = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Map section names to step numbers
$section_to_step = array_flip(array_column($sections, 'name'));
$steps_with_data = [];
foreach ($sections_with_data as $section) {
    if (isset($section_to_step[$section])) {
        $steps_with_data[] = $section_to_step[$section];
    }
}

// Get existing data for current section
$stmt = $db->prepare('SELECT field_name, field_value FROM handover_data 
                     WHERE handover_document_id = :doc_id AND section_name = :section');
$stmt->execute([':doc_id' => $doc_id, ':section' => $sections[$current_step]['name']]);
$existing_data = [];
while ($row = $stmt->fetch()) {
    $existing_data[$row['field_name']] = $row['field_value'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handover Wizard - Step <?php echo $current_step; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- FontAwesome Pro -->
    <script src="https://kit.fontawesome.com/d67c79608d.js" crossorigin="anonymous"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/main.css" rel="stylesheet"> <!-- Go up two levels from handover to root -->
    <link rel="icon" type="image/png" href="../../assets/logo.png"> <!-- Go up two levels from handover to root -->
    <style>
        /* Ensure profile image has correct size and shape */
        .profile-img { 
            width: 36px !important; 
            height: 36px !important; 
            object-fit: cover !important; 
            border-radius: 50% !important; 
        }
        
        .step-indicator {
            counter-reset: step;
        }
        .step-indicator li {
            counter-increment: step;
            position: relative;
        }
        .step-indicator li::before {
            content: counter(step);
            position: absolute;
            left: -25px;
            top: 50%;
            transform: translateY(-50%);
            background: #6c757d;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .step-indicator li.completed::before {
            background: #198754;
            content: "✓";
        }
        .step-indicator li.has-data::before {
            background: #ffc107;
            color: #000;
        }
        .step-indicator li.current::before {
            background: #0d6efd;
        }
        .step-indicator li.informational::before {
            background: #17a2b8;
            content: "i";
        }
        .auto-save-indicator {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1000;
        }
        .step-controls {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .form-readonly input,
        .form-readonly textarea,
        .form-readonly select {
            background-color: #f8f9fa !important;
            opacity: 0.8;
            pointer-events: none;
        }
        .form-readonly button:not(.btn-warning):not(.btn-secondary):not(.btn-outline-danger):not(.btn-primary) {
            display: none !important;
        }
        .form-readonly .btn-sm {
            /* Allow small buttons (usually table actions) to remain visible */
            display: inline-block !important;
        }
    </style>
</head>
<body class="bg-light">
    <?php include __DIR__ . '/../shared/topbar.php'; ?>

    <!-- Auto-save indicator -->
    <div id="autoSaveIndicator" class="auto-save-indicator d-none">
        <div class="alert alert-success alert-sm">
            <i class="fas fa-save"></i> Saved automatically
        </div>
    </div>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar with progress -->
            <div class="col-md-3">
                <div class="card sticky-top" style="top: 100px;">
                    <div class="card-header">
                        <h6 class="mb-0">Progress</h6>
                    </div>
                    <div class="card-body">
                        <div class="progress mb-3">
                            <?php 
                            // Calculate progress excluding Step 1 (informational only)
                            $total_steps = 14; // 15 total minus Step 1
                            $progress_step = $current_step > 1 ? $current_step - 1 : 1; // Adjust for Step 1 exclusion
                            $progress_percentage = ($progress_step / $total_steps) * 100;
                            ?>
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo $progress_percentage; ?>%" 
                                 aria-valuenow="<?php echo $progress_step; ?>" 
                                 aria-valuemin="0" aria-valuemax="<?php echo $total_steps; ?>">
                                <?php if ($current_step == 1): ?>
                                    Step 1: Information
                                <?php else: ?>
                                    Step <?php echo $progress_step; ?> of <?php echo $total_steps; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <ul class="list-unstyled step-indicator" style="padding-left: 25px;">
                            <?php foreach ($sections as $step => $section): ?>
                                <li class="mb-2 <?php 
                                    if ($step == 1) {
                                        echo 'informational';
                                    } elseif (in_array($step, $completed_steps)) {
                                        echo 'completed';
                                    } elseif (in_array($step, $steps_with_data)) {
                                        echo 'has-data';
                                    }
                                    if ($step == $current_step) {
                                        echo ' current';
                                    }
                                ?>">
                                    <a href="?doc_id=<?php echo $doc_id; ?>&step=<?php echo $step; ?>" 
                                       class="text-decoration-none <?php echo $step == $current_step ? 'fw-bold' : ''; ?>">
                                        <?php echo $section['title']; ?>
                                        <?php if (in_array($step, $completed_steps)): ?>
                                            <i class="fas fa-lock text-success ms-1" title="Completed and locked"></i>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">Step <?php echo $current_step; ?>: <?php echo $sections[$current_step]['title']; ?></h5>
                                <small class="text-muted"><?php echo htmlspecialchars($handover['short_description']); ?></small>
                            </div>
                            <div>
                                <?php if ($current_step > 1): ?>
                                    <a href="wizard.php?doc_id=<?php echo $doc_id; ?>&step=<?php echo $current_step - 1; ?>" class="btn btn-outline-secondary btn-sm me-2">
                                        <i class="fas fa-arrow-left"></i> Previous
                                    </a>
                                <?php endif; ?>
                                <a href="../app_view.php?id=<?php echo $handover['application_id']; ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-home"></i> Exit Wizard
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Step Completion Controls (skip for Step 1 - informational only) -->
                        <?php if ($current_step > 1): ?>
                        <div class="step-controls">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if ($is_step_completed): ?>
                                        <span class="badge bg-success fs-6">
                                            <i class="fas fa-check-circle"></i> Step Completed & Locked
                                        </span>
                                        <small class="text-muted d-block mt-1">This step is locked. Click "Reopen Step" to make changes.</small>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark fs-6">
                                            <i class="fas fa-edit"></i> Step In Progress
                                        </span>
                                        <small class="text-muted d-block mt-1">Complete this step when finished to lock the data.</small>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if ($is_step_completed): ?>
                                        <button type="button" id="reopenStepBtn" class="btn btn-warning btn-sm">
                                            <i class="fas fa-unlock"></i> Reopen Step
                                        </button>
                                    <?php else: ?>
                                        <button type="button" id="completeStepBtn" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Complete Step
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Step 1: Informational only -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> This is an informational section - no input required. You can proceed to the next step.
                        </div>
                        <?php endif; ?>

                        <form id="handoverForm" data-doc-id="<?php echo $doc_id; ?>" data-section="<?php echo $sections[$current_step]['name']; ?>" <?php echo $is_step_completed ? 'class="form-readonly"' : ''; ?>>

                            <?php
                            // Include the appropriate section template
                            $section_file = __DIR__ . '/sections/step_' . $current_step . '.php';
                            if (file_exists($section_file)) {
                                include $section_file;
                            } else {
                                echo '<div class="alert alert-warning">Section under development...</div>';
                            }
                            ?>

                        </form>
                    </div>

                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <div>
                                <?php if ($current_step > 1): ?>
                                    <a href="?doc_id=<?php echo $doc_id; ?>&step=<?php echo $current_step - 1; ?>" 
                                       class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left"></i> Previous
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div>
                                <button type="button" id="saveBtn" class="btn btn-success me-2" <?php echo $is_step_completed ? 'style="display:none"' : ''; ?>>
                                    <i class="fas fa-save"></i> Save
                                </button>

                                <?php if ($current_step < 15): ?>
                                    <a href="?doc_id=<?php echo $doc_id; ?>&step=<?php echo $current_step + 1; ?>" 
                                       class="btn btn-primary">
                                        Next <i class="fas fa-arrow-right"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="preview.php?document_id=<?php echo $doc_id; ?>" 
                                       class="btn btn-success">
                                        <i class="fas fa-eye"></i> Preview & Export
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script> <!-- Go up two levels from handover to root -->
    <script>
        // Auto-save functionality
        let autoSaveTimer;
        const AUTOSAVE_DELAY = 3000; // 3 seconds after last change

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('handoverForm');
            const saveBtn = document.getElementById('saveBtn');
            const autoSaveIndicator = document.getElementById('autoSaveIndicator');
            const completeStepBtn = document.getElementById('completeStepBtn');
            const reopenStepBtn = document.getElementById('reopenStepBtn');

            // Auto-save on input changes (only if step is not completed)
            if (!form.classList.contains('form-readonly')) {
                form.addEventListener('input', function() {
                    clearTimeout(autoSaveTimer);
                    autoSaveTimer = setTimeout(saveData, AUTOSAVE_DELAY);
                });
            }

            // Manual save button
            if (saveBtn) {
                saveBtn.addEventListener('click', function() {
                    saveData(true);
                });
            }

            // Complete step button
            if (completeStepBtn) {
                completeStepBtn.addEventListener('click', function() {
                    if (confirm('Are you sure you want to complete this step? The data will be locked.')) {
                        toggleStepCompletion(true);
                    }
                });
            }

            // Reopen step button
            if (reopenStepBtn) {
                reopenStepBtn.addEventListener('click', function() {
                    if (confirm('Are you sure you want to reopen this step for editing?')) {
                        toggleStepCompletion(false);
                    }
                });
            }

            function toggleStepCompletion(complete) {
                const docId = form.dataset.docId;
                const currentStep = <?php echo $current_step; ?>;
                
                fetch('../api/handover/toggle_step_completion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        document_id: docId,
                        step_number: currentStep,
                        complete: complete
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload page to update UI
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating step completion status.');
                });
            }

            function saveData(showIndicator = false) {
                const formData = new FormData(form);
                const docId = form.dataset.docId;
                const section = form.dataset.section;
                // Ensure required fields are sent
                formData.append('document_id', docId);
                formData.append('section_name', section);
                fetch('../api/handover/save_data.php', { // Correct relative path from /public/handover/ to /public/api/handover/
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && showIndicator) {
                        autoSaveIndicator.classList.remove('d-none');
                        setTimeout(() => {
                            autoSaveIndicator.classList.add('d-none');
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Save error:', error);
                });
            }

            // Save before page unload
            window.addEventListener('beforeunload', function() {
                navigator.sendBeacon('../api/handover/save_data.php', new FormData(form)); // Correct relative path from /public/handover/ to /public/api/handover/
            });
        });
    </script>
</body>
</html>
