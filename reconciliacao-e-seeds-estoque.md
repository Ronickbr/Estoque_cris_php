# Plano: Reconciliação de Estoque, Correção de Cálculos e Seeds de Validação

**Tipo de Projeto:** WEB / BACKEND (PHP 8.2, MySQL 8.0, Bootstrap 5)  
**Arquivo de Tarefa:** `reconciliacao-e-seeds-estoque.md`  
**Decisões Confirmadas:**
- **[EDGE CASE 1 - Opção A]:** Toda alteração direta de estoque gera movimentação de ajuste automática (`inventory_movements`), mantendo `products.stock` e `SUM(entradas - saidas)` sempre em paridade absoluta.
- **[EDGE CASE 2 - Opção A]:** Dashboard 100% dinâmico por fabricante (sem lista hardcoded), com separação visual entre produtos Esgotados (`stock = 0`) e produtos em Estoque Baixo (`0 < stock <= min_stock`).

---

## Fases de Execução

### Fase 1: Restauração do Backup Base
- [x] Restaurar arquivo `backups/backup_2025-04-22_19-26-05.sql` no banco MySQL do container `estoque-app-1` (98 produtos, 7 fabricantes, 216 movimentações).
- [x] Revalidar acesso do usuário `admin@example.com` (`admin123`).

### Fase 2: Correção da Arquitetura de Cálculo de Estoque
- [x] Criar rotina/serviço de reconciliação de estoque (`StockService` em `src/Services/StockService.php`):
  - Verifica produtos onde `products.stock != SUM(entradas - saidas)`.
  - Reconciliou 8 produtos do backup que possuíam divergências, atingindo 100% de paridade.
- [x] Ajustar `pages/relatorios/salvar_movimentacao.php`:
  - Processamento atômico em lote com lock de concorrência (`FOR UPDATE`).
  - Validação estrita de saldo disponível antes de efetivar saídas (previne estoque negativo).
- [x] Ajustar `pages/relatorios/index.php`:
  - Dropdown de produtos sincronizado com `products.stock` sem divergências.
- [x] Ajustar `pages/produtos/atualizar.php`:
  - Qualquer alteração manual de estoque registra movimentação de ajuste correspondente.

### Fase 3: Dashboard Dinâmico e Alertas Visuais
- [x] Refatorar `src/Services/DashboardService.php`:
  - Novo método `getEstoqueBaixoPorFabricante()` agrupando dinamicamente por fabricante com ordenação priorizando itens esgotados (`stock = 0`).
- [x] Atualizar visualização no `index.php`:
  - Removido array estático de fabricantes hardcoded.
  - Corrigido o bug do fabricante `Bioklen` (antigo `Bloken` que impedia a exibição de produtos esgotados).
  - Cards 100% dinâmicos para qualquer fabricante existente no banco.
  - Badge vermelha destacada `Esgotado (0 un)` para itens zerados e amarela para itens em estoque baixo.

### Fase 4: Criação das Novas Seeds de Teste
- [x] Criar script de seeds `database/seed_testes_estoque.php`:
  - **Cenário 1 (Esgotado):** `Creatina 100% Pura 300g` (Estoque: 0, Mínimo: 5) em Max Titanium.
  - **Cenário 2 (Crítico):** `100% Whey Protein Isolado 900g` (Estoque: 3, Mínimo: 5) em Max Titanium.
  - **Cenário 3 (Saudável):** `BCAA 2400 100 Caps` (Estoque: 50, Mínimo: 10) em Max Titanium.
  - **Cenário 4 (Alternado):** `Multivitamínico Daily Formula` (5 movimentações sequenciais, saldo final 15) em Biobalance.
  - **Cenário 5 (Zerado por venda):** `Coenzima Q10 100mg` (Estoque final: 0) em Bioklen.
  - Novo fabricante `Max Titanium` inserido com sucesso.

### Fase 5: Verificação e Testes Finais
- [x] Verificação de paridade de estoque em 103 produtos cadastrados: 0 divergências (100% de paridade).
- [x] Teste de tentativa de retirada sem estoque bloqueada com erro `"Estoque insuficiente"`.
- [x] Testes de requisição HTTP real no Apache com retorno HTTP 200 em todas as páginas e validação das badges visuais.
