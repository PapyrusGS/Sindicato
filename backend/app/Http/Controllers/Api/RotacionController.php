<?php

namespace App\Http\Controllers\Api;

use App\Services\RotacionParadaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RotacionController extends BaseController
{
    protected RotacionParadaService $rotacionService;

    public function __construct(RotacionParadaService $rotacionService)
    {
        $this->rotacionService = $rotacionService;
    }

    /**
     * Obtener la configuración general y payload de rotación del día (para sincronización web y móvil).
     * GET /api/rotacion/payload
     */
    public function payload(Request $request): JsonResponse
    {
        $fecha = $request->query('fecha') ?: now()->toDateString();
        $payload = $this->rotacionService->obtenerPayloadRotacion($fecha);

        return $this->sendResponse($payload, 'Configuración y rotación de paradas obtenida exitosamente')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    /**
     * Obtener el itinerario semanal de un grupo específico.
     * GET /api/rotacion/itinerario
     */
    public function itinerario(Request $request): JsonResponse
    {
        $grupoId = $request->query('grupo_id');
        $fecha = $request->query('fecha') ? Carbon::parse($request->query('fecha')) : now();

        if (!$grupoId) {
            return $this->sendError('Debe especificar un grupo_id', [], 422);
        }

        $itinerario = $this->rotacionService->obtenerItinerarioSemanal($grupoId, $fecha);

        return $this->sendResponse($itinerario, 'Itinerario semanal del grupo recuperado exitosamente')
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
