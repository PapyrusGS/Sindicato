<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChoferAuto extends Model
{
    use Auditable, HasFactory;

    protected $table = 'chofer_autos';

    protected $fillable = [
        'auto_id',
        'chofer_id',
        'grupo_id',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function auto(): BelongsTo
    {
        return $this->belongsTo(Auto::class, 'auto_id');
    }

    public function chofer(): BelongsTo
    {
        return $this->belongsTo(Chofer::class, 'chofer_id');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }
}
