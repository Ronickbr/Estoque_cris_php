<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/funcoes.php';

// Verificar se há título definido na página
$titulo = isset($titulo) ? $titulo . ' - Sistema de Estoque' : 'Sistema de Estoque';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <!-- CSS Personalizado -->
    <link href="<?php echo function_exists('url') ? url('/assets/css/style.css') : '/assets/css/style.css'; ?>?v=<?php echo @filemtime(__DIR__ . '/../assets/css/style.css') ?: '2.1'; ?>" rel="stylesheet">
</head>
<body>
    <?php if (estaLogado()): 
        $currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
        $isDash = (strpos($currentScript, 'index.php') !== false && strpos($currentScript, 'pages/') === false);
        $isProd = (strpos($currentScript, 'pages/produtos') !== false);
        $isFabr = (strpos($currentScript, 'pages/fabricantes') !== false);
        $isRela = (strpos($currentScript, 'pages/relatorios') !== false);
        $isBack = (strpos($currentScript, 'pages/backup') !== false);
        $isPerf = (strpos($currentScript, 'pages/perfil') !== false);
    ?>
    <nav class="navbar-mobile">
        <button class="navbar-toggler" type="button" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="navbar-brand">
            <i class="bi bi-boxes me-2 text-info"></i> Sistema de Estoque
        </div>
    </nav>

    <div class="sidebar" id="sidebar">
        <div class="logo">
            <i class="bi bi-boxes"></i> Estoque Pro
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $isDash ? 'active' : ''; ?>" href="<?php echo function_exists('url') ? url('/index.php') : '/index.php'; ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $isProd ? 'active' : ''; ?>" href="<?php echo function_exists('url') ? url('/pages/produtos/index.php') : '/pages/produtos/index.php'; ?>"><i class="bi bi-box-seam"></i> Produtos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $isFabr ? 'active' : ''; ?>" href="<?php echo function_exists('url') ? url('/pages/fabricantes/index.php') : '/pages/fabricantes/index.php'; ?>"><i class="bi bi-building"></i> Fabricantes</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $isRela ? 'active' : ''; ?>" href="<?php echo function_exists('url') ? url('/pages/relatorios/index.php') : '/pages/relatorios/index.php'; ?>"><i class="bi bi-file-earmark-bar-graph"></i> Relatórios</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $isBack ? 'active' : ''; ?>" href="<?php echo function_exists('url') ? url('/pages/backup/index.php') : '/pages/backup/index.php'; ?>"><i class="bi bi-database-fill-check"></i> Backup</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $isPerf ? 'active' : ''; ?>" href="<?php echo function_exists('url') ? url('/pages/perfil/index.php') : '/pages/perfil/index.php'; ?>"><i class="bi bi-person"></i> Perfil</a>
            </li>
        </ul>
        <div class="user-section">
            <a class="nav-link" href="<?php echo function_exists('url') ? url('/logout.php') : '/logout.php'; ?>">
                <i class="bi bi-box-arrow-right"></i> <?php echo htmlspecialchars($_SESSION['usuario_nome'] ?? ''); ?> (Sair)
            </a>
        </div>
    </div>

    <?php 
        // Lógica de Notificações
        $todasNotificacoes = [];
        $notificacoes = [];
        $qtdNaoLidas = 0;
        if (file_exists(__DIR__ . '/../src/Services/NotificationService.php')) {
            require_once __DIR__ . '/../src/Services/NotificationService.php';
            if (isset($conn)) {
                try {
                    $notifService = new Estoque\Services\NotificationService($conn);
                    $todasNotificacoes = $notifService->getNotifications(50);
                    $notificacoes = array_slice($todasNotificacoes, 0, 5);
                    $qtdNaoLidas = $notifService->countUnread();
                } catch (Exception $e) { /* Silenciar erro se serviço falhar */ }
            }
        }
    ?>

    <div class="main-wrapper" id="mainWrapper">
        <!-- Topbar Executiva Limpa e Fixa -->
        <header class="app-topbar">
            <div class="topbar-left d-flex align-items-center">
                <button class="navbar-toggler-btn d-lg-none me-3" type="button" onclick="toggleSidebar()" aria-label="Menu Lateral">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div class="topbar-title-section d-flex align-items-center">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 me-2 d-none d-sm-inline-flex align-items-center">
                        <i class="bi bi-shield-check me-1"></i>Painel Seguro
                    </span>
                    <span class="text-muted small d-none d-md-inline">Sistema de Gestão & Estoque</span>
                </div>
            </div>

            <div class="topbar-actions">
                <!-- Dropdown de Notificações -->
                <div class="dropdown notification-dropdown">
                    <a href="#" class="notification-bell" data-bs-toggle="dropdown" aria-expanded="false" title="Notificações">
                        <i class="bi bi-bell-fill"></i>
                        <?php if ($qtdNaoLidas > 0): ?>
                            <span class="badge rounded-pill bg-danger notification-badge">
                                <?php echo $qtdNaoLidas > 99 ? '99+' : $qtdNaoLidas; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end notification-list p-0 shadow border-0" style="min-width: 320px;">
                        <li class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                            <h6 class="m-0 fw-bold"><i class="bi bi-bell me-1"></i>Notificações</h6>
                            <?php if ($qtdNaoLidas > 0): ?>
                                <small><a href="#" class="text-decoration-none text-success fw-medium" onclick="markAllRead(event)">Marcar lidas</a></small>
                            <?php endif; ?>
                        </li>
                        <?php if (empty($notificacoes)): ?>
                            <li class="p-4 text-center text-muted">
                                <i class="bi bi-bell-slash fs-4 d-block mb-2 text-secondary"></i>
                                Nenhuma notificação
                            </li>
                        <?php else: ?>
                            <?php foreach ($notificacoes as $notif): 
                                $tipoNotif = $notif['type'] ?? 'info';
                                $iconClass = ($tipoNotif === 'danger') ? 'bi-exclamation-circle-fill text-danger' : 
                                            (($tipoNotif === 'success') ? 'bi-check-circle-fill text-success' : 
                                            (($tipoNotif === 'warning') ? 'bi-exclamation-circle-fill text-warning' : 'bi-info-circle-fill text-primary'));
                            ?>
                                <li>
                                    <a class="dropdown-item notification-item <?= empty($notif['is_read']) ? 'unread' : ''; ?> py-2 notif-clickable" 
                                       href="javascript:void(0)"
                                       data-notif-id="<?= $notif['id']; ?>"
                                       data-notif-type="<?= $tipoNotif; ?>"
                                       data-notif-message="<?= htmlspecialchars($notif['message'], ENT_QUOTES, 'UTF-8'); ?>"
                                       data-notif-date="<?= date('d/m/Y \à\s H:i', strtotime($notif['created_at'])); ?>"
                                       data-notif-link="<?= !empty($notif['link']) ? url($notif['link']) : ''; ?>"
                                       data-notif-read="<?= !empty($notif['is_read']) ? '1' : '0'; ?>">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-grow-1">
                                                <i class="bi <?= $iconClass; ?> me-1"></i>
                                                <span class="small fw-medium text-dark"><?= htmlspecialchars($notif['message']); ?></span>
                                                <small class="d-block text-muted mt-1"><i class="bi bi-clock me-1"></i><?= date('d/m H:i', strtotime($notif['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <li class="p-2 border-top bg-light text-center">
                            <button type="button" class="btn btn-sm btn-link text-decoration-none fw-semibold w-100 py-1" data-bs-toggle="modal" data-bs-target="#modalNotificacoes">
                                <i class="bi bi-arrows-fullscreen me-1"></i> Ver todas no modal
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Divisor -->
                <div class="topbar-divider d-none d-sm-block"></div>
                
                <!-- Dropdown de Usuário (Desktop) -->
                <div class="dropdown">
                    <a href="#" class="topbar-user-btn dropdown-toggle text-dark" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="topbar-avatar me-2">
                            <?php echo strtoupper(substr($_SESSION['usuario_nome'] ?? 'U', 0, 1)); ?>
                        </div>
                        <span class="fw-semibold text-dark small d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário'); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border shadow-sm" style="border-radius: var(--border-radius); border-color: var(--slate-200);">
                        <li><a class="dropdown-item py-2" href="<?php echo function_exists('url') ? url('/pages/perfil/index.php') : '/pages/perfil/index.php'; ?>"><i class="bi bi-person me-2 text-muted"></i>Perfil</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="<?php echo function_exists('url') ? url('/logout.php') : '/logout.php'; ?>"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="content" id="content">
    <?php endif; ?>

    <!-- Modal de Confirmação de Exclusão (POST + CSRF) -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="confirmDeleteForm" action="">
                <?php if (function_exists('campoCsrf')) { echo campoCsrf(); } ?>
                <input type="hidden" name="id" id="confirmDeleteId" value="">
                <input type="hidden" name="arquivo" id="confirmDeleteArquivo" value="">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-danger-subtle text-danger">
                        <h5 class="modal-title fw-bold" id="confirmDeleteModalLabel">
                            <i class="bi bi-trash-fill me-2"></i>Confirmar Exclusão
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body py-4">
                        <p class="fs-6 mb-2">Tem certeza que deseja excluir <span id="itemToDelete" class="fw-bold text-dark">este item</span>?</p>
                        <p class="text-danger small mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Esta ação é definitiva e não poderá ser desfeita.
                        </p>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger px-4" id="confirmDeleteBtn">
                            <i class="bi bi-trash me-1"></i>Excluir
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Confirmação de Restauração de Backup (POST + CSRF) -->
    <div class="modal fade" id="confirmRestoreModal" tabindex="-1" aria-labelledby="confirmRestoreModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="confirmRestoreForm" action="">
                <?php if (function_exists('campoCsrf')) { echo campoCsrf(); } ?>
                <input type="hidden" name="arquivo" id="confirmRestoreArquivo" value="">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-warning-subtle text-dark">
                        <h5 class="modal-title fw-bold" id="confirmRestoreModalLabel">
                            <i class="bi bi-arrow-counterclockwise text-warning me-2"></i>Confirmar Restauração
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body py-4">
                        <p class="fs-6 mb-3">Tem certeza que deseja restaurar o backup <span id="itemToRestore" class="fw-bold text-dark">este backup</span>?</p>
                        <div class="alert alert-warning d-flex align-items-center mb-0 py-2">
                            <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-warning flex-shrink-0"></i>
                            <div class="small">
                                <strong>Atenção:</strong> O banco de dados atual será completamente sobrescrito com os dados deste arquivo.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning px-4 fw-medium" id="confirmRestoreBtn">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (estaLogado()): ?>
    <!-- Modal Central de Notificações Dedicado -->
    <div class="modal fade" id="modalNotificacoes" tabindex="-1" aria-labelledby="modalNotificacoesLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light py-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 me-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-bell-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-dark" id="modalNotificacoesLabel">Central de Notificações</h5>
                            <small class="text-muted">Acompanhe alertas de estoque, movimentações e avisos do sistema</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <?php if ($qtdNaoLidas > 0): ?>
                        <button type="button" class="btn btn-sm btn-outline-success fw-medium" onclick="markAllRead(event)" id="btnMarcarTodasLidasModal">
                            <i class="bi bi-check2-all me-1"></i> Marcar todas como lidas
                        </button>
                        <?php endif; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                </div>

                <!-- Barra de Filtros Rápidos -->
                <div class="bg-white px-3 py-2 border-bottom d-flex gap-2 flex-wrap align-items-center">
                    <span class="small text-muted me-1 fw-medium">Filtrar:</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary active notif-filter-btn" data-filter="all">
                        Todas <span class="badge bg-secondary ms-1"><?= count($todasNotificacoes); ?></span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary notif-filter-btn" data-filter="unread">
                        Não lidas <span class="badge bg-danger ms-1 notification-badge-count"><?= $qtdNaoLidas; ?></span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary notif-filter-btn" data-filter="danger">
                        Alertas Críticos <span class="badge bg-danger ms-1"><?= count(array_filter($todasNotificacoes, fn($n) => ($n['type'] ?? '') === 'danger')); ?></span>
                    </button>
                </div>

                <div class="modal-body p-0">
                    <?php if (empty($todasNotificacoes)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-bell-slash text-muted" style="font-size: 3.5rem;"></i>
                            <h5 class="mt-3 text-secondary">Nenhuma notificação encontrada</h5>
                            <p class="text-muted small">Você está em dia com todos os alertas do estoque.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush" id="modalNotificacoesList">
                            <?php foreach ($todasNotificacoes as $notif): 
                                $isUnread = empty($notif['is_read']);
                                $tipo = $notif['type'] ?? 'info';
                                $bgClass = $isUnread ? 'bg-light bg-opacity-75' : '';
                                $iconClass = 'bi-info-circle-fill text-primary';
                                $badgeClass = 'bg-primary';
                                $badgeLabel = 'Informativo';

                                if ($tipo === 'danger') {
                                    $iconClass = 'bi-exclamation-triangle-fill text-danger';
                                    $badgeClass = 'bg-danger';
                                    $badgeLabel = 'Crítico';
                                } elseif ($tipo === 'warning') {
                                    $iconClass = 'bi-exclamation-circle-fill text-warning';
                                    $badgeClass = 'bg-warning text-dark';
                                    $badgeLabel = 'Alerta';
                                } elseif ($tipo === 'success') {
                                    $iconClass = 'bi-check-circle-fill text-success';
                                    $badgeClass = 'bg-success';
                                    $badgeLabel = 'Sucesso';
                                }
                            ?>
                            <div class="list-group-item p-3 modal-notif-item <?= $bgClass; ?> <?= $isUnread ? 'is-unread' : ''; ?> notif-clickable" 
                                 style="cursor: pointer; transition: background-color 0.2s;"
                                 data-type="<?= $tipo; ?>" 
                                 data-id="<?= $notif['id']; ?>"
                                 data-notif-id="<?= $notif['id']; ?>"
                                 data-notif-type="<?= $tipo; ?>"
                                 data-notif-message="<?= htmlspecialchars($notif['message'], ENT_QUOTES, 'UTF-8'); ?>"
                                 data-notif-date="<?= date('d/m/Y \à\s H:i', strtotime($notif['created_at'])); ?>"
                                 data-notif-link="<?= !empty($notif['link']) ? url($notif['link']) : ''; ?>"
                                 data-notif-read="<?= !empty($notif['is_read']) ? '1' : '0'; ?>">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="flex-shrink-0 mt-1">
                                        <i class="bi <?= $iconClass; ?> fs-4"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge <?= $badgeClass; ?>"><?= $badgeLabel; ?></span>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i><?= date('d/m/Y \à\s H:i', strtotime($notif['created_at'])); ?>
                                            </small>
                                        </div>
                                        <p class="mb-2 text-dark fw-medium fs-6 notif-msg">
                                            <?= htmlspecialchars($notif['message']); ?>
                                        </p>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="text-primary small fw-semibold">
                                                <i class="bi bi-eye me-1"></i> Clique para ver detalhes
                                            </span>
                                            <?php if ($isUnread): ?>
                                            <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none btn-mark-single ms-2" style="font-size: 0.8rem;" onclick="markSingleRead(event, <?= $notif['id']; ?>)">
                                                <i class="bi bi-check me-1"></i> Marcar como lida
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Card de Detalhes Exclusivo de Notificação -->
    <div class="modal fade" id="modalDetalheNotificacao" tabindex="-1" aria-labelledby="modalDetalheNotificacaoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom py-3" id="notifDetailHeader">
                    <div class="d-flex align-items-center">
                        <div id="notifDetailIconWrapper" class="rounded-circle p-2 me-2 d-flex align-items-center justify-content-center text-white" style="width: 42px; height: 42px;">
                            <i id="notifDetailIcon" class="bi bi-bell-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-dark" id="modalDetalheNotificacaoLabel">Detalhes do Alerta</h5>
                            <small id="notifDetailDate" class="text-muted"><i class="bi bi-clock me-1"></i></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span id="notifDetailBadge" class="badge px-3 py-2 fs-6"></span>
                        <span id="notifDetailStatus" class="badge px-3 py-2"></span>
                    </div>
                    
                    <div class="card bg-light border-0 p-3 mb-3 rounded-3">
                        <div class="text-muted small text-uppercase fw-bold mb-2 d-flex align-items-center">
                            <i class="bi bi-info-circle me-1"></i> Mensagem do Sistema
                        </div>
                        <p id="notifDetailMessage" class="fs-6 text-dark mb-0 fw-semibold lh-base"></p>
                    </div>

                    <div id="notifDetailActionWrapper" class="mt-4">
                        <a id="notifDetailActionBtn" href="#" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                            <i class="bi bi-box-arrow-up-right me-2"></i>Acessar Página Relacionada
                        </a>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnVoltarListaNotif">
                        <i class="bi bi-arrow-left me-1"></i> Todas as notificações
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-success btn-sm" id="btnMarcarLidaDetail" onclick="markCurrentDetailRead()">
                            <i class="bi bi-check2 me-1"></i>Marcar como lida
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
