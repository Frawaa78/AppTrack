<?php
// Minimal version of user_stories.php for testing
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Stories Test</title>
</head>
<body>
    <h1>User Stories Test Page</h1>
    <p>This is a minimal version to test if the problem is with user_stories.php</p>
    <p>Session User ID: <?php echo $_SESSION['user_id'] ?? 'Not found'; ?></p>
</body>
</html>
