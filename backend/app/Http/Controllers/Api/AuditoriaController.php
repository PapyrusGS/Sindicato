<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\QueryAuditoriaRequest;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditoriaController extends BaseController
{
    protected AuditoriaService $auditoriaService;

    public function __construct(AuditoriaService $auditoriaService)
    {
        $this->auditoriaService = $auditoriaService;
    }

    /**
     * Listar los registros de auditoría e historial del sistema.
     * GET /api/auditorias
     */
    public function index(QueryAuditoriaRequest $request): JsonResponse
    {
        $auditorias = $this->auditoriaService->listarAuditorias(
            filters: $request->validated(),
            usuarioAuth: $request->user()
        );

        return $this->sendResponse([
            'auditorias' => $auditorias->items(),
            'pagination' => [
                'total'        => $auditorias->total(),
                'per_page'     => $auditorias->perPage(),
                'current_page' => $auditorias->currentPage(),
                'last_page'    => $auditorias->lastPage(),
            ],
        ], 'Historial de auditoría recuperado con éxito');
    }

    /**
     * Obtener módulos y acciones auxiliares para filtrar la auditoría.
     * GET /api/auditorias/auxiliares
     */
    public function auxiliares(Request $request): JsonResponse
    {
        $auxiliares = $this->auditoriaService->obtenerAuxiliares($request->user());

        return $this->sendResponse($auxiliares, 'Auxiliares de auditoría recuperados con éxito');
    }
}
