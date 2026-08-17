<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pago extends Model
{
    use Auditable, HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'persona_id',
        'fecha_pago',
        'motivo',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_pago' => 'date',
            'estado'     => 'boolean',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function pagoObligaciones(): HasMany
    {
        return $this->hasMany(PagoObligacion::class, 'pago_id');
    }

    public function pagoMultas(): HasMany
    {
        return $this->hasMany(PagoMulta::class, 'pago_id');
    }

    // ─── Helpers ────────────────────────────────────────────────────

    /**
     * Total abonado en este pago (obligaciones + multas).
     */
    public function getTotalPagoAttribute(): float
    {
        $obligaciones = (float) $this->pagoObligaciones()->where('estado', true)->sum('monto_abonado');
        $multas = (float) $this->pagoMultas()->where('estado', true)->sum('monto_abonado');

        return $obligaciones + $multas;
    }
}
