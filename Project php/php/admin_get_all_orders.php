<?php
header('Content-Type: application/json');
require_once __DIR__.'/config.php';

try {
    $sql = "SELECT og.id AS order_group_id, og.security_code, og.total_amount, og.status, og.created_at,
                   u.name AS user_name
            FROM order_groups og
            JOIN users u ON og.user_id = u.id
            ORDER BY og.created_at DESC";
    $stmt = $pdo->query($sql);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($orders as &$o) {
        $o['total_amount'] = (float)$o['total_amount'];
    }
    
    echo json_encode(['success'=>true,'orders'=>$orders]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
