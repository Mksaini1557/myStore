<?php
session_start();
$projectConfig = dirname(__DIR__) . '/php/config.php';
$rootConfig    = dirname(__DIR__, 2) . '/php/config.php';
$configPath    = file_exists($projectConfig) ? $projectConfig : $rootConfig;
if (!file_exists($configPath)) { die('Config not found'); }
require_once $configPath;

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">Admin Panel</a>
    <div class="ms-auto">
      <span class="me-3 small text-muted"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? ''); ?></span>
      <a href="logout.php" class="btn btn-sm btn-outline-secondary">Logout</a>
    </div>
  </div>
</nav>

<div class="container">
  <h4 class="mb-3">Live Orders</h4>
  <div id="orders-list" class="mb-4"></div>

  <h4 class="mb-3">Scan Order QR</h4>
  <div class="row">
    <div class="col-md-5">
        <div id="qr-reader" style="width:100%;"></div>
        <div id="qr-result" class="mt-2"></div>
    </div>
    <div class="col-md-7">
        <div id="order-details" class="bg-white p-3 border rounded" style="min-height:200px;"></div>
    </div>
  </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="admin.js"></script>
</body>
</html>
