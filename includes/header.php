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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- CSS Personalizado -->
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
        }
        .navbar-mobile {
            display: none;
            background: #fff;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        .navbar-mobile .navbar-toggler {
            border: none;
            font-size: 1.5rem;
            color: #28a745;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #ffffff, #e9ecef);
            border-right: 1px solid #dee2e6;
            padding: 1rem 0;
            position: fixed;
            top: 0;
            bottom: 0;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
            z-index: 999;
        }
        .sidebar.collapsed {
            transform: translateX(-100%);
        }
        .sidebar .logo {
            display: flex;
            align-items: center;
            padding: 1rem;
            font-size: 1.25rem;
            font-weight: 600;
            color: #28a745;
        }
        .sidebar .nav-link {
            color: #495057;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover {
            background-color: #28a745;
            color: #fff;
            transform: translateX(5px);
        }
        .sidebar .nav-link i {
            margin-right: 0.5rem;
        }
        .sidebar .user-section {
            position: absolute;
            bottom: 1rem;
            width: 100%;
            padding: 0 1rem;
        }
        .content {
            margin-left: 250px;
            padding: 2rem;
            flex: 1;
            transition: margin-left 0.3s ease;
        }
        .content.collapsed {
            margin-left: 0;
        }
        .content .row {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Estilização Geral para Cards */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background: #fff;
        }
        .card-header {
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
        }
        .card-body {
            padding: 1.5rem;
        }

        /* Estilização dos Cards de Resumo (Dashboard) */
        .summary-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
        }
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        .summary-card .card-body {
            padding: 1.5rem;
            display: flex;
            align-items: center;
        }
        .summary-card .icon-wrapper {
            font-size: 2.5rem;
            color: #28a745;
            margin-right: 1rem;
        }
        .summary-card h5 {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .summary-card h3 {
            font-size: 2rem;
            font-weight: 600;
            color: #343a40;
            margin: 0;
        }
        .summary-card p {
            font-size: 0.9rem;
            color: #adb5bd;
            margin: 0;
        }

        /* Estilização dos Produtos Mais Vendidos (Dashboard) */
        .best-seller-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background: #fff;
            transition: transform 0.3s ease;
        }
        .best-seller-card:hover {
            transform: translateY(-5px);
        }
        .best-seller-card .card-body {
            padding: 1.5rem;
        }
        .best-seller-card h5 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 1rem;
        }
        .best-seller-card .list-group-item {
            border: none;
            padding: 0.5rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .best-seller-card .progress {
            height: 8px;
            margin-top: 0.5rem;
            background-color: #e9ecef;
        }
        .best-seller-card .progress-bar {
            background: linear-gradient(90deg, #28a745, #20c997);
        }
        .best-seller-card .badge {
            background: linear-gradient(90deg, #007bff, #0056b3);
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            transition: transform 0.3s ease;
        }
        .best-seller-card .badge:hover {
            transform: scale(1.1);
        }

        /* Estilização do Alerta de Estoque Baixo (Dashboard) */
        .stock-alert-card {
            border: 2px solid #dc3545;
            border-radius: 15px;
            background: #fff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .stock-alert-card .card-header {
            border-bottom: none;
            padding: 1rem 1.5rem;
        }
        .stock-alert-card h5 {
            font-size: 1.25rem;
            font-weight: 600;
        }
        .stock-alert-card .card-body {
            padding: 1.5rem;
        }
        .stock-alert-card .manufacturer-card {
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #fff, #f8f9fa);
            transition: transform 0.3s ease;
        }
        .stock-alert-card .manufacturer-card:hover {
            transform: translateY(-3px);
        }
        .stock-alert-card .manufacturer-card .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 0.75rem 1rem;
        }
        .stock-alert-card .manufacturer-card h6 {
            font-size: 1rem;
            font-weight: 600;
            color: #343a40;
            margin: 0;
        }
        .stock-alert-card .list-group-item {
            border: none;
            padding: 0.5rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stock-alert-card .product-name {
            max-width: 70%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .stock-alert-card .badge {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            flex-shrink: 0;
        }
        .stock-alert-card .badge.bg-danger {
            animation: blink 1.5s infinite;
        }
        @keyframes blink {
            50% { opacity: 0.5; }
        }

        /* Estilização da Tabela de Produtos (Página de Produtos) */
        .table-products th:nth-child(1),
        .table-products td:nth-child(1) {
            max-width: 300px;
        }
        .table-products .product-name {
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .navbar-mobile {
                display: block;
            }
            .sidebar {
                width: 200px;
                transform: translateX(-100%);
                top: 4rem; /* Ajuste para começar abaixo da navbar-mobile */
                height: calc(100vh - 4rem); /* Ajustar altura para ocupar o espaço restante */
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .content {
                margin-left: 0;
                padding: 1rem;
                padding-top: 4rem; /* Espaço para a navbar-mobile */
            }
            .content.collapsed {
                margin-left: 0;
            }
            .sidebar .logo {
                font-size: 1rem;
                padding: 0.75rem;
            }
            .sidebar .nav-link {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
            .sidebar .nav-link i {
                margin-right: 0.4rem;
                font-size: 1rem;
            }
            .sidebar .user-section {
                bottom: 0.5rem;
                padding: 0 0.5rem;
            }
            .summary-card .card-body {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }
            .summary-card .icon-wrapper {
                margin-right: 0;
                margin-bottom: 1rem;
                font-size: 2rem;
            }
            .summary-card h5 {
                font-size: 0.9rem;
            }
            .summary-card h3 {
                font-size: 1.5rem;
            }
            .summary-card p {
                font-size: 0.8rem;
            }
            .stock-alert-card .product-name {
                max-width: 60%;
                font-size: 0.85rem;
            }
            .stock-alert-card .badge {
                font-size: 0.75rem;
                padding: 0.3rem 0.6rem;
            }
            .table-products th:nth-child(1),
            .table-products td:nth-child(1) {
                max-width: 200px;
            }
            .table-products th,
            .table-products td {
                font-size: 0.85rem;
                padding: 0.5rem;
            }
        }

        @media (max-width: 576px) {
            .sidebar {
                width: 180px;
            }
            .sidebar .logo {
                font-size: 0.9rem;
            }
            .sidebar .nav-link {
                font-size: 0.85rem;
                padding: 0.4rem 0.75rem;
            }
            .content {
                padding: 0.75rem;
                padding-top: 4rem;
            }
            .summary-card .icon-wrapper {
                font-size: 1.5rem;
            }
            .summary-card h5 {
                font-size: 0.85rem;
            }
            .summary-card h3 {
                font-size: 1.25rem;
            }
            .summary-card p {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <?php if (estaLogado()): ?>
    <nav class="navbar-mobile">
        <button class="navbar-toggler" type="button" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="navbar-brand" style="color: #28a745; font-weight: 600;">
            Sistema de Estoque
        </div>
    </nav>

    <div class="sidebar" id="sidebar">
        <div class="logo">
            <i class="bi bi-leaf me-2"></i> Sistema de Estoque
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="/estoque/index.php"><i class="bi bi-house"></i> Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/estoque/pages/produtos/index.php"><i class="bi bi-box-seam"></i> Produtos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/estoque/pages/fabricantes/index.php"><i class="bi bi-building"></i> Fabricantes</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/estoque/pages/relatorios/index.php"><i class="bi bi-file-earmark-bar-graph"></i> Relatórios</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/estoque/pages/backup/index.php"><i class="bi bi-database-fill-check"></i> Backup</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/estoque/pages/perfil/index.php"><i class="bi bi-person"></i> Perfil</a>
            </li>
        </ul>
        <div class="user-section">
            <a class="nav-link" href="/estoque/logout.php">
                <i class="bi bi-box-arrow-right"></i> <?php echo $_SESSION['usuario_nome']; ?> (Sair)
            </a>
        </div>
    </div>

    <div class="content collapsed" id="content">
    <?php endif; ?>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir <span id="itemToDelete">este item</span>?</p>
                    <p class="text-danger"><small>Esta ação não pode ser desfeita.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" class="btn btn-danger" id="confirmDeleteBtn">Excluir</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Script para alternar a sidebar -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            sidebar.classList.toggle('active');
            content.classList.toggle('collapsed');
        }

        // Inicializar tooltips
        document.addEventListener('DOMContentLoaded', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>