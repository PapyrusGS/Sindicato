<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StorePagoRequest;
use App\Services\CobroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CobroController extends BaseController
{
    protected CobroService $cobroService;

    public function __construct(CobroService $cobroService)
    {
        $this->cobroService = $cobroService;
    }

    /**
     * Obtener lista de choferes elegibles para cobro según el rol (Tesorero exime su auto-cobro).
     * GET /api/cobros/choferes
     */
    public function choferesElegibles(Request $request): JsonResponse
    {
        $data = $this->cobroService->obtenerChoferesElegibles($request->user());

        return $this->sendResponse($data, 'Lista de choferes elegibles para cobro recuperada con éxito');
    }

    /**
     * Obtener el estado de cuenta y deudas pendientes (obligaciones y multas) de un chofer.
     * GET /api/cobros/estado-cuenta/{choferId}
     */
    public function estadoCuenta(int $choferId): JsonResponse
    {
        $estadoCuenta = $this->cobroService->obtenerEstadoCuentaChofer($choferId);

        return $this->sendResponse($estadoCuenta, 'Estado de cuenta del chofer recuperado correctamente');
    }

    /**
     * Registrar y procesar el cobro de deudas pendientes.
     * POST /api/cobros/procesar
     */
    public function store(StorePagoRequest $request): JsonResponse
    {
        try {
            $pago = $this->cobroService->registrarCobro(
                data: $request->validated(),
                usuarioAuth: $request->user()
            );

            return $this->sendResponse($pago, 'Pago registrado y procesado correctamente en el sistema', 201);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Listar el historial de pagos y cobros procesados.
     * GET /api/cobros/historial
     */
    public function historial(Request $request): JsonResponse
    {
        $historial = $this->cobroService->listarHistorialPagos(
            filters: $request->all(),
            usuarioAuth: $request->user()
        );

        return $this->sendResponse([
            'pagos' => $historial->items(),
            'pagination' => [
                'total'        => $historial->total(),
                'per_page'     => $historial->perPage(),
                'current_page' => $historial->currentPage(),
                'last_page'    => $historial->lastPage(),
            ],
        ], 'Historial de pagos recuperado correctamente');
    }
}
