<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida los datos enviados desde el formulario de inicio de sesión web.
 */
class WebLoginRequest extends FormRequest
{
    /**
     * Permite procesar la solicitud de autenticación.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define las reglas de validación del login web.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }
}