<?php

namespace App\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Trait Auditable
 *
 * Audita automáticamente todas las operaciones en la base de datos:
 * - Registra el usuario que realizó la acción (usuarioA).
 * - Registra la fecha exacta de la acción (fechaA).
 * - Mantiene el estado activo/inactivo (estado).
 *
 * Funciona automáticamente mediante hooks de Eloquent (creating, updating).
 */
trait Auditable
{
    /**
     * Boot del trait — registra los eventos de modelo de Eloquent.
     */
    public static function bootAuditable(): void
    {
        // Al CREAR un registro
        static::creating(function ($model) {
            $username = Auth::user()?->username ?? $model->usuarioA ?? $model->usuario_audit ?? 'system';
            $fecha = now()->toDateString();

            if ($model->hasAuditableColumn('usuarioA')) {
                $model->usuarioA = $username;
            }
            if ($model->hasAuditableColumn('usuario_audit')) {
                $model->usuario_audit = $username;
            }

            if ($model->hasAuditableColumn('fechaA')) {
                $model->fechaA = $fecha;
            }
            if ($model->hasAuditableColumn('fecha_audit')) {
                $model->fecha_audit = $fecha;
            }

            if ($model->hasAuditableColumn('estado') && is_null($model->estado)) {
                $model->estado = true;
            }
        });

        // Al ACTUALIZAR un registro
        static::updating(function ($model) {
            $username = Auth::user()?->username ?? $model->usuarioA ?? 'system';
            $fecha = now()->toDateString();

            if ($model->hasAuditableColumn('usuarioA')) {
                $model->usuarioA = $username;
            }
            if ($model->hasAuditableColumn('usuario_audit')) {
                $model->usuario_audit = $username;
            }

            if ($model->hasAuditableColumn('fechaA')) {
                $model->fechaA = $fecha;
            }
            if ($model->hasAuditableColumn('fecha_audit')) {
                $model->fecha_audit = $fecha;
            }
        });
    }

    /**
     * Inicializar el trait — declara campos en fillable y casts.
     */
    public function initializeAuditable(): void
    {
        $this->mergeFillable(['estado', 'usuarioA', 'fechaA', 'usuario_audit', 'fecha_audit']);

        $this->mergeCasts([
            'estado'      => 'boolean',
            'fechaA'      => 'date',
            'fecha_audit' => 'date',
        ]);
    }

    /**
     * Helper para verificar existencia de columnas en la tabla del modelo.
     */
    public function hasAuditableColumn(string $column): bool
    {
        try {
            return Schema::hasColumn($this->getTable(), $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Helper estático para agregar columnas de auditoría a las migraciones.
     *
     * Uso en migraciones:
     *   \App\Traits\Auditable::columns($table);
     */
    public static function columns(Blueprint $table): void
    {
        $table->string('usuarioA', 100)->nullable()->comment('Usuario auditor que realizó la acción');
        $table->date('fechaA')->nullable()->comment('Fecha en que se realizó la acción');
        $table->string('usuario_audit', 100)->nullable();
        $table->date('fecha_audit')->nullable();
    }

    /**
     * Accessor para usuarioA con fallback a usuario_audit.
     */
    public function getUsuarioAAttribute($value)
    {
        return $value ?? $this->attributes['usuario_audit'] ?? null;
    }

    /**
     * Accessor para fechaA con fallback a fecha_audit.
     */
    public function getFechaAAttribute($value)
    {
        return $value ?? $this->attributes['fecha_audit'] ?? null;
    }
}
