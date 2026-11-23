<?php
$titulo = 'Dashboard';
require_once __DIR__ . '/includes/funcoes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

// Verificar se usuário está logado
verificarLogin();

// Carregar Serviço de Dashboard
// Verifica se o autoloader do composer existe, senão carrega manual
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    require_once __DIR__ . '/src/Services/DashboardService.php';
    require_once __DIR__ . '/src/Services/NotificationService.php';
}

use Estoque\Services\DashboardService;
use Estoque\Services\NotificationService;

try {
    $dashboardService = new DashboardService($conn);
    
    // Inicializar e verificar notificações
    $notificationService = new NotificationService($conn);
    $notificationService->checkStockAlerts($dashboardService);

    // Obter resumo do dashboard em uma única chamada otimizada
    $dadosDashboard = $dashboardService->getDashboardSummary();

    $vendasDiarias = $dadosDashboard['vendas_diarias'];
    $vendasHoje = $dadosDashboard['vendas_hoje'];
    $vendasSemana = $dadosDashboard['vendas_semana'];
    $vendasMes = $dadosDashboard['vendas_mes'];
    $diaMaisVendas = $dadosDashboard['dia_mais_vendas'];
    $diaMaisVendasNome = $diaMaisVendas['nome_pt'];
    $produtosMaisVendidosMes = $dadosDashboard['produtos_top_mes'];
    $estoqueBaixo = $dadosDashboard['estoque_baixo'];
    $estoqueBaixoPorFabricante = $dadosDashboard['estoque_baixo_por_fabricante'] ?? [];

} catch (Exception $e) {
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao buscar dados: ' . $e->getMessage());
}
?>



    <?php 
    // Exibir mensagens de sessão se existirem
    if (isset($_SESSION['mensagem'])) {
        echo $_SESSION['mensagem'];
        unset($_SESSION['mensagem']);
    }
    ?>

    <!-- Header do Dashboard -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 text-dark fw-bold"><i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small text-muted">
                    <li class="breadcrumb-item"><a href="<?php echo function_exists('url') ? url('/index.php') : '/index.php'; ?>">Início</a></li>
                    <li class="breadcrumb-item active text-dark fw-medium">Visão Geral do Estoque</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="<?php echo function_exists('url') ? url('/pages/produtos/index.php') : '/pages/produtos/index.php'; ?>" class="btn btn-primary px-3 py-2 fw-medium shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Gerenciar Produtos
            </a>
        </div>
    </div>

    <!-- Cards de Resumo Executivo -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card kpi-success h-100 mb-0">
                <div class="card-body">
                    <div class="icon-wrapper">
                        <i class="bi bi-cart-check"></i>
                    </div>
                    <div>
                        <h5 class="card-title">Vendas Hoje</h5>
                        <h3><?php echo $vendasHoje; ?></h3>
                        <p class="card-text">Últimas 24 horas</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card h-100 mb-0">
                <div class="card-body">
                    <div class="icon-wrapper">
                        <i class="bi bi-calendar-week"></i>
                    </div>
                    <div>
                        <h5 class="card-title">Vendas na Semana</h5>
                        <h3><?php echo $vendasSemana; ?></h3>
                        <p class="card-text">Últimos 7 dias</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card h-100 mb-0">
                <div class="card-body">
                    <div class="icon-wrapper">
                        <i class="bi bi-calendar-month"></i>
                    </div>
                    <div>
                        <h5 class="card-title">Vendas no Mês</h5>
                        <h3><?php echo $vendasMes; ?></h3>
                        <p class="card-text">Últimos 30 dias</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card kpi-warning h-100 mb-0">
                <div class="card-body">
                    <div class="icon-wrapper">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div>
                        <h5 class="card-title">Melhor Dia</h5>
                        <h3 class="fs-4"><?php echo $diaMaisVendasNome; ?></h3>
                        <p class="card-text text-primary fw-semibold"><?php echo $diaMaisVendas['total'] ?? 0; ?> vendas registradas</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row g-3 mb-4">
        <!-- Gráfico de Evolução de Vendas -->
        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Evolução de Vendas (30 Dias)</h5>
                </div>
                <div class="card-body position-relative p-3" style="min-height: 290px; height: 290px;">
                    <canvas id="chartVendasDiarias"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Top Produtos -->
        <div class="col-12 col-lg-4">
             <div class="card h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-trophy text-warning me-2"></i>Top 5 Produtos (Mês)</h5>
                </div>
                <div class="card-body position-relative p-3" style="min-height: 290px; height: 290px;">
                    <canvas id="chartTopProdutos"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerta de Estoque Baixo -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-danger text-white d-flex align-items-center justify-content-between py-3 rounded-top flex-wrap gap-2">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <h4 class="mb-0 fs-5 fw-bold">Alertas de Estoque Crítico</h4>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-light text-danger fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNotificacoes" title="Abrir Central de Notificações">
                    <i class="bi bi-bell-fill me-1"></i> Ver Notificações no Modal
                </button>
                <span class="badge bg-white text-danger fw-bold">
                    <?= count($estoqueBaixo); ?> item(ns) requerem atenção
                </span>
            </div>
        </div>
        <div class="card-body bg-light">
            <div class="row">
                <?php if (!empty($estoqueBaixoPorFabricante)): ?>
                    <?php foreach ($estoqueBaixoPorFabricante as $fabData): ?>
                    <div class="col-12 col-md-6 col-lg-4 mb-4">
                        <div class="manufacturer-card h-100 bg-white shadow-sm border">
                            <div class="manufacturer-header p-3 text-white" style="background-color: <?= $fabData['cor']; ?>;">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($fabData['logo_url'])): ?>
                                        <img src="<?= url($fabData['logo_url']); ?>" alt="<?= htmlspecialchars($fabData['nome']); ?>" class="rounded-circle me-2 bg-white p-1" style="width: 32px; height: 32px; object-fit: cover;">
                                        <?php else: ?>
                                        <i class="bi bi-building me-2 fs-5"></i>
                                        <?php endif; ?>
                                        <h5 class="mb-0 fw-bold text-truncate" style="max-width: 180px;" title="<?= htmlspecialchars($fabData['nome']); ?>">
                                            <?= htmlspecialchars($fabData['nome']); ?>
                                        </h5>
                                    </div>
                                    <span class="badge bg-light text-dark">
                                        <?= count($fabData['produtos']); ?> produto(s)
                                    </span>
                                </div>
                                <div class="small d-flex gap-2 text-white-50 mt-1">
                                    <?php if ($fabData['total_zerados'] > 0): ?>
                                    <span class="badge bg-danger text-white">
                                        <i class="bi bi-x-circle me-1"></i><?= $fabData['total_zerados']; ?> esgotado(s)
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($fabData['total_baixo'] > 0): ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-exclamation-circle me-1"></i><?= $fabData['total_baixo']; ?> em alerta
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($fabData['produtos'] as $produto): 
                                        $isZerado = ((int)$produto['stock'] === 0);
                                    ?>
                                        <div class="list-group-item product-item border-bottom pb-2 pt-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="product-name fw-medium text-dark text-truncate me-2" title="<?= htmlspecialchars($produto['name']); ?>">
                                                    <?= htmlspecialchars($produto['name']); ?>
                                                </span>
                                                <?php if ($isZerado): ?>
                                                <span class="badge bg-danger text-white text-nowrap">
                                                    <i class="bi bi-slash-circle me-1"></i>Esgotado (0 un)
                                                </span>
                                                <?php else: ?>
                                                <span class="badge bg-warning text-dark text-nowrap">
                                                    <?= $produto['stock']; ?> un (Mín: <?= $produto['min_stock']; ?>)
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            <!-- Barra de nível de estoque -->
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar <?= $isZerado ? 'bg-danger' : 'bg-warning'; ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= $isZerado ? 100 : min((($produto['stock'] / max($produto['min_stock'] * 2, 1)) * 100), 100); ?>%">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-check-circle-fill text-success fs-1"></i>
                        <h4 class="mt-3 text-muted">Estoque Normalizado!</h4>
                        <p class="text-muted">Nenhum produto com estoque zerado ou abaixo do estoque mínimo.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<!-- Chart.js Libraries (Vendor local com fallback CDN) -->
<script src="<?php echo function_exists('url') ? url('/assets/js/vendor/chart.umd.min.js') : '/assets/js/vendor/chart.umd.min.js'; ?>"></script>
<script src="<?php echo function_exists('url') ? url('/assets/js/vendor/chartjs-plugin-datalabels.min.js') : '/assets/js/vendor/chartjs-plugin-datalabels.min.js'; ?>"></script>
<script src="<?php echo function_exists('url') ? url('/assets/js/dashboard.js') : '/assets/js/dashboard.js'; ?>"></script>
<script>
    (function() {
        function renderCharts() {
            const vendasDiarias = <?php echo json_encode($vendasDiarias ?? []); ?>;
            const topProdutos = <?php echo json_encode($produtosMaisVendidosMes ?? []); ?>;
            if (typeof initDashboardCharts === 'function') {
                initDashboardCharts(vendasDiarias, topProdutos);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', renderCharts);
        } else {
            renderCharts();
        }
    })();
</script>

<?php
// Incluir rodapé
include __DIR__ . '/includes/footer.php';
?>
