# Plano de Correção e Melhorias do Sistema de Estoque

**Tipo de Projeto:** WEB / BACKEND (PHP 8.2, MySQL 8.0, Apache, Bootstrap 5)  
**Meta:** Subir ambiente Docker, padronizar rotas e BASE_PATH, proteger ações destrutivas com CSRF/POST, e aprimorar a experiência do usuário.

---

## Tarefas de Execução

### Fase 1: Ambiente e Infraestrutura
- [x] Localizar executáveis do Docker no Windows e iniciar o build dos containers (`app`, `db`, `phpmyadmin`).
- [x] Verificar conectividade do serviço web em `http://localhost:8080` e banco de dados MySQL na porta `3307`.
- [x] Confirmar tabelas e usuário admin no banco (`admin@example.com` / `admin123`).

### Fase 2: Padronização de Rotas e BASE_PATH (Edge Case 1)
- [x] Definir helper de roteamento e constante dinâmica `BASE_PATH` em `config/database.php` — funções `url()`, `base_url()`, `redirect()`.
- [x] Corrigir caminhos com prefixo rígido `/estoque/` em formulários, links e `header('Location: ...')` em:
  - [x] `pages/produtos/salvar.php` e `pages/produtos/atualizar.php`
  - [x] `pages/fabricantes/index.php`, `salvar.php`, `atualizar.php` e `excluir.php`
  - [x] `pages/relatorios/index.php` e `salvar_movimentacao.php`
  - [x] `pages/backup/limpar_backups.php`, `restaurar_arquivo.php`, `excluir_backup.php` e `index.php`
  - [x] `pages/perfil/index.php`
- [x] Padronizar caminhos físicos de upload (`dirname(__DIR__, 2) . '/uploads/...'`).
- [x] Zero ocorrências de `/estoque/` hardcoded nos arquivos PHP (apenas comentário em `config/database.php`).

### Fase 3: Segurança e Robustez (Edge Case 2)
- [x] Criar funções CSRF em `includes/funcoes.php`: `gerarCsrfToken()`, `campoCsrf()`, `verificarCsrfToken()`, `exigirCsrf()`.
- [x] Injetar `<?= campoCsrf(); ?>` em todos os formulários (produtos, fabricantes, perfil, backup, relatórios, movimentações).
- [x] Migrar exclusão de **produtos** (`excluir.php`) para POST + CSRF — modal com `confirmDeleteForm`.
- [x] Migrar exclusão de **fabricantes** (`excluir.php`) para POST + CSRF — modal com `confirmDeleteForm`.
- [x] Migrar `backup/excluir_backup.php` para POST + CSRF.
- [x] Migrar `backup/limpar_backups.php` para POST + CSRF.
- [x] Migrar `backup/restaurar_arquivo.php` para POST + CSRF + `verificarAdmin()`.
- [x] Adicionar CSRF à `relatorios/salvar_movimentacao.php` (`exigirCsrf()`).
- [x] Adicionar CSRF à `perfil/index.php` (validação `exigirCsrf()` + campos nos 3 formulários).
- [x] Modais `#confirmDeleteModal` e `#confirmRestoreModal` adicionados ao `backup/index.php`.
- [x] `script.js` atualizado com handlers globais para ambos os modais (POST + CSRF via form submit).

### Fase 4: Usabilidade e Modernização de UI/UX
- [x] Assets CSS e JS carregando corretamente via paths `/assets/...` (sem prefixo `/estoque/`).
- [x] `formatarData()` adicionada ao `funcoes.php` — usada em relatórios e perfil.
- [x] Modais de exclusão com confirmação visual e feedback via `$_SESSION['mensagem']`.
- [x] Responsividade mantida em todas as páginas (Bootstrap 5, media queries customizadas).

### Fase 5: Verificação Final
- [x] Sintaxe PHP verificada em todos os arquivos editados — 0 erros.
- [x] HTTP 200 em `/login.php` e `/index.php`; 302 nas páginas protegidas (correto).
- [x] Teste de login e fluxo completo via HTTP autenticado (Dashboard, Produtos, Fabricantes, Relatórios, Backup, Perfil).
- [x] Validação anti-CSRF e restrição de método POST testadas contra tentativas de bypass (todas bloqueadas).
- [x] Credenciais padrão confirmadas e validadas: `admin@example.com` / `admin123`.
- [x] Verificação de ausência total de caminhos fixos `/estoque/` nas saídas HTML e redirects.

---

## Resumo de Mudanças por Arquivo

| Arquivo | Mudanças |
|---------|----------|
| `config/database.php` | `BASE_PATH` dinâmico, `url()`, `redirect()`, `base_url()` |
| `includes/funcoes.php` | `gerarCsrfToken()`, `campoCsrf()`, `verificarCsrfToken()`, `exigirCsrf()`, `formatarData()` |
| `assets/js/script.js` | Handlers globais para `#confirmDeleteModal` e `#confirmRestoreModal` |
| `pages/produtos/salvar.php` | `exigirCsrf()`, `redirect()`, path upload absoluto |
| `pages/produtos/atualizar.php` | `exigirCsrf()`, `redirect()`, remoção de imagem antiga |
| `pages/produtos/excluir.php` | POST only, `exigirCsrf()`, remoção de arquivo, `redirect()` |
| `pages/produtos/index.php` | Modal POST+CSRF para exclusão, botões `data-id` |
| `pages/fabricantes/salvar.php` | `redirect()`, path upload absoluto |
| `pages/fabricantes/atualizar.php` | `redirect()`, path upload absoluto |
| `pages/fabricantes/excluir.php` | POST only, `exigirCsrf()`, remoção de logo, `redirect()` |
| `pages/fabricantes/index.php` | Modal POST+CSRF para exclusão, `confirmDeleteForm` |
| `pages/backup/excluir_backup.php` | POST only, `exigirCsrf()`, path absoluto |
| `pages/backup/limpar_backups.php` | POST only, `exigirCsrf()`, path absoluto |
| `pages/backup/restaurar_arquivo.php` | POST only, `exigirCsrf()`, `verificarAdmin()`, path absoluto |
| `pages/backup/index.php` | Botões POST+CSRF, modais `#confirmDeleteModal` e `#confirmRestoreModal` adicionados |
| `pages/relatorios/index.php` | Form movimentação com `url()` + `campoCsrf()`, link Limpar Filtros e JS exportar com `url()` |
| `pages/relatorios/salvar_movimentacao.php` | `exigirCsrf()` adicionado |
| `pages/perfil/index.php` | `exigirCsrf()` em POST, `campoCsrf()` nos 3 formulários, `url()` em todos os links |
