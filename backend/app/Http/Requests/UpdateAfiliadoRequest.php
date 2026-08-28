<?php

namespace App\Http\Requests;

use App\Helpers\StringFormatter;
use App\Models\Persona;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAfiliadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'primer_nombre'    => StringFormatter::titleCase($this->primer_nombre),
            'segundo_nombre'   => StringFormatter::titleCase($this->segundo_nombre),
            'primer_apellido'  => StringFormatter::titleCase($this->primer_apellido),
            'segundo_apellido' => StringFormatter::titleCase($this->segundo_apellido),
            'ci'               => strtoupper(trim($this->ci ?? '')),
            'celular'          => trim($this->celular ?? '') !== '' ? trim($this->celular) : null,
            'auto_placa'       => StringFormatter::formatPlaca($this->auto_placa),
            'auto_marca'       => StringFormatter::titleCase($this->auto_marca),
            'auto_modelo'      => StringFormatter::titleCase($this->auto_modelo),
        ]);
    }

    public function rules(): array
    {
        $personaId = $this->route('id');
        $persona = Persona::with('usuario')->find($personaId);
        $usuarioId = $persona?->usuario?->id;

        return [
            // Persona (Validación estricta de nombres sin números y celular de 8 dígitos empezando en 6/7)
            'primer_nombre'    => ['required', 'string', 'max:100', 'regex:/^[\pL\s\'\-]+$/u'],
            'segundo_nombre'   => ['nullable', 'string', 'max:100', 'regex:/^[\pL\s\'\-]+$/u'],
            'primer_apellido'  => ['required', 'string', 'max:100', 'regex:/^[\pL\s\'\-]+$/u'],
            'segundo_apellido' => ['nullable', 'string', 'max:100', 'regex:/^[\pL\s\'\-]+$/u'],
            'ci'               => ['required', 'string', 'max:20', 'unique:personas,ci,' . $personaId],
            'celular'          => ['nullable', 'string', 'regex:/^[67]\d{7}$/'],
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
            'primer_nombre.regex'      => 'El primer nombre sólo puede contener letras y espacios, sin números ni símbolos.',
            'segundo_nombre.regex'     => 'El segundo nombre sólo puede contener letras y espacios.',
            'primer_apellido.required' => 'El primer apellido es obligatorio.',
            'primer_apellido.regex'    => 'El primer apellido sólo puede contener letras y espacios, sin números.',
            'segundo_apellido.regex'   => 'El segundo apellido sólo puede contener letras y espacios.',
            'ci.required'              => 'El CI es obligatorio.',
            'ci.unique'                => 'El CI ingresado ya pertenece a otra persona.',
            'celular.regex'            => 'El celular debe constar de exactamente 8 dígitos y comenzar con 6 o 7 (ej. 71234567).',
            'username.required'        => 'El nombre de usuario es obligatorio.',
            'username.unique'          => 'Este nombre de usuario ya pertenece a otra cuenta.',
            'password.min'             => 'La nueva contraseña debe tener al menos 6 caracteres.',
        ];
    }
}
