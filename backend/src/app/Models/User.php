<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Representa a un usuario de Wallet App.
 *
 * El usuario es propietario de sus bolsillos, movimientos
 * y pagos automáticos.
 */
#[Fillable([
    'name',
    'email',
    'password',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */

    /*
     * HasApiTokens:
     * Permite utilizar Laravel Sanctum para autenticación.
     *
     * HasFactory:
     * Permite crear usuarios fácilmente en pruebas.
     *
     * Notifiable:
     * Habilita el sistema de notificaciones de Laravel.
     */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Bolsillos virtuales pertenecientes al usuario.
     */
    public function pockets(): HasMany
    {
        return $this->hasMany(Pocket::class);
    }

    /**
     * Movimientos financieros pertenecientes al usuario.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    /**
     * Pagos automáticos configurados por el usuario.
     */
    public function recurringPayments(): HasMany
    {
        return $this->hasMany(RecurringPayment::class);
    }

    /**
     * Convierte automáticamente determinados campos
     * al tipo adecuado cuando Laravel los utiliza.
     */
    protected function casts(): array
    {
        return [
            // Convierte la fecha de verificación en objeto de fecha/hora.
            'email_verified_at' => 'datetime',

            // Laravel almacena automáticamente la contraseña como hash.
            'password' => 'hashed',
        ];
    }
}