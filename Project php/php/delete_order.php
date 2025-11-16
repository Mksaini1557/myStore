<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__.'/config.php';

$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$code = $payload['security_code'] ?? null;
$userId = (int)($payload['user_id'] ?? 0);

if (!$code || $userId <= 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'security_code & user_id required']);
    exit;
}

try {
    $grp = $pdo->prepare("SELECT id, status FROM order_groups WHERE security_code=:c AND user_id=:u LIMIT 1");
    $grp->execute([':c'=>$code, ':u'=>$userId]);
    $g = $grp->fetch(PDO::FETCH_ASSOC);
    
    if (!$g) {
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Order not found']);
        exit;
    }
    
    if ($g['status'] !== 'canceled') {
        http_response_code(403);
        echo json_encode(['success'=>false,'message'=>'Only canceled orders can be deleted']);
        exit;
    }

    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM order_items WHERE order_group_id=:id")->execute([':id'=>$g['id']]);
    $pdo->prepare("DELETE FROM order_groups WHERE id=:id")->execute([':id'=>$g['id']]);
    $pdo->commit();
    
    echo json_encode(['success'=>true,'message'=>'Order deleted']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
