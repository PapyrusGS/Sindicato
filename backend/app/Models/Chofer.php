<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chofer extends Model
{
    use Auditable, HasFactory;

    protected $table = 'choferes';

    protected $fillable = [
        'persona_id',
        'fecha_ingreso',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'estado'        => 'boolean',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function autos(): BelongsToMany
    {
        return $this->belongsToMany(Auto::class, 'chofer_autos', 'chofer_id', 'auto_id')
                    ->withPivot('grupo_id', 'estado', 'usuario_audit', 'fecha_audit')
                    ->withTimestamps();
    }

    public function choferAutos(): HasMany
    {
        return $this->hasMany(ChoferAuto::class, 'chofer_id');
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'chofer_id');
    }

    public function asistenciasComoInspector(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'inspector_id');
    }

    public function multas(): HasMany
    {
        return $this->hasMany(Multa::class, 'chofer_id');
    }
}
