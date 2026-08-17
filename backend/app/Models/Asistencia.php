<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    use Auditable, HasFactory;

    protected $table = 'asistencias';

    protected $fillable = [
        'chofer_id',
        'lugar_id',
        'inspector_id',
        'fecha_hora',
        'asistencia',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'asistencia' => 'boolean',
            'estado'     => 'boolean',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function chofer(): BelongsTo
    {
        return $this->belongsTo(Chofer::class, 'chofer_id');
    }

    public function lugar(): BelongsTo
    {
        return $this->belongsTo(Lugar::class, 'lugar_id');
    }

    /**
     * Inspector que registró la asistencia (es un chofer con rol de inspector).
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Chofer::class, 'inspector_id');
    }
}
