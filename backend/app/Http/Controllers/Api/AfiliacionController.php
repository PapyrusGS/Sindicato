<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreAfiliadoRequest;
use App\Http\Requests\UpdateAfiliadoRequest;
use App\Http\Resources\AfiliadoResource;
use App\Services\AfiliacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AfiliacionController extends BaseController
{
    protected AfiliacionService $afiliacionService;

    public function __construct(AfiliacionService $afiliacionService)
    {
        $this->afiliacionService = $afiliacionService;
    }

    /**
     * Listar afiliados con paginación, filtros y búsqueda.
     * GET /api/afiliacion
     */
    public function index(Request $request): JsonResponse
    {
        $afiliados = $this->afiliacionService->listarAfiliados($request->all());

        return $this->sendResponse([
            'afiliados' => AfiliadoResource::collection($afiliados->items()),
            'pagination' => [
                'total'        => $afiliados->total(),
                'per_page'     => $afiliados->perPage(),
                'current_page' => $afiliados->currentPage(),
                'last_page'    => $afiliados->lastPage(),
            ],
        ], 'Listado de afiliados obtenido correctamente');
    }

    /**
     * Obtener listas auxiliares para los selectores del formulario.
     * GET /api/afiliacion/auxiliares
     */
    public function auxiliares(): JsonResponse
    {
        $data = $this->afiliacionService->obtenerAuxiliares();

        return $this->sendResponse($data, 'Datos auxiliares obtenidos correctamente');
    }

    /**
     * Registrar un nuevo afiliado (Solo Administradores).
     * POST /api/afiliacion/registrar
     */
    public function store(StoreAfiliadoRequest $request): JsonResponse
    {
        $usuarioAudit = $request->user()?->username ?? 'admin';
        $persona = $this->afiliacionService->registrarAfiliado($request->validated(), $usuarioAudit);

        return $this->sendResponse(
            new AfiliadoResource($persona),
            'Afiliación y registro completados con éxito',
            201
        );
    }

    /**
     * Actualizar información de un afiliado (Solo Administradores).
     * PUT /api/afiliacion/{id}
     */
    public function update(UpdateAfiliadoRequest $request, int $id): JsonResponse
    {
        $usuarioAudit = $request->user()?->username ?? 'admin';
        $persona = $this->afiliacionService->actualizarAfiliado($id, $request->validated(), $usuarioAudit);

        return $this->sendResponse(
            new AfiliadoResource($persona),
            'Datos del afiliado actualizados correctamente'
        );
    }

    /**
     * Eliminación Lógica: Cambiar estado (Activo 1 / Inactivo 0) con auditoría (Solo Administradores).
     * PATCH /api/afiliacion/{id}/estado
     */
    public function toggleEstado(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'estado' => ['required', 'boolean'],
        ]);

        $usuarioAudit = $request->user()?->username ?? 'admin';
        $nuevoEstado = filter_var($request->input('estado'), FILTER_VALIDATE_BOOLEAN);

        $persona = $this->afiliacionService->cambiarEstado($id, $nuevoEstado, $usuarioAudit);

        $mensaje = $nuevoEstado ? 'Afiliado activado correctamente' : 'Afiliado desactivado (eliminación lógica realizada)';

        return $this->sendResponse(
            new AfiliadoResource($persona),
            $mensaje
        );
    }
}
