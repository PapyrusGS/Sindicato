<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QueryAuditoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'modulo'      => ['nullable', 'string'],
            'accion'      => ['nullable', 'string'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'q'           => ['nullable', 'string', 'max:255'],
        ];
    }
}
