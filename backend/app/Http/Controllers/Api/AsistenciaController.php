<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\GuardarAsistenciaRequest;
use App\Services\AsistenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AsistenciaController extends BaseController
{
    protected AsistenciaService $asistenciaService;

    public function __construct(AsistenciaService $asistenciaService)
    {
        $this->asistenciaService = $asistenciaService;
    }

    /**
     * Obtener la planilla de asistencia del día con parada asignada y estado de bloqueo a las 8:00 AM.
     * GET /api/asistencias/hoy
     */
    public function hoy(Request $request): JsonResponse
    {
        $grupoId = $request->query('grupo_id') ? (int) $request->query('grupo_id') : null;
        $fecha = $request->query('fecha') ?: null;

        $data = $this->asistenciaService->obtenerAsistenciaDelDia(
            usuarioAuth: $request->user(),
            grupoIdFilter: $grupoId,
            fechaFilter: $fecha
        );

        return $this->sendResponse($data, 'Planilla de asistencia del día generada correctamente');
    }

    /**
     * Guardar/Actualizar asistencias tomadas en lote por el inspector (Respeta regla de las 8:00 AM).
     * POST /api/asistencias/guardar
     */
    public function guardar(GuardarAsistenciaRequest $request): JsonResponse
    {
        $guardados = $this->asistenciaService->guardarAsistenciasMasivas(
            data: $request->validated(),
            usuarioAuth: $request->user()
        );

        return $this->sendResponse(
            ['total_registrados' => $guardados],
            "Se registraron exitosamente $guardados asistencias de choferes"
        );
    }

    /**
     * Consultar historial por fecha seleccionada en el calendario.
     * GET /api/asistencias/historial-fecha
     */
    public function historialPorFecha(Request $request): JsonResponse
    {
        $fecha = $request->query('fecha') ?: now()->toDateString();
        $grupoId = $request->query('grupo_id') ? (int) $request->query('grupo_id') : null;

        $data = $this->asistenciaService->consultarHistorialPorFecha(
            usuarioAuth: $request->user(),
            fecha: $fecha,
            grupoIdFilter: $grupoId
        );

        return $this->sendResponse($data, 'Historial de asistencia recuperado correctamente');
    }
}
