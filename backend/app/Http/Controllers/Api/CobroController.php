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

    /**
     * Anular cobro directamente si está dentro de la ventana de gracia de 90 segundos.
     * POST /api/cobros/{id}/anular-inmediato
     */
    public function anularInmediato(Request $request, int $id): JsonResponse
    {
        try {
            $pago = $this->cobroService->anularCobroDirecto($id, $request->user());

            return $this->sendResponse($pago, 'El cobro ha sido anulado directamente y las deudas asociadas fueron restablecidas.');
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        } catch (\Throwable $e) {
            return $this->sendError('Error al anular el cobro: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Solicitar cambio o anulación de un pago cuando se excedió la ventana de gracia.
     * POST /api/cobros/{id}/solicitar-cambio
     */
    public function solicitarCambio(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'motivo' => 'required|string|min:6|max:500',
        ]);

        try {
            $solicitud = $this->cobroService->solicitarCambioPago(
                pagoId: $id,
                motivo: $request->input('motivo'),
                usuarioAuth: $request->user()
            );

            return $this->sendResponse($solicitud, 'Solicitud de cambio enviada correctamente. Se ha notificado al chofer y al jefe de grupo para su autorización.', 201);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        } catch (\Throwable $e) {
            return $this->sendError('Error al crear la solicitud: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Listar solicitudes de cambio de cobro pendientes.
     * GET /api/solicitudes-cambio/pendientes
     */
    public function solicitudesPendientes(Request $request): JsonResponse
    {
        $solicitudes = $this->cobroService->listarSolicitudesPendientes($request->user());

        return $this->sendResponse($solicitudes, 'Solicitudes de cambio pendientes recuperadas con éxito');
    }

    /**
     * Responder a una solicitud de cambio (Aceptar o Denegar).
     * POST /api/solicitudes-cambio/{id}/responder
     */
    public function responderSolicitud(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'accion'      => 'required|in:ACEPTAR,DENEGAR,aceptar,denegar',
            'observacion' => 'nullable|string|max:500',
        ]);

        try {
            $solicitud = $this->cobroService->responderSolicitud(
                solicitudId: $id,
                accion: $request->input('accion'),
                observacion: $request->input('observacion'),
                usuarioAuth: $request->user()
            );

            $mensaje = ($solicitud->estado === 'APROBADO')
                ? 'Solicitud aceptada con éxito. El cobro fue anulado y las deudas fueron restauradas.'
                : 'Solicitud denegada. El cobro se mantiene vigente en el sistema.';

            return $this->sendResponse($solicitud, $mensaje);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        } catch (\Throwable $e) {
            return $this->sendError('Error al resolver la solicitud: ' . $e->getMessage(), [], 500);
        }
    }
}
