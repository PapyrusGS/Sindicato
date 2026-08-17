<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAfiliadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Controlado por middleware
    }

    public function rules(): array
    {
        $rules = [
            // Persona & Usuario
            'primer_nombre'    => ['required', 'string', 'max:100'],
            'segundo_nombre'   => ['nullable', 'string', 'max:100'],
            'primer_apellido'  => ['required', 'string', 'max:100'],
            'segundo_apellido' => ['nullable', 'string', 'max:100'],
            'ci'               => ['required', 'string', 'max:20', 'unique:personas,ci'],
            'celular'          => ['nullable', 'string', 'max:20'],
            'direccion'        => ['nullable', 'string', 'max:255'],
            'username'         => ['required', 'string', 'max:100', 'unique:usuarios,username'],
            'password'         => ['required', 'string', 'min:6'],
            'modalidad'        => ['required', 'string', 'in:propietario_auto,chofer_existente'],
            'grupo_id'         => ['required', 'integer', 'exists:grupos,id'],
            'roles'            => ['nullable', 'array'],
        ];

        if ($this->input('modalidad') === 'propietario_auto') {
            $rules['auto_placa']        = ['required', 'string', 'max:20', 'unique:autos,placa'];
            $rules['auto_marca']        = ['required', 'string', 'max:100'];
            $rules['auto_modelo']       = ['required', 'string', 'max:100'];
            $rules['auto_gestion']      = ['required', 'integer', 'min:1950', 'max:' . (date('Y') + 1)];
            $rules['es_el_chofer']      = ['required', 'boolean'];
            $rules['chofer_id_asignado'] = ['nullable', 'required_if:es_el_chofer,false', 'exists:choferes,id'];
        } else if ($this->input('modalidad') === 'chofer_existente') {
            $rules['auto_id_existente'] = ['nullable', 'exists:autos,id'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'primer_nombre.required'      => 'El primer nombre es obligatorio.',
            'primer_apellido.required'    => 'El primer apellido es obligatorio.',
            'ci.required'                 => 'El CI es obligatorio.',
            'ci.unique'                   => 'El CI ya se encuentra registrado en el sistema.',
            'username.required'           => 'El nombre de usuario es obligatorio.',
            'username.unique'             => 'Este nombre de usuario ya está registrado.',
            'password.required'           => 'La contraseña es obligatoria.',
            'password.min'                => 'La contraseña debe tener al menos 6 caracteres.',
            'modalidad.required'          => 'Debe seleccionar una modalidad de registro.',
            'grupo_id.required'           => 'Debe seleccionar un grupo.',
            'auto_placa.required'         => 'La placa del vehículo es obligatoria.',
            'auto_placa.unique'           => 'Esta placa de vehículo ya está registrada.',
            'auto_marca.required'         => 'La marca del vehículo es obligatoria.',
            'auto_modelo.required'        => 'El modelo del vehículo es obligatorio.',
            'auto_gestion.required'       => 'La gestión/año del vehículo es obligatoria.',
            'chofer_id_asignado.required_if' => 'Debe seleccionar un chofer de la lista si el propietario no conducirá el vehículo.',
        ];
    }
}
