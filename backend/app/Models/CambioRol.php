<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CambioRol extends Model
{
    use Auditable, HasFactory;

    protected $table = 'cambios_rol';

    protected $fillable = [
        'persona_id',
        'fecha_inicio',
        'fecha_fin',
        'descripcion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin'    => 'date',
            'estado'       => 'boolean',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }
}
