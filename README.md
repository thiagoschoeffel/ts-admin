# TSAdmin

Web-based system for industrial and commercial management of EPS (expanded polystyrene). Controls production (blocks and molded parts), shipping, inventory, raw materials, silos, records, machine downtime, CRM for clients/leads/opportunities, orders with PDF export, and user management with granular permissions.

<img width="1885" height="863" alt="image" src="https://github.com/user-attachments/assets/5065fa10-f91f-465e-9b67-368de035b911" />

## 🚀 Stack

| Layer           | Technology         |
|-----------------|--------------------|
| Backend         | PHP 8.4, Laravel 12 |
| Frontend        | Vue 3, Inertia.js, Tailwind CSS 4 |
| Database        | PostgreSQL 16      |
| Build Tools     | Vite 7, Ziggy (JS routes) |
| Charts          | ApexCharts         |
| PDF Generation  | DomPDF             |
| Email (dev)     | Mailpit            |
| Containerization| Docker & Docker Compose |

## ✨ Features

- **Dashboard** with production, inventory, and real-time indicator charts
- **CRM** — Leads, interactions, and opportunity pipeline
- **Clients** with multiple addresses
- **Orders** with items, status control, and PDF export
- **Products** with components (technical list)
- **Production** — Records, blocks, and molded parts
- **Shipping** — Dispatch of blocks and molded parts by client
- **Inventory** — Movements, reservations, silos, and raw materials
- **Infrastructure** — Machines, operators, sectors, reasons for downtimes
- **Users** with roles (`admin` / `user`) and granular permissions by resource
- Email verification and password reset

---

## 📦 Running with Docker *(recommended)*

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) ≥ 24
- [Docker Compose](https://docs.docker.com/compose/) v2

### Steps

```bash
# 1. Clone and enter the directory
git clone <repository-url>
cd tsadmin

# 2. Copy the environment file
cp .env.example .env

# 3. Start all services (first run builds automatically)
docker compose up -d --build
```

On initialization, the container automatically:
- Waits for the database to be available
- Generates `APP_KEY` if empty in `.env`
- Runs `php artisan migrate`
- Creates default users (`admin@example.com` and `user@example.com`) via idempotent seeders
- Generates Ziggy routes for the frontend
- Caches config/routes in the Docker volume (does not affect host files)
- Starts the PHP server and Vite dev server with HMR

Once up, access:

| Service          | URL                         |
|------------------|-----------------------------|
| Application      | [http://localhost:8080](http://localhost:8080) |
| Vite HMR         | [http://localhost:5173](http://localhost:5173) |
| Mailpit (emails) | [http://localhost:8025](http://localhost:8025) |
| PostgreSQL       | `localhost:5432`           |

> **Note:** If port `8080` is occupied, use `APP_PORT=9090 docker compose up`.

### Default Users

The following users are automatically created on the first initialization (and ensured on every restart via `updateOrCreate`):

| Email               | Password   | Role  |
|---------------------|------------|-------|
| `admin@example.com` | `password` | admin |
| `user@example.com`  | `password` | user  |

### Populate with demo data

```bash
# Demo seeder (all resources with fictitious data)
# ⚠️ Truncates and recreates all data — use only in development
docker compose exec app php artisan db:seed --class=DemoSeeder
```

### Useful Docker Commands

```bash
# View real-time logs
docker compose logs -f app

# Access the container shell
docker compose exec app bash

# Run artisan commands inside the container
docker compose exec app php artisan <command>

# Run tests
docker compose exec app php artisan test

# Stop services
docker compose stop

# Restart (no rebuild)
docker compose up -d

# Shutdown (containers + network; data volumes are preserved)
docker compose down

# Shutdown and remove ALL volumes (⚠️ deletes database)
docker compose down -v
```

### Rebuild after Dockerfile changes

```bash
docker compose build app
docker compose up -d
```

### Docker Service Structure

```
docker-compose.yml
├── app      → Laravel + Vite dev server  (PHP 8.4, Node 22)
├── queue    → Queue worker               (same Dockerfile, development target)
├── db       → PostgreSQL 16 Alpine
└── mailpit  → SMTP + web interface for test emails
```

**Named volumes:**

| Volume            | Mounted at                    | Purpose                   |
|-------------------|-------------------------------|---------------------------|
| `vendor`          | `/var/www/html/vendor`        | PHP dependencies (host-independent) |
| `node_modules`    | `/var/www/html/node_modules`  | Node dependencies (host-independent) |
| `bootstrap_cache` | `/var/www/html/bootstrap/cache` | Docker cache (does not affect host) |
| `db-data`         | PostgreSQL data              | Database persistence       |

---

## 🖥️ Running without Docker (local environment)

### Prerequisites

- PHP ≥ 8.4 with extensions: `pdo_pgsql`, `mbstring`, `gd`, `zip`, `intl`, `bcmath`, `pcntl`, `exif`, `dom`
- [Composer](https://getcomposer.org/) ≥ 2
- [Node.js](https://nodejs.org/) ≥ 18 + npm
- PostgreSQL ≥ 14

### Installation

```bash
# 1. Clone the repository
git clone <repository-url>
cd tsadmin

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Configure the environment
cp .env.example .env
php artisan key:generate

# 5. Configure the database in your .env file
# DB_HOST=127.0.0.1
# DB_DATABASE=ts_admin
# DB_USERNAME=your_user
# DB_PASSWORD=your_password

# 6. Create the tables
php artisan migrate

# 7. (Optional) Populate with example data
php artisan db:seed --class=DemoSeeder
```

### Start the development environment

```bash
# Start all processes together (server, queue, logs, and Vite server)
composer run dev
```

Or start them separately in different terminals:

```bash
# Terminal 1 — PHP server
php artisan serve

# Terminal 2 — Queue worker
php artisan queue:listen --tries=1

# Terminal 3 — Vite (assets + HMR)
npm run dev

# Terminal 4 — Real-time logs (optional)
php artisan pail
```

---

## 🔧 Environment Variables

Copy `.env.example` to `.env` and adjust according to your environment. Key variables are described below.

### Application

| Variable       | Default       | Description                           |
|----------------|---------------|---------------------------------------|
| `APP_NAME`     | `TSAdmin`     | Application name                      |
| `APP_ENV`      | `local`       | Environment (`local`, `production`, `testing`) |
| `APP_KEY`      | *(empty)*     | Encryption key — generate with `php artisan key:generate` |
| `APP_DEBUG`    | `true`        | Enables debug and detailed stack traces |
| `APP_URL`      | `http://localhost` | Base URL for the application         |
| `APP_LOCALE`   | `pt_BR`       | Default locale                        |

### Database

| Variable       | Default       | Description                           |
|----------------|---------------|---------------------------------------|
| `DB_CONNECTION`| `pgsql`       | Database driver                       |
| `DB_HOST`      | `db`          | PostgreSQL host (Docker: `db`; local: `127.0.0.1`) |
| `DB_PORT`      | `5432`        | Port                                  |
| `DB_DATABASE`  | `ts_admin`    | Database name                         |
| `DB_USERNAME`  | `admin`       | User                                  |
| `DB_PASSWORD`  | *(empty)*     | Password — set in `.env`              |

### Cache, Sessions, and Queues

| Variable        | Default       | Description                           |
|-----------------|---------------|---------------------------------------|
| `CACHE_STORE`   | `database`    | Cache driver (`database`, `redis`, `file`) |
| `SESSION_DRIVER`| `database`    | Session driver (`database`, `redis`, `cookie`) |
| `SESSION_LIFETIME` | `120`      | Session duration in minutes           |
| `QUEUE_CONNECTION` | `database` | Queue driver (`database`, `redis`, `sync`) |

### Email

| Variable        | Default         | Description                           |
|-----------------|-----------------|---------------------------------------|
| `MAIL_MAILER`   | `smtp`          | Email driver                          |
| `MAIL_HOST`     | `mailpit`       | SMTP server (Docker: `mailpit`; local: `127.0.0.1`) |
| `MAIL_PORT`     | `1025`          | SMTP port                             |
| `MAIL_FROM_ADDRESS` | `no-reply@ts-admin.local` | Default sender address      |
| `MAIL_FROM_NAME`| `${APP_NAME}`   | Default sender name                   |

---

## 🧪 Tests

```bash
# Run all tests
php artisan test

# With code coverage summary
php artisan test --coverage

# Specific suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Filter by test name
php artisan test --filter TestName

# Via Docker
docker compose exec app php artisan test
```

Tests use in-memory SQLite (configured in `phpunit.xml`) — no separate database is required for the test suite.

**What is covered:**

- Models: User, Client, Address, Product, Order, OrderItem, ProductComponent
- Controllers: ClientController, ProductController
- Form Requests: StoreClientRequest, UpdateClientRequest
- Policies: Client, User, Product, Order, Address and others
- Middlewares: Authenticate, HandleInertiaRequests
- Notifications: VerifyEmailNotification

---

## 🔐 Users and Permissions

### Roles

| Role   | Access                         |
|--------|---------------------------------|
| `admin`| Full access to all resources    |
| `user` | Restricted access by permissions |

### Granular Permissions

Users with the `user` role have permissions configured individually per resource. Each resource supports the following actions:

| Action    | Description                   |
|-----------|-------------------------------|
| `view`    | View listings and individual records |
| `create`  | Create new records            |
| `update`  | Edit existing records         |
| `delete`  | Delete records                |

Resources with extra actions:
- **orders**: `update_status`, `export_pdf`

### Manageable Resources by Permission

| Resource             | Description                              |
|----------------------|------------------------------------------|
| `clients`            | Clients                                 |
| `products`           | Products                                |
| `orders`             | Orders                                  |
| `leads`              | Leads                                   |
| `opportunities`      | Opportunities                           |
| `sectors`            | Sectors                                 |
| `raw_materials`      | Raw materials                           |
| `inventory_movements`| Inventory movements                     |
| `production_pointings` | Production records                     |
| `block_productions`  | Block productions                       |
| `molded_productions` | Molded productions                      |
| `block_dispatches`   | Block dispatches                        |
| `molded_dispatches`  | Molded dispatches                       |
| `silos`              | Silos                                   |
| `block_types`        | Block types                             |
| `almoxarifados`      | Warehouses                              |
| `machines`           | Machines                                |
| `operators`          | Operators                               |
| `reason_types`       | Reason types                            |
| `reasons`            | Reasons                                 |
| `machine_downtimes`  | Machine downtimes                       |

Permissions are enforced via **Laravel Policies** registered in the `AuthServiceProvider`. All controllers use `$this->authorize()` to check access before performing operations.

---

## 🚢 Production Deploy

### 1. Build the production image

```bash
docker build --target production -t tsadmin:prod .
```

The production image:
- Does not include Node.js or development dependencies
- Includes assets compiled by Vite in `public/build/`
- Automatically caches config, routes, and views on startup

### 2. Required production variables

```bash
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generate with: php artisan key:generate --show>
APP_URL=https://your-domain.com

DB_HOST=<database-host>
DB_DATABASE=<database-name>
DB_USERNAME=<username>
DB_PASSWORD=<strong-password>

SESSION_DRIVER=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=<smtp-server>
MAIL_PORT=587
MAIL_USERNAME=<smtp-username>
MAIL_PASSWORD=<smtp-password>
MAIL_FROM_ADDRESS=<sender@domain.com>
MAIL_FROM_NAME=TSAdmin
```

### 3. Example Docker Compose for production

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
      APP_URL: https://your-domain.com
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

### 4. First deploy

```bash
# Migrations are automatically executed at container startup.
# Seeders are disabled in production — create the admin user manually:
docker compose exec app php artisan tinker --execute="
  App\Models\User::create([
    'name'              => 'Administrator',
    'email'             => 'admin@your-domain.com',
    'password'          => 'strong-password-here',
    'role'              => 'admin',
    'status'            => 'active',
    'email_verified_at' => now(),
  ]);
"
```

### 5. Nginx as a reverse proxy

```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

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

### 6. Post-deploy commands

```bash
# Update without downtime (after new image)
docker compose pull
docker compose up -d --no-deps app queue

# Run update migrations
docker compose exec app php artisan migrate --force

# Manually clear and recreate caches if needed
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan optimize
```

---

## 📁 Project Structure

```
tsadmin/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # 34 controllers (Auth, Admin, Inventory, etc.)
│   │   ├── Middleware/         # Authenticate, EnsureUserIsAdmin, CheckPolicy...
│   │   └── Requests/           # Form requests with validation rules
│   ├── Models/                 # 31 Eloquent models
│   ├── Policies/               # 24 authorization policies by resource
│   ├── Providers/              # AppServiceProvider, AuthServiceProvider
│   └── Services/               # Business logic services
├── config/
│   ├── permissions.php         # Resource and action definitions
│   └── ...                     # database, cache, queue, mail, etc.
├── database/
│   ├── migrations/             # 20+ migrations
│   ├── seeders/                # 31 seeders (Base, Demo, Dev, Test)
│   └── factories/              # Factories for testing
├── docker/
│   ├── entrypoint.dev.sh       # Development initialization script
│   └── entrypoint.prod.sh      # Production initialization script
├── docs/                       # Technical module documentation
├── public/build/               # Compiled Vite assets (gitignored)
├── resources/
│   ├── css/app.css             # Tailwind CSS
│   └── js/
│       ├── app.js              # Main entrypoint
│       ├── inertia.js          # Inertia.js entrypoint
│       ├── ziggy.js            # Generated routes — gitignored, recreated at boot
│       ├── Components/         # Reusable Vue components
│       ├── Layouts/            # Application layouts
│       └── Pages/              # 76 Vue pages organized by module
│           ├── Auth/           # Login, registration, password recovery
│           ├── Admin/          # Dashboard and CRUD for all modules
│           └── Errors/         # Error pages (403, 404, 419, 500)
├── routes/
│   └── web.php                 # All routes (guest + admin protected)
├── tests/
│   ├── Feature/                # Integration tests
│   └── Unit/                   # Unit tests
├── .env.example                # Environment variable template
├── .dockerignore
├── Dockerfile                  # Multi-stage: base → development / production
├── docker-compose.yml          # app, queue, db, mailpit
├── composer.json
├── package.json
├── vite.config.js
└── phpunit.xml
```

---

## 🛠️ Command Reference

### Composer

```bash
composer run dev      # Starts server, queue, logs, and Vite (all together)
composer run test     # Clears config cache and runs tests
```

### NPM

```bash
npm run dev           # Generates Ziggy routes + starts Vite dev server
npm run build         # Generates Ziggy routes + production build
npm run ziggy         # Regenerates resources/js/ziggy.js only
npm run icons         # Copies Heroicons to the project
```

### Artisan

```bash
# Database
php artisan migrate                          # Run pending migrations
php artisan migrate:fresh --seed             # Recreate the database from scratch + seeds
php artisan db:seed                          # Run default DatabaseSeeder
php artisan db:seed --class=DemoSeeder       # Full seed with demo data

# Cache
php artisan optimize                         # Cache config, routes, and views
php artisan optimize:clear                   # Clear all caches
php artisan config:cache                     # Cache configurations only
php artisan route:cache                      # Cache routes only

# Queues
php artisan queue:listen --tries=1           # Development queue worker
php artisan queue:work --tries=3             # Production queue worker (no reload)
php artisan queue:failed                     # View failed jobs

# Utilities
php artisan ziggy:generate                   # Regenerate frontend routes
php artisan tinker                           # Interactive REPL
php artisan pail                             # Real-time logs
php artisan about                            # Environment information
```

---

## 📄 License

**No license.**

This repository is made available for **viewing only**. **Use, copying, modification, or distribution of the code** is not allowed without **written** authorization from the author.

All rights reserved.