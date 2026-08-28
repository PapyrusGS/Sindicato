<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarAsistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lugar_id'                 => ['required', 'integer', 'exists:lugares,id'],
            'fecha'                    => ['required', 'date'],
            'asistencias'              => ['required', 'array', 'min:1'],
            'asistencias.*.chofer_id'  => ['required', 'integer', 'exists:choferes,id'],
            'asistencias.*.asistencia' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'lugar_id.required'                 => 'El lugar/parada es obligatorio.',
            'lugar_id.exists'                   => 'El lugar seleccionado no es válido.',
            'fecha.required'                    => 'La fecha de asistencia es obligatoria.',
            'asistencias.required'              => 'Debe enviar al menos un registro de chofer.',
            'asistencias.*.chofer_id.required'  => 'El ID del chofer es obligatorio.',
            'asistencias.*.asistencia.required' => 'El estado de asistencia es obligatorio.',
        ];
    }
}
