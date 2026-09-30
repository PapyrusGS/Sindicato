<?php

namespace App\Http\Controllers\Api;

use App\Services\NotificacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends BaseController
{
    protected NotificacionService $notificacionService;

    public function __construct(NotificacionService $notificacionService)
    {
        $this->notificacionService = $notificacionService;
    }

    /**
     * Listar notificaciones del usuario autenticado.
     * GET /api/notificaciones
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->notificacionService->listarNotificaciones($request->user());

        return $this->sendResponse($data, 'Notificaciones recuperadas correctamente');
    }

    /**
     * Marcar una notificación específica como leída.
     * PATCH /api/notificaciones/{id}/leer
     */
    public function marcarLeida(Request $request, int $id): JsonResponse
    {
        $ok = $this->notificacionService->marcarLeida($id, $request->user());

        if (!$ok) {
            return $this->sendError('Notificación no encontrada o no pertenece al usuario', [], 404);
        }

        return $this->sendResponse(null, 'Notificación marcada como leída');
    }

    /**
     * Marcar todas las notificaciones del usuario como leídas.
     * PATCH /api/notificaciones/marcar-todas
     */
    public function marcarTodas(Request $request): JsonResponse
    {
        $count = $this->notificacionService->marcarTodasLeidas($request->user());

        return $this->sendResponse(['afectadas' => $count], 'Todas las notificaciones fueron marcadas como leídas');
    }
}
