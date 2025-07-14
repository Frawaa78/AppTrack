<?php
// public/dashboard.php
require_once __DIR__ . '/../src/db/db.php';
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$applications = $db->query('SELECT * FROM applications ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications Dashboard | AppTrack</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        .profile-img {
            width: 36px;
            height: 36px;
            object-fit: cover;
            border-radius: 50%;
        }
        .navbar-brand {
            font-weight: bold;
            letter-spacing: 1px;
        }
        .search-bar {
            min-width: 350px;
            max-width: 600px;
            width: 100%;
        }
        @media (max-width: 768px) {
            .search-bar { min-width: 150px; }
        }
    </style>
</head>
<body class="bg-light">
<!-- Topbar -->
<?php include __DIR__ . '/shared/topbar.php'; ?>

<div class="container-fluid mt-4">
    <h2 class="mb-4">Applications</h2>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Short description</th>
                    <th>Pre-ops portfolio</th>
                    <th>Phase</th>
                    <th>Status</th>
                    <th>Due date</th>
                    <th>Project manager</th>
                    <th>Product owner</th>
                    <th>Application Portfolio</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($applications): foreach ($applications as $app): ?>
                <tr style="cursor:pointer;" onclick="window.location='app_view.php?id=<?php echo $app['id']; ?>'">
                    <td><?php echo htmlspecialchars($app['id']); ?></td>
                    <td><?php echo htmlspecialchars($app['short_description']); ?></td>
                    <td><?php echo htmlspecialchars($app['preops_portfolio'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($app['phase']); ?></td>
                    <td><?php echo htmlspecialchars($app['status']); ?></td>
                    <td><?php echo htmlspecialchars($app['due_date']); ?></td>
                    <td><?php echo htmlspecialchars($app['project_manager'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($app['product_owner'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($app['application_portfolio'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($app['updated_at'] ?? ''); ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="10" class="text-center">No applications found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
