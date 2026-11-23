/**
 * Dashboard Charts Controller
 * Chart.js v4.4+ e ChartDataLabels v2.2+
 */

// Instâncias globais para permitir re-renderização limpa
let chartVendasInstance = null;
let chartTopProdutosInstance = null;

function initDashboardCharts(vendasDiarias, topProdutos) {
    if (typeof Chart === 'undefined') {
        console.warn('[Dashboard] Chart.js não está carregado.');
        return;
    }

    // Configurações Globais de Tipografia e Cor
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
    Chart.defaults.color = '#64748b';

    // Registrar Plugin DataLabels com segurança se disponível
    if (typeof ChartDataLabels !== 'undefined') {
        try {
            Chart.register(ChartDataLabels);
        } catch (e) {
            console.warn('[Dashboard] Aviso ao registrar ChartDataLabels:', e);
        }
    }

    // =========================================================================
    // 1. Gráfico de Evolução de Vendas (30 Dias) - Linha Suave
    // =========================================================================
    const canvasVendas = document.getElementById('chartVendasDiarias');
    if (canvasVendas) {
        try {
            // Destruir instância anterior se já existir
            if (chartVendasInstance) {
                chartVendasInstance.destroy();
            }

            const ctxVendas = canvasVendas.getContext('2d');
            const gradientVendas = ctxVendas.createLinearGradient(0, 0, 0, 280);
            gradientVendas.addColorStop(0, 'rgba(2, 132, 199, 0.22)');
            gradientVendas.addColorStop(1, 'rgba(2, 132, 199, 0.0)');

            const labelsVendas = Array.isArray(vendasDiarias) ? vendasDiarias.map(d => d.data || '') : [];
            const dataVendas = Array.isArray(vendasDiarias) ? vendasDiarias.map(d => Number(d.total) || 0) : [];

            chartVendasInstance = new Chart(ctxVendas, {
                type: 'line',
                data: {
                    labels: labelsVendas,
                    datasets: [{
                        label: 'Vendas',
                        data: dataVendas,
                        borderColor: '#0284c7',
                        backgroundColor: gradientVendas,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#0284c7',
                        pointBorderWidth: 2,
                        pointRadius: 3.5,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.35
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        datalabels: {
                            align: 'top',
                            anchor: 'end',
                            offset: 2,
                            color: '#0284c7',
                            font: { weight: '600', size: 10 },
                            formatter: function (value) {
                                return value > 0 ? value : '';
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#ffffff',
                            bodyColor: '#e2e8f0',
                            borderColor: '#1e293b',
                            borderWidth: 1,
                            padding: 10,
                            cornerRadius: 6,
                            callbacks: {
                                label: function (context) {
                                    return ' ' + context.parsed.y + ' un. vendidas';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { borderDash: [4, 4], color: '#f1f5f9' },
                            ticks: { precision: 0, color: '#94a3b8', font: { size: 11 } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#94a3b8', font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 12 }
                        }
                    }
                }
            });
        } catch (err) {
            console.error('[Dashboard] Erro ao renderizar gráfico de vendas:', err);
        }
    }

    // =========================================================================
    // 2. Gráfico de Top 5 Produtos (Mês) - Barras Horizontais
    // =========================================================================
    const canvasTop = document.getElementById('chartTopProdutos');
    if (canvasTop) {
        try {
            // Destruir instância anterior se já existir
            if (chartTopProdutosInstance) {
                chartTopProdutosInstance.destroy();
            }

            const ctxTop = canvasTop.getContext('2d');
            const produtos = Array.isArray(topProdutos) ? topProdutos : [];
            const labelsTop = produtos.map(p => {
                const name = p.name || '';
                return name.length > 22 ? name.substring(0, 20) + '...' : name;
            });
            const dataTop = produtos.map(p => Number(p.total) || 0);
            const maxVal = dataTop.length > 0 ? Math.max(...dataTop, 1) : 1;

            chartTopProdutosInstance = new Chart(ctxTop, {
                type: 'bar',
                indexAxis: 'y',
                data: {
                    labels: labelsTop,
                    datasets: [{
                        label: 'Qtd Vendida',
                        data: dataTop,
                        backgroundColor: [
                            '#0284c7',
                            '#2563eb',
                            '#0ea5e9',
                            '#059669',
                            '#d97706'
                        ],
                        borderRadius: 4,
                        barPercentage: 0.65
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        datalabels: {
                            anchor: 'end',
                            align: 'end',
                            offset: 4,
                            color: '#334155',
                            font: { weight: '600', size: 11 },
                            formatter: function (value) {
                                return value > 0 ? (value + ' un') : '';
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#ffffff',
                            bodyColor: '#e2e8f0',
                            borderColor: '#1e293b',
                            borderWidth: 1,
                            padding: 10,
                            cornerRadius: 6,
                            callbacks: {
                                title: function (tooltipItems) {
                                    if (!tooltipItems || !tooltipItems.length) return '';
                                    const index = tooltipItems[0].dataIndex;
                                    return (produtos[index] && produtos[index].name) ? produtos[index].name : '';
                                },
                                label: function (context) {
                                    return ' Total: ' + context.parsed.x + ' unidades vendidas';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: false,
                            beginAtZero: true,
                            max: Math.ceil(maxVal * 1.3)
                        },
                        y: {
                            grid: { display: false },
                            ticks: { color: '#475569', font: { size: 11, weight: '500' } }
                        }
                    },
                    layout: {
                        padding: { right: 35 }
                    }
                }
            });
        } catch (err) {
            console.error('[Dashboard] Erro ao renderizar top produtos:', err);
        }
    }
}
