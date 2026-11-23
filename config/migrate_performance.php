<?php
require_once __DIR__ . '/database.php';

echo "Iniciando otimização do banco de dados...\n";

// Função auxiliar para verificar e criar índice com segurança
function criarIndiceSeNaoExiste($conn, $tabela, $nomeIndice, $colunas) {
    echo "Verificando índice '$nomeIndice' na tabela '$tabela'...\n";
    
    // Verificar se índice existe
    $stmt = $conn->prepare("SHOW INDEX FROM $tabela WHERE Key_name = ?");
    $stmt->execute([$nomeIndice]);
    
    if ($stmt->rowCount() > 0) {
        echo "[INFO] Índice '$nomeIndice' já existe. Pulando.\n";
        return;
    }

    // Criar índice
    try {
        $sql = "CREATE INDEX $nomeIndice ON $tabela ($colunas)";
        $conn->exec($sql);
        echo "[OK] Índice '$nomeIndice' criado com sucesso.\n";
    } catch (PDOException $e) {
        echo "[ERRO] Falha ao criar '$nomeIndice': " . $e->getMessage() . "\n";
    }
}

try {
    // 1. Index para queries de vendas (Dashboard)
    criarIndiceSeNaoExiste($conn, 'inventory_movements', 'idx_movements_type_date', 'type, created_at');

    // 2. Index para Joins de produtos
    criarIndiceSeNaoExiste($conn, 'inventory_movements', 'idx_movements_product', 'product_id');

    // 3. Index para Joins de fabricantes
    criarIndiceSeNaoExiste($conn, 'products', 'idx_products_manufacturer', 'manufacturer_id');

    // 4. Index para busca de estoque baixo
    criarIndiceSeNaoExiste($conn, 'products', 'idx_products_stock', 'stock, min_stock');

    echo "\nSUCESSO: Processo de otimização finalizado.\n";

} catch (Exception $e) {
    echo "Erro geral: " . $e->getMessage();
}

?>
