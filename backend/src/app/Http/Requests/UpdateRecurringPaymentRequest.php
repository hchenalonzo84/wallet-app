<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida los cambios realizados
 * sobre un pago automático existente.
 *
 * La mayoría de campos utilizan "sometimes"
 * porque una petición PATCH puede modificar
 * únicamente uno o varios valores.
 */
class UpdateRecurringPaymentRequest extends FormRequest
{
    /**
     * Permite procesar la petición.
     *
     * La autenticación se controla
     * mediante auth:sanctum.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define las reglas necesarias para actualizar
     * un pago automático existente.
     */
    public function rules(): array
    {
        return [
            /*
             * Si se cambia el bolsillo, el nuevo debe:
             * - existir,
             * - pertenecer al usuario,
             * - estar activo.
             */
            'pocket_id' => [
                'sometimes',
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
             * Permite cambiar el nombre del pago automático.
             */
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            /*
             * La descripción puede:
             * - cambiarse,
             * - mantenerse,
             * - establecerse explícitamente en null.
             */
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],

            /*
             * Permite modificar el monto
             * para las ejecuciones futuras.
             */
            'amount' => [
                'sometimes',
                'required',
                'numeric',
                'decimal:0,2',
                'gt:0',
            ],

            /*
             * Permite modificar la fecha inicial.
             *
             * Si cambia, RecurringPaymentService recalculará
             * billing_day y next_due_on.
             *
             * Internamente la API mantiene el formato Y-m-d.
             */
            'starts_on' => [
                'sometimes',
                'required',
                'date_format:Y-m-d',
            ],

            /*
             * Permite cambiar o eliminar la fecha final.
             *
             * Si se envía null, el pago vuelve
             * a no tener una fecha de finalización.
             */
            'ends_on' => [
                'sometimes',
                'nullable',
                'date_format:Y-m-d',
            ],
        ];
    }

    /**
     * Define mensajes personalizados
     * para los errores de validación.
     */
    public function messages(): array
    {
        return [
            'pocket_id.integer' =>
                'El bolsillo seleccionado no es válido.',

            'pocket_id.exists' =>
                'El bolsillo no existe, no te pertenece o está inactivo.',

            'name.required' =>
                'El nombre no puede quedar vacío.',

            'name.max' =>
                'El nombre no puede superar los 100 caracteres.',

            'description.max' =>
                'La descripción no puede superar los 500 caracteres.',

            'amount.required' =>
                'El monto no puede quedar vacío.',

            'amount.numeric' =>
                'El monto debe ser un valor numérico.',

            'amount.decimal' =>
                'El monto puede tener como máximo dos decimales.',

            'amount.gt' =>
                'El monto debe ser mayor que cero.',

            'starts_on.required' =>
                'La fecha inicial no puede quedar vacía.',

            /*
             * El usuario no necesita conocer
             * el formato técnico utilizado por la API.
             */
            'starts_on.date_format' =>
                'La fecha inicial no tiene un formato válido.',

            'ends_on.date_format' =>
                'La fecha final no tiene un formato válido.',
        ];
    }
}