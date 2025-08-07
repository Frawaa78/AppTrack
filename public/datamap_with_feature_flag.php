<?php
require_once '../src/config/config.php';
require_once '../src/db/db.php';
session_start();

//=============================================================================
// REFACTORING CONTROL PANEL
//=============================================================================
$USE_REFACTORED_VERSION = false; // Sett til true når klar for testing

// VIKTIG: Ikke exit her - vi må prosessere data først!
// if ($USE_REFACTORED_VERSION) vil bli sjekket etter at data er lastet
//=============================================================================
// ORIGINAL KODE FORTSETTER HER (uendret)
//=============================================================================

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ensure user data is loaded in session
if (!isset($_SESSION['user_display_name']) || !isset($_SESSION['user_email'])) {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        $stmt = $pdo->prepare("SELECT email, display_name, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_display_name'] = $user['display_name'];
            $_SESSION['user_role'] = $user['role'];
        }
    } catch (Exception $e) {
        // If we can't load user data, continue with what we have
    }
}

// Get application ID from URL parameter
$application_id = isset($_GET['app_id']) ? intval($_GET['app_id']) : null;

if (!$application_id) {
    // Redirect with more helpful error
    header('Location: /public/dashboard.php?error=DataMap editor requires an application ID. Please select an application first.');
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
        header('Location: /public/dashboard.php?error=Application not found. Please check the application ID.');
        exit;
    }
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}

//=============================================================================
// REFACTORED VERSION REDIRECT - ETTER DATA ER LASTET
//=============================================================================
if ($USE_REFACTORED_VERSION) {
    // Sjekk om refaktorerte filer eksisterer
    if (file_exists('datamap_refactored/index.php')) {
        include 'datamap_refactored/index.php';
        exit;
    } else {
        // Fall back til original hvis refaktorerte filer mangler
        echo "<!-- FALLBACK: Refaktorerte filer ikke funnet, bruker original versjon -->";
    }
}
//=============================================================================
?>
