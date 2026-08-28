<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSancionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chofer_id'       => ['required', 'integer', 'exists:choferes,id'],
            'lugar_id'        => ['nullable', 'integer', 'exists:lugares,id'],
            'tipo_sancion'    => ['required', 'string', 'in:ECONOMICA,CASTIGO'],
            'motivo'          => ['required', 'string', 'max:500'],
            'sancion_detalle' => ['nullable', 'string', 'max:500'],
            'monto'           => ['required_if:tipo_sancion,ECONOMICA', 'nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'chofer_id.required'      => 'Debe seleccionar un chofer sancionado.',
            'chofer_id.exists'        => 'El chofer seleccionado no es válido.',
            'tipo_sancion.required'   => 'El tipo de sanción es obligatorio.',
            'tipo_sancion.in'         => 'El tipo de sanción debe ser ECONOMICA o CASTIGO.',
            'motivo.required'         => 'El motivo de la infracción es obligatorio.',
            'monto.required_if'       => 'El monto en bolivianos es obligatorio para sanciones económicas.',
            'monto.numeric'           => 'El monto debe ser un valor numérico.',
        ];
    }
}
