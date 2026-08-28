<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| PHPUnit Testing Environment
|--------------------------------------------------------------------------
|
| Docker Compose ejecuta normalmente Laravel contra "wallet_app".
|
| Antes de cargar Laravel durante PHPUnit, forzamos todas las fuentes
| de variables de entorno para que las pruebas utilicen exclusivamente
| "wallet_app_testing".
|
*/

$testingEnvironment = [
    'APP_ENV' => 'testing',

    'DB_CONNECTION' => 'pgsql',
    'DB_HOST' => 'database',
    'DB_PORT' => '5432',
    'DB_DATABASE' => 'wallet_app_testing',

    /*
     * Evita que una URL completa de conexión pueda sobrescribir
     * los parámetros individuales anteriores.
     */
    'DB_URL' => '',
];

foreach ($testingEnvironment as $key => $value) {
    /*
     * Variable de entorno del proceso PHP.
     */
    putenv("{$key}={$value}");

    /*
     * Superglobal utilizada por PHPUnit / Dotenv.
     */
    $_ENV[$key] = $value;

    /*
     * Otra fuente que puede utilizar PHP / Laravel.
     */
    $_SERVER[$key] = $value;
}

/*
|--------------------------------------------------------------------------
| Composer Autoloader
|--------------------------------------------------------------------------
|
| Después de establecer el entorno de testing cargamos Composer.
| Laravel todavía no ha iniciado en este momento.
|
*/

require dirname(__DIR__).'/vendor/autoload.php';