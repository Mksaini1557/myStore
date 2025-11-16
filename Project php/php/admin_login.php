<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__.'/config.php';

$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$email = $payload['email'] ?? '';
$password = $payload['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['success'=>false,'message'=>'Email and password required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name, password_hash, role FROM admins WHERE email=:email AND is_active=1 LIMIT 1");
    $stmt->execute([':email'=>$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_role'] = $admin['role'];
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Invalid credentials']);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
