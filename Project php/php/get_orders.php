<?php
declare(strict_types=1);
error_reporting(0);
header('Content-Type: application/json');
require_once __DIR__.'/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true) ?: [];
    $userId = (int)($payload['user_id'] ?? 0);
    $flat = !empty($payload['flat']);
} else {
    $userId = (int)($_GET['user_id'] ?? 0);
    $flat = isset($_GET['flat']);
}

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'user_id required']);
    exit;
}

try {
    $u = $pdo->prepare("SELECT id FROM users WHERE id=:id LIMIT 1");
    $u->execute([':id'=>$userId]);
    if (!$u->fetch()) {
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'User not found']);
        exit;
    }

    if ($flat) {
        $sql = "SELECT u.name AS user_name, og.id AS order_group_id, og.security_code,
                og.status, og.total_amount, oi.item_name, oi.price, oi.option_text,
                oi.created_at AS item_ordered_at
                FROM users u
                JOIN order_groups og ON u.id = og.user_id
                JOIN order_items oi ON og.id = oi.order_group_id
                WHERE u.id = :uid ORDER BY og.id DESC, oi.id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid'=>$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['order_group_id'] = (int)$r['order_group_id'];
            $r['price'] = (float)$r['price'];
            $r['total_amount'] = (float)$r['total_amount'];
        }
        echo json_encode(['success'=>true,'orders'=>$rows]);
        exit;
    }

    $g = $pdo->prepare("SELECT id, security_code, total_amount, status, created_at
                        FROM order_groups WHERE user_id=:uid ORDER BY id DESC");
    $g->execute([':uid'=>$userId]);
    $groups = $g->fetchAll(PDO::FETCH_ASSOC);

    $itemsStmt = $pdo->prepare("SELECT id, item_id, item_name, price, option_text
                                FROM order_items WHERE order_group_id=:gid");
    $orders = [];
    foreach ($groups as $grp) {
        $itemsStmt->execute([':gid'=>$grp['id']]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as &$it) {
            $it['id'] = (int)$it['id'];
            $it['price'] = (float)$it['price'];
        }
        $orders[] = [
            'order_group_id'=>(int)$grp['id'],
            'security_code'=>$grp['security_code'],
            'total_amount'=>(float)$grp['total_amount'],
            'status'=>$grp['status'],
            'created_at'=>$grp['created_at'],
            'items'=>$items
        ];
    }
    echo json_encode(['success'=>true,'orders'=>$orders]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}