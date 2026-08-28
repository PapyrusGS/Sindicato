<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chofer_id'                => ['required', 'integer', 'exists:choferes,id'],
            'metodo_pago'              => ['required', 'string', 'in:EFECTIVO,TRANSFERENCIA_QR'],
            'observacion'              => ['nullable', 'string', 'max:500'],
            'deudas_obligaciones'      => ['nullable', 'array'],
            'deudas_obligaciones.*.id' => ['required_with:deudas_obligaciones', 'integer', 'exists:obligacion_choferes,id'],
            'deudas_obligaciones.*.monto' => ['required_with:deudas_obligaciones', 'numeric', 'min:0.5'],
            'deudas_multas'            => ['nullable', 'array'],
            'deudas_multas.*.id'       => ['required_with:deudas_multas', 'integer', 'exists:multas,id'],
            'deudas_multas.*.monto'    => ['required_with:deudas_multas', 'numeric', 'min:0.5'],
        ];
    }

    public function messages(): array
    {
        return [
            'chofer_id.required'    => 'Debe seleccionar un chofer a quien se le realizará el cobro.',
            'chofer_id.exists'      => 'El chofer seleccionado no es válido.',
            'metodo_pago.required'  => 'El método de pago es obligatorio (EFECTIVO o TRANSFERENCIA_QR).',
            'metodo_pago.in'        => 'El método de pago debe ser EFECTIVO o TRANSFERENCIA_QR.',
        ];
    }
}
