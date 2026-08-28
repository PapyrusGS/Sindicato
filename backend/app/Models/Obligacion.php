<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Obligacion extends Model
{
    use HasFactory, Auditable;

    protected $table = 'obligaciones';

    protected $fillable = [
        'grupo_id',
        'jefe_persona_id',
        'tipo_obligacion_id',
        'tipo_categoria',
        'concepto',
        'monto_individual',
        'monto_total_esperado',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'usuarioA',
        'fechaA',
    ];

    protected function casts(): array
    {
        return [
            'monto_individual'     => 'decimal:2',
            'monto_total_esperado' => 'decimal:2',
            'fecha_inicio'         => 'date',
            'fecha_fin'            => 'date',
            'estado'               => 'boolean',
            'fechaA'               => 'date',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function jefePersona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'jefe_persona_id');
    }

    public function tipoObligacion(): BelongsTo
    {
        return $this->belongsTo(TipoObligacion::class, 'tipo_obligacion_id');
    }

    public function asignacionesChoferes(): HasMany
    {
        return $this->hasMany(ObligacionChofer::class, 'obligacion_id');
    }
}
