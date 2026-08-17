<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoObligacion extends Model
{
    use Auditable, HasFactory;

    protected $table = 'pago_obligaciones';

    protected $fillable = [
        'pago_id',
        'obligacion_id',
        'monto_abonado',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'monto_abonado' => 'decimal:2',
            'estado'        => 'boolean',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }

    public function obligacion(): BelongsTo
    {
        return $this->belongsTo(Obligacion::class, 'obligacion_id');
    }
}
