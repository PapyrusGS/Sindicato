<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Multa extends Model
{
    use Auditable, HasFactory;

    protected $table = 'multas';

    protected $fillable = [
        'chofer_id',
        'inspector_id',
        'monto',
        'motivo',
        'sancion',
        'fecha_infraccion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'monto'            => 'decimal:2',
            'fecha_infraccion' => 'date',
            'estado'           => 'boolean',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function chofer(): BelongsTo
    {
        return $this->belongsTo(Chofer::class, 'chofer_id');
    }

    /**
     * Inspector que registró la multa (referencia a persona).
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'inspector_id');
    }

    public function pagoMultas(): HasMany
    {
        return $this->hasMany(PagoMulta::class, 'multa_id');
    }

    // ─── Helpers ────────────────────────────────────────────────────

    public function getTotalAbonadoAttribute(): float
    {
        return (float) $this->pagoMultas()->where('estado', true)->sum('monto_abonado');
    }

    public function getSaldoPendienteAttribute(): float
    {
        return (float) $this->monto - $this->total_abonado;
    }
}
