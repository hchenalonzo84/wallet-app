<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\Pocket;
use App\Models\RecurringPayment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Servicio encargado de administrar y ejecutar pagos automáticos.
 *
 * Sus responsabilidades principales son:
 * - crear pagos recurrentes,
 * - actualizar su configuración,
 * - desactivarlos,
 * - calcular fechas mensuales,
 * - procesar pagos vencidos,
 * - generar movimientos withdrawal automáticamente.
 */
class RecurringPaymentService
{
    /**
     * Crea un nuevo pago automático
     * y calcula su primer vencimiento.
     */
    public function create(
        User $user,
        array $data
    ): RecurringPayment {
        return DB::transaction(function () use ($user, $data) {
            $timezone = (string) config(
                'app.timezone',
                'UTC'
            );

            /*
             * Convierte la fecha inicial recibida
             * en una fecha inmutable.
             */
            $startsOn = CarbonImmutable::parse(
                $data['starts_on'],
                $timezone
            )->startOfDay();

            /*
             * El día mensual se obtiene directamente
             * de la fecha inicial elegida por el usuario.
             *
             * Ejemplo:
             * 2026-09-15 -> billing_day = 15.
             */
            $billingDay = $startsOn->day;

            /*
             * Calcula el primer vencimiento.
             */
            $nextDueOn = $this->calculateDueDate(
                $startsOn->year,
                $startsOn->month,
                $billingDay
            );

            return RecurringPayment::create([
                'user_id' => $user->id,
                'pocket_id' => $data['pocket_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,

                /*
                 * Normaliza siempre el monto a dos decimales.
                 */
                'amount' => bcadd(
                    (string) $data['amount'],
                    '0',
                    2
                ),

                /*
                 * Primera versión:
                 * solamente recurrencia mensual.
                 */
                'frequency' => 'monthly',

                'billing_day' => $billingDay,
                'starts_on' => $startsOn->toDateString(),
                'next_due_on' => $nextDueOn->toDateString(),
                'ends_on' => $data['ends_on'] ?? null,
                'is_active' => true,
            ]);
        });
    }

    /**
     * Actualiza un pago automático existente.
     *
     * Los movimientos históricos ya generados
     * nunca son modificados.
     */
    public function update(
        RecurringPayment $recurringPayment,
        array $data
    ): RecurringPayment {
        return DB::transaction(
            function () use ($recurringPayment, $data) {
                $timezone = (string) config(
                    'app.timezone',
                    'UTC'
                );

                /*
                 * Si se recibe una nueva fecha inicial,
                 * utilizamos esa fecha.
                 *
                 * De lo contrario conservamos la existente.
                 */
                $startsOn = isset($data['starts_on'])
                    ? CarbonImmutable::parse(
                        $data['starts_on'],
                        $timezone
                    )->startOfDay()
                    : CarbonImmutable::parse(
                        $recurringPayment->starts_on->toDateString(),
                        $timezone
                    )->startOfDay();

                /*
                 * Determina cuál será la fecha final efectiva.
                 */
                $endsOnValue = array_key_exists(
                    'ends_on',
                    $data
                )
                    ? $data['ends_on']
                    : $recurringPayment->ends_on?->toDateString();

                $endsOn = $endsOnValue !== null
                    ? CarbonImmutable::parse(
                        $endsOnValue,
                        $timezone
                    )->startOfDay()
                    : null;

                /*
                 * Protección adicional:
                 * la fecha final nunca puede ser anterior
                 * a la fecha inicial efectiva.
                 */
                if (
                    $endsOn !== null
                    && $endsOn->lt($startsOn)
                ) {
                    throw ValidationException::withMessages([
                        'ends_on' => [
                            'La fecha final no puede ser anterior a la fecha inicial.',
                        ],
                    ]);
                }

                /*
                 * El día mensual corresponde al día
                 * de la fecha inicial.
                 */
                $billingDay = $startsOn->day;

                $recurringPayment->fill([
                    'pocket_id' => $data['pocket_id']
                        ?? $recurringPayment->pocket_id,

                    'name' => $data['name']
                        ?? $recurringPayment->name,

                    /*
                     * array_key_exists permite distinguir:
                     *
                     * campo ausente -> conservar valor actual.
                     * campo = null  -> eliminar descripción.
                     */
                    'description' => array_key_exists(
                        'description',
                        $data
                    )
                        ? $data['description']
                        : $recurringPayment->description,

                    'amount' => isset($data['amount'])
                        ? bcadd(
                            (string) $data['amount'],
                            '0',
                            2
                        )
                        : $recurringPayment->amount,

                    'starts_on' => $startsOn->toDateString(),
                    'billing_day' => $billingDay,
                    'ends_on' => $endsOn?->toDateString(),
                ]);

                /*
                 * Si cambia starts_on también cambia
                 * el calendario futuro del pago.
                 */
                if (isset($data['starts_on'])) {
                    $recurringPayment->next_due_on =
                        $this->calculateDueDate(
                            $startsOn->year,
                            $startsOn->month,
                            $billingDay
                        )->toDateString();
                }

                /*
                 * Si la nueva fecha final queda antes
                 * del próximo vencimiento, ya no existe
                 * otro pago pendiente.
                 */
                if (
                    $endsOn !== null
                    && $recurringPayment->next_due_on !== null
                ) {
                    $nextDueOn = CarbonImmutable::parse(
                        $recurringPayment->next_due_on->toDateString(),
                        $timezone
                    )->startOfDay();

                    if ($nextDueOn->gt($endsOn)) {
                        $recurringPayment->is_active = false;
                        $recurringPayment->next_due_on = null;
                    }
                }

                $recurringPayment->save();

                return $recurringPayment->refresh();
            }
        );
    }

    /**
     * Desactiva manualmente un pago automático.
     *
     * No elimina el registro ni su historial.
     */
    public function deactivate(
        RecurringPayment $recurringPayment
    ): RecurringPayment {
        $recurringPayment->update([
            'is_active' => false,
        ]);

        return $recurringPayment->refresh();
    }

    /**
     * Procesa todos los pagos automáticos vencidos
     * hasta la fecha indicada.
     *
     * Devuelve la cantidad de movimientos nuevos creados.
     *
     * $today se puede proporcionar manualmente en pruebas.
     */
    public function processDuePayments(
        ?CarbonImmutable $today = null
    ): int {
        $timezone = (string) config(
            'app.timezone',
            'UTC'
        );

        /*
         * Si no recibimos una fecha específica,
         * utilizamos el día actual.
         */
        $processingDate = $today !== null
            ? $today->setTimezone($timezone)->startOfDay()
            : CarbonImmutable::now($timezone)->startOfDay();

        /*
         * Obtenemos solamente pagos:
         * - activos,
         * - con próximo vencimiento,
         * - cuya fecha ya llegó.
         */
        $recurringPaymentIds = RecurringPayment::query()
            ->where('is_active', true)
            ->whereNotNull('next_due_on')
            ->whereDate(
                'next_due_on',
                '<=',
                $processingDate->toDateString()
            )
            ->orderBy('id')
            ->pluck('id');

        $createdMovements = 0;

        /*
         * Procesamos cada pago individualmente.
         */
        foreach ($recurringPaymentIds as $id) {
            $createdMovements +=
                $this->processRecurringPayment(
                    (int) $id,
                    $processingDate
                );
        }

        return $createdMovements;
    }

    /**
     * Calcula el próximo vencimiento mensual
     * de un pago automático.
     */
    public function calculateNextDueDate(
        RecurringPayment $recurringPayment
    ): CarbonImmutable {
        $timezone = (string) config(
            'app.timezone',
            'UTC'
        );

        /*
         * Parte del vencimiento actual.
         */
        $currentDueDate = CarbonImmutable::parse(
            $recurringPayment->next_due_on->toDateString(),
            $timezone
        )->startOfDay();

        /*
         * Nos movemos al primer día del siguiente mes.
         *
         * Esto evita problemas al pasar desde días
         * como 29, 30 o 31.
         */
        $nextMonth = $currentDueDate
            ->startOfMonth()
            ->addMonth();

        return $this->calculateDueDate(
            $nextMonth->year,
            $nextMonth->month,
            $recurringPayment->billing_day
        );
    }

    /**
     * Obtiene una fecha mensual válida.
     *
     * Si billing_day no existe en ese mes,
     * utiliza el último día disponible.
     *
     * Ejemplo para billing_day = 31:
     * enero   -> 31
     * febrero -> 28/29
     * marzo   -> 31
     * abril   -> 30
     */
    public function calculateDueDate(
        int $year,
        int $month,
        int $billingDay
    ): CarbonImmutable {
        $timezone = (string) config(
            'app.timezone',
            'UTC'
        );

        /*
         * Creamos primero el día 1 para garantizar
         * que siempre partimos de una fecha válida.
         */
        $firstDayOfMonth = CarbonImmutable::create(
            $year,
            $month,
            1,
            0,
            0,
            0,
            $timezone
        );

        /*
         * Si el día solicitado supera los días
         * disponibles, utilizamos el último del mes.
         */
        $validDay = min(
            $billingDay,
            $firstDayOfMonth->daysInMonth
        );

        return $firstDayOfMonth
            ->setDay($validDay)
            ->startOfDay();
    }

    /**
     * Procesa un pago automático individual.
     *
     * El registro se bloquea mientras se procesa
     * para evitar ejecuciones simultáneas.
     */
    private function processRecurringPayment(
        int $recurringPaymentId,
        CarbonImmutable $processingDate
    ): int {
        return DB::transaction(
            function () use (
                $recurringPaymentId,
                $processingDate
            ) {
                $timezone = (string) config(
                    'app.timezone',
                    'UTC'
                );

                /*
                 * Bloquea el pago automático hasta
                 * finalizar esta transacción.
                 *
                 * Esto ayuda a impedir que dos procesos
                 * lo ejecuten simultáneamente.
                 */
                $recurringPayment = RecurringPayment::query()
                    ->whereKey($recurringPaymentId)
                    ->lockForUpdate()
                    ->first();

                /*
                 * Puede haber cambiado desde que realizamos
                 * la consulta inicial.
                 */
                if (
                    $recurringPayment === null
                    || ! $recurringPayment->is_active
                    || $recurringPayment->next_due_on === null
                ) {
                    return 0;
                }

                /*
                 * También bloqueamos el bolsillo asociado.
                 */
                $pocket = Pocket::query()
                    ->whereKey($recurringPayment->pocket_id)
                    ->where(
                        'user_id',
                        $recurringPayment->user_id
                    )
                    ->lockForUpdate()
                    ->first();

                /*
                 * Si el bolsillo ya no está activo,
                 * detenemos automáticamente la recurrencia.
                 *
                 * No se genera ningún retiro.
                 */
                if (
                    $pocket === null
                    || ! $pocket->is_active
                ) {
                    $recurringPayment->update([
                        'is_active' => false,
                    ]);

                    return 0;
                }

                $createdMovements = 0;

                /*
                 * El bucle permite recuperar varios meses
                 * pendientes si el scheduler estuvo detenido.
                 */
                while (
                    $recurringPayment->is_active
                    && $recurringPayment->next_due_on !== null
                ) {
                    $dueDate = CarbonImmutable::parse(
                        $recurringPayment
                            ->next_due_on
                            ->toDateString(),
                        $timezone
                    )->startOfDay();

                    /*
                     * Si el siguiente pago todavía no vence,
                     * terminamos el procesamiento.
                     */
                    if ($dueDate->gt($processingDate)) {
                        break;
                    }

                    $endsOn = $recurringPayment->ends_on !== null
                        ? CarbonImmutable::parse(
                            $recurringPayment
                                ->ends_on
                                ->toDateString(),
                            $timezone
                        )->startOfDay()
                        : null;

                    /*
                     * Si ya superamos la fecha final,
                     * cerramos la recurrencia.
                     */
                    if (
                        $endsOn !== null
                        && $dueDate->gt($endsOn)
                    ) {
                        $recurringPayment->is_active = false;
                        $recurringPayment->next_due_on = null;
                        $recurringPayment->save();

                        break;
                    }

                    /*
                     * Verificamos si este vencimiento
                     * ya produjo un movimiento.
                     *
                     * Además de esta comprobación, PostgreSQL
                     * tiene una restricción UNIQUE sobre:
                     *
                     * recurring_payment_id + scheduled_for
                     */
                    $alreadyProcessed = Movement::query()
                        ->where(
                            'recurring_payment_id',
                            $recurringPayment->id
                        )
                        ->whereDate(
                            'scheduled_for',
                            $dueDate->toDateString()
                        )
                        ->exists();

                    /*
                     * Solo creamos el retiro si el vencimiento
                     * todavía no fue registrado.
                     */
                    if (! $alreadyProcessed) {
                        Movement::create([
                            'user_id' =>
                                $recurringPayment->user_id,

                            'pocket_id' =>
                                $recurringPayment->pocket_id,

                            /*
                             * Los pagos automáticos utilizan
                             * el tipo financiero normal withdrawal.
                             */
                            'type' => 'withdrawal',

                            'amount' =>
                                $recurringPayment->amount,

                            /*
                             * Guardamos una descripción histórica.
                             * Si después cambia el nombre del pago,
                             * el movimiento antiguo conserva su texto.
                             */
                            'description' =>
                                $this->movementDescription(
                                    $recurringPayment
                                ),

                            /*
                             * occurred_at representa cuándo
                             * corresponde financieramente el cobro.
                             */
                            'occurred_at' => $dueDate,

                            /*
                             * Permite conocer qué pago automático
                             * originó este movimiento.
                             */
                            'recurring_payment_id' =>
                                $recurringPayment->id,

                            /*
                             * Fecha exacta programada para evitar
                             * duplicados del mismo vencimiento.
                             */
                            'scheduled_for' =>
                                $dueDate->toDateString(),
                        ]);

                        $createdMovements++;
                    }

                    /*
                     * Calculamos el siguiente mes conservando
                     * el billing_day original.
                     */
                    $nextDueDate =
                        $this->calculateNextDueDate(
                            $recurringPayment
                        );

                    /*
                     * Si el próximo vencimiento superaría
                     * ends_on, la recurrencia termina aquí.
                     */
                    if (
                        $endsOn !== null
                        && $nextDueDate->gt($endsOn)
                    ) {
                        $recurringPayment->is_active = false;
                        $recurringPayment->next_due_on = null;
                    } else {
                        $recurringPayment->next_due_on =
                            $nextDueDate->toDateString();
                    }

                    $recurringPayment->save();
                }

                return $createdMovements;
            }
        );
    }

    /**
     * Construye la descripción almacenada
     * en el movimiento automático.
     *
     * Se limita a 500 caracteres porque ese es
     * el tamaño máximo de movements.description.
     */
    private function movementDescription(
        RecurringPayment $recurringPayment
    ): string {
        $description = 'Pago automático: '
            .$recurringPayment->name;

        /*
         * Agrega la descripción adicional cuando existe.
         */
        if (
            $recurringPayment->description !== null
            && $recurringPayment->description !== ''
        ) {
            $description .= ' - '
                .$recurringPayment->description;
        }

        return Str::limit(
            $description,
            500,
            ''
        );
    }
}