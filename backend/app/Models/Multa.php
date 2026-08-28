<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Multa extends Model
{
    use HasFactory, Auditable;

    protected $table = 'multas';

    protected $fillable = [
        'chofer_id',
        'inspector_id',
        'lugar_id',
        'tipo_sancion',
        'motivo',
        'sancion_detalle',
        'monto',
        'estado_pago',
        'fecha_infraccion',
        'estado',
        'usuarioA',
        'fechaA',
    ];

    protected function casts(): array
    {
        return [
            'monto'            => 'decimal:2',
            'fecha_infraccion' => 'datetime',
            'estado'           => 'boolean',
            'fechaA'           => 'date',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function chofer(): BelongsTo
    {
        return $this->belongsTo(Chofer::class, 'chofer_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Chofer::class, 'inspector_id');
    }

    public function lugar(): BelongsTo
    {
        return $this->belongsTo(Lugar::class, 'lugar_id');
    }
}
