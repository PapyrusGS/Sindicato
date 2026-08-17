<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rol extends Model
{
    use Auditable, HasFactory;

    protected $table = 'roles';

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

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'usuario_roles', 'rol_id', 'usuario_id')
                    ->withPivot('estado', 'usuario_audit', 'fecha_audit')
                    ->withTimestamps();
    }
}
