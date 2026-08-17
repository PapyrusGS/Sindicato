<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Persona extends Model
{
    use Auditable, HasFactory;

    protected $table = 'personas';

    protected $fillable = [
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'ci',
        'celular',
        'direccion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    // ─── Accessors ──────────────────────────────────────────────────

    /**
     * Nombre completo de la persona.
     */
    public function getNombreCompletoAttribute(): string
    {
        $partes = array_filter([
            $this->primer_nombre,
            $this->segundo_nombre,
            $this->primer_apellido,
            $this->segundo_apellido,
        ]);

        return implode(' ', $partes);
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function chofer(): HasOne
    {
        return $this->hasOne(Chofer::class, 'persona_id');
    }

    public function propietario(): HasOne
    {
        return $this->hasOne(Propietario::class, 'persona_id');
    }

    public function usuario(): HasOne
    {
        return $this->hasOne(Usuario::class, 'persona_id');
    }

    public function cambiosRol(): HasMany
    {
        return $this->hasMany(CambioRol::class, 'persona_id');
    }

    public function obligaciones(): HasMany
    {
        return $this->hasMany(Obligacion::class, 'persona_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'persona_id');
    }

    public function multasComoInspector(): HasMany
    {
        return $this->hasMany(Multa::class, 'inspector_id');
    }
}
