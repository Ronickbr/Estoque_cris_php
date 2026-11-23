<?php
require_once __DIR__ . '/config/database.php';

echo "Verificando tabela de notificações...\n";

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('warning', 'success', 'info', 'danger') NOT NULL DEFAULT 'info',
        message TEXT NOT NULL,
        link VARCHAR(255) NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $conn->exec($sql);
    echo "Tabela 'notifications' criada ou já existente com sucesso!\n";
    
    // Inserir uma notificação de teste se estiver vazia
    $check = $conn->query("SELECT COUNT(*) FROM notifications");
    if ($check->fetchColumn() == 0) {
        $stmt = $conn->prepare("INSERT INTO notifications (type, message, created_at) VALUES (?, ?, NOW())");
        $stmt->execute(['info', 'Bem-vindo ao novo sistema de Notificações!']);
        echo "Notificação de teste inserida.\n";
    }

} catch (PDOException $e) {
    die("Erro ao criar tabela: " . $e->getMessage() . "\n");
}
