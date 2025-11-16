<?php
// checkout.php

declare(strict_types=1);
error_reporting(0);
header('Content-Type: application/json');
require_once __DIR__.'/config.php';

$method = $_SERVER['REQUEST_METHOD'];

// DELETE handling (cancel order)
if ($method === 'DELETE') {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!$payload || empty($payload['security_code']) || empty($payload['user_id'])) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'security_code & user_id required']);
        exit;
    }
    $code = $payload['security_code'];
    $userId = (int)$payload['user_id'];
    
    try {
        $grp = $pdo->prepare("SELECT id, created_at, status FROM order_groups WHERE security_code=:c AND user_id=:u LIMIT 1");
        $grp->execute([':c'=>$code, ':u'=>$userId]);
        $g = $grp->fetch(PDO::FETCH_ASSOC);
        
        if (!$g) {
            http_response_code(404);
            echo json_encode(['success'=>false,'message'=>'Order not found']);
            exit;
        }
        
        if ($g['status'] !== 'active') {
            http_response_code(409);
            echo json_encode(['success'=>false,'message'=>'Already canceled']);
            exit;
        }
        
        $ageMs = (time() - strtotime($g['created_at'])) * 1000;
        if ($ageMs > 5*60*1000) {
            http_response_code(403);
            echo json_encode(['success'=>false,'message'=>'Cancel window expired']);
            exit;
        }
        
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM order_items WHERE order_group_id=:id")->execute([':id'=>$g['id']]);
        $pdo->prepare("UPDATE order_groups SET status='canceled' WHERE id=:id")->execute([':id'=>$g['id']]);
        $pdo->commit();
        
        echo json_encode(['success'=>true,'message'=>'Order canceled']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('checkout.php DELETE error: '.$e->getMessage());
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'Server error']);
    }
    exit;
}

// POST handling (place order)
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);

if (!isset($payload['items'], $payload['totalAmount'], $payload['user_id'])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid payload']);
    exit;
}

$userId = (int)$payload['user_id'];
$items = $payload['items'];
$total = (float)$payload['totalAmount'];
$code = isset($payload['securityCode']) ? $payload['securityCode'] : 'mk'.strtoupper(bin2hex(random_bytes(6)));

try {
    $pdo->beginTransaction();
    
    // Insert order group
    $grpStmt = $pdo->prepare(
        "INSERT INTO order_groups (user_id, security_code, total_amount) 
         VALUES (:u, :s, :t)"
    );
    $grpStmt->execute([':u'=>$userId, ':s'=>$code, ':t'=>$total]);
    $groupId = (int)$pdo->lastInsertId();

    // Insert order items
    $itemStmt = $pdo->prepare(
        "INSERT INTO order_items (order_group_id, item_id, item_name, price, option_text)
         VALUES (:gid, :item_id, :item_name, :price, :opt)"
    );
    
    foreach ($items as $it) {
        $itemStmt->execute([
            ':gid' => $groupId,
            ':item_id' => $it['id'],
            ':item_name' => $it['name'],
            ':price' => $it['price'],
            ':opt' => $it['options'] ?? null
        ]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'security_code' => $code,
        'order_group_id' => $groupId,
        'orderId' => $groupId
    ]);
    
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('checkout.php POST error: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
exit;