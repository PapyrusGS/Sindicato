<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudCambioPago extends Model
{
    use HasFactory, Auditable;

    protected $table = 'solicitudes_cambio_pago';

    protected $fillable = [
        'pago_id',
        'solicitante_persona_id',
        'chofer_id',
        'jefe_persona_id',
        'motivo',
        'estado',
        'respuesta_observacion',
        'respondido_por_persona_id',
        'fecha_respuesta',
        'usuarioA',
        'fechaA',
    ];

    protected function casts(): array
    {
        return [
            'estado'          => 'string',
            'fecha_respuesta' => 'datetime',
            'fechaA'          => 'date',
        ];
    }

    public function getEstadoAttribute($value): string
    {
        return (string) ($this->attributes['estado'] ?? 'PENDIENTE');
    }

    public function setEstadoAttribute($value): void
    {
        $this->attributes['estado'] = (string) $value;
    }

    public function getCasts(): array
    {
        $casts = parent::getCasts();
        $casts['estado'] = 'string';
        return $casts;
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }

    public function solicitantePersona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'solicitante_persona_id');
    }

    public function chofer(): BelongsTo
    {
        return $this->belongsTo(Chofer::class, 'chofer_id');
    }

    public function jefePersona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'jefe_persona_id');
    }

    public function respondidoPorPersona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'respondido_por_persona_id');
    }
}
