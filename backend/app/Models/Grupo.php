<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    use Auditable, HasFactory;

    protected $table = 'grupos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function choferAutos(): HasMany
    {
        return $this->hasMany(ChoferAuto::class, 'grupo_id');
    }
}
