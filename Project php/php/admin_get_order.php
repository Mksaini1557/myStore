<?php
header('Content-Type: application/json');
require_once __DIR__.'/config.php';

$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$code = $payload['security_code'] ?? '';

if (!$code) {
    echo json_encode(['success'=>false,'message'=>'Security code required']);
    exit;
}

try {
    $sql = "SELECT og.id AS order_group_id, og.security_code, og.total_amount, og.status, og.created_at,
                   u.name AS user_name
            FROM order_groups og
            JOIN users u ON og.user_id = u.id
            WHERE og.security_code = :code LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':code'=>$code]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success'=>false,'message'=>'Order not found']);
        exit;
    }
    
    $itemStmt = $pdo->prepare("SELECT item_name, price, option_text FROM order_items WHERE order_group_id=:gid");
    $itemStmt->execute([':gid'=>$order['order_group_id']]);
    $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    $order['total_amount'] = (float)$order['total_amount'];
    
    echo json_encode(['success'=>true,'order'=>$order]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
