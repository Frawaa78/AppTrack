<?php
// public/handover/index.php
require_once __DIR__ . '/../../src/db/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$app_id = isset($_GET['app_id']) ? (int)$_GET['app_id'] : 0;
if ($app_id <= 0) {
    // No app_id provided - redirect to dashboard with error
    header('Location: ../dashboard.php?error=missing_app_id');
    exit;
}

$db = Database::getInstance()->getConnection();

// Get application info - verify it exists
$stmt = $db->prepare('SELECT * FROM applications WHERE id = :id');
$stmt->execute([':id' => $app_id]);
$app = $stmt->fetch();
if (!$app) {
    // Application doesn't exist - redirect to dashboard with error
    header('Location: ../dashboard.php?error=app_not_found');
    exit;
}

// Get or create handover document
$stmt = $db->prepare('SELECT * FROM handover_documents WHERE application_id = :app_id');
$stmt->execute([':app_id' => $app_id]);
$handover_doc = $stmt->fetch();

if (!$handover_doc) {
    // Create new handover document
    $stmt = $db->prepare('INSERT INTO handover_documents (application_id, created_by) VALUES (:app_id, :user_id)');
    $stmt->execute([':app_id' => $app_id, ':user_id' => $_SESSION['user_id']]);
    $handover_doc_id = $db->lastInsertId();
    
    // Fetch the newly created document
    $stmt = $db->prepare('SELECT * FROM handover_documents WHERE id = :id');
    $stmt->execute([':id' => $handover_doc_id]);
    $handover_doc = $stmt->fetch();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handover Documentation - <?php echo htmlspecialchars($app['short_description']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
    <style>
        /* Ensure profile image has correct size and shape */
        .profile-img { 
            width: 36px !important; 
            height: 36px !important; 
            object-fit: cover !important; 
            border-radius: 50% !important; 
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../shared/topbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <!-- Header -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h3 mb-1">Handover Documentation</h1>
                                <p class="text-muted mb-0">
                                    <strong><?php echo htmlspecialchars($app['short_description']); ?></strong>
                                    <?php if ($app['application_service']): ?>
                                        - <?php echo htmlspecialchars($app['application_service']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div>
                                <a href="../app_view.php?id=<?php echo $app['id']; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to App
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <h6 class="text-muted">Status</h6>
                                <span class="badge bg-<?php 
                                    echo $handover_doc['status'] === 'completed' ? 'success' : 
                                         ($handover_doc['status'] === 'review' ? 'warning' : 
                                         ($handover_doc['status'] === 'in_progress' ? 'info' : 'secondary')); 
                                ?>">
                                    <?php echo ucfirst($handover_doc['status']); ?>
                                </span>
                            </div>
                            <div class="col-md-3">
                                <h6 class="text-muted">Progress</h6>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $handover_doc['completion_percentage']; ?>%" 
                                         aria-valuenow="<?php echo $handover_doc['completion_percentage']; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        <?php echo number_format($handover_doc['completion_percentage'], 1); ?>%
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <h6 class="text-muted">Current step</h6>
                                <span class="fw-bold"><?php echo $handover_doc['current_step']; ?> of 15</span>
                            </div>
                            <div class="col-md-3">
                                <h6 class="text-muted">Last updated</h6>
                                <small><?php echo date('d.m.Y H:i', strtotime($handover_doc['updated_at'])); ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Cards -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-edit fa-3x text-primary mb-3"></i>
                                <h5>Continue with wizard</h5>
                                <p class="text-muted">Fill out the handover documentation step by step</p>
                                <a href="wizard.php?doc_id=<?php echo $handover_doc['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-arrow-right"></i> Start/Continue
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-eye fa-3x text-info mb-3"></i>
                                <h5>Preview</h5>
                                <p class="text-muted">See how the finished document will look</p>
                                <a href="preview.php?document_id=<?php echo $handover_doc['id']; ?>" class="btn btn-info">
                                    <i class="fas fa-search"></i> Preview
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-download fa-3x text-success mb-3"></i>
                                <h5>Export document</h5>
                                <p class="text-muted">Download as PDF or Word document</p>
                                <div class="btn-group-vertical d-grid">
                                    <a href="export.php?doc_id=<?php echo $handover_doc['id']; ?>&format=pdf" class="btn btn-success">
                                        <i class="fas fa-file-pdf"></i> Download PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
