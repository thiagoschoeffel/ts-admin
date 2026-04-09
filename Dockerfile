# =============================================================================
# TSAdmin Dockerfile
# Suporta dois targets: development (dev com hot-reload) e production
#
# Uso:
#   Dev:  docker compose up
#   Prod: docker build --target production -t tsadmin:prod .
# =============================================================================

# =============================================================================
# Stage 1: Base PHP com todas as extensões necessárias
# =============================================================================
FROM php:8.4-fpm-alpine AS base

ARG UID=1000
ARG GID=1000

# Dependências do sistema
RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    icu-dev

# Extensões PHP necessárias para Laravel + dompdf + PostgreSQL
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        dom \
        opcache \
        intl

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Cria usuário não-root com UID/GID do host (evita problemas de permissão em volumes)
RUN addgroup -g ${GID} -S appgroup \
    && adduser -u ${UID} -S appuser -G appgroup -s /bin/bash

WORKDIR /var/www/html

# =============================================================================
# Stage 2: Dependências PHP de produção (sem dev deps)
# =============================================================================
FROM base AS composer-prod

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# =============================================================================
# Stage 3: Build dos assets frontend (para produção)
# =============================================================================
FROM node:22-alpine AS node-builder

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --prefer-offline

COPY . .

# ziggy.js está rastreado no git — pula geração e executa vite diretamente
RUN npx vite build

# =============================================================================
# Stage 4: Imagem de desenvolvimento (PHP + Node.js para hot-reload)
# =============================================================================
FROM base AS development

# Node.js para o servidor Vite dev
RUN apk add --no-cache nodejs npm

WORKDIR /var/www/html

# Instala dependências PHP (incluindo dev para testes/tinker/pail)
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-interaction --prefer-dist

# Instala dependências Node
COPY package.json package-lock.json ./
RUN npm ci

# Copia o restante da aplicação
COPY . .

# Permissões
RUN chown -R appuser:appgroup /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Entrypoint de desenvolvimento
COPY docker/entrypoint.dev.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

USER appuser

# 8000 = PHP artisan serve | 5173 = Vite HMR
EXPOSE 8000 5173

ENTRYPOINT ["/entrypoint.sh"]

# =============================================================================
# Stage 5: Imagem de produção
# =============================================================================
FROM base AS production

WORKDIR /var/www/html

# Copia dependências PHP de produção
COPY --from=composer-prod /var/www/html/vendor ./vendor

# Copia assets compilados
COPY --from=node-builder /app/public/build ./public/build

# Copia a aplicação
COPY . .

# Permissões
RUN chown -R appuser:appgroup /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

COPY docker/entrypoint.prod.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

USER appuser

EXPOSE 8000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
