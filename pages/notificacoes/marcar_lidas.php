<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Services/NotificationService.php';

use Estoque\Services\NotificationService;

verificarLogin();
exigirCsrf('/index.php');

header('Content-Type: application/json');

try {
    $notifService = new NotificationService($conn);
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    if ($id && $id > 0) {
        $notifService->markAsRead($id);
    } else {
        $notifService->markAllAsRead();
    }
    
    $unread = $notifService->countUnread();
    echo json_encode(['status' => 'success', 'unread' => (int)$unread]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
