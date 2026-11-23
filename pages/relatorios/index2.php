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
    $sql = "SELECT p.id, p.name, p.manufacturer_id FROM products p JOIN manufacturers m ON p.manufacturer_id = m.id";
    if ($manufacturer_id) {
        $sql .= " WHERE p.manufacturer_id = " . $conn->quote($manufacturer_id);
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

<link href="/assets/css/style.css" rel="stylesheet">

<div class="content">
    <!-- Exibir mensagens de sessão se existirem -->
    <?php 
    if (isset($_SESSION['mensagem'])) {
        echo $_SESSION['mensagem'];
        unset($_SESSION['mensagem']);
    }
    ?>

    <!-- Cabeçalho da Página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0 text-dark fw-bold"><i class="bi bi-file-earmark-bar-graph me-2"></i>Relatórios</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Relatórios</li>
                </ol>
            </nav>
        </div>
        <div>
            <button type="button" class="btn btn-primary btn-lg me-2" data-bs-toggle="modal" data-bs-target="#modalMovimentacao">
                <i class="bi bi-plus-circle"></i> Nova Movimentação
            </button>
            <button type="button" class="btn btn-success btn-lg" id="btnExportar">
                <i class="bi bi-file-excel"></i> Exportar para Excel
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
                <form id="formMovimentacao" action="/pages/relatorios/salvar_movimentacao.php" method="POST">
                    <div class="modal-body">
                        <div id="linhasMovimentacao">
                            <!-- Template para linha de movimentação -->
                            <div class="row mb-3 linha-movimentacao">
                                <div class="col-md-3">
                                    <label class="form-label">Fabricante</label>
                                    <select name="manufacturers[]" class="form-select manufacturer-select">
                                        <option value="">Selecione...</option>
                                        <?php foreach ($manufacturers as $m): ?>
                                        <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Produto</label>
                                    <select name="products[]" class="form-select product-select" required disabled>
                                        <option value="">Selecione o fabricante...</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Tipo</label>
                                    <select name="types[]" class="form-select" required>
                                        <option value="">Selecione...</option>
                                        <option value="entrada">Entrada</option>
                                        <option value="saida">Saída</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Quantidade</label>
                                    <input type="number" name="quantities[]" class="form-control" min="1" required>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-danger btn-remover-linha" style="display: none;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div class="col-md-3 mt-2">
                                    <label class="form-label">Data da Movimentação (opcional)</label>
                                    <input type="date" name="movement_dates[]" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-9 mt-2">
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
                    <div class="col-md-3">
                        <label for="data_inicio" class="form-label">Data Início</label>
                        <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $dataInicio; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="data_fim" class="form-label">Data Fim</label>
                        <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $dataFim; ?>">
                    </div>
                    <div class="col-md-3">
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
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <label for="type" class="form-label">Tipo</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">Todos os tipos</option>
                            <option value="entrada" <?php echo ($type == 'entrada') ? 'selected' : ''; ?>>Entrada</option>
                            <option value="saida" <?php echo ($type == 'saida') ? 'selected' : ''; ?>>Saída</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="filtrar" value="1" class="btn btn-primary">Filtrar</button>
                        <a href="/pages/relatorios/index.php" class="btn btn-outline-secondary">Limpar Filtros</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Cards de Resumo -->
    <div class="row mb-5">
        <div class="col-md-4 mb-4">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Total de Movimentações</h6>
                            <h2 class="mb-0 fw-bold"><?php echo count($movements); ?></h2>
                            <small class="text-muted">No período selecionado</small>
                        </div>
                        <div class="icon-bg rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: rgba(13, 110, 253, 0.1);">
                            <i class="bi bi-arrow-left-right fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Total de Entradas</h6>
                            <h2 class="mb-0 fw-bold text-success"><?php echo $totalEntradas; ?></h2>
                            <small class="text-muted">Produtos recebidos</small>
                        </div>
                        <div class="icon-bg rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: rgba(25, 135, 84, 0.1);">
                            <i class="bi bi-arrow-down-circle fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card summary-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Total de Saídas</h6>
                            <h2 class="mb-0 fw-bold text-danger"><?php echo $totalSaidas; ?></h2>
                            <small class="text-muted">Produtos enviados</small>
                        </div>
                        <div class="icon-bg rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: rgba(220, 53, 69, 0.1);">
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
                <img src="/assets/img/no-data.svg" alt="Sem dados" style="width: 120px; opacity: 0.5;">
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
        let url = '/pages/relatorios/exportar.php?';
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
                input.value = '';
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
            e.target.closest('.linha-movimentacao').remove();
        }
    });

    // Array com todos os produtos
    const allProducts = <?php echo json_encode($products); ?>;

    // Função para filtrar produtos por fabricante
    function filterProducts(manufacturerId, productSelect) {
        const filteredProducts = manufacturerId 
            ? allProducts.filter(p => p.manufacturer_id === manufacturerId)
            : [];
        
        productSelect.innerHTML = '<option value="">Selecione um produto...</option>';
        
        filteredProducts.forEach(p => {
            const option = document.createElement('option');
            option.value = p.id;
            option.textContent = p.name;
            productSelect.appendChild(option);
        });
        
        productSelect.disabled = !manufacturerId;
    }

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