<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Test</title>
</head>
<body>
    <h1>Simple Test Page</h1>
    <p>This is a simple test to see if PHP pages work correctly.</p>
    <p>User ID: <?php echo $_SESSION['user_id'] ?? 'Not set'; ?></p>
    <p>Current time: <?php echo date('Y-m-d H:i:s'); ?></p>
</body>
</html>
