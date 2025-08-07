<?php
//=============================================================================
// REFACTORED DATAMAP - MAIN INDEX
// Versjon 1.0 - Modularisert struktur
//=============================================================================

require_once '../../src/config/config.php';
require_once '../../src/db/db.php';
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get application ID from URL parameter
$application_id = isset($_GET['app_id']) ? intval($_GET['app_id']) : null;

if (!$application_id) {
    // Redirect with more helpful error
    header('Location: ../dashboard.php?error=DataMap editor requires an application ID. Please select an application first.');
    exit;
}

// Get application details for context
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("SELECT short_description as name, business_need as description, status FROM applications WHERE id = ?");
    $stmt->execute([$application_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        header('Location: ../dashboard.php?error=Application not found.');
        exit;
    }
} catch (Exception $e) {
    error_log("DataMap: Database error: " . $e->getMessage());
    $application = ['name' => 'Unknown Application', 'description' => '', 'status' => 'unknown'];
}

// Include header og dependencies
require_once 'includes/header.php';
?>

<!-- REFACTORED VERSION MARKER -->
<!-- Dette er den refaktorerte versjonen av DataMap -->

<body data-version="refactored">
    <?php include 'includes/topbar.php'; ?>
    
    <div class="content-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/app_header.php'; ?>
            <?php include 'includes/editor_section.php'; ?>
        </div>
    </div>
    
    <?php include 'includes/footer_scripts.php'; ?>
</body>
</html>
