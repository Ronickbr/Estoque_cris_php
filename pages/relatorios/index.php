<?php
$titulo = 'Relatórios';
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

verificarLogin();

// Determinar datas padrão para filtro (últimos 30 dias)
$dataFim = date('Y-m-d');
$dataInicio = date('Y-m-d', strtotime('-30 days'));

// Filtros
if (isset($_GET['filtrar'])) {
    $dataInicio = $_GET['data_inicio'] ?? $dataInicio;
    $dataFim = $_GET['data_fim'] ?? $dataFim;
    $manufacturer_id = isset($_GET['manufacturer_id']) && !empty($_GET['manufacturer_id']) ? $_GET['manufacturer_id'] : null;
    $product_id = isset($_GET['product_id']) && !empty($_GET['product_id']) ? $_GET['product_id'] : null;
    $type = isset($_GET['type']) && in_array($_GET['type'], ['entrada', 'saida']) ? $_GET['type'] : null;
} else {
    $manufacturer_id = null;
    $product_id = null;
    $type = null;
}

// Buscar fabricantes
try {
    $stmtManufacturers = $conn->query("SELECT id, name FROM manufacturers ORDER BY name ASC");
    $manufacturers = $stmtManufacturers->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao buscar fabricantes: ' . $e->getMessage());
}

// Buscar produtos
try {
    $sql = "SELECT p.id, p.name, p.manufacturer_id, p.stock as current_stock
           FROM products p 
           LEFT JOIN manufacturers mf ON p.manufacturer_id = mf.id
           WHERE 1=1";
    
    if ($manufacturer_id) {
        $sql .= " AND p.manufacturer_id = " . $conn->quote($manufacturer_id);
    }
    
    $sql .= " ORDER BY p.name ASC";
    
    $stmtProducts = $conn->query($sql);
    $products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao buscar produtos: ' . $e->getMessage());
}

// Buscar movimentações com filtros
try {
    $sql = "SELECT m.id, m.product_id, m.type, m.quantity, m.created_at, m.notes, 
                   p.name as product_name, mf.name as manufacturer_name
            FROM inventory_movements m
            JOIN products p ON m.product_id = p.id
            LEFT JOIN manufacturers mf ON p.manufacturer_id = mf.id
            WHERE m.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
    
    $params = [$dataInicio, $dataFim];
    
    if ($manufacturer_id) {
        $sql .= " AND p.manufacturer_id = ?";
        $params[] = $manufacturer_id;
    }
    
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
    
    // Calcular totais
    $totalEntradas = 0;
    $totalSaidas = 0;
    
    foreach ($movements as $mov) {
        if ($mov['type'] == 'entrada') {
            $totalEntradas += $mov['quantity'];
        } else {
            $totalSaidas += $mov['quantity'];
        }
    }
    
} catch(PDOException $e) {
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao buscar movimentações: ' . $e->getMessage());
}

// Incluir cabeçalho
include __DIR__ . '/../../includes/header.php';
?>

<style>
/* Estilos adicionais para responsividade */
.summary-card h6 {
    font-size: 1rem;
}
.summary-card h2 {
    font-size: 1.75rem;
}
.summary-card small {
    font-size: 0.875rem;
}
.icon-bg {
    width: 48px;
    height: 48px;
}
.table th, .table td {
    font-size: 0.9rem;
    padding: 0.75rem;
}
.badge {
    font-size: 0.875rem;
}
.form-label {
    font-size: 0.9rem;
}
.form-control, .form-select {
    font-size: 0.9rem;
}

/* Responsividade */
@media (max-width: 767.98px) {
    .content {
        padding: 1rem;
        padding-top: 4rem; /* Espaço para a navbar-mobile */
        margin-left: 0 !important; /* Garante que o conteúdo não tenha margem à esquerda */
    }
    .content .row {
        max-width: 100%; /* Remove a limitação de largura */
        margin: 0 auto;
        display: flex;
        justify-content: center; /* Centraliza os cards */
        flex-wrap: wrap;
    }
    .content .col-md-4 {
        max-width: 90%; /* Limita a largura dos cards para centralizar */
    }
    h1 {
        font-size: 1.5rem;
    }
    .breadcrumb {
        font-size: 0.8rem;
    }
    .btn-lg {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    .card-header h5 {
        font-size: 1.1rem;
    }
    .card-body {
        padding: 1rem;
    }
    .form-label {
        font-size: 0.85rem;
    }
    .form-control, .form-select {
        font-size: 0.85rem;
        height: calc(2.5rem + 2px);
    }
    .btn {
        font-size: 0.85rem;
        padding: 0.5rem 1rem;
    }
    .summary-card h6 {
        font-size: 0.9rem;
    }
    .summary-card h2 {
        font-size: 1.5rem;
    }
    .summary-card small {
        font-size: 0.8rem;
    }
    .icon-bg {
        width: 40px;
        height: 40px;
    }
    .icon-bg i {
        font-size: 1.25rem;
    }
    .table th, .table td {
        font-size: 0.85rem;
        padding: 0.5rem;
    }
    .badge {
        font-size: 0.8rem;
    }
    .table-responsive {
        -webkit-overflow-scrolling: touch; /* Melhorar rolagem em dispositivos iOS */
    }
    .text-center h3 {
        font-size: 1.25rem;
    }
    .text-center p {
        font-size: 0.85rem;
    }
    img[alt="Sem dados"] {
        width: 80px; /* Reduzir o tamanho da imagem */
        display: block;
        margin: 0 auto;
    }
    /* Ajustes para modais */
    .modal-dialog {
        margin: 0.5rem;
    }
    .modal-title {
        font-size: 1.1rem;
    }
    .modal-body .form-label {
        font-size: 0.85rem;
    }
    .modal-body .form-control,
    .modal-body .form-select {
        font-size: 0.85rem;
        height: calc(2.5rem + 2px);
    }
    .modal-footer .btn {
        font-size: 0.85rem;
        padding: 0.5rem 1rem;
    }
    .btn-remover-linha {
        font-size: 0.85rem;
        padding: 0.25rem 0.5rem;
    }
}

@media (max-width: 575.98px) {
    .content .col-md-4 {
        max-width: 95%; /* Ajusta ainda mais a largura dos cards */
    }
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
    .card-header h5 {
        font-size: 1rem;
    }
    .form-label {
        font-size: 0.8rem;
    }
    .form-control, .form-select {
        font-size: 0.8rem;
        height: calc(2.25rem + 2px);
    }
    .btn {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
    }
    .summary-card h6 {
        font-size: 0.85rem;
    }
    .summary-card h2 {
        font-size: 1.25rem;
    }
    .summary-card small {
        font-size: 0.75rem;
    }
    .icon-bg {
        width: 36px;
        height: 36px;
    }
    .icon-bg i {
        font-size: 1rem;
    }
    .table th, .table td {
        font-size: 0.8rem;
        padding: 0.4rem;
    }
    .badge {
        font-size: 0.75rem;
    }
    .text-center h3 {
        font-size: 1.1rem;
    }
    .text-center p {
        font-size: 0.8rem;
    }
    .modal-title {
        font-size: 1rem;
    }
    .modal-body .form-label {
        font-size: 0.8rem;
    }
    .modal-body .form-control,
    .modal-body .form-select {
        font-size: 0.8rem;
        height: calc(2.25rem + 2px);
    }
    .modal-footer .btn {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
    }
    .btn-remover-linha {
        font-size: 0.8rem;
        padding: 0.2rem 0.4rem;
    }
}

/* Estilos adicionais */
.product-option-with-stock {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stock-indicator {
    font-size: 0.85em;
    padding: 2px 6px;
    border-radius: 3px;
    margin-left: 8px;
}

.stock-zero {
    color: #dc3545;
}

.product-select option[data-stock="0"] {
    color: #dc3545;
}

.product-select {
    font-family: monospace;
}
</style>

<div class="content">
    <!-- Exibir mensagens de sessão se existirem -->
    <?php 
    if (isset($_SESSION['mensagem'])) {
        echo $_SESSION['mensagem'];
        unset($_SESSION['mensagem']);
    }
    ?>

    <!-- Cabeçalho da Página -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 text-dark fw-bold"><i class="bi bi-file-earmark-bar-graph text-primary me-2"></i>Relatórios de Movimentação</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small text-muted">
                    <li class="breadcrumb-item"><a href="<?php echo function_exists('url') ? url('/index.php') : '/index.php'; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active text-dark fw-medium">Histórico de Entradas e Saídas</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary px-3 py-2 fw-medium shadow-sm" data-bs-toggle="modal" data-bs-target="#modalMovimentacao">
                <i class="bi bi-plus-lg me-1"></i> Nova Movimentação
            </button>
            <button type="button" class="btn btn-outline-success px-3 py-2 fw-medium shadow-sm" id="btnExportar">
                <i class="bi bi-file-earmark-excel me-1"></i> Exportar Excel
            </button>
        </div>
    </div>

    <!-- Modal de Nova Movimentação -->
    <div class="modal fade" id="modalMovimentacao" tabindex="-1" aria-labelledby="modalMovimentacaoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMovimentacaoLabel">Nova Movimentação de Estoque</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formMovimentacao" action="<?= url('/pages/relatorios/salvar_movimentacao.php'); ?>" method="POST">
                    <?= function_exists('campoCsrf') ? campoCsrf() : ''; ?>
                    <div class="modal-body">
                        <div id="linhasMovimentacao">
                            <!-- Template para linha de movimentação -->
                            <div class="row mb-3 linha-movimentacao">
                                <div class="col-12 col-md-3">
                                    <label class="form-label">Fabricante</label>
                                    <select name="manufacturers[]" class="form-select manufacturer-select">
                                        <option value="">Selecione...</option>
                                        <?php foreach ($manufacturers as $m): ?>
                                        <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label">Produto</label>
                                    <select name="products[]" class="form-select product-select" required disabled>
                                        <option value="">Selecione o fabricante...</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label">Tipo</label>
                                    <select name="types[]" class="form-select" required>
                                        <option value="">Selecione...</option>
                                        <option value="entrada">Entrada</option>
                                        <option value="saida" selected>Saída</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label">Quantidade</label>
                                    <input type="number" name="quantities[]" class="form-control" min="1" required>
                                </div>
                                <div class="col-12 col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-danger btn-remover-linha" style="display: none;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div class="col-12 col-md-3 mt-2">
                                    <label class="form-label">Data da Movimentação (opcional)</label>
                                    <input type="date" name="movement_dates[]" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-12 col-md-9 mt-2">
                                    <label class="form-label">Observações</label>
                                    <input type="text" name="notes[]" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-secondary" id="btnAdicionarLinha">
                                <i class="bi bi-plus-circle"></i> Adicionar Produto
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Movimentações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0 font-weight-bold text-primary">
                <i class="bi bi-funnel me-2"></i>Filtros
            </h5>
        </div>
        <div class="card-body">
            <form action="" method="get">
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <label for="data_inicio" class="form-label">Data Início</label>
                        <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $dataInicio; ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="data_fim" class="form-label">Data Fim</label>
                        <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $dataFim; ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="manufacturer_id" class="form-label">Fabricante</label>
                        <select class="form-select" id="manufacturer_id" name="manufacturer_id">
                            <option value="">Todos os fabricantes</option>
                            <?php foreach ($manufacturers as $m): ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo ($manufacturer_id == $m['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="product_id" class="form-label">Produto</label>
                        <select class="form-select" id="product_id" name="product_id">
                            <option value="">Todos os produtos</option>
                            <?php foreach ($products as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($product_id == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="type" class="form-label">Tipo</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">Todos os tipos</option>
                            <option value="entrada" <?php echo ($type == 'entrada') ? 'selected' : ''; ?>>Entrada</option>
                            <option value="saida" <?php echo ($type == 'saida') ? 'selected' : ''; ?>>Saída</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="filtrar" value="1" class="btn btn-primary me-2">Filtrar</button>
                        <a href="<?= url('/pages/relatorios/index.php'); ?>" class="btn btn-outline-secondary">Limpar Filtros</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Cards de Resumo -->
    <div class="row mb-5">
        <div class="col-12 col-md-4 mb-4">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Total de Movimentações</h6>
                            <h2 class="mb-0 fw-bold"><?php echo count($movements); ?></h2>
                            <small class="text-muted">No período selecionado</small>
                        </div>
                        <div class="icon-bg rounded-circle d-flex align-items-center justify-content-center" style="background-color: rgba(13, 110, 253, 0.1);">
                            <i class="bi bi-arrow-left-right fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 mb-4">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Total de Entradas</h6>
                            <h2 class="mb-0 fw-bold text-success"><?php echo $totalEntradas; ?></h2>
                            <small class="text-muted">Produtos recebidos</small>
                        </div>
                        <div class="icon-bg rounded-circle d-flex align-items-center justify-content-center" style="background-color: rgba(25, 135, 84, 0.1);">
                            <i class="bi bi-arrow-down-circle fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 mb-4">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Total de Saídas</h6>
                            <h2 class="mb-0 fw-bold text-danger"><?php echo $totalSaidas; ?></h2>
                            <small class="text-muted">Produtos enviados</small>
                        </div>
                        <div class="icon-bg rounded-circle d-flex align-items-center justify-content-center" style="background-color: rgba(220, 53, 69, 0.1);">
                            <i class="bi bi-arrow-up-circle fs-4 text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Movimentações de Estoque -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 font-weight-bold text-primary">
                <i class="bi bi-list-ul me-2"></i>Movimentações de Estoque
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (count($movements) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tabelaMovimentacoes">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Produto</th>
                            <th>Fabricante</th>
                            <th>Tipo</th>
                            <th>Quantidade</th>
                            <th>Observações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movements as $mov): ?>
                        <tr>
                            <td><?php echo formatarData($mov['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($mov['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($mov['manufacturer_name']); ?></td>
                            <td>
                                <?php if ($mov['type'] == 'entrada'): ?>
                                <span class="badge bg-success">Entrada</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Saída</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $mov['quantity']; ?></td>
                            <td><?php echo htmlspecialchars($mov['notes'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <img src="<?= function_exists('url') ? url('/assets/img/no-data.svg') : '/assets/img/no-data.svg'; ?>" alt="Sem dados" style="width: 120px; opacity: 0.5;">
                <h3 class="mt-4">Nenhuma movimentação encontrada</h3>
                <p class="text-muted">Tente ajustar os filtros para um período diferente.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Exportar para Excel
    document.getElementById('btnExportar').addEventListener('click', function() {
        // Redirecionar para página de exportação com os mesmos filtros
        let url = '<?= url('/pages/relatorios/exportar.php'); ?>?';
        url += 'data_inicio=' + document.getElementById('data_inicio').value;
        url += '&data_fim=' + document.getElementById('data_fim').value;
        
        const manufacturer = document.getElementById('manufacturer_id').value;
        if (manufacturer) {
            url += '&manufacturer_id=' + manufacturer;
        }

        const product = document.getElementById('product_id').value;
        if (product) {
            url += '&product_id=' + product;
        }
        
        const type = document.getElementById('type').value;
        if (type) {
            url += '&type=' + type;
        }
        
        window.location.href = url;
    });

    // Adicionar nova linha de movimentação
    document.getElementById('btnAdicionarLinha').addEventListener('click', function() {
        const template = document.querySelector('.linha-movimentacao').cloneNode(true);
        template.querySelectorAll('input, select').forEach(input => {
            if (input.type !== 'date') {
                if (input.name === 'types[]') {
                    input.value = 'saida'; // Define saída como padrão
                } else {
                    input.value = '';
                }
            } else {
                input.value = '<?php echo date('Y-m-d'); ?>';
            }
        });
        template.querySelector('.btn-remover-linha').style.display = 'block';
        document.getElementById('linhasMovimentacao').appendChild(template);
    });

    // Remover linha de movimentação
    document.getElementById('linhasMovimentacao').addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remover-linha') || e.target.closest('.btn-remover-linha')) {
            const linha = e.target.closest('.linha-movimentacao');
            if (document.querySelectorAll('.linha-movimentacao').length > 1) {
                linha.remove();
            }
        }
    });

    // Array com todos os produtos
    const allProducts = <?php echo json_encode($products); ?>;

    // Substituir a função filterProducts existente por:
    function filterProducts(manufacturerId, productSelect) {
        const filteredProducts = manufacturerId 
            ? allProducts.filter(p => p.manufacturer_id === manufacturerId)
            : [];
        
        productSelect.innerHTML = '<option value="">Selecione um produto...</option>';
        
        filteredProducts.forEach(p => {
            const option = document.createElement('option');
            const stock = parseInt(p.current_stock) || 0;
            
            option.value = p.id;
            option.innerHTML = `${p.name.padEnd(30)} [${stock}]`;
            option.dataset.stock = stock;
            
            if (stock === 0) {
                option.classList.add('stock-zero');
                option.style.color = '#dc3545';
            }
            
            productSelect.appendChild(option);
        });
        
        productSelect.disabled = !manufacturerId;
    }

    // Adicionar listener para quando um produto é selecionado
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('product-select')) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const stock = parseInt(selectedOption.dataset.stock) || 0;
            const typeSelect = e.target.closest('.linha-movimentacao').querySelector('select[name="types[]"]');
            const quantityInput = e.target.closest('.linha-movimentacao').querySelector('input[name="quantities[]"]');
            
            if (typeSelect.value === 'saida') {
                quantityInput.max = stock;
                quantityInput.placeholder = `Máximo: ${stock}`;
            } else {
                quantityInput.removeAttribute('max');
                quantityInput.placeholder = 'Quantidade';
            }
        }
    });

    // Adicionar listener para quando o tipo de movimentação é alterado
    document.addEventListener('change', function(e) {
        if (e.target.name && e.target.name.includes('types')) {
            const linha = e.target.closest('.linha-movimentacao');
            const productSelect = linha.querySelector('.product-select');
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const stock = parseInt(selectedOption.dataset.stock) || 0;
            const quantityInput = linha.querySelector('input[name="quantities[]"]');
            
            if (e.target.value === 'saida') {
                quantityInput.max = stock;
                quantityInput.placeholder = `Máximo: ${stock}`;
            } else {
                quantityInput.removeAttribute('max');
                quantityInput.placeholder = 'Quantidade';
            }
        }
    });

    // Adicionar listener para selects de fabricante
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('manufacturer-select')) {
            const productSelect = e.target.closest('.linha-movimentacao').querySelector('.product-select');
            filterProducts(e.target.value, productSelect);
        }
    });
});
</script>

<?php
// Incluir rodapé
include __DIR__ . '/../../includes/footer.php';
?>
