<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Valida los datos enviados desde el registro web.
 */
class WebRegisterRequest extends FormRequest
{
    /**
     * El registro web está disponible para usuarios invitados.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas necesarias para crear una cuenta web.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(8),
            ],
        ];
    }
}