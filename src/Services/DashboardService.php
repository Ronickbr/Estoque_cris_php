<?php

namespace Estoque\Services;

use PDO;
use PDOException;

class DashboardService
{
    private $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Retorna estatísticas de vendas agregadas (Hoje, Semana, Mês) em uma única consulta.
     */
    public function getSalesStats()
    {
        $stmt = $this->conn->query("
            SELECT 
                SUM(CASE WHEN created_at >= NOW() - INTERVAL 1 DAY THEN quantity ELSE 0 END) as hoje,
                SUM(CASE WHEN created_at >= NOW() - INTERVAL 7 DAY THEN quantity ELSE 0 END) as semana,
                SUM(CASE WHEN created_at >= NOW() - INTERVAL 30 DAY THEN quantity ELSE 0 END) as mes
            FROM inventory_movements 
            WHERE type = 'saida' 
            AND created_at >= NOW() - INTERVAL 30 DAY
        ");
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'hoje' => $result['hoje'] ?? 0,
            'semana' => $result['semana'] ?? 0,
            'mes' => $result['mes'] ?? 0
        ];
    }

    /**
     * Retorna o total de vendas registradas nas últimas 24 horas.
     */
    public function getVendasHoje()
    {
        return $this->getSalesStats()['hoje'];
    }

    /**
     * Retorna o total de vendas registradas nos últimos 7 dias.
     */
    public function getVendasSemana()
    {
        return $this->getSalesStats()['semana'];
    }

    /**
     * Retorna o total de vendas registradas nos últimos 30 dias.
     */
    public function getVendasMes()
    {
        return $this->getSalesStats()['mes'];
    }

    /**
     * Retorna a evolução diária de vendas para um determinado intervalo de dias.
     */
    public function getVendasDiarias($dias = 30)
    {
        // Gera uma série de datas e faz left join com as vendas
        // Como MySQL < 8.0 não tem gerador de série fácil (recursive cte é 8.0+), 
        // simplificamos pegando os dias que tem venda ou apenas fazendo o group by simples.
        // Se precisar de dias com 0 vendas, o ideal é tratar no PHP ou ter tabela de calendário.
        // Vamos usar Group By simples data, o Chart.js lida bem se passarmos as labels corretas.
        
        $stmt = $this->conn->prepare("
            SELECT DATE(created_at) as data, COALESCE(SUM(quantity), 0) as total 
            FROM inventory_movements 
            WHERE type = 'saida' 
            AND created_at >= DATE(NOW()) - INTERVAL ? DAY
            GROUP BY DATE(created_at)
            ORDER BY data ASC
        ");
        
        $stmt->bindValue(1, $dias, PDO::PARAM_INT);
        $stmt->execute();
        
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Pós-processamento para garantir que todos os dias existam (preenchendo com 0)
        $dadosCompletos = [];
        $hoje = new \DateTime();
        $inicio = (clone $hoje)->modify("-{$dias} days");
        
        // Mapa de vendas indexado por data
        $vendasMap = [];
        foreach ($resultados as $row) {
            $vendasMap[$row['data']] = (int)$row['total'];
        }
        
        // Loop para preencher buracos
        for ($i = 0; $i <= $dias; $i++) {
            $dataAtual = (clone $inicio)->modify("+{$i} days");
            $dataStr = $dataAtual->format('Y-m-d');
            $dadosCompletos[] = [
                'data' => $dataAtual->format('d/m'), // Formato visual
                'data_full' => $dataStr,
                'total' => $vendasMap[$dataStr] ?? 0
            ];
        }
        
        return $dadosCompletos;
    }

    /**
     * Retorna o dia da semana com mais vendas nos últimos 30 dias.
     */
    public function getDiaMaisVendas()
    {
        $stmt = $this->conn->query("
            SELECT DAYNAME(created_at) as dia, COALESCE(SUM(quantity), 0) as total 
            FROM inventory_movements 
            WHERE type = 'saida' 
            AND created_at >= NOW() - INTERVAL 30 DAY 
            GROUP BY DAYNAME(created_at) 
            ORDER BY total DESC 
            LIMIT 1
        ");
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$resultado) {
            return ['dia' => 'Desconhecido', 'nome_pt' => 'Desconhecido', 'total' => 0];
        }

        $diaIngles = ucfirst(strtolower($resultado['dia']));
        $diasTraduzidos = [
            'Monday' => 'Segunda-feira',
            'Tuesday' => 'Terça-feira',
            'Wednesday' => 'Quarta-feira',
            'Thursday' => 'Quinta-feira',
            'Friday' => 'Sexta-feira',
            'Saturday' => 'Sábado',
            'Sunday' => 'Domingo'
        ];

        return [
            'dia' => $resultado['dia'],
            'nome_pt' => $diasTraduzidos[$diaIngles] ?? $diaIngles,
            'total' => $resultado['total']
        ];
    }

    /**
     * Retorna os produtos mais vendidos em um determinado intervalo de dias.
     */
    public function getProdutosMaisVendidos($dias = 30, $limite = 5)
    {
        $stmt = $this->conn->prepare("
            SELECT p.name, SUM(m.quantity) as total 
            FROM inventory_movements m 
            JOIN products p ON m.product_id = p.id 
            WHERE m.type = 'saida' 
            AND m.created_at >= NOW() - INTERVAL ? DAY 
            GROUP BY p.id, p.name 
            ORDER BY total DESC 
            LIMIT ?
        ");
        
        // PDO bindParam expects variable reference or specific types, bindValue is safer for literals
        $stmt->bindValue(1, $dias, PDO::PARAM_INT);
        $stmt->bindValue(2, $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna produtos com estoque baixo, opcionalmente filtrados por fabricante (busca parcial).
     */
    public function getEstoqueBaixo($filtroFabricante = null)
    {
        $sql = "
            SELECT p.id, p.name, p.stock, p.min_stock, m.id as manufacturer_id, m.name as manufacturer, m.logo_url
            FROM products p 
            LEFT JOIN manufacturers m ON p.manufacturer_id = m.id 
            WHERE p.stock <= COALESCE(p.min_stock, 5)
            ORDER BY (p.stock = 0) DESC, p.stock ASC, m.name ASC, p.name ASC
        ";
        
        $stmt = $this->conn->query($sql);
        $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($filtroFabricante) {
            return array_filter($todos, fn($item) => 
                $item['manufacturer'] && stripos($item['manufacturer'], $filtroFabricante) !== false
            );
        }

        return $todos;
    }
    
    /**
     * Retorna produtos com estoque baixo agrupados dinamicamente por fabricante.
     * Não depende de listas fixas de fabricantes.
     */
    public function getEstoqueBaixoPorFabricante()
    {
        $todos = $this->getEstoqueBaixo();
        $agrupado = [];
        
        // Cores harmoniosas dinâmicas para cabeçalhos de fabricantes
        $paleta = ['#445d6b', '#18a085', '#9b62ef', '#d4af37', '#26a1fe', '#e67e22', '#fb7cbf', '#20c997', '#6f42c1', '#fd7e14'];
        
        $i = 0;
        foreach ($todos as $item) {
            $fabNome = $item['manufacturer'] ?: 'Sem Fabricante';
            if (!isset($agrupado[$fabNome])) {
                $agrupado[$fabNome] = [
                    'nome' => $fabNome,
                    'logo_url' => $item['logo_url'] ?? null,
                    'cor' => $paleta[$i % count($paleta)],
                    'produtos' => [],
                    'total_zerados' => 0,
                    'total_baixo' => 0
                ];
                $i++;
            }
            
            if ((int)$item['stock'] === 0) {
                $agrupado[$fabNome]['total_zerados']++;
            } else {
                $agrupado[$fabNome]['total_baixo']++;
            }
            
            $agrupado[$fabNome]['produtos'][] = $item;
        }
        
        return $agrupado;
    }

    /**
     * Método auxiliar legado para manter retrocompatibilidade se chamado
     */
    public function getEstoqueBaixoAgrupado(array $fabricantesAlvo)
    {
        $todos = $this->getEstoqueBaixo();
        $resultado = [];
        
        foreach ($fabricantesAlvo as $chave => $termoBusca) {
             $resultado[$chave] = array_filter($todos, fn($item) => 
                $item['manufacturer'] && stripos(strtolower($item['manufacturer']), strtolower($termoBusca)) !== false
            );
        }
        
        return $resultado;
    }

    /**
     * Retorna todos os dados para o dashboard de uma só vez.
     */
    public function getDashboardSummary()
    {
        // Estatísticas de vendas (Hoje, Semana, Mês) em uma única consulta
        $stats = $this->getSalesStats();

        return [
            'vendas_hoje' => $stats['hoje'],
            'vendas_semana' => $stats['semana'],
            'vendas_mes' => $stats['mes'],
            'dia_mais_vendas' => $this->getDiaMaisVendas(),
            'vendas_diarias' => $this->getVendasDiarias(30),
            'produtos_top_mes' => $this->getProdutosMaisVendidos(30, 5),
            'estoque_baixo' => $this->getEstoqueBaixo(),
            'estoque_baixo_por_fabricante' => $this->getEstoqueBaixoPorFabricante()
        ];
    }
}
