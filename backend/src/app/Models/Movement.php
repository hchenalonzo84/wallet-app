<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'pocket_id',
    'type',
    'amount',
    'description',
    'occurred_at',
    'transfer_group_id',
    'recurring_payment_id',
    'scheduled_for',
])]
class Movement extends Model
{
    /**
     * Usuario propietario del movimiento.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bolsillo afectado por el movimiento.
     */
    public function pocket(): BelongsTo
    {
        return $this->belongsTo(Pocket::class);
    }

    /**
     * Pago automático que originó el movimiento, si aplica.
     */
    public function recurringPayment(): BelongsTo
    {
        return $this->belongsTo(RecurringPayment::class);
    }

    /**
     * Convierte automáticamente ciertos campos al tipo adecuado.
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'occurred_at' => 'datetime',
            'scheduled_for' => 'date',
        ];
    }
}