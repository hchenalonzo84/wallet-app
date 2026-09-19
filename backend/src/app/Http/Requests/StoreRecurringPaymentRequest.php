<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida los datos necesarios para crear un pago automático.
 *
 * Algunos campos como billing_day, next_due_on, frequency
 * e is_active no se reciben directamente del cliente porque
 * serán controlados por RecurringPaymentService.
 */
class StoreRecurringPaymentRequest extends FormRequest
{
    /**
     * Permite procesar la petición.
     *
     * La autenticación general del endpoint se controla
     * mediante el middleware auth:sanctum.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define las reglas necesarias para crear
     * un nuevo pago automático.
     */
    public function rules(): array
    {
        return [
            /*
             * El bolsillo debe:
             * - existir,
             * - pertenecer al usuario autenticado,
             * - estar activo.
             */
            'pocket_id' => [
                'required',
                'integer',

                Rule::exists('pockets', 'id')
                    ->where(
                        function (Builder $query): void {
                            $query
                                ->where(
                                    'user_id',
                                    $this->user()->id
                                )
                                ->where(
                                    'is_active',
                                    true
                                );
                        }
                    ),
            ],

            /*
             * Nombre identificativo del pago automático.
             *
             * Ejemplos:
             * Netflix, Internet, Seguro, etc.
             */
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            /*
             * Descripción opcional para agregar
             * información adicional sobre el pago.
             */
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],

            /*
             * El monto debe:
             * - ser numérico,
             * - ser mayor que cero,
             * - tener como máximo dos decimales.
             */
            'amount' => [
                'required',
                'numeric',
                'decimal:0,2',
                'gt:0',
            ],

            /*
             * Fecha desde la que comienza la recurrencia.
             *
             * Internamente la API utiliza el formato Y-m-d.
             * La interfaz web/móvil podrá mostrar la fecha
             * según el idioma configurado por el usuario.
             */
            'starts_on' => [
                'required',
                'date_format:Y-m-d',
            ],

            /*
             * Fecha opcional en la que finalizará
             * el pago automático.
             *
             * No puede ser anterior a starts_on.
             */
            'ends_on' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:starts_on',
            ],
        ];
    }

    /**
     * Define mensajes personalizados para
     * los errores de validación.
     */
    public function messages(): array
    {
        return [
            'pocket_id.required' =>
                'Debes seleccionar un bolsillo.',

            'pocket_id.integer' =>
                'El bolsillo seleccionado no es válido.',

            'pocket_id.exists' =>
                'El bolsillo no existe, no te pertenece o está inactivo.',

            'name.required' =>
                'El nombre del pago automático es obligatorio.',

            'name.max' =>
                'El nombre no puede superar los 100 caracteres.',

            'description.max' =>
                'La descripción no puede superar los 500 caracteres.',

            'amount.required' =>
                'El monto del pago automático es obligatorio.',

            'amount.numeric' =>
                'El monto debe ser un valor numérico.',

            'amount.decimal' =>
                'El monto puede tener como máximo dos decimales.',

            'amount.gt' =>
                'El monto debe ser mayor que cero.',

            'starts_on.required' =>
                'La fecha inicial es obligatoria.',

            /*
             * No mostramos al usuario el formato técnico Y-m-d.
             * Ese detalle queda interno en la API.
             */
            'starts_on.date_format' =>
                'La fecha inicial no tiene un formato válido.',

            'ends_on.date_format' =>
                'La fecha final no tiene un formato válido.',

            'ends_on.after_or_equal' =>
                'La fecha final no puede ser anterior a la fecha inicial.',
        ];
    }
}