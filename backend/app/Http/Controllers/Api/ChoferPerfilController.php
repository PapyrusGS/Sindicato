<?php

namespace App\Http\Controllers\Api;

use App\Services\ChoferPerfilService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChoferPerfilController extends BaseController
{
    protected ChoferPerfilService $choferPerfilService;

    public function __construct(ChoferPerfilService $choferPerfilService)
    {
        $this->choferPerfilService = $choferPerfilService;
    }

    /**
     * Obtener el perfil, datos personales y mapa de vehículos asignados/conducidos por el usuario.
     * GET /api/chofer/mi-perfil
     */
    public function miPerfil(Request $request): JsonResponse
    {
        $perfil = $this->choferPerfilService->obtenerMiPerfil($request->user());

        return $this->sendResponse($perfil, 'Perfil y vehículos del afiliado recuperados con éxito');
    }

    /**
     * Obtener el historial de asistencias, deudas pendientes y pagos realizados del chofer.
     * GET /api/chofer/mi-historial
     */
    public function miHistorial(Request $request): JsonResponse
    {
        $historial = $this->choferPerfilService->obtenerMiHistorial($request->user());

        return $this->sendResponse($historial, 'Historial personal de asistencias, deudas y pagos recuperado con éxito');
    }
}
