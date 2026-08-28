<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObligacionChofer extends Model
{
    use HasFactory, Auditable;

    protected $table = 'obligacion_choferes';

    protected $fillable = [
        'obligacion_id',
        'chofer_id',
        'monto_asignado',
        'monto_pagado',
        'estado_pago',
        'fecha_pago',
        'estado',
        'usuarioA',
        'fechaA',
    ];

    protected function casts(): array
    {
        return [
            'monto_asignado' => 'decimal:2',
            'monto_pagado'   => 'decimal:2',
            'fecha_pago'     => 'datetime',
            'estado'         => 'boolean',
            'fechaA'         => 'date',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function obligacion(): BelongsTo
    {
        return $this->belongsTo(Obligacion::class, 'obligacion_id');
    }

    public function chofer(): BelongsTo
    {
        return $this->belongsTo(Chofer::class, 'chofer_id');
    }
}
