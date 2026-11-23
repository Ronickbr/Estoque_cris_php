<?php

namespace Estoque\Services;

use PDO;
use PDOException;

class NotificationService
{
    private $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Retorna todas as notificações, ordenadas das mais recentes.
     * @param int $limit Limite de notificações
     */
    public function getNotifications($limit = 10)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM notifications 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conta quantas notificações não lidas existem.
     */
    public function countUnread()
    {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
        return $stmt->fetchColumn();
    }

    /**
     * Marca todas as notificações como lidas.
     */
    public function markAllAsRead()
    {
        $this->conn->exec("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
    }

    /**
     * Marca uma notificação individual como lida.
     */
    public function markAsRead($id)
    {
        $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->execute([(int)$id]);
    }

    /**
     * Cria uma nova notificação. Evita duplicatas recentíssimas (mesmo dia/tipo/msg) para não spammar.
     */
    public function createNotification($message, $type = 'info', $link = null)
    {
        // Verifica se já existe notification igual hoje para evitar spam
        $check = $this->conn->prepare("
            SELECT COUNT(*) FROM notifications 
            WHERE message = ? AND type = ? AND DATE(created_at) = DATE(NOW())
        ");
        $check->execute([$message, $type]);
        
        if ($check->fetchColumn() == 0) {
            $stmt = $this->conn->prepare("
                INSERT INTO notifications (message, type, link, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$message, $type, $link]);
            return true;
        }
        return false;
    }

    /**
     * Gera notificações automáticas baseadas no estoque baixo.
     * Deve ser chamado periodicamente ou ao carregar o dashboard.
     */
    public function checkStockAlerts(DashboardService $dashboardService)
    {
        $produtosBaixos = $dashboardService->getEstoqueBaixo();
        
        $count = count($produtosBaixos);
        if ($count > 0) {
            // Notificação consolidada
            if ($count === 1) {
                $p = $produtosBaixos[0];
                $msg = "Alerta: Produto '{$p['name']}' atingiu estoque crítico ({$p['stock']} un).";
                $this->createNotification($msg, 'danger', '/pages/produtos/index.php?name=' . urlencode($p['name']));
            } else {
                $msg = "Alerta: Existem {$count} produtos com estoque baixo ou zerado.";
                $this->createNotification($msg, 'danger', '/pages/produtos/index.php?stock=low');
            }
        }
    }
}
