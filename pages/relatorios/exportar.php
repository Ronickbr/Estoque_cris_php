<?php
// Clear any existing output buffers
while (ob_get_level()) {
    ob_end_clean();
}

require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

verificarLogin();

// Get filter parameters
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');
$product_id = isset($_GET['product_id']) && !empty($_GET['product_id']) ? $_GET['product_id'] : null;
$type = isset($_GET['type']) && in_array($_GET['type'], ['entrada', 'saida']) ? $_GET['type'] : null;

try {
    $sql = "SELECT m.id, m.product_id, m.type, m.quantity, m.created_at, m.notes, 
                   p.name as product_name, mf.name as manufacturer_name
            FROM inventory_movements m
            JOIN products p ON m.product_id = p.id
            LEFT JOIN manufacturers mf ON p.manufacturer_id = mf.id
            WHERE m.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
    
    $params = [$dataInicio, $dataFim];
    
    if ($product_id) {
        $sql .= " AND m.product_id = ?";
        $params[] = $product_id;
    }
    
    if ($type) {
        $sql .= " AND m.type = ?";
        $params[] = $type;
    }
    
    $sql .= " ORDER BY m.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=inventory_movements_' . date('Y-m-d') . '.xls');
    header('Pragma: no-cache');
    header('Expires: 0');
} catch(PDOException $e) {
    die('Error fetching data: ' . $e->getMessage());
}
?>
<table border="1">
    <tr>
        <th>Data</th>
        <th>Produto</th>
        <th>Fabricante</th>
        <th>Tipo</th>
        <th>Quantidade</th>
        <th>Observações</th>
    </tr>
    <?php foreach ($movements as $mov): ?>
    <tr>
        <td><?php echo date('d/m/Y H:i', strtotime($mov['created_at'])); ?></td>
        <td><?php echo htmlspecialchars($mov['product_name']); ?></td>
        <td><?php echo htmlspecialchars($mov['manufacturer_name']); ?></td>
        <td><?php echo ucfirst($mov['type']); ?></td>
        <td><?php echo $mov['quantity']; ?></td>
        <td><?php echo htmlspecialchars($mov['notes'] ?? '-'); ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php exit; ?>