<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa un bolsillo virtual del usuario.
 *
 * Cada bolsillo agrupa movimientos financieros y puede tener
 * pagos automáticos asociados.
 */
#[Fillable([
    'user_id',
    'name',
    'description',
    'is_active',
])]
class Pocket extends Model
{
    /**
     * Usuario propietario del bolsillo.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Movimientos financieros registrados en este bolsillo.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    /**
     * Pagos automáticos que se descuentan desde este bolsillo.
     */
    public function recurringPayments(): HasMany
    {
        return $this->hasMany(RecurringPayment::class);
    }

    /**
     * Convierte automáticamente ciertos campos al tipo adecuado
     * cuando Laravel los obtiene desde la base de datos.
     */
    protected function casts(): array
    {
        return [
            // Indica si el bolsillo está activo o desactivado.
            'is_active' => 'boolean',
        ];
    }
}