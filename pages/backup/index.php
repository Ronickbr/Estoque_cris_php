<?php
$titulo = 'Backup e Restauração';
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar se usuário está logado e é admin
verificarLogin();

// Atualizar a definição do diretório de backup
$diretorioBackup = dirname(__DIR__, 2) . '/backups/';

// Verificar e criar o diretório se não existir
if (!file_exists($diretorioBackup)) {
    mkdir($diretorioBackup, 0755, true);
}

// Mensagem de status
$status = '';

// Adicione esta função de formatação de bytes no início do arquivo
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Incluir cabeçalho
include __DIR__ . '/../../includes/header.php';
?>

<link href="/estoque/assets/css/style.css" rel="stylesheet">

<style>
/* Estilos adicionais para melhorar a responsividade e centralização */
.alert {
    font-size: 0.9rem;
}
.alert i {
    font-size: 1rem;
}
.card-header h5 {
    font-size: 1.25rem;
}
.card-body p {
    font-size: 0.9rem;
}
.form-label {
    font-size: 0.9rem;
}
.form-check-label {
    font-size: 0.85rem;
}
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
.card {
    max-width: 100%;
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
    .content .col-12 {
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
    .card-body p {
        font-size: 0.85rem;
    }
    .alert {
        font-size: 0.85rem;
    }
    .alert i {
        font-size: 0.9rem;
    }
    .form-label {
        font-size: 0.85rem;
    }
    .form-check-label {
        font-size: 0.8rem;
    }
    .btn-lg {
        font-size: 0.85rem;
        padding: 0.5rem 1rem;
    }
    .btn-sm {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
    .btn-sm i {
        font-size: 0.8rem;
    }
    .table th, .table td {
        font-size: 0.85rem;
        padding: 0.5rem;
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
}

@media (max-width: 575.98px) {
    .content .col-12 {
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
    .card-body p {
        font-size: 0.8rem;
    }
    .alert {
        font-size: 0.8rem;
    }
    .form-label {
        font-size: 0.8rem;
    }
    .form-check-label {
        font-size: 0.75rem;
    }
    .table th, .table td {
        font-size: 0.8rem;
        padding: 0.4rem;
    }
    .text-center h3 {
        font-size: 1.1rem;
    }
    .text-center p {
        font-size: 0.8rem;
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
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h1 class="mb-0 text-dark fw-bold"><i class="bi bi-database-fill-check me-2"></i>Backup e Restauração</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/estoque/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Backup</li>
                </ol>
            </nav>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="/estoque/index.php" class="btn btn-secondary btn-lg">
                <i class="bi bi-arrow-left"></i> Voltar para Dashboard
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Card de Backup -->
        <div class="col-12 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0 font-weight-bold text-primary">
                        <i class="bi bi-download me-2"></i>Backup do Banco de Dados
                    </h5>
                </div>
                <div class="card-body">
                    <p>Crie um backup completo do banco de dados para uso posterior ou por segurança.</p>
                    <p>O backup será gerado como arquivo SQL e disponibilizado para download.</p>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i> O processo pode levar alguns segundos dependendo do tamanho do banco de dados.
                    </div>
                    
                    <a href="/estoque/pages/backup/gerar_backup.php" class="btn btn-success btn-lg">
                        <i class="bi bi-download"></i> Gerar Backup
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Card de Restauração -->
        <div class="col-12 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0 font-weight-bold text-primary">
                        <i class="bi bi-upload me-2"></i>Restaurar Banco de Dados
                    </h5>
                </div>
                <div class="card-body">
                    <p>Restaure o banco de dados a partir de um arquivo de backup SQL ou JSON.</p>
                    <p>Atenção: Este processo substituirá todos os dados atuais!</p>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i> <strong>Aviso!</strong> A restauração apagará todos os dados atuais do sistema. Certifique-se de ter um backup recente antes de continuar.
                    </div>
                    
                    <form action="/estoque/pages/backup/restaurar_backup.php" method="post" enctype="multipart/form-data" class="mt-3">
                        <div class="mb-3">
                            <label for="arquivo_backup" class="form-label">Arquivo de Backup (SQL ou JSON)</label>
                            <input type="file" class="form-control" id="arquivo_backup" name="arquivo_backup" accept=".sql,.json" required>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmar" name="confirmar" required>
                            <label class="form-check-label" for="confirmar">
                                Confirmo que entendo os riscos e desejo prosseguir com a restauração
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-upload"></i> Restaurar Backup
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Histórico de Backups -->
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <h5 class="mb-0 font-weight-bold text-primary">
                        <i class="bi bi-list-ul me-2"></i>Backups Disponíveis
                    </h5>
                    <a href="/estoque/pages/backup/limpar_backups.php" 
                       class="btn btn-sm btn-outline-danger mt-2 mt-md-0"
                       data-delete
                       data-item-name="todos os backups antigos"
                       title="Limpar Backups Antigos">
                        <i class="bi bi-trash"></i> Limpar Backups Antigos
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php
                    // Verificar se existem arquivos
                    $arquivos = glob($diretorioBackup . '*.sql');
                    
                    // Debug para verificar o caminho e arquivos encontrados
                    error_log('Diretório de backup: ' . $diretorioBackup);
                    error_log('Arquivos encontrados: ' . print_r($arquivos, true));
                    
                    if (!empty($arquivos)):
                    ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nome do Arquivo</th>
                                    <th>Tipo</th>
                                    <th>Tamanho</th>
                                    <th>Data de Criação</th>
                                    <th width="120">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($arquivos as $arquivo): 
                                    $info = pathinfo($arquivo);
                                    $nome = $info['basename'];
                                    $tipo = strtoupper($info['extension']);
                                    $tamanho = filesize($arquivo);
                                    $data = filemtime($arquivo);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($nome); ?></td>
                                    <td><?php echo $tipo; ?></td>
                                    <td><?php echo formatBytes($tamanho); ?></td>
                                    <td><?php echo date('d/m/Y H:i:s', $data); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="/estoque/pages/backup/download_backup.php?arquivo=<?php echo urlencode($nome); ?>" 
                                               class="btn btn-primary btn-sm"
                                               data-bs-toggle="tooltip" 
                                               title="Download">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <a href="/estoque/pages/backup/restaurar_backup.php?arquivo=<?php echo urlencode($nome); ?>" 
                                               class="btn btn-warning btn-sm restaurar-btn"
                                               data-bs-toggle="tooltip" 
                                               title="Restaurar backup">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </a>
                                            <a href="/estoque/pages/backup/excluir_backup.php?arquivo=<?php echo urlencode($nome); ?>" 
                                               class="btn btn-danger btn-sm excluir-btn"
                                               data-bs-toggle="tooltip" 
                                               title="Excluir">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <img src="/estoque/assets/img/no-data.svg" alt="Sem dados" style="width: 120px; opacity: 0.5;">
                        <h3 class="mt-4">Nenhum backup disponível</h3>
                        <p class="text-muted">Clique no botão acima para gerar um novo backup.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir rodapé
include __DIR__ . '/../../includes/footer.php';
?>