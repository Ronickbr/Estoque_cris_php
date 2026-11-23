<?php
$titulo = 'Fabricantes';
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar se usuário está logado
verificarLogin();

// Buscar fabricantes
try {
    $sql = "SELECT m.id, m.name, m.logo_url, COUNT(p.id) as total_products
            FROM manufacturers m
            LEFT JOIN products p ON m.id = p.manufacturer_id
            GROUP BY m.id, m.name, m.logo_url
            ORDER BY m.name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $manufacturers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao buscar fabricantes: ' . $e->getMessage());
}

include __DIR__ . '/../../includes/header.php';
?>

<style>
/* Estilos adicionais para melhorar a responsividade */
.table th, .table td {
    font-size: 0.9rem;
    padding: 0.75rem;
}
.btn-group .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}
.btn-group .btn-sm i {
    font-size: 0.9rem;
}
.table img {
    width: 40px;
    height: 40px;
}
.modal-body .form-label {
    font-size: 0.9rem;
}
.modal-body .form-control {
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
    .table th, .table td {
        font-size: 0.85rem;
        padding: 0.5rem;
    }
    .table img {
        width: 32px;
        height: 32px;
    }
    .btn-group .btn-sm {
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }
    .btn-group .btn-sm i {
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
    .modal-body .form-control {
        font-size: 0.85rem;
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
    .card-header h5 {
        font-size: 1rem;
    }
    .table th, .table td {
        font-size: 0.8rem;
        padding: 0.4rem;
    }
    .table img {
        width: 28px;
        height: 28px;
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
    .modal-body .form-control {
        font-size: 0.8rem;
    }
    .modal-footer .btn {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
    }
}
</style>

<div class="content">
    <?php 
    if (isset($_SESSION['mensagem'])) {
        echo $_SESSION['mensagem'];
        unset($_SESSION['mensagem']);
    }
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 text-dark fw-bold"><i class="bi bi-building text-primary me-2"></i>Fabricantes</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small text-muted">
                    <li class="breadcrumb-item"><a href="<?php echo function_exists('url') ? url('/index.php') : '/index.php'; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active text-dark fw-medium">Fabricantes Parceiros</li>
                </ol>
            </nav>
        </div>
        <div>
            <button type="button" class="btn btn-primary px-3 py-2 fw-medium shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovoFabricante">
                <i class="bi bi-plus-lg me-1"></i> Novo Fabricante
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 font-weight-bold text-primary">
                <i class="bi bi-list-ul me-2"></i>Lista de Fabricantes
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (count($manufacturers) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fabricante</th>
                            <th>Total de Produtos</th>
                            <th width="120">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($manufacturers as $manufacturer): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if ($manufacturer['logo_url']): ?>
                                    <img src="<?= url($manufacturer['logo_url']); ?>" 
                                         class="rounded-circle me-3" 
                                         style="width: 40px; height: 40px; object-fit: cover;" 
                                         alt="<?php echo htmlspecialchars($manufacturer['name']); ?>">
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($manufacturer['name']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $manufacturer['total_products']; ?> produtos</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEditarFabricante" 
                                            data-id="<?php echo $manufacturer['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($manufacturer['name']); ?>"
                                            data-logo="<?php echo htmlspecialchars(!empty($manufacturer['logo_url']) ? url($manufacturer['logo_url']) : ''); ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($manufacturer['total_products'] == 0): ?>
                                    <button type="button" class="btn btn-danger btn-sm"
                                           data-bs-toggle="modal" 
                                           data-bs-target="#confirmDeleteModal" 
                                           data-id="<?php echo $manufacturer['id']; ?>"
                                           data-name="<?php echo htmlspecialchars($manufacturer['name']); ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-danger" disabled 
                                            data-bs-toggle="tooltip" 
                                            title="Não é possível excluir um fabricante com produtos cadastrados">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <img src="/assets/img/no-data.svg" alt="Sem dados" style="width: 120px; opacity: 0.5;">
                <h3 class="mt-4">Nenhum fabricante encontrado</h3>
                <p class="text-muted">Clique no botão acima para adicionar um novo fabricante.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Novo Fabricante -->
    <div class="modal fade" id="modalNovoFabricante" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="<?= url('/pages/fabricantes/salvar.php'); ?>" method="post" enctype="multipart/form-data">
                    <?= function_exists('campoCsrf') ? campoCsrf() : ''; ?>
                    <div class="modal-header">
                        <h5 class="modal-title">Novo Fabricante</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome do Fabricante *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="logo" class="form-label">Logo do Fabricante</label>
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
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

    <!-- Modal Editar Fabricante -->
    <div class="modal fade" id="modalEditarFabricante" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="<?= url('/pages/fabricantes/atualizar.php'); ?>" method="post" enctype="multipart/form-data">
                    <?= function_exists('campoCsrf') ? campoCsrf() : ''; ?>
                    <input type="hidden" id="editar_id" name="id">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Fabricante</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editar_name" class="form-label">Nome do Fabricante *</label>
                            <input type="text" class="form-control" id="editar_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editar_logo" class="form-label">Logo do Fabricante</label>
                            <input type="file" class="form-control" id="editar_logo" name="logo" accept="image/*">
                            <div id="preview_logo" class="mt-2"></div>
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEditarFabricante = document.getElementById('modalEditarFabricante');
    if (modalEditarFabricante) {
        modalEditarFabricante.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const logo = button.getAttribute('data-logo');
            
            document.getElementById('editar_id').value = id;
            document.getElementById('editar_name').value = name;
            
            const previewDiv = document.getElementById('preview_logo');
            if (logo) {
                previewDiv.innerHTML = `<img src="${logo}" class="img-thumbnail" style="max-height: 100px;">`;
            } else {
                previewDiv.innerHTML = '';
            }
        });
    }

    // Modal de exclusão de fabricante via POST
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    if (confirmDeleteModal) {
        confirmDeleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const itemToDelete = document.getElementById('itemToDelete');
            if (itemToDelete) itemToDelete.textContent = `o fabricante "${name}"`;
            document.getElementById('confirmDeleteId').value = id;
        });
    }
});
</script>

<!-- Modal Confirmar Exclusão Fabricante -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="confirmDeleteForm" action="<?= url('/pages/fabricantes/excluir.php'); ?>" method="post">
                <?= function_exists('campoCsrf') ? campoCsrf() : ''; ?>
                <input type="hidden" id="confirmDeleteId" name="id" value="">
                <input type="hidden" id="confirmDeleteArquivo" name="arquivo" value="">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir <strong id="itemToDelete"></strong>?</p>
                    <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>Esta ação não poderá ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Excluir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>