<?php
$titulo = 'Dashboard';
require_once __DIR__ . '/includes/funcoes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

// Verificar se usuário está logado
verificarLogin();

try {
    // Vendas hoje (últimas 24h)
    $stmtVendasHoje = $conn->query("
        SELECT COUNT(*) as total 
        FROM inventory_movements 
        WHERE type = 'saida' 
        AND created_at >= NOW() - INTERVAL 1 DAY
    ");
    $vendasHoje = $stmtVendasHoje->fetch(PDO::FETCH_ASSOC)['total'];

    // Vendas na semana (últimos 7 dias)
    $stmtVendasSemana = $conn->query("
        SELECT COUNT(*) as total 
        FROM inventory_movements 
        WHERE type = 'saida' 
        AND created_at >= NOW() - INTERVAL 7 DAY
    ");
    $vendasSemana = $stmtVendasSemana->fetch(PDO::FETCH_ASSOC)['total'];

    // Vendas no mês (últimos 30 dias)
    $stmtVendasMes = $conn->query("
        SELECT COUNT(*) as total 
        FROM inventory_movements 
        WHERE type = 'saida' 
        AND created_at >= NOW() - INTERVAL 30 DAY
    ");
    $vendasMes = $stmtVendasMes->fetch(PDO::FETCH_ASSOC)['total'];

    // Dia com mais vendas (baseado nos últimos 30 dias)
    $stmtDiaMaisVendas = $conn->query("
        SELECT DAYNAME(created_at) as dia, COUNT(*) as total 
        FROM inventory_movements 
        WHERE type = 'saida' 
        AND created_at >= NOW() - INTERVAL 30 DAY 
        GROUP BY DAYNAME(created_at) 
        ORDER BY total DESC 
        LIMIT 1
    ");
    $diaMaisVendas = $stmtDiaMaisVendas->fetch(PDO::FETCH_ASSOC);
    $diaMaisVendasNome = $diaMaisVendas ? ucfirst(strtolower($diaMaisVendas['dia'])) : 'Desconhecido';
    $diasTraduzidos = [
        'Monday' => 'Segunda-feira',
        'Tuesday' => 'Terça-feira',
        'Wednesday' => 'Quarta-feira',
        'Thursday' => 'Quinta-feira',
        'Friday' => 'Sexta-feira',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo'
    ];
    $diaMaisVendasNome = $diasTraduzidos[$diaMaisVendasNome] ?? $diaMaisVendasNome;

    // Produtos mais vendidos no mês
    $stmtProdutosMaisVendidosMes = $conn->query("
        SELECT p.name, SUM(m.quantity) as total 
        FROM inventory_movements m 
        JOIN products p ON m.product_id = p.id 
        WHERE m.type = 'saida' 
        AND m.created_at >= NOW() - INTERVAL 30 DAY 
        GROUP BY p.id, p.name 
        ORDER BY total DESC 
        LIMIT 5
    ");
    $produtosMaisVendidosMes = $stmtProdutosMaisVendidosMes->fetchAll(PDO::FETCH_ASSOC);
    $maxVendasMes = !empty($produtosMaisVendidosMes) ? max(array_column($produtosMaisVendidosMes, 'total')) : 1;

    // Produtos mais vendidos hoje
    $stmtProdutosHoje = $conn->query("
        SELECT p.name, SUM(m.quantity) as total 
        FROM inventory_movements m 
        JOIN products p ON m.product_id = p.id 
        WHERE m.type = 'saida' 
        AND m.created_at >= NOW() - INTERVAL 1 DAY 
        GROUP BY p.id, p.name 
        ORDER BY total DESC 
        LIMIT 5
    ");
    $produtosVendidosHoje = $stmtProdutosHoje->fetchAll(PDO::FETCH_ASSOC);
    $maxVendasHoje = !empty($produtosVendidosHoje) ? max(array_column($produtosVendidosHoje, 'total')) : 1;

    // Produtos mais vendidos na semana
    $stmtProdutosSemana = $conn->query("
        SELECT p.name, SUM(m.quantity) as total 
        FROM inventory_movements m 
        JOIN products p ON m.product_id = p.id 
        WHERE m.type = 'saida' 
        AND m.created_at >= NOW() - INTERVAL 7 DAY 
        GROUP BY p.id, p.name 
        ORDER BY total DESC 
        LIMIT 5
    ");
    $produtosVendidosSemana = $stmtProdutosSemana->fetchAll(PDO::FETCH_ASSOC);
    $maxVendasSemana = !empty($produtosVendidosSemana) ? max(array_column($produtosVendidosSemana, 'total')) : 1;

    // Produtos com estoque baixo (por fabricante)
    $stmtEstoqueBaixo = $conn->query("
        SELECT p.name, p.stock, p.min_stock, m.name as manufacturer 
        FROM products p 
        LEFT JOIN manufacturers m ON p.manufacturer_id = m.id 
        WHERE p.stock <= COALESCE(p.min_stock, 5)
        ORDER BY m.name, p.stock
    ");
    $estoqueBaixo = $stmtEstoqueBaixo->fetchAll(PDO::FETCH_ASSOC);

    // Separar produtos por fabricante (normalizando para evitar problemas de capitalização)
    $estoqueBaixoBiobalance = array_filter($estoqueBaixo, fn($item) => $item['manufacturer'] && stripos(strtolower($item['manufacturer']), 'biobalance') !== false);
    $estoqueBaixoNutrify = array_filter($estoqueBaixo, fn($item) => $item['manufacturer'] && stripos(strtolower($item['manufacturer']), 'nutrify') !== false);
    $estoqueBaixoGauer = array_filter($estoqueBaixo, fn($item) => $item['manufacturer'] && stripos(strtolower($item['manufacturer']), 'gauer') !== false);
    $estoqueBaixoBloken = array_filter($estoqueBaixo, fn($item) => $item['manufacturer'] && stripos(strtolower($item['manufacturer']), 'bloken') !== false);
    $estoqueBaixoStem = array_filter($estoqueBaixo, fn($item) => $item['manufacturer'] && stripos(strtolower($item['manufacturer']), 'stem') !== false);
    $estoqueBaixoEqualiv = array_filter($estoqueBaixo, fn($item) => $item['manufacturer'] && stripos(strtolower($item['manufacturer']), 'equaliv') !== false);
    $estoqueBaixoDivinite = array_filter($estoqueBaixo, fn($item) => $item['manufacturer'] && stripos(strtolower($item['manufacturer']), 'divinite') !== false);

} catch (PDOException $e) {
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao buscar dados: ' . $e->getMessage());
}
?>

<style>
.manufacturer-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border-radius: 12px;
    overflow: hidden;
    border: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}
.manufacturer-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}
.manufacturer-header {
    position: relative;
    padding: 1.5rem;
    color: white;
}
.manufacturer-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0.1;
    background: linear-gradient(45deg, rgba(255, 255, 255, 0.2), transparent);
}
.manufacturer-icon {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s ease;
}
.manufacturer-icon i {
    font-size: 1.2rem;
    color: white;
}
.manufacturer-card:hover .manufacturer-icon {
    background: rgba(255, 255, 255, 0.4);
}
.product-item {
    transition: background 0.3s ease;
}
.product-item:hover {
    background: #f8f9fa;
}
.product-name {
    font-size: 0.9rem;
    color: #495057;
    font-weight: 500;
}
.stock-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    color: white;
}
.progress-stock {
    height: 6px;
    margin-top: 0.5rem;
    border-radius: 5px;
    background: #f8f9fa;
    overflow: hidden;
}
.progress-stock .progress-bar {
    transition: width 0.5s ease;
}
.list-group-item {
    border-left: none;
    border-right: none;
    padding: 1rem;
}
.list-group-item:first-child {
    border-top: none;
}
.list-group-item:last-child {
    border-bottom: none;
}
.border-danger {
    border-width: 2px !important;
}
.card-header {
    border-bottom: 2px solid rgba(255, 255, 255, 0.2);
}
.card-header h4 {
    font-weight: 600;
    font-size: 1.25rem;
}

/* Estilos responsivos */
@media (max-width: 767.98px) {
    .content {
        padding: 1rem; /* Reduzir o padding em telas pequenas */
    }
    h1 {
        font-size: 1.5rem; /* Reduzir o tamanho do título */
    }
    .breadcrumb {
        font-size: 0.8rem; /* Reduzir o tamanho do breadcrumb */
    }
    .btn-lg {
        font-size: 0.9rem; /* Reduzir o tamanho do botão */
        padding: 0.5rem 1rem;
    }

    /* Cards de Resumo */
    .summary-card {
        padding: 0.75rem;
    }
    .summary-card h3 {
        font-size: 1.5rem; /* Reduzir o tamanho do número */
    }
    .summary-card h5 {
        font-size: 1rem; /* Reduzir o tamanho do título */
    }
    .summary-card p {
        font-size: 0.8rem; /* Reduzir o tamanho do texto */
    }
    .icon-wrapper {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
    }
    .icon-wrapper i {
        font-size: 1.2rem;
    }

    /* Produtos Mais Vendidos */
    .best-seller-card .card-title {
        font-size: 1.1rem;
    }
    .list-group-item {
        padding: 0.5rem 0.75rem;
    }
    .list-group-item span.text-truncate {
        font-size: 0.85rem;
    }
    .badge.rounded-pill {
        font-size: 0.7rem;
        padding: 0.2rem 0.5rem;
    }
    .progress {
        height: 5px;
    }

    /* Alerta de Estoque Baixo */
    .card-header h4 {
        font-size: 1.1rem;
    }
    .manufacturer-header {
        padding: 1rem;
    }
    .manufacturer-header h5 {
        font-size: 1rem;
    }
    .manufacturer-header small {
        font-size: 0.7rem;
    }
    .manufacturer-icon {
        width: 30px;
        height: 30px;
    }
    .manufacturer-icon i {
        font-size: 1rem;
    }
    .product-name {
        font-size: 0.85rem;
    }
    .stock-badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.5rem;
    }
    .progress-stock {
        height: 4px;
    }

    /* Imagem "No Data" */
    img[alt="Sem dados"] {
        width: 80px; /* Reduzir o tamanho da imagem */
        display: block;
        margin: 0 auto;
    }
}

/* Ajustes para telas muito pequenas (ex.: < 576px) */
@media (max-width: 575.98px) {
    h1 {
        font-size: 1.25rem;
    }
    .breadcrumb {
        font-size: 0.75rem;
    }
    .btn-lg {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
    }
    .summary-card h3 {
        font-size: 1.25rem;
    }
    .summary-card h5 {
        font-size: 0.9rem;
    }
    .summary-card p {
        font-size: 0.75rem;
    }
    .card-header h4 {
        font-size: 1rem;
    }
}

/* Adicione estes estilos ao seu CSS existente */
.best-seller-card .list-group-item {
    padding: 0.75rem 1rem;
    border-left: none;
    border-right: none;
    transition: background-color 0.2s ease;
}

.best-seller-card .list-group-item:hover {
    background-color: rgba(0,0,0,0.02);
}

.best-seller-card .list-group-item:first-child {
    border-top: none;
}

.best-seller-card .list-group-item:last-child {
    border-bottom: none;
}

.best-seller-card .badge {
    font-size: 0.875rem;
    padding: 0.5em 0.8em;
}

.best-seller-card .text-truncate {
    max-width: 200px;
}

.best-seller-card .bi-box-seam {
    font-size: 1.1rem;
}
</style>

<div class="content">
    <?php 
    // Exibir mensagens de sessão se existirem
    if (isset($_SESSION['mensagem'])) {
        echo $_SESSION['mensagem'];
        unset($_SESSION['mensagem']);
    }
    ?>

    <h1 class="mb-4 text-dark fw-bold"><i class="bi bi-house"></i> Dashboard</h1>

    <!-- Breadcrumb -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/estoque/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Produtos</li>
                </ol>
            </nav>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="/estoque/pages/produtos/index.php" class="btn btn-primary btn-lg">
                <i class="bi bi-plus-lg"></i> Novo Produto
            </a>
        </div>
    </div>

    <!-- Cards de Resumo -->
    <div class="row mb-5">
        <div class="col-12 col-md-6 col-lg-3 mb-4">
            <div class="card summary-card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-wrapper me-3">
                        <i class="bi bi-cart-check-fill"></i>
                    </div>
                    <div>
                        <h5 class="card-title">Vendas Hoje</h5>
                        <h3><?php echo $vendasHoje; ?></h3>
                        <p class="card-text">Produtos vendidos nas últimas 24h</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3 mb-4">
            <div class="card summary-card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-wrapper me-3">
                        <i class="bi bi-calendar-week-fill"></i>
                    </div>
                    <div>
                        <h5 class="card-title">Vendas na Semana</h5>
                        <h3><?php echo $vendasSemana; ?></h3>
                        <p class="card-text">Produtos vendidos nos últimos 7 dias</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3 mb-4">
            <div class="card summary-card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-wrapper me-3">
                        <i class="bi bi-calendar-month-fill"></i>
                    </div>
                    <div>
                        <h5 class="card-title">Vendas no Mês</h5>
                        <h3><?php echo $vendasMes; ?></h3>
                        <p class="card-text">Produtos vendidos nos últimos 30 dias</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3 mb-4">
            <div class="card summary-card">
                <div class="card-body d-flex align-items-center">
                    <div class="icon-wrapper me-3">
                        <i class="bi bi-trophy-fill"></i>
                    </div>
                    <div>
                        <h5 class="card-title">Dia com Mais Vendas</h5>
                        <h3><?php echo $diaMaisVendasNome; ?></h3>
                        <p class="card-text"><?php echo $vendasMes; ?> produtos vendidos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Produtos Mais Vendidos -->
    <div class="row mb-5">
        <div class="col-12 col-md-4 mb-4">
            <div class="card best-seller-card">
                <div class="card-body">
                    <h5 class="card-title">Produtos Mais Vendidos (Hoje)</h5>
                    <?php if (count($produtosVendidosHoje) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($produtosVendidosHoje as $produto): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-truncate me-2" title="<?php echo htmlspecialchars($produto['name']); ?>">
                                            <?php echo htmlspecialchars($produto['name']); ?>
                                        </span>
                                        <span class="badge rounded-pill"><?php echo $produto['total']; ?> un</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo ($produto['total'] / $maxVendasHoje) * 100; ?>%" aria-valuenow="<?php echo $produto['total']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $maxVendasHoje; ?>"></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0">Nenhuma venda hoje</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 mb-4">
            <div class="card best-seller-card">
                <div class="card-body">
                    <h5 class="card-title">Produtos Mais Vendidos (Semana)</h5>
                    <?php if (count($produtosVendidosSemana) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($produtosVendidosSemana as $produto): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-truncate me-2" title="<?php echo htmlspecialchars($produto['name']); ?>">
                                            <?php echo htmlspecialchars($produto['name']); ?>
                                        </span>
                                        <span class="badge rounded-pill"><?php echo $produto['total']; ?> un</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo ($produto['total'] / $maxVendasSemana) * 100; ?>%" aria-valuenow="<?php echo $produto['total']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $maxVendasSemana; ?>"></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0">Nenhuma venda esta semana</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 mb-4">
            <div class="card best-seller-card">
                <div class="card-body">
                    <h5 class="card-title">Produtos Mais Vendidos (Mês)</h5>
                    <?php if (count($produtosMaisVendidosMes) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($produtosMaisVendidosMes as $produto): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center flex-grow-1">
                                        <div class="me-3">
                                            <i class="bi bi-box-seam text-muted"></i>
                                        </div>
                                        <div class="text-truncate">
                                            <?php echo htmlspecialchars($produto['name']); ?>
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        <span class="badge bg-primary rounded-pill">
                                            <?php echo $produto['total']; ?> un
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Nenhuma venda este mês</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerta de Estoque Baixo -->
    <div class="card border-danger mb-4">
        <div class="card-header bg-danger text-white d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <h4 class="mb-0">Alertas de Estoque Baixo</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <?php
                $fabricantes = [
                    ['nome' => 'Biobalance', 'dados' => $estoqueBaixoBiobalance, 'cor' => '#445d6b', 'icone' => 'leaf-fill'],
                    ['nome' => 'Nutrify', 'dados' => $estoqueBaixoNutrify, 'cor' => '#18a085', 'icone' => 'droplet-fill'],
                    ['nome' => 'Gauer', 'dados' => $estoqueBaixoGauer, 'cor' => '#9b62ef', 'icone' => 'shield-fill'],
                    ['nome' => 'Bloken', 'dados' => $estoqueBaixoBloken, 'cor' => '#ffff84', 'icone' => 'capsule'],
                    ['nome' => 'Stem', 'dados' => $estoqueBaixoStem, 'cor' => '#26a1fe', 'icone' => 'flower1'],
                    ['nome' => 'Equaliv', 'dados' => $estoqueBaixoEqualiv, 'cor' => '#e67e22', 'icone' => 'stars'],
                    ['nome' => 'Divinite', 'dados' => $estoqueBaixoDivinite, 'cor' => '#fb7cbf', 'icone' => 'heart-fill']
                ];

                foreach ($fabricantes as $fabricante):
                    if (!empty($fabricante['dados'])):
                ?>
                    <div class="col-12 col-md-6 col-lg-4 mb-4">
                        <div class="manufacturer-card h-100">
                            <div class="manufacturer-header" style="background-color: <?php echo $fabricante['cor']; ?>">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="manufacturer-icon me-3">
                                        <i class="bi bi-<?php echo $fabricante['icone']; ?>"></i>
                                    </div>
                                    <h5 class="mb-0"><?php echo $fabricante['nome']; ?></h5>
                                </div>
                                <small class="text-white-50">
                                    <?php echo count($fabricante['dados']); ?> produto(s) com estoque baixo
                                </small>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($fabricante['dados'] as $produto): ?>
                                        <div class="list-group-item product-item">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="product-name" title="<?php echo htmlspecialchars($produto['name']); ?>">
                                                    <?php echo htmlspecialchars($produto['name']); ?>
                                                </span>
                                                <span class="stock-badge <?php echo $produto['stock'] == 0 ? 'bg-danger' : 'bg-warning'; ?>">
                                                    <?php echo $produto['stock']; ?> un
                                                </span>
                                            </div>
                                            <div class="progress-stock">
                                                <div class="progress-bar <?php echo $produto['stock'] == 0 ? 'bg-danger' : 'bg-warning'; ?>" 
                                                     role="progressbar" 
                                                     style="width: <?php echo ($produto['stock'] / ($produto['min_stock'] * 2)) * 100; ?>%" 
                                                     aria-valuenow="<?php echo $produto['stock']; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="<?php echo $produto['min_stock']; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir rodapé
include __DIR__ . '/includes/footer.php';
?>