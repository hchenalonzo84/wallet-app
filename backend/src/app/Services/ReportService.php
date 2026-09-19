<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\Pocket;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Servicio encargado de construir reportes financieros históricos.
 *
 * Los reportes se calculan a partir de los movimientos registrados
 * en cada bolsillo, sin depender de una tabla de balances históricos.
 */
class ReportService
{
    /**
     * Genera un reporte completo para un usuario.
     *
     * El reporte puede incluir todos los bolsillos del usuario
     * o solamente uno si se recibe pocket_id en los filtros.
     */
    public function generate(
        User $user,
        array $filters
    ): array {
        /*
         * Convierte los filtros recibidos en un rango real
         * de fechas: fecha inicial y fecha final.
         */
        [$from, $to] = $this->resolvePeriod($filters);

        /*
         * Obtiene únicamente los bolsillos pertenecientes
         * al usuario autenticado.
         */
        $pocketsQuery = Pocket::query()
            ->where('user_id', $user->id)
            ->orderBy('id');

        /*
         * Si el usuario solicita un bolsillo específico,
         * limitamos el reporte solamente a ese bolsillo.
         */
        if (! empty($filters['pocket_id'])) {
            $pocketsQuery->where(
                'id',
                $filters['pocket_id']
            );
        }

        $pockets = $pocketsQuery->get();

        /*
         * Genera un reporte individual para cada bolsillo.
         */
        $pocketReports = $pockets
            ->map(
                fn (Pocket $pocket) => $this->reportForPocket(
                    $user,
                    $pocket,
                    $from,
                    $to
                )
            )
            ->values();

        /*
         * Construye el resumen general sumando
         * los resultados de todos los bolsillos.
         */
        $summary = $this->buildSummary(
            $pocketReports
        );

        /*
         * Estructura final que será enviada al controlador/API.
         */
        return [
            'period' => [
                'type' => $filters['type'],
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],

            'summary' => $summary,

            'pockets' => $pocketReports,
        ];
    }

    /**
     * Construye el reporte financiero de un solo bolsillo
     * dentro del período solicitado.
     */
    private function reportForPocket(
        User $user,
        Pocket $pocket,
        CarbonImmutable $from,
        CarbonImmutable $to
    ): array {
        /*
         * Calcula cuánto dinero tenía el bolsillo
         * justo antes de comenzar el período.
         */
        $openingBalance = $this->openingBalance(
            $user,
            $pocket,
            $from
        );

        /*
         * Agrupa todos los movimientos ocurridos dentro del período.
         *
         * PostgreSQL calcula directamente:
         * - cantidad de movimientos
         * - saldos iniciales registrados dentro del período
         * - depósitos
         * - retiros
         * - ajustes
         * - transferencias
         */
        $stats = Movement::query()
            ->where('user_id', $user->id)
            ->where('pocket_id', $pocket->id)
            ->whereBetween(
                'occurred_at',
                [$from, $to]
            )
            ->selectRaw(
                "
                COUNT(*) AS movement_count,

                COALESCE(
                    SUM(
                        CASE
                            WHEN type = 'opening_balance'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS opening_entries,

                COALESCE(
                    SUM(
                        CASE
                            WHEN type = 'deposit'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS deposits,

                COALESCE(
                    SUM(
                        CASE
                            WHEN type = 'withdrawal'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS withdrawals,

                COALESCE(
                    SUM(
                        CASE
                            WHEN type = 'adjustment_in'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS adjustments_in,

                COALESCE(
                    SUM(
                        CASE
                            WHEN type = 'adjustment_out'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS adjustments_out,

                COALESCE(
                    SUM(
                        CASE
                            WHEN type = 'transfer_in'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS transfers_in,

                COALESCE(
                    SUM(
                        CASE
                            WHEN type = 'transfer_out'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS transfers_out
                "
            )
            ->first();

        /*
         * Normalizamos todos los importes monetarios
         * para trabajar siempre con dos decimales.
         */
        $openingEntries = $this->money(
            $stats->opening_entries
        );

        $deposits = $this->money(
            $stats->deposits
        );

        $withdrawals = $this->money(
            $stats->withdrawals
        );

        $adjustmentsIn = $this->money(
            $stats->adjustments_in
        );

        $adjustmentsOut = $this->money(
            $stats->adjustments_out
        );

        $transfersIn = $this->money(
            $stats->transfers_in
        );

        $transfersOut = $this->money(
            $stats->transfers_out
        );

        /*
         * Calcula todas las entradas del período.
         *
         * Se utiliza BCMath para evitar errores
         * de precisión con números decimales.
         */
        $entries = '0.00';

        foreach ([
            $openingEntries,
            $deposits,
            $adjustmentsIn,
            $transfersIn,
        ] as $value) {
            $entries = bcadd(
                $entries,
                $value,
                2
            );
        }

        /*
         * Calcula todas las salidas del período.
         */
        $exits = '0.00';

        foreach ([
            $withdrawals,
            $adjustmentsOut,
            $transfersOut,
        ] as $value) {
            $exits = bcadd(
                $exits,
                $value,
                2
            );
        }

        /*
         * Movimiento neto:
         *
         * entradas - salidas
         */
        $netMovement = bcsub(
            $entries,
            $exits,
            2
        );

        /*
         * Saldo final:
         *
         * saldo inicial + movimiento neto
         */
        $closingBalance = bcadd(
            $openingBalance,
            $netMovement,
            2
        );

        /*
         * Resultado financiero final del bolsillo.
         */
        return [
            'pocket' => [
                'id' => $pocket->id,
                'name' => $pocket->name,
                'is_active' => $pocket->is_active,
            ],

            'opening_balance' => $openingBalance,

            'opening_entries' => $openingEntries,

            'deposits' => $deposits,

            'withdrawals' => $withdrawals,

            'adjustments_in' => $adjustmentsIn,

            'adjustments_out' => $adjustmentsOut,

            'transfers_in' => $transfersIn,

            'transfers_out' => $transfersOut,

            'entries' => $entries,

            'exits' => $exits,

            'net_movement' => $netMovement,

            'closing_balance' => $closingBalance,

            'movement_count' => (int) $stats->movement_count,
        ];
    }

    /**
     * Calcula el saldo que tenía un bolsillo antes
     * de comenzar el período solicitado.
     */
    private function openingBalance(
        User $user,
        Pocket $pocket,
        CarbonImmutable $from
    ): string {
        /*
         * Solo toma movimientos anteriores a la fecha inicial.
         *
         * Los tipos positivos suman.
         * Los tipos negativos restan.
         */
        $balance = Movement::query()
            ->where('user_id', $user->id)
            ->where('pocket_id', $pocket->id)
            ->where(
                'occurred_at',
                '<',
                $from
            )
            ->selectRaw(
                "
                CAST(
                    COALESCE(
                        SUM(
                            CASE
                                WHEN type IN (
                                    'opening_balance',
                                    'deposit',
                                    'transfer_in',
                                    'adjustment_in'
                                )
                                THEN amount

                                WHEN type IN (
                                    'withdrawal',
                                    'transfer_out',
                                    'adjustment_out'
                                )
                                THEN -amount

                                ELSE 0
                            END
                        ),
                        0
                    )
                    AS NUMERIC(18, 2)
                ) AS balance
                "
            )
            ->value('balance');

        /*
         * Devuelve siempre el saldo con dos decimales.
         */
        return $this->money($balance);
    }

    /**
     * Construye el resumen general del reporte
     * sumando los resultados de todos los bolsillos.
     */
    private function buildSummary(
        Collection $reports
    ): array {
        /*
         * Inicializamos todos los acumuladores en cero.
         */
        $summary = [
            'opening_balance' => '0.00',
            'opening_entries' => '0.00',
            'deposits' => '0.00',
            'withdrawals' => '0.00',
            'adjustments_in' => '0.00',
            'adjustments_out' => '0.00',
            'transfers_in' => '0.00',
            'transfers_out' => '0.00',
            'entries' => '0.00',
            'exits' => '0.00',
            'net_movement' => '0.00',
            'closing_balance' => '0.00',
            'movement_count' => 0,
        ];

        /*
         * Recorre cada reporte individual de bolsillo.
         */
        foreach ($reports as $report) {
            /*
             * Suma todos los campos monetarios.
             */
            foreach ([
                'opening_balance',
                'opening_entries',
                'deposits',
                'withdrawals',
                'adjustments_in',
                'adjustments_out',
                'transfers_in',
                'transfers_out',
                'entries',
                'exits',
                'net_movement',
                'closing_balance',
            ] as $field) {
                $summary[$field] = bcadd(
                    $summary[$field],
                    $report[$field],
                    2
                );
            }

            /*
             * Suma por separado la cantidad de movimientos,
             * ya que este valor es entero y no monetario.
             */
            $summary['movement_count'] +=
                $report['movement_count'];
        }

        return $summary;
    }

    /**
     * Determina las fechas inicial y final del reporte
     * según el tipo de período solicitado.
     */
    private function resolvePeriod(
        array $filters
    ): array {
        /*
         * Utiliza la zona horaria configurada en Laravel.
         */
        $timezone = config(
            'app.timezone',
            'UTC'
        );

        /*
         * Cada tipo de reporte delega el cálculo
         * del rango a su método específico.
         */
        return match ($filters['type']) {
            'monthly' => $this->monthlyPeriod(
                (int) $filters['year'],
                (int) $filters['month'],
                $timezone
            ),

            'quarterly' => $this->quarterlyPeriod(
                (int) $filters['year'],
                (int) $filters['quarter'],
                $timezone
            ),

            'semiannual' => $this->semiannualPeriod(
                (int) $filters['year'],
                (int) $filters['semester'],
                $timezone
            ),

            'annual' => $this->annualPeriod(
                (int) $filters['year'],
                $timezone
            ),

            'custom' => $this->customPeriod(
                $filters['from'],
                $filters['to'],
                $timezone
            ),
        };
    }

    /**
     * Construye el rango completo de un mes.
     *
     * Ejemplo:
     * 2026-08-01 00:00:00
     * hasta
     * 2026-08-31 23:59:59
     */
    private function monthlyPeriod(
        int $year,
        int $month,
        string $timezone
    ): array {
        $from = CarbonImmutable::create(
            $year,
            $month,
            1,
            0,
            0,
            0,
            $timezone
        )->startOfDay();

        return [
            $from,
            $from->endOfMonth()->endOfDay(),
        ];
    }

    /**
     * Construye el rango de un trimestre.
     *
     * Trimestre 1: enero-marzo
     * Trimestre 2: abril-junio
     * Trimestre 3: julio-septiembre
     * Trimestre 4: octubre-diciembre
     */
    private function quarterlyPeriod(
        int $year,
        int $quarter,
        string $timezone
    ): array {
        /*
         * Convierte el número de trimestre
         * en el primer mes correspondiente.
         */
        $month = (($quarter - 1) * 3) + 1;

        $from = CarbonImmutable::create(
            $year,
            $month,
            1,
            0,
            0,
            0,
            $timezone
        )->startOfDay();

        /*
         * Sumamos dos meses para llegar al tercer mes
         * del trimestre y tomamos su último día.
         */
        $to = $from
            ->addMonths(2)
            ->endOfMonth()
            ->endOfDay();

        return [$from, $to];
    }

    /**
     * Construye el rango de un semestre.
     *
     * Semestre 1: enero-junio
     * Semestre 2: julio-diciembre
     */
    private function semiannualPeriod(
        int $year,
        int $semester,
        string $timezone
    ): array {
        /*
         * El primer semestre comienza en enero.
         * El segundo comienza en julio.
         */
        $month = $semester === 1
            ? 1
            : 7;

        $from = CarbonImmutable::create(
            $year,
            $month,
            1,
            0,
            0,
            0,
            $timezone
        )->startOfDay();

        /*
         * Sumamos cinco meses para llegar
         * al último mes del semestre.
         */
        $to = $from
            ->addMonths(5)
            ->endOfMonth()
            ->endOfDay();

        return [$from, $to];
    }

    /**
     * Construye el rango completo de un año.
     */
    private function annualPeriod(
        int $year,
        string $timezone
    ): array {
        $from = CarbonImmutable::create(
            $year,
            1,
            1,
            0,
            0,
            0,
            $timezone
        )->startOfDay();

        return [
            $from,
            $from->endOfYear()->endOfDay(),
        ];
    }

    /**
     * Construye un período personalizado
     * utilizando fechas indicadas directamente por el usuario.
     */
    private function customPeriod(
        string $from,
        string $to,
        string $timezone
    ): array {
        return [
            /*
             * La fecha inicial comienza a las 00:00:00.
             */
            CarbonImmutable::parse(
                $from,
                $timezone
            )->startOfDay(),

            /*
             * La fecha final incluye todo el día.
             */
            CarbonImmutable::parse(
                $to,
                $timezone
            )->endOfDay(),
        ];
    }

    /**
     * Normaliza un valor monetario para que siempre
     * sea una cadena con exactamente dos decimales.
     *
     * También convierte valores null en 0.00.
     */
    private function money(
        mixed $value
    ): string {
        return bcadd(
            (string) ($value ?? '0'),
            '0',
            2
        );
    }
}