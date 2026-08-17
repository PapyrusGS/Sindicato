<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Obligacion extends Model
{
    use Auditable, HasFactory;

    protected $table = 'obligaciones';

    protected $fillable = [
        'persona_id',
        'tipo_obligacion_id',
        'monto',
        'fecha_creacion',
        'fecha_fin',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'monto'          => 'decimal:2',
            'fecha_creacion' => 'date',
            'fecha_fin'      => 'date',
            'estado'         => 'boolean',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function tipoObligacion(): BelongsTo
    {
        return $this->belongsTo(TipoObligacion::class, 'tipo_obligacion_id');
    }

    public function pagoObligaciones(): HasMany
    {
        return $this->hasMany(PagoObligacion::class, 'obligacion_id');
    }

    // ─── Helpers ────────────────────────────────────────────────────

    /**
     * Calcular el monto total abonado a esta obligación.
     */
    public function getTotalAbonadoAttribute(): float
    {
        return (float) $this->pagoObligaciones()->where('estado', true)->sum('monto_abonado');
    }

    /**
     * Calcular el saldo pendiente.
     */
    public function getSaldoPendienteAttribute(): float
    {
        return (float) $this->monto - $this->total_abonado;
    }
}
