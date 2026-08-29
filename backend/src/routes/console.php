<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/**
 * Comando de ejemplo incluido por Laravel.
 *
 * Muestra una frase inspiradora cuando se ejecuta:
 * php artisan inspire
 */
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Procesa periódicamente los pagos automáticos vencidos.
 *
 * Se ejecuta cada hora para que, si el servidor estuvo apagado
 * o el scheduler se reinició, pueda recuperar rápidamente
 * cualquier pago pendiente del mismo día.
 *
 * processDuePayments() es idempotente, por lo que ejecutar
 * este comando varias veces no duplica movimientos.
 */
Schedule::command('recurring-payments:process')
    ->hourly()

    /*
     * Evita que una nueva ejecución comience mientras
     * la ejecución anterior todavía esté trabajando.
     */
    ->withoutOverlapping();