<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoMulta extends Model
{
    use HasFactory, Auditable;

    protected $table = 'pago_multas';

    protected $fillable = [
        'pago_id',
        'multa_id',
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

    public function multa(): BelongsTo
    {
        return $this->belongsTo(Multa::class, 'multa_id');
    }
}
