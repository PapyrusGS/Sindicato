<?php

namespace App\Http\Requests;

use App\Models\Persona;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAfiliadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $personaId = $this->route('id');
        $persona = Persona::with('usuario')->find($personaId);
        $usuarioId = $persona?->usuario?->id;

        return [
            // Persona
            'primer_nombre'    => ['required', 'string', 'max:100'],
            'segundo_nombre'   => ['nullable', 'string', 'max:100'],
            'primer_apellido'  => ['required', 'string', 'max:100'],
            'segundo_apellido' => ['nullable', 'string', 'max:100'],
            'ci'               => ['required', 'string', 'max:20', 'unique:personas,ci,' . $personaId],
            'celular'          => ['nullable', 'string', 'max:20'],
            'direccion'        => ['nullable', 'string', 'max:255'],

            // Usuario
            'username'         => ['required', 'string', 'max:100', 'unique:usuarios,username,' . $usuarioId],
            'password'         => ['nullable', 'string', 'min:6'],

            // Roles
            'roles'            => ['nullable', 'array'],

            // Vehículo si aplica
            'auto_id'          => ['nullable', 'exists:autos,id'],
            'auto_placa'       => ['nullable', 'string', 'max:20'],
            'auto_marca'       => ['nullable', 'string', 'max:100'],
            'auto_modelo'      => ['nullable', 'string', 'max:100'],
            'auto_gestion'     => ['nullable', 'integer', 'min:1950', 'max:' . (date('Y') + 1)],
            'grupo_id'         => ['nullable', 'exists:grupos,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'primer_nombre.required'   => 'El primer nombre es obligatorio.',
            'primer_apellido.required' => 'El primer apellido es obligatorio.',
            'ci.required'              => 'El CI es obligatorio.',
            'ci.unique'                => 'El CI ingresado ya pertenece a otra persona.',
            'username.required'        => 'El nombre de usuario es obligatorio.',
            'username.unique'          => 'Este nombre de usuario ya pertenece a otra cuenta.',
            'password.min'             => 'La nueva contraseña debe tener al menos 6 caracteres.',
        ];
    }
}
