<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreObligacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grupo_id'         => ['required', 'integer', 'exists:grupos,id'],
            'tipo_categoria'   => ['required', 'string', 'in:MENSUAL,AYUDA'],
            'concepto'         => ['required', 'string', 'max:255'],
            'monto_individual' => ['required', 'numeric', 'min:1'],
            'fecha_inicio'     => ['required', 'date'],
            'fecha_fin'        => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'grupo_id.required'         => 'Debe seleccionar un grupo de trabajo.',
            'grupo_id.exists'           => 'El grupo seleccionado no es válido.',
            'tipo_categoria.required'   => 'El tipo de categoría es obligatorio (Cuota Mensual o Aporte de Ayuda).',
            'tipo_categoria.in'         => 'El tipo debe ser MENSUAL o AYUDA.',
            'concepto.required'         => 'El concepto o motivo de la obligación es obligatorio.',
            'monto_individual.required' => 'El monto individual a cobrar por chofer es obligatorio.',
            'monto_individual.min'      => 'El monto debe ser mayor a 0 Bs.',
            'fecha_inicio.required'     => 'La fecha de inicio es obligatoria.',
            'fecha_fin.after_or_equal'  => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
        ];
    }
}
