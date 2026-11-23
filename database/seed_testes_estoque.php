<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/StockService.php';

use Estoque\Services\StockService;

echo "=== INICIANDO GERACAO DE SEEDS DE TESTE ===\n\n";

$stockService = new StockService($conn);

// 1. Criar ou obter Fabricante de Teste "Max Titanium"
$stmtFab = $conn->prepare("SELECT id FROM manufacturers WHERE name = ?");
$stmtFab->execute(['Max Titanium']);
$maxTitaniumId = $stmtFab->fetchColumn();

if (!$maxTitaniumId) {
    $bytes = random_bytes(16);
    $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
    $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
    $maxTitaniumId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    
    $stmt = $conn->prepare("INSERT INTO manufacturers (id, name, logo_url, created_at) VALUES (?, 'Max Titanium', NULL, NOW())");
    $stmt->execute([$maxTitaniumId]);
    echo "1. Fabricante 'Max Titanium' criado com ID: $maxTitaniumId\n";
} else {
    echo "1. Fabricante 'Max Titanium' já existe com ID: $maxTitaniumId\n";
}

// Obter ID do fabricante Biobalance
$stmtBio = $conn->prepare("SELECT id FROM manufacturers WHERE name LIKE ? LIMIT 1");
$stmtBio->execute(['%Biobalance%']);
$biobalanceId = $stmtBio->fetchColumn() ?: $maxTitaniumId;

// Obter ID do fabricante Bioklen
$stmtBioklen = $conn->prepare("SELECT id FROM manufacturers WHERE name LIKE ? LIMIT 1");
$stmtBioklen->execute(['%Bioklen%']);
$bioklenId = $stmtBioklen->fetchColumn() ?: $maxTitaniumId;

$adminId = '1';

// Função auxiliar para criar produto e movimentações com validação matemática
function seedProductWithMovements($conn, $stockService, $fabId, $name, $minStock, array $movSteps, $adminId) {
    // Verificar se produto já existe para não duplicar seeds
    $stmtCheck = $conn->prepare("SELECT id FROM products WHERE name = ?");
    $stmtCheck->execute([$name]);
    $prodId = $stmtCheck->fetchColumn();

    if ($prodId) {
        // Limpar movimentações anteriores do produto de teste
        $stmtDelMov = $conn->prepare("DELETE FROM inventory_movements WHERE product_id = ?");
        $stmtDelMov->execute([$prodId]);
        $stmtDel = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmtDel->execute([$prodId]);
    }

    $bytes = random_bytes(16);
    $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
    $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
    $prodId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));

    // Inserir produto com estoque 0 inicialmente
    $stmt = $conn->prepare("INSERT INTO products (id, name, manufacturer_id, stock, min_stock, created_at, updated_at) VALUES (?, ?, ?, 0, ?, NOW(), NOW())");
    $stmt->execute([$prodId, $name, $fabId, $minStock]);

    // Aplicar movimentações passo a passo usando o StockService
    $batch = [];
    $expectedFinalStock = 0;
    foreach ($movSteps as $step) {
        $type = $step['type'];
        $qty = $step['qty'];
        $notes = $step['notes'] ?? 'Seed de teste';
        $date = $step['date'] ?? date('Y-m-d H:i:s');

        $batch[] = [
            'product_id' => $prodId,
            'type' => $type,
            'quantity' => $qty,
            'notes' => $notes,
            'created_at' => $date
        ];

        if ($type === 'entrada') {
            $expectedFinalStock += $qty;
        } else {
            $expectedFinalStock -= $qty;
        }
    }

    $stockService->processBatchMovements($batch, $adminId);

    // Verificar se o estoque no banco bate exatamente com o esperado
    $stmtVerify = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    $stmtVerify->execute([$prodId]);
    $actualStock = (int)$stmtVerify->fetchColumn();

    $status = ($actualStock === $expectedFinalStock) ? "OK" : "ERRO";
    echo "   [Produto: $name] -> Estoque Esperado: $expectedFinalStock | Estoque Real no DB: $actualStock [$status]\n";
}

echo "\n2. GERANDO PRODUTOS E CENARIOS DE ESTOQUE:\n";

// Cenário 1: Esgotado / Zerado
seedProductWithMovements($conn, $stockService, $maxTitaniumId, 'Creatina 100% Pura 300g (Seed)', 5, [
    ['type' => 'entrada', 'qty' => 10, 'notes' => 'Estoque inicial', 'date' => date('Y-m-d H:i:s', strtotime('-5 days'))],
    ['type' => 'saida', 'qty' => 6, 'notes' => 'Venda pedido #101', 'date' => date('Y-m-d H:i:s', strtotime('-3 days'))],
    ['type' => 'saida', 'qty' => 4, 'notes' => 'Venda pedido #102', 'date' => date('Y-m-d H:i:s', strtotime('-1 days'))],
], $adminId);

// Cenário 2: Crítico / Estoque Baixo
seedProductWithMovements($conn, $stockService, $maxTitaniumId, '100% Whey Protein Isolado 900g (Seed)', 5, [
    ['type' => 'entrada', 'qty' => 20, 'notes' => 'Estoque inicial', 'date' => date('Y-m-d H:i:s', strtotime('-4 days'))],
    ['type' => 'saida', 'qty' => 17, 'notes' => 'Venda pedido #103', 'date' => date('Y-m-d H:i:s', strtotime('-2 days'))],
], $adminId);

// Cenário 3: Saudável / Alto
seedProductWithMovements($conn, $stockService, $maxTitaniumId, 'BCAA 2400 100 Caps (Seed)', 10, [
    ['type' => 'entrada', 'qty' => 50, 'notes' => 'Entrada lote #201', 'date' => date('Y-m-d H:i:s', strtotime('-6 days'))],
    ['type' => 'saida', 'qty' => 10, 'notes' => 'Venda pedido #104', 'date' => date('Y-m-d H:i:s', strtotime('-4 days'))],
    ['type' => 'entrada', 'qty' => 15, 'notes' => 'Reposição lote #202', 'date' => date('Y-m-d H:i:s', strtotime('-3 days'))],
    ['type' => 'saida', 'qty' => 5, 'notes' => 'Venda pedido #105', 'date' => date('Y-m-d H:i:s', strtotime('-1 days'))],
], $adminId);

// Cenário 4: Biobalance com múltiplas movimentações alternadas
seedProductWithMovements($conn, $stockService, $biobalanceId, 'Multivitamínico Daily Formula (Seed)', 10, [
    ['type' => 'entrada', 'qty' => 30, 'notes' => 'Estoque inicial', 'date' => date('Y-m-d H:i:s', strtotime('-10 days'))],
    ['type' => 'saida', 'qty' => 12, 'notes' => 'Venda diária #01', 'date' => date('Y-m-d H:i:s', strtotime('-8 days'))],
    ['type' => 'entrada', 'qty' => 10, 'notes' => 'Reforço estoque', 'date' => date('Y-m-d H:i:s', strtotime('-5 days'))],
    ['type' => 'saida', 'qty' => 8, 'notes' => 'Venda diária #02', 'date' => date('Y-m-d H:i:s', strtotime('-3 days'))],
    ['type' => 'saida', 'qty' => 5, 'notes' => 'Venda diária #03', 'date' => date('Y-m-d H:i:s', strtotime('-1 days'))],
], $adminId);

// Cenário 5: Bioklen com produto zerado
seedProductWithMovements($conn, $stockService, $bioklenId, 'Coenzima Q10 100mg Bioklen (Seed)', 5, [
    ['type' => 'entrada', 'qty' => 4, 'notes' => 'Lote promocional', 'date' => date('Y-m-d H:i:s', strtotime('-7 days'))],
    ['type' => 'saida', 'qty' => 4, 'notes' => 'Venda total do lote', 'date' => date('Y-m-d H:i:s', strtotime('-2 days'))],
], $adminId);

echo "\n3. VALIDACAO FINAL DE DISCREPANCIAS APOS SEEDS:\n";
$discrepancies = $stockService->getDiscrepancies();
echo "   Discrepâncias encontradas em toda a base: " . count($discrepancies) . "\n";
if (count($discrepancies) === 0) {
    echo "   SUCESSO: Todas as seeds foram aplicadas e o estoque está 100% íntegro!\n";
} else {
    echo "   ALERTA: Houve divergência em " . count($discrepancies) . " produto(s)!\n";
}

echo "\n=== SEEDS CONCLUIDAS ===\n";
