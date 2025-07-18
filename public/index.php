<?php
// public/index.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velkommen til AppTrack</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        .logo {
            max-width: 200px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <!-- Logo (bytt src til din logo om ønskelig) -->
                <img src="../assets/logo.png" alt="AppTrack Logo" class="logo mb-4" onerror="this.style.display='none'">
                <h2 class="mb-4">Welcome to AppTrack</h2>
                <h5 class="mb-4">Application tracking and Management Platform</h5><br>
                <div class="d-grid gap-3">
                    <a href="login.php" class="btn btn-primary btn-lg">Login</a>
                    <a href="register.php" class="btn btn-outline-secondary btn-lg">Register new user</a>
                </div><br>
                <h6 class="mb-4">For more info, contact info@apptrack.no.</h6><br>
            </div>
        </div>
    </div>
</body>
</html>
