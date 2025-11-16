<?php
header('Content-Type: application/json');
require_once __DIR__.'/config.php';

$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$code = $payload['security_code'] ?? '';
$status = $payload['status'] ?? '';

if (!$code || !$status || !in_array($status, ['cooking','cooked','canceled'])) {
    echo json_encode(['success'=>false,'message'=>'Invalid parameters']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE order_groups SET status=:status WHERE security_code=:code");
    $stmt->execute([':status'=>$status, ':code'=>$code]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Order not found']);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
