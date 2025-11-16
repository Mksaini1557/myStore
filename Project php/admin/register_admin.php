<?php
session_start();

// FIX: robust config path resolution (project php first, then root)
$projectConfig = dirname(__DIR__) . '/php/config.php';
$rootConfig    = dirname(__DIR__, 2) . '/php/config.php';
$configPath    = file_exists($projectConfig) ? $projectConfig : $rootConfig;

if (!file_exists($configPath)) {
    exit('Config file not found: ' . $configPath);
}
require_once $configPath;

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($name === '') $errors[] = 'Name required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required';
    if ($password === '') $errors[] = 'Password required';
    if ($password !== $confirm) $errors[] = 'Passwords do not match';

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already registered';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins = $pdo->prepare('INSERT INTO admins (name,email,password_hash,role,is_active) VALUES (:name,:email,:hash,"admin",1)');
                $ins->execute([':name'=>$name, ':email'=>$email, ':hash'=>$hash]);
                $success = true;
            }
        } catch (Throwable $e) {
            $errors[] = 'Database error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register Admin</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:520px;">
    <h3 class="mb-4 text-center">Admin Registration</h3>

    <?php if ($success): ?>
        <div class="alert alert-success">Admin created. <a href="login.php" class="alert-link">Login</a></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" novalidate>
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input name="name" type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email (Admin)</label>
            <input name="email" type="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input name="password" type="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input name="confirm_password" type="password" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Create Admin</button>
        <div class="text-center mt-3">
            <a href="../index.php" class="small">Back to Home</a>
            <span class="small mx-2">|</span>
            <a href="login.php" class="small">Login</a>
        </div>
    </form>
</div>
</body>
</html>
