<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Propietario extends Model
{
    use Auditable, HasFactory;

    protected $table = 'propietarios';

    protected $fillable = [
        'persona_id',
        'fecha_registro',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_registro' => 'date',
            'estado'         => 'boolean',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function autos(): HasMany
    {
        return $this->hasMany(Auto::class, 'propietario_id');
    }
}
