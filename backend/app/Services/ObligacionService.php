<?php

namespace App\Services;

use App\Models\Chofer;
use App\Models\ChoferAuto;
use App\Models\Grupo;
use App\Models\Obligacion;
use App\Models\ObligacionChofer;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ObligacionService
{
    /**
     * Comprobar si el usuario autenticado es Administrador.
     */
    protected function esAdministrador(Usuario $usuario): bool
    {
        return $usuario->roles()->where('nombre', 'Administrador')->exists();
    }

    /**
     * Obtener el ID del grupo asignado al Jefe de Grupo autenticado.
     */
    public function obtenerGrupoIdDelJefe(Usuario $usuario): ?int
    {
        $chofer = Chofer::where('persona_id', $usuario->persona_id)->first();
        if (!$chofer) return null;

        return ChoferAuto::where('chofer_id', $chofer->id)->value('grupo_id');
    }

    /**
     * Crear una nueva obligación (Mensual o Ayuda de Emergencia) y asignarla masivamente a los choferes del grupo.
     */
    public function crearObligacion(array $data, Usuario $usuarioAuth): Obligacion
    {
        return DB::transaction(function () use ($data, $usuarioAuth) {
            $isAdmin = $this->esAdministrador($usuarioAuth);
            $grupoId = (int) $data['grupo_id'];

            if (!$isAdmin) {
                $grupoJefe = $this->obtenerGrupoIdDelJefe($usuarioAuth);
                if ($grupoJefe) {
                    $grupoId = $grupoJefe;
                }
            }

            $tipoCategoria = strtoupper($data['tipo_categoria']);
            $fechaInicio = Carbon::parse($data['fecha_inicio']);

            // Si es MENSUAL, duración estricta de 1 mes (ej. 2026-08-01 a 2026-08-31)
            if ($tipoCategoria === 'MENSUAL') {
                $fechaFin = (clone $fechaInicio)->addMonth()->subDay();
            } else {
                $fechaFin = !empty($data['fecha_fin'])
                    ? Carbon::parse($data['fecha_fin'])
                    : (clone $fechaInicio)->addWeeks(2);
            }

            // Obtener choferes activos pertenecientes al grupo
            $choferIds = ChoferAuto::where('grupo_id', $grupoId)
                ->where('estado', true)
                ->pluck('chofer_id')
                ->unique()
                ->values()
                ->toArray();

            $cantidadChoferes = count($choferIds);
            $montoIndividual = (float) $data['monto_individual'];
            $montoTotalEsperado = $montoIndividual * $cantidadChoferes;

            // 1. Crear cabecera de Obligación
            $obligacion = Obligacion::create([
                'grupo_id'             => $grupoId,
                'jefe_persona_id'      => $usuarioAuth->persona_id,
                'tipo_categoria'       => $tipoCategoria,
                'concepto'             => $data['concepto'],
                'monto_individual'     => $montoIndividual,
                'monto_total_esperado' => $montoTotalEsperado,
                'fecha_inicio'         => $fechaInicio,
                'fecha_fin'            => $fechaFin,
                'estado'               => true,
                'usuarioA'             => $usuarioAuth->username,
                'fechaA'               => now(),
            ]);

            // 2. CARGA MASIVA AUTOMÁTICA: Crear registro de deuda individual para cada chofer del grupo
            foreach ($choferIds as $choferId) {
                ObligacionChofer::create([
                    'obligacion_id'  => $obligacion->id,
                    'chofer_id'      => $choferId,
                    'monto_asignado' => $montoIndividual,
                    'monto_pagado'   => 0.00,
                    'estado_pago'    => 'PENDIENTE',
                    'estado'         => true,
                    'usuarioA'       => $usuarioAuth->username,
                    'fechaA'         => now(),
                ]);
            }

            return $obligacion->load(['grupo', 'jefePersona', 'asignacionesChoferes.chofer.persona']);
        });
    }

    /**
     * Actualizar / Corregir una obligación y reajustar los montos de choferes pendientes.
     */
    public function actualizarObligacion(int $id, array $data, Usuario $usuarioAuth): Obligacion
    {
        return DB::transaction(function () use ($id, $data, $usuarioAuth) {
            $obligacion = Obligacion::findOrFail($id);

            $montoNuevo = (float) $data['monto_individual'];
            $montoAnterior = (float) $obligacion->monto_individual;

            $fechaInicio = Carbon::parse($data['fecha_inicio']);
            $tipoCategoria = strtoupper($data['tipo_categoria']);

            if ($tipoCategoria === 'MENSUAL') {
                $fechaFin = (clone $fechaInicio)->addMonth()->subDay();
            } else {
                $fechaFin = !empty($data['fecha_fin']) ? Carbon::parse($data['fecha_fin']) : $obligacion->fecha_fin;
            }

            $cantidadChoferes = $obligacion->asignacionesChoferes()->count();
            $montoTotalEsperado = $montoNuevo * $cantidadChoferes;

            // Actualizar la obligación
            $obligacion->update([
                'tipo_categoria'       => $tipoCategoria,
                'concepto'             => $data['concepto'],
                'monto_individual'     => $montoNuevo,
                'monto_total_esperado' => $montoTotalEsperado,
                'fecha_inicio'         => $fechaInicio,
                'fecha_fin'            => $fechaFin,
                'usuarioA'             => $usuarioAuth->username,
                'fechaA'               => now(),
            ]);

            // Si cambió el monto, actualizar las deudas PENDIENTES de los choferes
            if ($montoNuevo !== $montoAnterior) {
                ObligacionChofer::where('obligacion_id', $obligacion->id)
                    ->where('estado_pago', 'PENDIENTE')
                    ->update([
                        'monto_asignado' => $montoNuevo,
                        'usuarioA'       => $usuarioAuth->username,
                        'fechaA'         => now(),
                    ]);
            }

            return $obligacion->fresh(['grupo', 'jefePersona', 'asignacionesChoferes.chofer.persona']);
        });
    }

    /**
     * Listar obligaciones con filtros y restringidas al grupo del Jefe de Grupo.
     */
    public function listarObligaciones(array $filters, Usuario $usuarioAuth)
    {
        $query = Obligacion::with(['grupo', 'jefePersona', 'asignacionesChoferes.chofer.persona'])
            ->where('estado', true);

        $isAdmin = $this->esAdministrador($usuarioAuth);

        if (!$isAdmin) {
            $grupoJefe = $this->obtenerGrupoIdDelJefe($usuarioAuth);
            if ($grupoJefe) {
                $query->where('grupo_id', $grupoJefe);
            }
        } elseif (!empty($filters['grupo_id'])) {
            $query->where('grupo_id', $filters['grupo_id']);
        }

        if (!empty($filters['tipo_categoria'])) {
            $query->where('tipo_categoria', strtoupper($filters['tipo_categoria']));
        }

        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->where('concepto', 'like', "%{$q}%");
        }

        $paginated = $query->orderBy('fecha_inicio', 'desc')->paginate(15);

        // Adjuntar métricas computadas de recaudación a cada obligación
        $paginated->getCollection()->transform(function ($ob) {
            $totalChoferes = $ob->asignacionesChoferes->count();
            $pagadosCount = $ob->asignacionesChoferes->where('estado_pago', 'PAGADO')->count();
            $montoRecaudado = $ob->asignacionesChoferes->sum('monto_pagado');
            $porcentaje = $ob->monto_total_esperado > 0
                ? round(($montoRecaudado / $ob->monto_total_esperado) * 100, 1)
                : 0;

            $ob->metrics = [
                'total_choferes'      => $totalChoferes,
                'choferes_pagados'    => $pagadosCount,
                'monto_recaudado'     => $montoRecaudado,
                'porcentaje_pagado'   => $porcentaje,
            ];

            return $ob;
        });

        return $paginated;
    }

    /**
     * Obtener el desglose de choferes asignados a una obligación.
     */
    public function obtenerDetallesChoferes(int $obligacionId): array
    {
        $obligacion = Obligacion::with(['grupo', 'jefePersona'])->findOrFail($obligacionId);

        $asignaciones = ObligacionChofer::with(['chofer.persona'])
            ->where('obligacion_id', $obligacionId)
            ->get()
            ->map(fn($ac) => [
                'id'              => $ac->id,
                'chofer_id'       => $ac->chofer_id,
                'nombre_completo' => $ac->chofer->persona?->nombre_completo ?? 'N/A',
                'ci'              => $ac->chofer->persona?->ci ?? '',
                'monto_asignado'  => (float) $ac->monto_asignado,
                'monto_pagado'    => (float) $ac->monto_pagado,
                'estado_pago'     => $ac->estado_pago,
                'fecha_pago'      => $ac->fecha_pago?->format('d/m/Y H:i'),
            ]);

        return [
            'obligacion'    => $obligacion,
            'choferes'      => $asignaciones,
            'recaudacion'   => [
                'total_esperado' => (float) $obligacion->monto_total_esperado,
                'total_pagado'   => (float) $asignaciones->sum('monto_pagado'),
                'pendientes'     => $asignaciones->where('estado_pago', 'PENDIENTE')->count(),
                'completados'    => $asignaciones->where('estado_pago', 'PAGADO')->count(),
            ],
        ];
    }

    /**
     * Datos auxiliares para los formularios de obligaciones.
     */
    public function obtenerAuxiliares(Usuario $usuarioAuth): array
    {
        $isAdmin = $this->esAdministrador($usuarioAuth);
        $grupoJefeId = $this->obtenerGrupoIdDelJefe($usuarioAuth);

        $gruposQuery = Grupo::where('estado', true);
        if (!$isAdmin && $grupoJefeId) {
            $gruposQuery->where('id', $grupoJefeId);
        }

        $grupos = $gruposQuery->get()->map(function ($g) {
            $choferesCount = ChoferAuto::where('grupo_id', $g->id)->where('estado', true)->distinct('chofer_id')->count();
            return [
                'id'              => $g->id,
                'nombre'          => $g->nombre,
                'descripcion'     => $g->descripcion,
                'total_choferes'  => $choferesCount,
            ];
        });

        return [
            'grupos'        => $grupos,
            'grupo_jefe_id' => $grupoJefeId,
            'is_admin'      => $isAdmin,
        ];
    }
}
