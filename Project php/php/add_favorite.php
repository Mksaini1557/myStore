<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__.'/config.php';

$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$userId = (int)($payload['user_id'] ?? 0);
$itemId = (int)($payload['item_id'] ?? 0);

if ($userId <= 0 || $itemId <= 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'user_id and item_id required']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO favorites (user_id, item_id) VALUES (:uid, :iid)");
    $stmt->execute([':uid'=>$userId, ':iid'=>$itemId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success'=>true,'message'=>'Added to favorites']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Already in favorites']);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
