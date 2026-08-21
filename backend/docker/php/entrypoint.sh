#!/bin/sh
set -e

cd /var/www/html

echo "Wallet App - iniciando backend..."

# Laravel debe existir previamente.
# Docker NO crea ni recrea el proyecto Laravel.
if [ ! -f artisan ]; then
    echo "ERROR: No se encontró Laravel en /var/www/html."
    echo "El proyecto debe existir previamente en backend/src."
    exit 1
fi

# Archivo que utilizaremos para saber con qué composer.lock
# fueron instaladas las dependencias actuales.
COMPOSER_STAMP="vendor/.composer-lock.sha256"

# Calcular hash del composer.lock actual.
CURRENT_LOCK_HASH="$(sha256sum composer.lock | awk '{print $1}')"

INSTALL_DEPENDENCIES=false

# Primera instalación: vendor todavía no existe.
if [ ! -f vendor/autoload.php ]; then
    INSTALL_DEPENDENCIES=true
fi

# vendor existe, pero todavía no tenemos nuestra marca.
if [ ! -f "$COMPOSER_STAMP" ]; then
    INSTALL_DEPENDENCIES=true
fi

# composer.lock cambió desde la última instalación.
if [ -f "$COMPOSER_STAMP" ]; then
    INSTALLED_LOCK_HASH="$(cat "$COMPOSER_STAMP")"

    if [ "$CURRENT_LOCK_HASH" != "$INSTALLED_LOCK_HASH" ]; then
        INSTALL_DEPENDENCIES=true
    fi
fi

if [ "$INSTALL_DEPENDENCIES" = true ]; then
    echo "Instalando/actualizando dependencias de Composer..."

    composer install \
        --no-interaction \
        --prefer-dist \
        --no-progress

    echo "$CURRENT_LOCK_HASH" > "$COMPOSER_STAMP"

    echo "Dependencias de Composer preparadas."
else
    echo "Dependencias de Composer actualizadas. No se requiere instalación."
fi

echo "Iniciando PHP-FPM..."

exec "$@"