<?php
declare(strict_types=1);
error_reporting(0);
header('Content-Type: application/json');
require_once __DIR__.'/config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$userId = isset($payload['user_id']) ? (int)$payload['user_id'] : 0;

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'user_id required']);
    exit;
}

try {
    // Fetches ONLY orders belonging to logged-in user
    $grpStmt = $pdo->prepare("SELECT id, security_code, total_amount, status, created_at FROM order_groups WHERE user_id=:u ORDER BY id DESC");
    $grpStmt->execute([':u'=>$userId]);
    $groups = $grpStmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    // Fetches items for each order group
    $itemStmt = $pdo->prepare("SELECT id, item_name, price, option_text FROM order_items WHERE order_group_id=:gid");
    
    foreach ($groups as $g) {
        $itemStmt->execute([':gid'=>$g['id']]);
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Cast item IDs to int
        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['price'] = (float)$item['price'];
        }
        
        $result[] = [
            'order_group_id'=>(int)$g['id'],
            'security_code'=>$g['security_code'],
            'total_amount'=>(float)$g['total_amount'],
            'status'=>$g['status'],
            'created_at'=>$g['created_at'],
            'items'=>$items
        ];
    }
    
    echo json_encode(['success'=>true,'orders'=>$result]);
} catch (Throwable $e) {
    error_log('user_orders.php error: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
exit;
