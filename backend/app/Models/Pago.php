<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pago extends Model
{
    use HasFactory, Auditable;

    protected $table = 'pagos';

    protected $fillable = [
        'chofer_id',
        'cobrador_persona_id',
        'monto_total',
        'fecha_pago',
        'metodo_pago',
        'observacion',
        'estado',
        'usuarioA',
        'fechaA',
    ];

    protected function casts(): array
    {
        return [
            'monto_total' => 'decimal:2',
            'fecha_pago'  => 'datetime',
            'estado'      => 'boolean',
            'fechaA'      => 'date',
        ];
    }

    protected $appends = [
        'puede_anular_directo',
        'segundos_restantes_gracia',
    ];

    // ─── Relaciones ─────────────────────────────────────────────────

    public function chofer(): BelongsTo
    {
        return $this->belongsTo(Chofer::class, 'chofer_id');
    }

    public function cobradorPersona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'cobrador_persona_id');
    }

    public function pagoObligaciones(): HasMany
    {
        return $this->hasMany(PagoObligacion::class, 'pago_id');
    }

    public function pagoMultas(): HasMany
    {
        return $this->hasMany(PagoMulta::class, 'pago_id');
    }

    public function solicitudesCambio(): HasMany
    {
        return $this->hasMany(SolicitudCambioPago::class, 'pago_id');
    }

    // ─── Helpers de Ventana de Gracia (90s / 1:30 min) ───────────────

    public function getPuedeAnularDirectoAttribute(): bool
    {
        if (!$this->estado || !$this->created_at) return false;
        return abs(now()->diffInSeconds($this->created_at)) <= 90;
    }

    public function getSegundosRestantesGraciaAttribute(): int
    {
        if (!$this->estado || !$this->created_at) return 0;
        $segundos = 90 - abs(now()->diffInSeconds($this->created_at));
        return max(0, (int) $segundos);
    }
}
