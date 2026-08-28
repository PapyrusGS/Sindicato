<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    use HasFactory;

    protected $table = 'auditorias';
    protected $primaryKey = 'id_auditoria';

    protected $fillable = [
        'tabla_nombre',
        'registro_id',
        'accion',
        'campo',
        'valor_anterior',
        'valor_nuevo',
        'persona_id',
        'fecha_a',
        'direccion_ip',
    ];

    protected function casts(): array
    {
        return [
            'fecha_a' => 'datetime',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }
}
