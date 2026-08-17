<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Auto extends Model
{
    use Auditable, HasFactory;

    protected $table = 'autos';

    protected $fillable = [
        'propietario_id',
        'placa',
        'modelo',
        'marca',
        'gestion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'gestion' => 'integer',
            'estado'  => 'boolean',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class, 'propietario_id');
    }

    public function choferes(): BelongsToMany
    {
        return $this->belongsToMany(Chofer::class, 'chofer_autos', 'auto_id', 'chofer_id')
                    ->withPivot('grupo_id', 'estado', 'usuario_audit', 'fecha_audit')
                    ->withTimestamps();
    }

    public function choferAutos(): HasMany
    {
        return $this->hasMany(ChoferAuto::class, 'auto_id');
    }
}
