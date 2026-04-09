# TSAdmin

Sistema web para gestão industrial e comercial de EPS (poliestireno expandido). Controla produção (blocos e moldados), expedição, estoque, matéria-prima, silos, apontamentos, paradas de máquina, CRM de clientes/leads/oportunidades, pedidos com exportação em PDF e gerenciamento de usuários com permissões granulares.

## 🚀 Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.4, Laravel 12 |
| Frontend | Vue 3, Inertia.js, Tailwind CSS 4 |
| Banco de dados | PostgreSQL 16 |
| Build | Vite 7, Ziggy (rotas no JS) |
| Charts | ApexCharts |
| PDF | DomPDF |
| E-mail (dev) | Mailpit |
| Containerização | Docker & Docker Compose |

## ✨ Funcionalidades

- **Dashboard** com gráficos de produção, estoque e indicadores em tempo real
- **CRM** — Leads, interações e pipeline de oportunidades
- **Clientes** com endereços múltiplos
- **Pedidos** com itens, controle de status e exportação em PDF
- **Produtos** com componentes (lista técnica)
- **Produção** — Apontamentos, blocos e moldados
- **Expedição** — Saída de blocos e moldados por cliente
- **Estoque** — Movimentações, reservas, silos e matéria-prima
- **Infraestrutura** — Máquinas, operadores, setores, paradas com motivo
- **Usuários** com papéis (`admin` / `user`) e permissões granulares por recurso
- Verificação de e-mail e redefinição de senha

---

## 📦 Rodando com Docker *(recomendado)*

### Pré-requisitos

- [Docker](https://docs.docker.com/get-docker/) ≥ 24
- [Docker Compose](https://docs.docker.com/compose/) v2

### Passos

```bash
# 1. Clonar e entrar na pasta
git clone <url-do-repositorio>
cd tsadmin

# 2. Copiar o arquivo de ambiente
cp .env.example .env

# 3. Subir todos os serviços (na 1ª vez faz o build automaticamente)
docker compose up -d --build
```

Na inicialização, o container executa automaticamente:
- Aguarda o banco de dados estar disponível
- Gera `APP_KEY` se estiver vazio no `.env`
- Roda `php artisan migrate`
- Cria os usuários base (`admin@example.com` e `user@example.com`) via seed idempotente
- Gera as rotas Ziggy para o frontend
- Cacheia config/rotas no volume Docker (não afeta arquivos do host)
- Inicia o servidor PHP e o Vite dev server com HMR

Quando subir, acesse:

| Serviço | URL |
|---|---|
| Aplicação | [http://localhost:8080](http://localhost:8080) |
| Vite HMR | [http://localhost:5173](http://localhost:5173) |
| Mailpit (e-mails) | [http://localhost:8025](http://localhost:8025) |
| PostgreSQL | `localhost:5432` |

> **Nota:** Se a porta `8080` estiver ocupada, suba com `APP_PORT=9090 docker compose up`.

### Usuários padrão

Os usuários abaixo são criados automaticamente na primeira inicialização (e garantidos em todo restart via `updateOrCreate`):

| E-mail | Senha | Papel |
|---|---|---|
| `admin@example.com` | `password` | admin |
| `user@example.com` | `password` | user |

### Popular com dados de demonstração

```bash
# Seed de demonstração (todos os recursos com dados fictícios)
# ⚠️ Trunca e recria todos os dados — use apenas em desenvolvimento
docker compose exec app php artisan db:seed --class=DemoSeeder
```

### Comandos úteis no Docker

```bash
# Ver logs em tempo real
docker compose logs -f app

# Acessar o shell do container
docker compose exec app bash

# Rodar artisan dentro do container
docker compose exec app php artisan <comando>

# Rodar os testes
docker compose exec app php artisan test

# Parar os serviços
docker compose stop

# Subir novamente (sem rebuild)
docker compose up -d

# Derrubar tudo (containers + rede; volumes de dados são preservados)
docker compose down

# Derrubar e remover TODOS os volumes (⚠️ apaga o banco de dados)
docker compose down -v
```

### Reconstruir após mudanças no Dockerfile

```bash
docker compose build app
docker compose up -d
```

### Estrutura dos serviços Docker

```
docker-compose.yml
├── app      → Laravel + Vite dev server  (PHP 8.4, Node 22)
├── queue    → Worker de filas            (mesmo Dockerfile, target: development)
├── db       → PostgreSQL 16 Alpine
└── mailpit  → SMTP + interface web para e-mails de teste
```

**Volumes nomeados:**

| Volume | Montado em | Finalidade |
|---|---|---|
| `vendor` | `/var/www/html/vendor` | Deps PHP (isoladas do host) |
| `node_modules` | `/var/www/html/node_modules` | Deps Node (isoladas do host) |
| `bootstrap_cache` | `/var/www/html/bootstrap/cache` | Cache Docker (não afeta o host) |
| `db-data` | dados do PostgreSQL | Persistência do banco |

---

## 🖥️ Rodando sem Docker (ambiente local)

### Pré-requisitos

- PHP ≥ 8.4 com extensões: `pdo_pgsql`, `mbstring`, `gd`, `zip`, `intl`, `bcmath`, `pcntl`, `exif`, `dom`
- [Composer](https://getcomposer.org/) ≥ 2
- [Node.js](https://nodejs.org/) ≥ 18 + npm
- PostgreSQL ≥ 14

### Instalação

```bash
# 1. Clonar o repositório
git clone <url-do-repositorio>
cd tsadmin

# 2. Instalar dependências PHP
composer install

# 3. Instalar dependências Node
npm install

# 4. Configurar ambiente
cp .env.example .env
php artisan key:generate

# 5. Ajustar banco de dados no .env
# DB_HOST=127.0.0.1
# DB_DATABASE=ts_admin
# DB_USERNAME=seu_usuario
# DB_PASSWORD=sua_senha

# 6. Criar as tabelas
php artisan migrate

# 7. (Opcional) Popular com dados de exemplo
php artisan db:seed --class=DemoSeeder
```

### Iniciar o ambiente de desenvolvimento

```bash
# Inicia todos os processos juntos (servidor, queue, logs e Vite)
composer run dev
```

Ou separadamente em terminais distintos:

```bash
# Terminal 1 — Servidor PHP
php artisan serve

# Terminal 2 — Worker de filas
php artisan queue:listen --tries=1

# Terminal 3 — Vite (assets + HMR)
npm run dev

# Terminal 4 — Logs em tempo real (opcional)
php artisan pail
```

---

## 🔧 Variáveis de Ambiente

Copie `.env.example` para `.env` e ajuste conforme o ambiente. As variáveis mais relevantes estão descritas abaixo.

### Aplicação

| Variável | Padrão | Descrição |
|---|---|---|
| `APP_NAME` | `TSAdmin` | Nome da aplicação |
| `APP_ENV` | `local` | Ambiente (`local`, `production`, `testing`) |
| `APP_KEY` | *(vazio)* | Chave de criptografia — gerar com `php artisan key:generate` |
| `APP_DEBUG` | `true` | Habilita debug e stack traces detalhados |
| `APP_URL` | `http://localhost` | URL base da aplicação |
| `APP_LOCALE` | `pt_BR` | Localidade padrão |

### Banco de dados

| Variável | Padrão | Descrição |
|---|---|---|
| `DB_CONNECTION` | `pgsql` | Driver do banco |
| `DB_HOST` | `db` | Host do PostgreSQL (Docker: `db`; local: `127.0.0.1`) |
| `DB_PORT` | `5432` | Porta |
| `DB_DATABASE` | `ts_admin` | Nome do banco |
| `DB_USERNAME` | `admin` | Usuário |
| `DB_PASSWORD` | *(vazio)* | Senha — definir no `.env` |

### Cache, sessão e filas

| Variável | Padrão | Descrição |
|---|---|---|
| `CACHE_STORE` | `database` | Driver de cache (`database`, `redis`, `file`) |
| `SESSION_DRIVER` | `database` | Driver de sessão (`database`, `redis`, `cookie`) |
| `SESSION_LIFETIME` | `120` | Duração da sessão em minutos |
| `QUEUE_CONNECTION` | `database` | Driver de filas (`database`, `redis`, `sync`) |

### E-mail

| Variável | Padrão | Descrição |
|---|---|---|
| `MAIL_MAILER` | `smtp` | Driver de e-mail |
| `MAIL_HOST` | `mailpit` | Servidor SMTP (Docker: `mailpit`; local: `127.0.0.1`) |
| `MAIL_PORT` | `1025` | Porta SMTP |
| `MAIL_FROM_ADDRESS` | `no-reply@ts-admin.local` | Endereço remetente padrão |
| `MAIL_FROM_NAME` | `${APP_NAME}` | Nome do remetente |

---

## 🧪 Testes

```bash
# Rodar todos os testes
php artisan test

# Com resumo de cobertura de código
php artisan test --coverage

# Suite específica
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Filtrar por nome do teste
php artisan test --filter NomeDoTeste

# Via Docker
docker compose exec app php artisan test
```

Os testes utilizam SQLite em memória (configurado no `phpunit.xml`) — não é necessário banco separado para a suíte de testes.

**O que está coberto:**

- Modelos: User, Client, Address, Product, Order, OrderItem, ProductComponent
- Controllers: ClientController, ProductController
- Form Requests: StoreClientRequest, UpdateClientRequest
- Policies: Client, User, Product, Order, Address e demais
- Middlewares: Authenticate, HandleInertiaRequests
- Notificações: VerifyEmailNotification

---

## 🔐 Usuários e Permissões

### Papéis (roles)

| Papel | Acesso |
|---|---|
| `admin` | Acesso total a todos os recursos, sem restrições |
| `user` | Acesso restrito pelas permissões definidas individualmente |

### Permissões granulares

Usuários com papel `user` possuem permissões configuradas individualmente por recurso. Cada recurso suporta as ações:

| Ação | Descrição |
|---|---|
| `view` | Visualizar listagens e registros individuais |
| `create` | Criar novos registros |
| `update` | Editar registros existentes |
| `delete` | Excluir registros |

Recursos com ações extras:
- **orders**: `update_status`, `export_pdf`

### Recursos gerenciáveis por permissão

| Recurso | Descrição |
|---|---|
| `clients` | Clientes |
| `products` | Produtos |
| `orders` | Pedidos |
| `leads` | Leads |
| `opportunities` | Oportunidades |
| `sectors` | Setores |
| `raw_materials` | Matérias-primas |
| `inventory_movements` | Movimentos de estoque |
| `production_pointings` | Apontamentos de produção |
| `block_productions` | Produções de blocos |
| `molded_productions` | Produções moldadas |
| `block_dispatches` | Saídas de blocos |
| `molded_dispatches` | Saídas de moldados |
| `silos` | Silos |
| `block_types` | Tipos de blocos |
| `almoxarifados` | Almoxarifados |
| `machines` | Máquinas |
| `operators` | Operadores |
| `reason_types` | Tipos de motivo |
| `reasons` | Motivos |
| `machine_downtimes` | Paradas de máquina |

As permissões são verificadas via **Laravel Policies** registradas no `AuthServiceProvider`. Todos os controllers usam `$this->authorize()` para checar acesso antes de executar qualquer operação.

---

## 🚢 Deploy em Produção

### 1. Build da imagem de produção

```bash
docker build --target production -t tsadmin:prod .
```

A imagem de produção:
- Não contém Node.js nem dependências de desenvolvimento
- Inclui os assets compilados pelo Vite em `public/build/`
- Executa automaticamente `config:cache`, `route:cache`, `view:cache` e `event:cache` na inicialização

### 2. Variáveis obrigatórias em produção

```bash
APP_ENV=production
APP_DEBUG=false
APP_KEY=<gerar com: php artisan key:generate --show>
APP_URL=https://seu-dominio.com

DB_HOST=<host-do-banco>
DB_DATABASE=<nome-do-banco>
DB_USERNAME=<usuario>
DB_PASSWORD=<senha-forte>

SESSION_DRIVER=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=<smtp-server>
MAIL_PORT=587
MAIL_USERNAME=<usuario-smtp>
MAIL_PASSWORD=<senha-smtp>
MAIL_FROM_ADDRESS=<remetente@dominio.com>
MAIL_FROM_NAME=TSAdmin
```

### 3. Exemplo de docker-compose para produção

```yaml
services:
  app:
    image: tsadmin:prod
    restart: always
    ports:
      - '8000:8000'
    environment:
      APP_ENV: production
      APP_DEBUG: false
      APP_KEY: ${APP_KEY}
      APP_URL: https://seu-dominio.com
      DB_HOST: db
      DB_DATABASE: ${DB_DATABASE:-ts_admin}
      DB_USERNAME: ${DB_USERNAME}
      DB_PASSWORD: ${DB_PASSWORD}
      MAIL_HOST: ${MAIL_HOST}
      MAIL_PORT: ${MAIL_PORT}
      MAIL_USERNAME: ${MAIL_USERNAME}
      MAIL_PASSWORD: ${MAIL_PASSWORD}
      MAIL_FROM_ADDRESS: ${MAIL_FROM_ADDRESS}
    depends_on:
      db:
        condition: service_healthy

  queue:
    image: tsadmin:prod
    restart: always
    command: php artisan queue:work --tries=3 --timeout=90
    environment:
      APP_ENV: production
      APP_KEY: ${APP_KEY}
      DB_HOST: db
      DB_DATABASE: ${DB_DATABASE:-ts_admin}
      DB_USERNAME: ${DB_USERNAME}
      DB_PASSWORD: ${DB_PASSWORD}
    depends_on:
      db:
        condition: service_healthy

  db:
    image: postgres:16-alpine
    restart: always
    environment:
      POSTGRES_DB: ${DB_DATABASE:-ts_admin}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - db-data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME} -d ${DB_DATABASE:-ts_admin}"]
      interval: 5s
      retries: 10

volumes:
  db-data:
```

### 4. Primeiro deploy

```bash
# Migrações são executadas automaticamente na inicialização do container.
# Seeders estão desabilitados em produção — crie o usuário admin manualmente:
docker compose exec app php artisan tinker --execute="
  App\Models\User::create([
    'name'              => 'Administrador',
    'email'             => 'admin@seu-dominio.com',
    'password'          => 'senha-forte-aqui',
    'role'              => 'admin',
    'status'            => 'active',
    'email_verified_at' => now(),
  ]);
"
```

### 5. Nginx como proxy reverso

```nginx
server {
    listen 80;
    server_name seu-dominio.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name seu-dominio.com;

    ssl_certificate     /etc/ssl/certs/cert.pem;
    ssl_certificate_key /etc/ssl/private/key.pem;

    location / {
        proxy_pass         http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header   Host              $host;
        proxy_set_header   X-Real-IP         $remote_addr;
        proxy_set_header   X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
    }
}
```

### 6. Comandos pós-deploy

```bash
# Atualizar sem downtime (após nova imagem)
docker compose pull
docker compose up -d --no-deps app queue

# Rodar migrações de atualização
docker compose exec app php artisan migrate --force

# Limpar e recriar caches manualmente (se necessário)
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan optimize
```

---

## 📁 Estrutura do Projeto

```
tsadmin/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # 34 controllers (Auth, Admin, Inventory, etc.)
│   │   ├── Middleware/         # Authenticate, EnsureUserIsAdmin, CheckPolicy...
│   │   └── Requests/           # Form requests com regras de validação
│   ├── Models/                 # 31 modelos Eloquent
│   ├── Policies/               # 24 policies de autorização por recurso
│   ├── Providers/              # AppServiceProvider, AuthServiceProvider
│   └── Services/               # Serviços de lógica de negócio
├── config/
│   ├── permissions.php         # Definição dos recursos e ações permitidas
│   └── ...                     # database, cache, queue, mail, etc.
├── database/
│   ├── migrations/             # 20+ migrations
│   ├── seeders/                # 31 seeders (Base, Demo, Dev, Test)
│   └── factories/              # Factories para testes
├── docker/
│   ├── entrypoint.dev.sh       # Script de inicialização (desenvolvimento)
│   └── entrypoint.prod.sh      # Script de inicialização (produção)
├── docs/                       # Documentação técnica dos módulos
├── public/build/               # Assets compilados pelo Vite (gitignored)
├── resources/
│   ├── css/app.css             # Tailwind CSS
│   └── js/
│       ├── app.js              # Entrypoint principal
│       ├── inertia.js          # Entrypoint Inertia.js
│       ├── ziggy.js            # Rotas geradas — gitignored, recriado no boot
│       ├── Components/         # Componentes Vue reutilizáveis
│       ├── Layouts/            # Layouts da aplicação
│       └── Pages/              # 76 páginas Vue organizadas por módulo
│           ├── Auth/           # Login, registro, recuperação de senha
│           ├── Admin/          # Dashboard e CRUD de todos os módulos
│           └── Errors/         # Páginas de erro (403, 404, 419, 500)
├── routes/
│   └── web.php                 # Todas as rotas (guest + admin protegidas)
├── tests/
│   ├── Feature/                # Testes de integração
│   └── Unit/                   # Testes unitários
├── .env.example                # Template de variáveis de ambiente
├── .dockerignore
├── Dockerfile                  # Multi-stage: base → development / production
├── docker-compose.yml          # app, queue, db, mailpit
├── composer.json
├── package.json
├── vite.config.js
└── phpunit.xml
```

---

## 🛠️ Referência de Comandos

### Composer

```bash
composer run dev      # Inicia servidor, queue, logs e Vite (tudo junto)
composer run test     # Limpa config cache e roda os testes
```

### NPM

```bash
npm run dev           # Gera rotas Ziggy + inicia Vite dev server
npm run build         # Gera rotas Ziggy + build de produção
npm run ziggy         # Apenas regenera resources/js/ziggy.js
npm run icons         # Copia ícones Heroicons para o projeto
```

### Artisan

```bash
# Banco de dados
php artisan migrate                          # Roda migrações pendentes
php artisan migrate:fresh --seed             # Recria o banco do zero + seeds
php artisan db:seed                          # Roda o DatabaseSeeder padrão
php artisan db:seed --class=DemoSeeder       # Seed completo com dados fictícios

# Cache
php artisan optimize                         # Cacheia config, rotas e views
php artisan optimize:clear                   # Limpa todos os caches
php artisan config:cache                     # Cacheia apenas configurações
php artisan route:cache                      # Cacheia apenas rotas

# Filas
php artisan queue:listen --tries=1           # Worker de filas (dev)
php artisan queue:work --tries=3             # Worker de filas (prod, sem reload)
php artisan queue:failed                     # Lista jobs com falha

# Utilitários
php artisan ziggy:generate                   # Regenera rotas para o frontend
php artisan tinker                           # REPL interativo
php artisan pail                             # Logs em tempo real
php artisan about                            # Informações do ambiente
```

---

## 📄 Licença

**Sem licença (No license).**

Este repositório é disponibilizado apenas para **visualização**. **Não é permitido** usar, copiar, modificar ou distribuir o código sem autorização **por escrito** do autor.

Todos os direitos reservados.
