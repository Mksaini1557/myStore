<?php
declare(strict_types=1);
error_reporting(0); // suppress warnings from mixing into JSON
header('Content-Type: application/json');
require_once __DIR__.'/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$payload = json_decode(file_get_contents('php://input'), true);

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit;
}

if (!$payload) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid JSON']); exit;
}

$action = $payload['action'] ?? 'signup';
$email = trim($payload['email'] ?? '');

if ($email === '') {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Email required']); exit;
}

try {
    // --- LOGIN action ---
    if ($action === 'login') {
        $password = (string)($payload['password'] ?? '');
        if ($password === '') {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'Password required']); exit;
        }
        $sel = $pdo->prepare("SELECT id, name, email, password_hash FROM users WHERE email = :email LIMIT 1");
        $sel->execute([':email'=>$email]);
        $user = $sel->fetch(PDO::FETCH_ASSOC);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success'=>false,'message'=>'Invalid email or password']); exit;
        }
        echo json_encode(['success'=>true,'user_id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email']]);
        exit;
    }

    // --- SIGNUP action (default) ---
    $name = trim($payload['name'] ?? '');
    $password = (string)($payload['password'] ?? '');

    if ($password === '') {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'Password required']); exit;
    }

    // Check if user exists
    $sel = $pdo->prepare("SELECT id, name, email FROM users WHERE email = :email LIMIT 1");
    $sel->execute([':email'=>$email]);
    $existing = $sel->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        http_response_code(409);
        echo json_encode(['success'=>false,'message'=>'Email already registered']); exit;
    }

    if ($name === '') {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'Name required for signup']); exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $pdo->prepare("INSERT INTO users (name,email,password_hash) VALUES (:name,:email,:hash)");
    $ins->execute([':name'=>$name, ':email'=>$email, ':hash'=>$hash]);
    $userId = (int)$pdo->lastInsertId();
    echo json_encode(['success'=>true,'user_id'=>$userId]);
    exit; // ensure nothing else outputs

} catch (Throwable $e) {
    error_log($e->getMessage()); // log to file instead of outputting
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
    exit;
}
