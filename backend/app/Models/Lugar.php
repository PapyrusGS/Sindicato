<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lugar extends Model
{
    use Auditable, HasFactory;

    protected $table = 'lugares';

    protected $fillable = [
        'nombre',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'lugar_id');
    }
}
