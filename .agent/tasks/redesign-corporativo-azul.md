# Task: Redesign Minimalista Corporativo (Executive Cobalt Slate)

## 1. Visão Geral
Modernização completa da interface de usuário do Sistema de Estoque, aplicando os princípios de **Design Intelligence** da skill `ui-ux-pro-max` e **Refinamento & Deslopping** da skill `baseline-ui`.

- **Estilo:** Minimalista Corporativo (High Information Density, Clean Slate & Cobalt)
- **Paleta de Cores:** Azul Oceano / Cobalto Executivo (`#0284c7`, `#2563eb`, `#1d4ed8`) com tons neutros frios Slate (`#0f172a`, `#1e293b`, `#334155`, `#64748b`, `#e2e8f0`, `#f8fafc`).
- **Escopo:** Layout Global (Sidebar, Topbar, Modais) e todas as telas (Dashboard, Produtos, Fabricantes, Relatórios, Backup e Login).

## 2. Princípios de Design & Tokens (Design Commitment)

### Design Commitment: Executive Cobalt Slate
- **Topological Choice:** Substituição de cards com sombras pesadas e bordas arredondadas exageradas (15px) por arquitetura de alta densidade corporativa com bordas nítidas de 1px (`#e2e8f0`), elevação micro (0 1px 2px rgba(0,0,0,0.05)), hierarquia tipográfica precisa com números tabulares para inventário.
- **Risk Factor:** Remoção total do gradiente verde legado na sidebar em favor de um layout corporativo sóbrio (Slate 900 com contraste WCAG AAA para texto e acentos em Azul Cobalto ativo).
- **Readability & Ergonomics:** Aumento da legibilidade de tabelas e listas de produtos com cabeçalhos compactos em caixa alta, espaçamento consistente de 44px para alvos de toque em mobile, badges de estoque contextuais de alta visibilidade.
- **Cliché Liquidation:** Banimento de violeta/roxo, sem mesh gradients com blur excessivo, sem glassmorphism falso; foco absoluto em utilidade, nitidez e usabilidade operacional.

## 3. Plano de Implementação

### Fase 1: Tokens e Estilos Globais (`assets/css/style.css`)
- [ ] Definir variáveis CSS (`--primary`, `--primary-hover`, `--slate-900`, `--slate-800`, `--slate-100`, etc.)
- [ ] Refatorar Sidebar para visual executivo dark/slate com marca e links nítidos
- [ ] Refatorar Topbar, dropdown de usuário e Central de Notificações
- [ ] Padronizar classes de componentes: `.card`, `.table-products`, `.summary-card`, `.badge`, `.btn`

### Fase 2: Layout Base & Header (`includes/header.php`)
- [ ] Atualizar fontes Google Fonts (adicionar Inter / Plus Jakarta Sans para máxima legibilidade de dados)
- [ ] Sincronizar ícones e cores dos badges, dropdown e modal de notificações
- [ ] Garantir consistência nas rotas com `url()`

### Fase 3: Telas Principais
- [ ] **Dashboard (`index.php`):** Reorganizar KPIs em grid limpo, cards de estoque crítico com indicadores nítidos e gráficos legíveis.
- [ ] **Produtos (`pages/produtos/index.php`):** Barra de filtros compacta e funcional, tabela de produtos de alta densidade com badges de estoque claros e botões de ação ergonômicos.
- [ ] **Fabricantes (`pages/fabricantes/index.php`):** Grid/tabela limpa de fabricantes com contadores e upload integrado.
- [ ] **Relatórios (`pages/relatorios/index.php`):** Filtros rápidos por período, resumo consolidado de entradas/saídas e tabela de movimentação refinada.
- [ ] **Backup (`pages/backup/index.php`):** Tabela de histórico de backups com tamanho formatado, alertas e ações seguras.
- [ ] **Login (`login.php`):** Card corporativo centralizado, campos de entrada nítidos e botão em cobalto.

### Fase 4: Validação & Verificação
- [ ] Testar renderização de todas as páginas no Docker.
- [ ] Verificar contraste de cores e ergonomia em dispositivos móveis.
