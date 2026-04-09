#!/bin/bash
set -e

echo "======================================================"
echo " TSAdmin — ambiente de produção"
echo "======================================================"

# --- Aguarda o banco de dados ---
echo ""
echo "==> Aguardando banco de dados (${DB_HOST:-db}:${DB_PORT:-5432})..."
until php -r "
    try {
        \$pdo = new PDO(
            'pgsql:host=${DB_HOST:-db};port=${DB_PORT:-5432};dbname=${DB_DATABASE:-ts_admin}',
            '${DB_USERNAME:-admin}',
            '${DB_PASSWORD:-admin}'
        );
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    echo "    Banco não disponível ainda, tentando novamente em 2s..."
    sleep 2
done
echo "    Banco de dados pronto!"

# --- Migrations ---
echo ""
echo "==> Executando migrations..."
php artisan migrate --force --ansi

# --- Cache de configuração (melhora performance em produção) ---
echo ""
echo "==> Cacheando configurações..."
php artisan config:cache --ansi
php artisan route:cache --ansi
php artisan view:cache --ansi
php artisan event:cache --ansi

echo ""
echo "==> Iniciando aplicação na porta 8000..."
echo ""

exec "$@"
