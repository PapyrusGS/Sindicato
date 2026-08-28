<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreSancionRequest;
use App\Services\SancionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SancionController extends BaseController
{
    protected SancionService $sancionService;

    public function __construct(SancionService $sancionService)
    {
        $this->sancionService = $sancionService;
    }

    /**
     * Listar historial de sanciones con filtros.
     * GET /api/sanciones
     */
    public function index(Request $request): JsonResponse
    {
        $sanciones = $this->sancionService->listarSanciones(
            filters: $request->all(),
            usuarioAuth: $request->user()
        );

        return $this->sendResponse([
            'sanciones' => $sanciones->items(),
            'pagination' => [
                'total'        => $sanciones->total(),
                'per_page'     => $sanciones->perPage(),
                'current_page' => $sanciones->currentPage(),
                'last_page'    => $sanciones->lastPage(),
            ],
        ], 'Historial de sanciones recuperado correctamente');
    }

    /**
     * Registrar e imponer una nueva sanción (Económica o Castigo Operativo).
     * POST /api/sanciones/registrar
     */
    public function store(StoreSancionRequest $request): JsonResponse
    {
        $sancion = $this->sancionService->registrarSancion(
            data: $request->validated(),
            usuarioAuth: $request->user()
        );

        $mensaje = $sancion->tipo_sancion === 'ECONOMICA'
            ? "Sanción económica de Bs. " . number_format($sancion->monto, 2) . " impuesta correctamente (Pendiente de Cobro en Tesorería)"
            : "Castigo operativo impuesto correctamente y registrado en el expediente del chofer";

        return $this->sendResponse($sancion->load(['chofer.persona', 'inspector.persona', 'lugar']), $mensaje, 201);
    }

    /**
     * Actualizar / Corregir una sanción existente.
     * PUT /api/sanciones/{id}
     */
    public function update(StoreSancionRequest $request, int $id): JsonResponse
    {
        $sancion = $this->sancionService->actualizarSancion(
            id: $id,
            data: $request->validated(),
            usuarioAuth: $request->user()
        );

        return $this->sendResponse($sancion, 'Sanción modificada y auditada correctamente en el sistema');
    }

    /**
     * Obtener choferes de su grupo, paradas e infracciones sugeridas.
     * GET /api/sanciones/auxiliares
     */
    public function auxiliares(Request $request): JsonResponse
    {
        $auxiliares = $this->sancionService->obtenerAuxiliares($request->user());

        return $this->sendResponse($auxiliares, 'Listas auxiliares para sanciones obtenidas correctamente');
    }
}
