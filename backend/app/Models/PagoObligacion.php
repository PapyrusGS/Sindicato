<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoObligacion extends Model
{
    use HasFactory, Auditable;

    protected $table = 'pago_obligaciones';

    protected $fillable = [
        'pago_id',
        'obligacion_chofer_id',
        'monto_abonado',
        'estado',
        'usuarioA',
        'fechaA',
    ];

    protected function casts(): array
    {
        return [
            'monto_abonado' => 'decimal:2',
            'estado'        => 'boolean',
            'fechaA'        => 'date',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }

    public function obligacionChofer(): BelongsTo
    {
        return $this->belongsTo(ObligacionChofer::class, 'obligacion_chofer_id');
    }
}
