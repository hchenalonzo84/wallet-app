<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa un pago automático configurado por el usuario.
 *
 * Ejemplos:
 * - Netflix
 * - Internet
 * - Seguro
 * - Suscripciones mensuales
 */
#[Fillable([
    'user_id',
    'pocket_id',
    'name',
    'description',
    'amount',
    'frequency',
    'billing_day',
    'starts_on',
    'next_due_on',
    'ends_on',
    'is_active',
])]
class RecurringPayment extends Model
{
    /**
     * Usuario propietario del pago automático.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bolsillo desde el cual se descontará el pago.
     */
    public function pocket(): BelongsTo
    {
        return $this->belongsTo(Pocket::class);
    }

    /**
     * Movimientos generados automáticamente por este pago.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    /**
     * Convierte automáticamente los campos al tipo adecuado
     * cuando Laravel los lee desde la base de datos.
     */
    protected function casts(): array
    {
        return [
            // Mantiene el importe con dos decimales.
            'amount' => 'decimal:2',

            // Día del mes configurado para el cobro.
            'billing_day' => 'integer',

            // Fecha desde la que comienza el pago automático.
            'starts_on' => 'date',

            // Próxima fecha que debe procesar el scheduler.
            'next_due_on' => 'date',

            // Fecha opcional en la que finaliza la recurrencia.
            'ends_on' => 'date',

            // Indica si el pago automático sigue activo.
            'is_active' => 'boolean',
        ];
    }
}