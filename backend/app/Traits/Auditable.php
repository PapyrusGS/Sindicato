<?php

namespace App\Traits;

use App\Models\Auditoria;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Trait Auditable
 *
 * Registra automáticamente la auditoría detallada en la tabla `auditorias`
 * para cualquier creación, actualización o eliminación en la base de datos:
 * - Tabla afectada y ID del registro.
 * - Acción ejecutada (CREACION, MODIFICACION, DESACTIVACION, REACTIVACION, ELIMINACION).
 * - Campo modificado con su valor anterior y nuevo valor.
 * - Persona que realizó la acción (persona_id) e IP del dispositivo.
 * - Fecha y hora exacta (fecha_a).
 */
trait Auditable
{
    /**
     * Boot del trait — registra los eventos automáticos de Eloquent.
     */
    public static function bootAuditable(): void
    {
        // ─── AL CREAR UN REGISTRO ──────────────────────────────────────
        static::created(function ($model) {
            self::registrarAuditoria(
                model: $model,
                accion: 'CREACION',
                campo: null,
                valorAnterior: null,
                valorNuevo: 'Registro creado'
            );
        });

        // ─── AL ACTUALIZAR UN REGISTRO ─────────────────────────────────
        static::updated(function ($model) {
            $cambios = $model->getChanges();
            $original = $model->getOriginal();

            // Atributos a ignorar en auditoría detallada de campo
            $ignorar = ['updated_at', 'created_at', 'remember_token', 'fechaA', 'fecha_audit', 'usuarioA', 'usuario_audit'];

            foreach ($cambios as $campo => $nuevoValor) {
                if (in_array($campo, $ignorar)) {
                    continue;
                }

                $valorAnterior = $original[$campo] ?? null;

                // Formatear valores booleanos / arreglos / objetos para guardar como texto legible
                $strAnterior = is_bool($valorAnterior) ? ($valorAnterior ? 'true (1)' : 'false (0)') : (is_array($valorAnterior) ? json_encode($valorAnterior) : (string) $valorAnterior);
                $strNuevo = is_bool($nuevoValor) ? ($nuevoValor ? 'true (1)' : 'false (0)') : (is_array($nuevoValor) ? json_encode($nuevoValor) : (string) $nuevoValor);

                // Determinar tipo de acción
                $accion = 'MODIFICACION';
                if ($campo === 'estado') {
                    $accion = filter_var($nuevoValor, FILTER_VALIDATE_BOOLEAN) ? 'REACTIVACION' : 'DESACTIVACION';
                }

                self::registrarAuditoria(
                    model: $model,
                    accion: $accion,
                    campo: $campo,
                    valorAnterior: $strAnterior,
                    valorNuevo: $strNuevo
                );
            }
        });

        // ─── AL ELIMINAR UN REGISTRO ──────────────────────────────────
        static::deleted(function ($model) {
            self::registrarAuditoria(
                model: $model,
                accion: 'ELIMINACION',
                campo: null,
                valorAnterior: 'Registro existente',
                valorNuevo: 'Eliminado'
            );
        });
    }

    /**
     * Crear una fila en la tabla `auditorias`.
     */
    protected static function registrarAuditoria($model, string $accion, ?string $campo, ?string $valorAnterior, ?string $valorNuevo): void
    {
        try {
            // Evitar bucle infinito si el propio modelo es Auditoria
            if ($model->getTable() === 'auditorias') {
                return;
            }

            // Obtener el ID de la persona autenticada
            $personaId = Auth::user()?->persona_id ?? 1; // Fallback al ID 1 (Admin) en seeders/CLI

            // Obtener la IP de la solicitud
            $ip = request()?->ip() ?? '127.0.0.1';

            Auditoria::create([
                'tabla_nombre'   => $model->getTable(),
                'registro_id'    => $model->getKey(),
                'accion'         => $accion,
                'campo'          => $campo,
                'valor_anterior' => $valorAnterior,
                'valor_nuevo'    => $valorNuevo,
                'persona_id'     => $personaId,
                'fecha_a'        => now(),
                'direccion_ip'   => $ip,
            ]);
        } catch (\Throwable $e) {
            // Silenciar fallos de auditoría en migraciones/instalación inicial
        }
    }

    /**
     * Inicializar el trait — declara campos en fillable y casts para compatibilidad.
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
     * Helper estático para agregar columnas legacy de auditoría en migraciones.
     */
    public static function columns(Blueprint $table): void
    {
        $table->string('usuarioA', 100)->nullable();
        $table->date('fechaA')->nullable();
        $table->string('usuario_audit', 100)->nullable();
        $table->date('fecha_audit')->nullable();
    }
}
