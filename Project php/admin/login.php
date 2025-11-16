<?php
session_start();
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}
// FIX: adjust config path to project-level php folder, fallback to root-level
$projectConfig = dirname(__DIR__) . '/php/config.php';
$rootConfig    = dirname(__DIR__, 2) . '/php/config.php';
$configPath    = file_exists($projectConfig) ? $projectConfig : $rootConfig;

if (!file_exists($configPath)) {
    die('Config file not found: ' . $configPath);
}
require_once $configPath;

$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email === '' || $password === '') {
        $err = 'Email and password required';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id,name,password_hash,role FROM admins WHERE email=:email AND is_active=1 LIMIT 1");
            $stmt->execute([':email'=>$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($admin && password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                header('Location: index.php'); // FIX: was dashboard.php causing 404
                exit;
            } else {
                $err = 'Invalid credentials';
            }
        } catch (Throwable $e) {
            $err = 'Server error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5" style="max-width:420px;">
        <h3 class="mb-4 text-center">Admin Login</h3>
        <?php if ($err): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input name="email" type="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input name="password" type="password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Login</button>
            <div class="d-flex justify-content-between mt-3">
                <a href="../index.php" class="small">Back Home</a>
                <a href="register_admin.php" class="small text-danger fw-semibold">Register Admin</a>
            </div>
        </form>
    </div>
</body>
</html>
