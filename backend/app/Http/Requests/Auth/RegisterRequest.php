<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtener las reglas de validación para la solicitud.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'persona_id' => ['required', 'integer', 'exists:personas,id'],
            'username'   => ['required', 'string', 'max:100', 'unique:usuarios,username'],
            'password'   => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    /**
     * Mensajes de validación personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'persona_id.required' => 'La persona es obligatoria.',
            'persona_id.exists'   => 'La persona seleccionada no existe en el sistema.',
            'username.required'   => 'El nombre de usuario es obligatorio.',
            'username.unique'     => 'Este nombre de usuario ya está en uso.',
            'username.max'        => 'El nombre de usuario no puede exceder los 100 caracteres.',
            'password.required'   => 'La contraseña es obligatoria.',
            'password.min'        => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed'  => 'La confirmación de contraseña no coincide.',
        ];
    }
}
