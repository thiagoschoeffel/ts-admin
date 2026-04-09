#!/bin/bash
set -e

# Mata processos filhos ao sair (Ctrl+C funciona corretamente no modo padrão)
_cleanup() {
    echo ""
    echo "==> Encerrando..."
    kill $(jobs -p) 2>/dev/null || true
    exit 0
}

echo "======================================================"
echo " TSAdmin — ambiente de desenvolvimento"
echo "======================================================"

# --- Aguarda o banco de dados estar pronto ---
echo ""
echo "==> Aguardando banco de dados (${DB_HOST:-db}:${DB_PORT:-5432})..."
until php -r "
    try {
        new PDO(
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

# --- Gera APP_KEY se estiver vazio no .env ---
if [ -f ".env" ]; then
    APP_KEY_VALUE=$(grep "^APP_KEY=" .env | cut -d'=' -f2 | tr -d '"' | tr -d "'" | xargs)
    if [ -z "$APP_KEY_VALUE" ]; then
        echo ""
        echo "==> Gerando APP_KEY..."
        php artisan key:generate --ansi
    fi
fi

# --- Migrations ---
echo ""
echo "==> Executando migrations..."
php artisan migrate --force --ansi

# --- Seed: usuários base (idempotente via updateOrCreate) ---
# Garante que admin@example.com e user@example.com existam a cada start.
# UserSeeder usa updateOrCreate — seguro de rodar em todo restart.
echo ""
echo "==> Seeding usuários base..."
php artisan db:seed --class=UserSeeder --force --ansi

# --- Gera rotas Ziggy ---
echo ""
echo "==> Gerando rotas Ziggy..."
php artisan ziggy:generate --ansi

# --- Cache de configuração Docker ---
# Necessário para que env vars do Docker (DB_HOST=db, etc.) prevaleçam sobre
# o .env local montado via volume. O cache é gravado no volume bootstrap_cache
# e NÃO afeta os arquivos do host.
echo ""
echo "==> Cacheando configurações para o ambiente Docker..."
php artisan config:cache --ansi
php artisan route:cache --ansi

# --- Modo: comando customizado (ex: queue:listen) ---
# Se argumentos foram passados ao entrypoint, executa-os e para por aí.
if [ $# -gt 0 ]; then
    echo ""
    echo "==> Executando: $*"
    echo ""
    exec "$@"
fi

# --- Modo padrão: PHP serve + Vite dev server ---
trap _cleanup EXIT INT TERM

echo ""
echo "======================================================"
echo " Iniciando serviços:"
echo "   PHP  → http://localhost:8000"
echo "   Vite → http://localhost:5173"
echo "======================================================"
echo ""

php artisan serve --host=0.0.0.0 --port=8000 &
npx vite --host 0.0.0.0 &

wait
