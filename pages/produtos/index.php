<?php
$titulo = 'Produtos';
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

verificarLogin();

// Configurar filtros
$name = limparEntrada($_GET['name'] ?? '');
$manufacturer_id = limparEntrada($_GET['manufacturer'] ?? '');
$stock = limparEntrada($_GET['stock'] ?? '');

// Construir consulta com filtros
$sql = "SELECT p.id, p.name, p.stock, p.min_stock, p.manufacturer_id, p.image_url,
               m.name as manufacturer_name, m.logo_url 
        FROM products p 
        LEFT JOIN manufacturers m ON p.manufacturer_id = m.id";

$where = [];
$params = [];

if (!empty($name)) {
    $where[] = "p.name LIKE ?";
    $params[] = "%$name%";
}

if (!empty($manufacturer_id)) {
    $where[] = "p.manufacturer_id = ?";
    $params[] = $manufacturer_id;
}

if (!empty($stock)) {
    switch ($stock) {
        case 'low':
            $where[] = "p.stock <= p.min_stock";
            break;
        case 'medium':
            $where[] = "p.stock > p.min_stock AND p.stock <= (p.min_stock * 2)";
            break;
        case 'high':
            $where[] = "p.stock > (p.min_stock * 2)";
            break;
    }
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY p.name ASC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar fabricantes para o filtro
    $stmtManufacturers = $conn->query("SELECT id, name FROM manufacturers ORDER BY name ASC");
    $manufacturers = $stmtManufacturers->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao buscar produtos: ' . $e->getMessage());
}

// Include header
include __DIR__ . '/../../includes/header.php';
?>

<style>
/* Estilos existentes */
.product-card {
    transition: all 0.3s ease;
}
.product-card:hover {
    background-color: rgba(0, 0, 0, 0.02);
}
.product-image {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    object-fit: cover;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}
.product-card:hover .product-image {
    transform: scale(1.1);
}
.manufacturer-logo {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.stock-progress {
    height: 6px;
    border-radius: 3px;
    background-color: #f0f0f0;
}
.stock-progress .progress-bar {
    border-radius: 3px;
    transition: width 0.5s ease;
}
.table-products td {
    padding: 1rem;
    vertical-align: middle;
}
.product-name {
    font-size: 1rem;
    margin-bottom: 0.25rem;
    color: #2c3e50;
}
.manufacturer-name {
    font-size: 0.875rem;
    color: #6c757d;
}
.btn-group .btn {
    padding: 0.375rem 0.75rem;
}
.stock-badge {
    min-width: 45px;
    text-align: center;
}
.table-products tbody tr {
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}
.table-products tbody tr:last-child {
    border-bottom: none;
}
.table-products {
    border-top: 2px solid rgba(0, 0, 0, 0.05);
    margin-top: 1rem;
}
.table-products thead {
    border-bottom: 2px solid rgba(0, 0, 0, 0.05);
}
.table-products thead th {
    font-weight: 600;
    color: #495057;
    padding: 1rem;
}
.product-card:hover {
    background-color: rgba(0, 0, 0, 0.02);
    border-bottom-color: rgba(0, 0, 0, 0.1);
}

/* Estilos adicionais para responsividade */
.form-floating label,
.form-floating .form-control,
.form-floating .form-select {
    font-size: 0.9rem;
}
.card-header h6 {
    font-size: 1.1rem;
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
    .card-header h6 {
        font-size: 1rem;
    }
    .card-body {
        padding: 1rem;
    }
    .form-floating label,
    .form-floating .form-control,
    .form-floating .form-select {
        font-size: 0.85rem;
    }
    .form-floating .form-control,
    .form-floating .form-select {
        height: calc(2.5rem + 2px);
    }
    .btn {
        font-size: 0.85rem;
        padding: 0.5rem 1rem;
    }
    .table-products thead th,
    .table-products td {
        font-size: 0.85rem;
        padding: 0.5rem;
    }
    .product-image {
        width: 40px;
        height: 40px;
    }
    .manufacturer-logo {
        width: 28px;
        height: 28px;
    }
    .product-name {
        font-size: 0.9rem;
    }
    .manufacturer-name {
        font-size: 0.8rem;
    }
    .stock-badge {
        min-width: 40px;
        font-size: 0.8rem;
    }
    .stock-progress {
        height: 5px;
    }
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    .btn-group .btn i {
        font-size: 0.9rem;
    }
    .table-responsive {
        -webkit-overflow-scrolling: touch; /* Melhorar rolagem em dispositivos iOS */
    }
    /* Ajustes para modais */
    .modal-dialog {
        margin: 0.5rem;
    }
    .modal-title {
        font-size: 1.1rem;
    }
    .modal-body .form-floating label,
    .modal-body .form-floating .form-control,
    .modal-body .form-floating .form-select {
        font-size: 0.85rem;
    }
    .modal-body .form-floating .form-control,
    .modal-body .form-floating .form-select {
        height: calc(2.5rem + 2px);
    }
    .modal-footer .btn {
        font-size: 0.85rem;
        padding: 0.5rem 1rem;
    }
}

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
    .card-header h6 {
        font-size: 0.9rem;
    }
    .form-floating label,
    .form-floating .form-control,
    .form-floating .form-select {
        font-size: 0.8rem;
    }
    .form-floating .form-control,
    .form-floating .form-select {
        height: calc(2.25rem + 2px);
    }
    .btn {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
    }
    .table-products thead th,
    .table-products td {
        font-size: 0.8rem;
        padding: 0.4rem;
    }
    .product-image {
        width: 36px;
        height: 36px;
    }
    .manufacturer-logo {
        width: 24px;
        height: 24px;
    }
    .product-name {
        font-size: 0.85rem;
    }
    .manufacturer-name {
        font-size: 0.75rem;
    }
    .stock-badge {
        min-width: 35px;
        font-size: 0.75rem;
    }
    .modal-title {
        font-size: 1rem;
    }
    .modal-body .form-floating label,
    .modal-body .form-floating .form-control,
    .modal-body .form-floating .form-select {
        font-size: 0.8rem;
    }
    .modal-body .form-floating .form-control,
    .modal-body .form-floating .form-select {
        height: calc(2.25rem + 2px);
    }
    .modal-footer .btn {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
    }
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
            <h1 class="mb-1 text-dark fw-bold"><i class="bi bi-box-seam text-primary me-2"></i>Gestão de Produtos</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small text-muted">
                    <li class="breadcrumb-item"><a href="<?php echo function_exists('url') ? url('/index.php') : '/index.php'; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active text-dark fw-medium">Catálogo de Produtos</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="<?php echo function_exists('url') ? url('/pages/produtos/novo.php') : '/pages/produtos/novo.php'; ?>" class="btn btn-primary px-3 py-2 fw-medium shadow-sm" id="btnNovoProduto">
                <i class="bi bi-plus-lg me-1"></i> Novo Produto
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0 font-weight-bold text-primary">
                <i class="bi bi-funnel me-2"></i> Filtros
            </h6>
        </div>
        <div class="card-body">
            <form action="" method="get" class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>" placeholder="Nome do produto">
                        <label for="name">Nome do produto</label>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <select class="form-select" id="manufacturer" name="manufacturer">
                            <option value="">Selecione um fabricante</option>
                            <?php foreach ($manufacturers as $manufacturer): ?>
                            <option value="<?php echo $manufacturer['id']; ?>" <?php echo ($manufacturer_id == $manufacturer['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($manufacturer['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="manufacturer">Fabricante</label>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <select class="form-select" id="stock" name="stock">
                            <option value="">Todos os níveis</option>
                            <option value="low" <?php echo ($stock == 'low') ? 'selected' : ''; ?>>Estoque Baixo</option>
                            <option value="medium" <?php echo ($stock == 'medium') ? 'selected' : ''; ?>>Estoque Médio</option>
                            <option value="high" <?php echo ($stock == 'high') ? 'selected' : ''; ?>>Estoque Alto</option>
                        </select>
                        <label for="stock">Nível de Estoque</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <a href="<?php echo function_exists('url') ? url('/pages/produtos/index.php') : '/pages/produtos/index.php'; ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Produtos -->
    <div class="table-responsive">
        <table class="table table-borderless align-middle mb-0 table-products">
            <thead class="table-light">
                <tr>
                    <th>Produto</th>
                    <th>Fabricante</th>
                    <th>Estoque</th>
                    <th width="120">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                <tr>
                    <td colspan="4" class="text-center py-5">
                        <img src="/assets/img/no-data.svg" alt="Sem dados" style="width: 120px; opacity: 0.5;">
                        <h3 class="mt-4">Nenhum produto encontrado</h3>
                        <p class="text-muted">Tente ajustar os filtros ou adicionar um novo produto.</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($products as $product): ?>
                <tr class="product-card" 
                    data-id="<?php echo $product['id']; ?>" 
                    data-manufacturer-id="<?php echo $product['manufacturer_id']; ?>" 
                    data-min-stock="<?php echo $product['min_stock']; ?>">
                    <td>
                        <div class="d-flex align-items-center">
                            <?php if ($product['image_url']): ?>
                            <img src="<?= url($product['image_url']); ?>" 
                                 class="product-image me-3"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                            <div class="product-image me-3 bg-light d-flex align-items-center justify-content-center">
                                <i class="bi bi-box-seam text-muted"></i>
                            </div>
                            <?php endif; ?>
                            <div class="product-name text-truncate" data-bs-toggle="tooltip" 
                                 title="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <?php if ($product['logo_url']): ?>
                            <img src="<?= url($product['logo_url']); ?>" 
                                 class="manufacturer-logo me-2"
                                 alt="<?php echo htmlspecialchars($product['manufacturer_name']); ?>">
                            <?php endif; ?>
                            <span class="manufacturer-name"><?php echo htmlspecialchars($product['manufacturer_name']); ?></span>
                        </div>
                    </td>
                    <td>
                        <?php 
                        $isZerado = ((int)$product['stock'] === 0);
                        $isBaixo = ((int)$product['stock'] <= (int)$product['min_stock']);
                        $stockStatus = $isZerado ? 'danger' : ($isBaixo ? 'warning text-dark' : 'success');
                        $progressColor = $isZerado ? 'bg-danger' : ($isBaixo ? 'bg-warning' : 'bg-success');
                        ?>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-<?php echo $stockStatus; ?> me-2 stock-badge fw-bold">
                                <?php if ($isZerado): ?>
                                    0 (Esgotado)
                                <?php elseif ($isBaixo): ?>
                                    <?php echo $product['stock']; ?> (Baixo)
                                <?php else: ?>
                                    <?php echo $product['stock']; ?>
                                <?php endif; ?>
                            </span>
                            <div class="stock-progress flex-grow-1" style="min-width: 60px;">
                                <div class="progress-bar <?php echo $progressColor; ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo $isZerado ? 100 : min(($product['stock'] / max($product['min_stock'] * 2, 1)) * 100, 100); ?>%">
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button type="button" 
                                    class="btn btn-sm btn-outline-primary" 
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditarProduto"
                                    title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm"
                               data-bs-toggle="modal" 
                               data-bs-target="#confirmDeleteModal" 
                               data-id="<?php echo $product['id']; ?>"
                               data-name="<?php echo htmlspecialchars($product['name']); ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Novo Produto -->
<div class="modal fade" id="modalNovoProduto" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= url('/pages/produtos/salvar.php'); ?>" method="post" enctype="multipart/form-data">
                <?= function_exists('campoCsrf') ? campoCsrf() : ''; ?>
                <div class="modal-header">
                    <h5 class="modal-title">Novo Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="name" name="name" required>
                                <label for="name">Nome do Produto *</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="manufacturer_id" name="manufacturer_id" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($manufacturers as $manufacturer): ?>
                                    <option value="<?php echo $manufacturer['id']; ?>">
                                        <?php echo htmlspecialchars($manufacturer['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="manufacturer_id">Fabricante *</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                                <label for="stock">Estoque Inicial *</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="min_stock" name="min_stock" min="0" required>
                                <label for="min_stock">Estoque Mínimo *</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <label for="image">Imagem do Produto</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Produto -->
<div class="modal fade" id="modalEditarProduto" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= url('/pages/produtos/atualizar.php'); ?>" method="post" enctype="multipart/form-data">
                <?= function_exists('campoCsrf') ? campoCsrf() : ''; ?>
                <input type="hidden" id="editar_id" name="id">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="editar_name" name="name" required>
                                <label for="editar_name">Nome do Produto *</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="editar_manufacturer_id" name="manufacturer_id" required>
                                    <?php foreach ($manufacturers as $manufacturer): ?>
                                    <option value="<?php echo $manufacturer['id']; ?>">
                                        <?php echo htmlspecialchars($manufacturer['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="editar_manufacturer_id">Fabricante *</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="editar_stock" name="stock" min="0" required>
                                <label for="editar_stock">Estoque *</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="editar_min_stock" name="min_stock" min="0" required>
                                <label for="editar_min_stock">Estoque Mínimo *</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="file" class="form-control" id="editar_image" name="image" accept="image/*">
                                <label for="editar_image">Nova Imagem do Produto</label>
                            </div>
                            <div id="preview_image" class="mt-2"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
// Include footer
include __DIR__ . '/../../includes/footer.php'; 
?>

<!-- Modal Confirmar Exclusão Produto -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="confirmDeleteForm" action="<?= url('/pages/produtos/excluir.php'); ?>" method="post">
                <?= function_exists('campoCsrf') ? campoCsrf() : ''; ?>
                <input type="hidden" id="confirmDeleteId" name="id" value="">
                <input type="hidden" id="confirmDeleteArquivo" name="arquivo" value="">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir <strong id="itemToDelete"></strong>?</p>
                    <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>Esta ação não poderá ser desfeita. O histórico de movimentações também será removido.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Excluir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Atualizar botão "Novo Produto" para abrir modal
    document.querySelector('#btnNovoProduto').setAttribute('data-bs-toggle', 'modal');
    document.querySelector('#btnNovoProduto').setAttribute('data-bs-target', '#modalNovoProduto');
    document.querySelector('#btnNovoProduto').removeAttribute('href');

    // Modal de Edição
    const modalEditarProduto = document.getElementById('modalEditarProduto');
    if (modalEditarProduto) {
        modalEditarProduto.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const row = button.closest('tr');
            
            const id = row.dataset.id;
            const name = row.querySelector('.product-name').textContent.trim();
            const manufacturer_id = row.dataset.manufacturerId;
            const min_stock = row.dataset.minStock;
            const stock = row.querySelector('.stock-badge').textContent.trim();
            const image = row.querySelector('.product-image')?.src;
            
            // Preencher os campos
            document.getElementById('editar_id').value = id;
            document.getElementById('editar_name').value = name;
            document.getElementById('editar_manufacturer_id').value = manufacturer_id;
            document.getElementById('editar_stock').value = stock;
            document.getElementById('editar_min_stock').value = min_stock;
            
            // Atualizar preview da imagem considerando o prefixo /estoque
            const previewDiv = document.getElementById('preview_image');
            if (image && !image.includes('box-seam')) {
                previewDiv.innerHTML = `<img src="${image}" class="img-thumbnail" style="max-height: 100px;">`;
            } else {
                previewDiv.innerHTML = '';
            }
        });
    }

    // Modal de exclusão de produto via POST
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    if (confirmDeleteModal) {
        confirmDeleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            document.getElementById('itemToDelete').textContent = `o produto "${name}"`;
            document.getElementById('confirmDeleteId').value = id;
        });
    }

    // Preview de imagem ao selecionar arquivo
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function(e) {
            const preview = document.getElementById('preview_image');
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-height: 100px;">`;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
});
</script>