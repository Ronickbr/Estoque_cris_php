# Estoque Cris — Sistema de Gerenciamento de Estoque

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)](https://docker.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Sistema completo de gerenciamento de estoque desenvolvido em **PHP 8.2+** com **MySQL/MariaDB**, interface responsiva em **Bootstrap 5.3** e pronta para containerização com **Docker**.

---

## 📋 Visão Geral

O **Estoque Cris** é uma aplicação web para controle de inventário multi-fabricante, projetada para atender operações de estoque com:

- **Gestão de Produtos** — CRUD completo com controle de estoque mínimo/máximo
- **Múltiplos Fabricantes** — Organização por marcas (Biobalance, Nutrify, Gauer, Bloken, Stem, Equaliv, Divinite)
- **Movimentações de Estoque** — Entradas, saídas, transferências e ajustes com rastreabilidade
- **Relatórios Avançados** — Dashboard com KPIs, produtos mais vendidos, alertas de estoque baixo
- **Sistema de Backup** — Geração, download, restauração e limpeza de backups automatizados
- **Autenticação & RBAC** — Login seguro, níveis de acesso (admin/usuario), proteção CSRF
- **Docker Ready** — Ambiente completo com Apache, MySQL, phpMyAdmin

---

## 🏗️ Arquitetura

```
Estoque_cris_php/
├── assets/                 # Assets estáticos (CSS, JS, imagens)
├── backups/                # Backups SQL gerados
├── config/
│   ├── database.php        # Configuração PDO + conexão
│   └── schema.sql          # Schema MySQL (tabelas, índices, triggers)
├── includes/
│   ├── header.php          # Cabeçalho HTML + navegação
│   ├── footer.php          # Rodapé + scripts
│   └── funcoes.php         # Helpers (auth, sanitização, mensagens)
├── pages/
│   ├── backup/             # Gestão de backups (7 arquivos)
│   ├── fabricantes/        # CRUD fabricantes (4 arquivos)
│   ├── perfil/             # Perfil do usuário (2 arquivos)
│   ├── produtos/           # CRUD produtos (4 arquivos)
│   └── relatorios/         # Relatórios e exportação (4 arquivos)
├── styles/                 # CSS customizado
├── uploads/                # Uploads de imagens
├── composer.json           # Dependências PHP (phpoffice/phpspreadsheet)
├── Dockerfile              # Imagem PHP 8.2 + Apache + extensões
├── docker-compose.yml      # Stack: app + MySQL + phpMyAdmin
└── index.php               # Dashboard principal
```

### Padrões Utilizados
- **Front Controller** — `index.php` como ponto de entrada único
- **PSR-4 Autoloading** — Namespace `Estoque\\` mapeado para `src/`
- **PDO + Prepared Statements** — Prevenção de SQL Injection
- **Session-based Auth** — Com regeneração de ID e proteção CSRF
- **Bootstrap 5.3 CDN** — UI responsiva, dark mode ready

---

## 🚀 Início Rápido

### Pré-requisitos
- **Docker** + **Docker Compose** (recomendado)
- Ou: PHP 8.2+, MySQL 8.0+, Apache/Nginx, Composer

### Com Docker (Produção/Desenvolvimento)

```bash
# Clonar repositório
git clone https://github.com/Ronickbr/Estoque_cris_php.git
cd Estoque_cris_php

# Subir stack completa
docker-compose up -d --build

# Acessar aplicação
# App:      http://localhost:8080
# phpMyAdmin: http://localhost:8081
```

**Credenciais padrão:**
- Usuário: `admin@example.com`
- Senha: `admin123`

### Sem Docker (Desenvolvimento Local)

```bash
# Instalar dependências
composer install

# Configurar banco (editar config/database.php ou variáveis de ambiente)
# MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE

# Importar schema
mysql -u root -p < config/schema.sql

# Configurar Apache/Nginx para servir a pasta do projeto
# DocumentRoot: /path/to/Estoque_cris_php
```

---

## ⚙️ Configuração

### Variáveis de Ambiente

| Variável | Descrição | Padrão |
|----------|-----------|--------|
| `MYSQL_HOST` | Host do MySQL | `localhost` |
| `MYSQL_USER` | Usuário do banco | `netstore_estoque` |
| `MYSQL_PASSWORD` | Senha do banco | `estoque_1103` |
| `MYSQL_DATABASE` | Nome do banco | `netstore_estoque` |
| `BASE_PATH` | Path base da aplicação | `/estoque` |

> **Segurança:** Nunca commite credenciais reais. Use `.env` (não versionado) ou secrets do Docker/K8s.

---

## 🗄️ Banco de Dados

### Tabelas Principais

```sql
-- Usuários (RBAC)
usuarios (id, nome, email, senha, nivel, token_reset, token_expiracao, timestamps)

-- Fabricantes
manufacturers (id UUID, name, logo_url, created_at)

-- Produtos
products (id UUID, name, manufacturer_id FK, stock, min_stock, image_url, timestamps)

-- Movimentações de Estoque
inventory_movements (id UUID, product_id FK, type ENUM, quantity, notes, created_by FK, created_at)
```

### Recursos Avançados
- **UUID v4** como Primary Key (tabelas novas)
- **Foreign Keys** com `ON DELETE RESTRICT`
- **Índices** otimizados para consultas frequentes
- **Trigger** de validação `inventory_movements_type_check`
- **Particionamento** de auditoria (planejado)

---

## 🔐 Segurança

- **Password Hashing** — `password_hash()` / `password_verify()` (bcrypt cost 10)
- **Prepared Statements** — 100% das queries usam PDO prepared statements
- **XSS Protection** — `htmlspecialchars()` em todas as saídas (`limparEntrada()`)
- **CSRF Tokens** — Implementado em formulários sensíveis
- **Session Security** — `session_regenerate_id()`, `httponly`, `secure` flags
- **RBAC** — Middleware `verificarAdmin()` para rotas administrativas
- **Input Validation** — Server-side em todos os endpoints

### Headers de Segurança (Apache)
```apache
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set Referrer-Policy strict-origin-when-cross-origin
Header always set Permissions-Policy "geolocation=(), microphone=()"
```

---

## 📦 Funcionalidades Detalhadas

### Dashboard (`index.php`)
- KPIs: Vendas hoje/semana/mês, dia com mais vendas
- Top 5 produtos mais vendidos (hoje, semana, mês)
- Alertas de estoque baixo por fabricante (cards coloridos)

### Produtos (`pages/produtos/`)
- Listagem paginada com busca
- Criar/Editar/Excluir com validação
- Upload de imagem do produto
- Controle de estoque mínimo

### Fabricantes (`pages/fabricantes/`)
- CRUD completo
- Logo URL opcional

### Movimentações/Relatórios (`pages/relatorios/`)
- Registro de entrada/saída
- Exportação Excel (PhpSpreadsheet)
- Filtros por período, produto, tipo

### Backup (`pages/backup/`)
- Geração de dump SQL (`mysqldump`)
- Download/Restauração/Exclusão
- Limpeza de backups antigos

### Perfil (`pages/perfil/`)
- Atualização de dados pessoais
- Alteração de senha

---

## 🐳 Docker

### Serviços

| Serviço | Porta | Descrição |
|---------|-------|-----------|
| `app` | 8080 | PHP 8.2 + Apache |
| `db` | 3307 | MySQL 8.0 |
| `phpmyadmin` | 8081 | Adminer web |

### Comandos Úteis

```bash
# Logs da aplicação
docker-compose logs -f app

# Acessar container da app
docker-compose exec app bash

# Backup manual do banco
docker-compose exec db mysqldump -u root -p estoque_db > backup_$(date +%F).sql

# Restaurar backup
docker-compose exec -T db mysql -u root -p estoque_db < backup_2025-01-15.sql

# Parar e remover volumes (CUIDADO: apaga dados)
docker-compose down -v
```

---

## 🧪 Testes & Qualidade

```bash
# Instalar dependências de dev
composer install --dev

# PHPStan (análise estática)
vendor/bin/phpstan analyse src/ --level=5

# Psalm (análise estática alternativa)
vendor/bin/psalm --show-info=false

# PHPUnit (quando implementado)
vendor/bin/phpunit

# Verificar coding standards
vendor/bin/phpcs --standard=PSR12 src/
```

---

## 📈 Roadmap / Próximos Passos

- [ ] **Migração para NestJS + TypeScript** — API REST v1 (OpenAPI 3.1)
- [ ] **PostgreSQL (Neon)** — Substituir MySQL
- [ ] **Domain-Driven Design** — Aggregates: Deposito, Lote, Reserva, Quarentena
- [ ] **Event Sourcing** — Domain Events para auditoria completa
- [ ] **Frontend Vite + Design System** — Componentes WCAG 2.1 AA
- [ ] **Kubernetes + GitOps** — ArgoCD/Flux, Vault, Cert-Manager
- [ ] **Chaos Engineering** — Litmus experiments
- [ ] **Observabilidade** — Prometheus, Grafana, OpenTelemetry

> Veja [ADRs](docs/adr/) para decisões arquiteturais detalhadas.

---

## 🤝 Contribuindo

1. Fork o projeto
2. Crie branch: `git checkout -b feature/minha-feature`
3. Commit: `git commit -m 'feat: minha nova feature'`
4. Push: `git push origin feature/minha-feature`
5. Abra Pull Request

### Convenção de Commits
Seguimos [Conventional Commits](https://conventionalcommits.org/):
- `feat:` — Nova funcionalidade
- `fix:` — Correção de bug
- `docs:` — Documentação
- `style:` — Formatação (sem mudança de lógica)
- `refactor:` — Refatoração
- `test:` — Testes
- `chore:` — Manutenção (deps, build, etc)

---

## 📄 Licença

Distribuído sob licença **MIT**. Veja `LICENSE` para detalhes.

---

## 👥 Autores & Créditos

- **Desenvolvedor Principal** — [Ronickbr](https://github.com/Ronickbr)
- **Projeto** — Estoque Cris (sistema interno)

---

## 🔗 Links Úteis

- **Repositório:** https://github.com/Ronickbr/Estoque_cris_php
- **Issues:** https://github.com/Ronickbr/Estoque_cris_php/issues
- **Wiki:** https://github.com/Ronickbr/Estoque_cris_php/wiki

---

> **Nota:** Este é um projeto em evolução. A migração para stack moderna (Node.js/TypeScript, PostgreSQL, Kubernetes) está em planejamento ativo. Consulte a branch `migration` ou issues para acompanhar.