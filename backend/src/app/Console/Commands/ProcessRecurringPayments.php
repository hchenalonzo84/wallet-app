<?php

namespace App\Console\Commands;

use App\Services\RecurringPaymentService;
use Illuminate\Console\Command;

/**
 * Comando encargado de iniciar el procesamiento
 * de los pagos automáticos vencidos.
 *
 * Este comando será ejecutado posteriormente
 * por Laravel Scheduler.
 */
class ProcessRecurringPayments extends Command
{
    /**
     * Nombre utilizado para ejecutar el comando
     * desde Artisan.
     *
     * Ejemplo:
     * php artisan recurring-payments:process
     */
    protected $signature = 'recurring-payments:process';

    /**
     * Descripción mostrada en la lista de comandos Artisan.
     */
    protected $description =
        'Procesa los pagos automáticos que hayan llegado a su fecha de vencimiento.';

    /**
     * Ejecuta el procesamiento de pagos automáticos.
     *
     * RecurringPaymentService es inyectado automáticamente
     * por el contenedor de dependencias de Laravel.
     */
    public function handle(
        RecurringPaymentService $recurringPaymentService
    ): int {
        /*
         * processDuePayments() utiliza la fecha actual
         * configurada en la aplicación y procesa todos
         * los pagos cuyo next_due_on ya haya llegado.
         */
        $processedPayments =
            $recurringPaymentService->processDuePayments();

        /*
         * Mostramos información útil en consola y logs
         * para saber cuántos movimientos fueron generados.
         */
        $this->info(
            sprintf(
                'Pagos automáticos procesados: %d.',
                $processedPayments
            )
        );

        /*
         * Código 0 indica que el comando terminó
         * correctamente.
         */
        return self::SUCCESS;
    }
}