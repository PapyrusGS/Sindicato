<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreObligacionRequest;
use App\Services\ObligacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ObligacionController extends BaseController
{
    protected ObligacionService $obligacionService;

    public function __construct(ObligacionService $obligacionService)
    {
        $this->obligacionService = $obligacionService;
    }

    /**
     * Listar las obligaciones del grupo con métricas de recaudación.
     * GET /api/obligaciones
     */
    public function index(Request $request): JsonResponse
    {
        $obligaciones = $this->obligacionService->listarObligaciones(
            filters: $request->all(),
            usuarioAuth: $request->user()
        );

        return $this->sendResponse([
            'obligaciones' => $obligaciones->items(),
            'pagination' => [
                'total'        => $obligaciones->total(),
                'per_page'     => $obligaciones->perPage(),
                'current_page' => $obligaciones->currentPage(),
                'last_page'    => $obligaciones->lastPage(),
            ],
        ], 'Obligaciones del grupo recuperadas correctamente');
    }

    /**
     * Crear una nueva obligación y asignarla masivamente a los choferes del grupo.
     * POST /api/obligaciones/crear
     */
    public function store(StoreObligacionRequest $request): JsonResponse
    {
        $obligacion = $this->obligacionService->crearObligacion(
            data: $request->validated(),
            usuarioAuth: $request->user()
        );

        $choferesCount = $obligacion->asignacionesChoferes->count();
        $tipoStr = $obligacion->tipo_categoria === 'MENSUAL' ? 'Cuota Mensual (Vigencia 1 Mes)' : 'Aporte Solidario de Ayuda / Emergencia';

        return $this->sendResponse(
            $obligacion,
            "{$tipoStr} creada e impuesta masivamente a {$choferesCount} choferes del grupo con éxito",
            201
        );
    }

    /**
     * Actualizar / Corregir una obligación existente.
     * PUT /api/obligaciones/{id}
     */
    public function update(StoreObligacionRequest $request, int $id): JsonResponse
    {
        $obligacion = $this->obligacionService->actualizarObligacion(
            id: $id,
            data: $request->validated(),
            usuarioAuth: $request->user()
        );

        return $this->sendResponse($obligacion, 'Obligación modificada y auditada correctamente en el sistema');
    }

    /**
     * Obtener el desglose de choferes asignados a una obligación y sus estados de pago.
     * GET /api/obligaciones/{id}/detalles
     */
    public function detalles(int $id): JsonResponse
    {
        $detalles = $this->obligacionService->obtenerDetallesChoferes($id);

        return $this->sendResponse($detalles, 'Desglose de choferes asignados recuperado con éxito');
    }

    /**
     * Obtener listas auxiliares para la creación de obligaciones.
     * GET /api/obligaciones/auxiliares
     */
    public function auxiliares(Request $request): JsonResponse
    {
        $auxiliares = $this->obligacionService->obtenerAuxiliares($request->user());

        return $this->sendResponse($auxiliares, 'Auxiliares de obligaciones obtenidos con éxito');
    }
}
