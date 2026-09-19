<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        /*
         * No permitimos ejecutar pruebas con configuración
         * Laravel cacheada.
         */
        $configCache = dirname(__DIR__)
            . '/bootstrap/cache/config.php';

        if (is_file($configCache)) {
            throw new RuntimeException(
                'Pruebas canceladas por seguridad. '
                . 'Existe bootstrap/cache/config.php. '
                . 'Ejecuta php artisan config:clear.'
            );
        }

        /*
         * Comprobamos las tres fuentes de variables de entorno
         * antes de permitir que Laravel inicialice el test.
         */
        $environmentValues = [
            'getenv' => getenv('APP_ENV') ?: null,
            '_ENV' => $_ENV['APP_ENV'] ?? null,
            '_SERVER' => $_SERVER['APP_ENV'] ?? null,
        ];

        foreach ($environmentValues as $source => $value) {
            if ($value !== 'testing') {
                throw new RuntimeException(
                    'Pruebas canceladas por seguridad. '
                    . "APP_ENV incorrecto en {$source}: "
                    . var_export($value, true)
                );
            }
        }

        $databaseValues = [
            'getenv' => getenv('DB_DATABASE') ?: null,
            '_ENV' => $_ENV['DB_DATABASE'] ?? null,
            '_SERVER' => $_SERVER['DB_DATABASE'] ?? null,
        ];

        foreach ($databaseValues as $source => $value) {
            if ($value !== 'wallet_app_testing') {
                throw new RuntimeException(
                    'Pruebas canceladas por seguridad. '
                    . "DB_DATABASE incorrecto en {$source}: "
                    . var_export($value, true)
                );
            }
        }

        /*
         * Ahora sí permitimos que Laravel arranque.
         */
        parent::setUp();

        /*
         * Último fusible:
         * comprobamos la configuración realmente cargada
         * por Laravel.
         */
        $connection = config('database.default');

        $database = config(
            "database.connections.{$connection}.database"
        );

        if ($database !== 'wallet_app_testing') {
            throw new RuntimeException(
                'Pruebas canceladas por seguridad. '
                . 'Laravel está utilizando la base: '
                . var_export($database, true)
            );
        }
    }
}