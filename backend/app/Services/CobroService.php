<?php

namespace App\Services;

use App\Models\Chofer;
use App\Models\ChoferAuto;
use App\Models\Multa;
use App\Models\ObligacionChofer;
use App\Models\Pago;
use App\Models\PagoMulta;
use App\Models\PagoObligacion;
use App\Models\SolicitudCambioPago;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class CobroService
{
    protected NotificacionService $notificacionService;

    public function __construct(NotificacionService $notificacionService)
    {
        $this->notificacionService = $notificacionService;
    }

    protected function roles(Usuario $usuario): array
    {
        return $usuario->roles()->pluck('nombre')->toArray();
    }

    /**
     * Obtener lista de choferes a los cuales el usuario autenticado puede cobrarle.
     * REGLAS ESTRUCTURALES:
     * - Tesorero: Cobra a choferes de SU grupo, EXCLUYENDO a sí mismo.
     * - Jefe de Grupo: Cobra a choferes de TODOS los grupos.
     * - Administrador: Acceso global ilimitado.
     */
    public function obtenerChoferesElegibles(Usuario $usuarioAuth): array
    {
        $rolesUser = $this->roles($usuarioAuth);

        $esAdmin = in_array('Administrador', $rolesUser);
        $esJefe = in_array('Jefe de Grupo', $rolesUser);
        $esTesorero = in_array('Tesorero', $rolesUser);

        $choferQuery = ChoferAuto::with(['chofer.persona', 'auto', 'grupo'])
            ->where('estado', true);

        $choferTesoreroId = null;

        // Si es sólo Tesorero (y no es ni Jefe ni Admin)
        if ($esTesorero && !$esAdmin && !$esJefe) {
            $choferTesorero = Chofer::where('persona_id', $usuarioAuth->persona_id)->first();
            $choferTesoreroId = $choferTesorero?->id;

            if ($choferTesoreroId) {
                $grupoId = ChoferAuto::where('chofer_id', $choferTesoreroId)->value('grupo_id');
                if ($grupoId) {
                    $choferQuery->where('grupo_id', $grupoId)
                        ->where('chofer_id', '!=', $choferTesoreroId); // ❌ EXCLUSIÓN AUTO-COBRO
                }
            }
        }

        $choferesData = $choferQuery->get()->map(fn($ca) => [
            'id'              => $ca->chofer->id,
            'persona_id'      => $ca->chofer->persona_id,
            'nombre_completo' => $ca->chofer->persona?->nombre_completo ?? 'N/A',
            'ci'              => $ca->chofer->persona?->ci ?? '',
            'celular'         => $ca->chofer->persona?->celular ?? '',
            'placa'           => $ca->auto?->placa ?? 'Sin auto',
            'grupo_id'        => $ca->grupo_id,
            'grupo_nombre'    => $ca->grupo?->nombre ?? '',
        ])->unique('id')->values()->toArray();

        return [
            'choferes'            => $choferesData,
            'es_tesorero'         => $esTesorero && !$esAdmin && !$esJefe,
            'es_jefe_o_admin'     => $esJefe || $esAdmin,
            'chofer_tesorero_id'  => $choferTesoreroId,
        ];
    }

    /**
     * Obtener el estado de cuenta y deudas pendientes de un chofer (Obligaciones y Multas Económicas).
     */
    public function obtenerEstadoCuentaChofer(int $choferId): array
    {
        $chofer = Chofer::with('persona')->findOrFail($choferId);

        // 1. Deudas por Obligaciones de Grupo (Mensual y Ayuda)
        $obligacionesPendientes = ObligacionChofer::with(['obligacion.grupo'])
            ->where('chofer_id', $choferId)
            ->where('estado_pago', '!=', 'PAGADO')
            ->where('estado', true)
            ->get()
            ->map(fn($oc) => [
                'id'               => $oc->id,
                'obligacion_id'    => $oc->obligacion_id,
                'concepto'         => $oc->obligacion?->concepto ?? 'Cuota de Grupo',
                'tipo_categoria'   => $oc->obligacion?->tipo_categoria ?? 'MENSUAL',
                'grupo_nombre'     => $oc->obligacion?->grupo?->nombre ?? '',
                'monto_asignado'   => (float) $oc->monto_asignado,
                'monto_pagado'     => (float) $oc->monto_pagado,
                'saldo_pendiente'  => (float) ($oc->monto_asignado - $oc->monto_pagado),
                'fecha_vencimiento'=> $oc->obligacion?->fecha_fin?->format('d/m/Y'),
            ]);

        // 2. Deudas por Multas / Infracciones Económicas
        $multasPendientes = Multa::with(['lugar'])
            ->where('chofer_id', $choferId)
            ->where('tipo_sancion', 'ECONOMICA')
            ->where('estado_pago', 'PENDIENTE')
            ->where('estado', true)
            ->get()
            ->map(fn($m) => [
                'id'               => $m->id,
                'motivo'           => $m->motivo,
                'sancion_detalle'  => $m->sancion_detalle,
                'lugar_nombre'     => $m->lugar?->nombre ?? 'En Ruta',
                'monto'            => (float) $m->monto,
                'fecha_infraccion' => $m->fecha_infraccion?->format('d/m/Y H:i'),
            ]);

        $totalPendiente = $obligacionesPendientes->sum('saldo_pendiente') + $multasPendientes->sum('monto');

        return [
            'chofer'                 => [
                'id'              => $chofer->id,
                'nombre_completo' => $chofer->persona?->nombre_completo ?? 'N/A',
                'ci'              => $chofer->persona?->ci ?? '',
            ],
            'obligaciones_pendientes' => $obligacionesPendientes,
            'multas_pendientes'       => $multasPendientes,
            'total_deuda_pendiente'   => $totalPendiente,
        ];
    }

    /**
     * Procesar un cobro y registrar la transacción con auditoría completa.
     */
    public function registrarCobro(array $data, Usuario $usuarioAuth): Pago
    {
        return DB::transaction(function () use ($data, $usuarioAuth) {
            $choferId = (int) $data['chofer_id'];

            // Verificar regla de auto-cobro para Tesorero
            $rolesUser = $this->roles($usuarioAuth);
            $esAdmin = in_array('Administrador', $rolesUser);
            $esJefe = in_array('Jefe de Grupo', $rolesUser);
            $esTesorero = in_array('Tesorero', $rolesUser);

            if ($esTesorero && !$esAdmin && !$esJefe) {
                $choferTesorero = Chofer::where('persona_id', $usuarioAuth->persona_id)->first();
                if ($choferTesorero && $choferTesorero->id === $choferId) {
                    throw new \InvalidArgumentException('Un Tesorero no puede registrar el cobro de sus propias deudas. Debe ser procesado por el Jefe de Grupo o Administrador.');
                }
            }

            $deudasObligaciones = $data['deudas_obligaciones'] ?? [];
            $deudasMultas = $data['deudas_multas'] ?? [];

            $montoTotalObligaciones = array_reduce($deudasObligaciones, fn($carry, $item) => $carry + (float)$item['monto'], 0.00);
            $montoTotalMultas = array_reduce($deudasMultas, fn($carry, $item) => $carry + (float)$item['monto'], 0.00);

            $montoTotalPago = $montoTotalObligaciones + $montoTotalMultas;

            if ($montoTotalPago <= 0) {
                throw new \InvalidArgumentException('Debe seleccionar al menos una deuda o concepto con monto positivo a cobrar.');
            }

            // 1. Crear registro maestro de Pago
            $pago = Pago::create([
                'chofer_id'           => $choferId,
                'cobrador_persona_id' => $usuarioAuth->persona_id,
                'monto_total'         => $montoTotalPago,
                'fecha_pago'          => now(),
                'metodo_pago'         => strtoupper($data['metodo_pago']),
                'observacion'         => $data['observacion'] ?? 'Cobro en caja registrado correctamente',
                'estado'              => true,
                'usuarioA'            => $usuarioAuth->username,
                'fechaA'              => now(),
            ]);

            // 2. Procesar Deudas por Obligaciones de Grupo
            foreach ($deudasObligaciones as $item) {
                $obChofer = ObligacionChofer::findOrFail($item['id']);
                $montoAbonado = (float) $item['monto'];

                $nuevoMontoPagado = (float) $obChofer->monto_pagado + $montoAbonado;
                $estadoPago = ($nuevoMontoPagado >= (float) $obChofer->monto_asignado) ? 'PAGADO' : 'PARCIAL';

                $obChofer->update([
                    'monto_pagado' => $nuevoMontoPagado,
                    'estado_pago'  => $estadoPago,
                    'fecha_pago'   => now(),
                    'usuarioA'     => $usuarioAuth->username,
                    'fechaA'       => now(),
                ]);

                PagoObligacion::create([
                    'pago_id'              => $pago->id,
                    'obligacion_chofer_id' => $obChofer->id,
                    'monto_abonado'        => $montoAbonado,
                    'estado'               => true,
                    'usuarioA'             => $usuarioAuth->username,
                    'fechaA'               => now(),
                ]);
            }

            // 3. Procesar Deudas por Multas Económicas
            foreach ($deudasMultas as $item) {
                $multa = Multa::findOrFail($item['id']);
                $montoAbonado = (float) $item['monto'];

                $multa->update([
                    'estado_pago' => 'PAGADO',
                    'usuarioA'    => $usuarioAuth->username,
                    'fechaA'      => now(),
                ]);

                PagoMulta::create([
                    'pago_id'       => $pago->id,
                    'multa_id'      => $multa->id,
                    'monto_abonado' => $montoAbonado,
                    'estado'        => true,
                    'usuarioA'      => $usuarioAuth->username,
                    'fechaA'        => now(),
                ]);
            }

            return $pago->load([
                'chofer.persona',
                'cobradorPersona',
                'pagoObligaciones.obligacionChofer.obligacion',
                'pagoMultas.multa',
            ]);
        });
    }

    /**
     * Revertir internamente las obligaciones y multas vinculadas a un pago y marcarlo inactivo.
     */
    protected function ejecutarReversionPago(Pago $pago, string $motivoDetalle, Usuario $usuarioAuth): void
    {
        // 1. Revertir Obligaciones de Grupo
        foreach ($pago->pagoObligaciones as $po) {
            $obChofer = ObligacionChofer::find($po->obligacion_chofer_id);
            if ($obChofer) {
                $montoRestado = max(0.00, (float)$obChofer->monto_pagado - (float)$po->monto_abonado);
                $nuevoEstado = ($montoRestado <= 0) ? 'PENDIENTE' : 'PARCIAL';
                $obChofer->update([
                    'monto_pagado' => $montoRestado,
                    'estado_pago'  => $nuevoEstado,
                    'fecha_pago'   => ($montoRestado <= 0) ? null : $obChofer->fecha_pago,
                    'usuarioA'     => $usuarioAuth->username,
                    'fechaA'       => now(),
                ]);
            }
        }

        // 2. Revertir Multas Económicas
        foreach ($pago->pagoMultas as $pm) {
            $multa = Multa::find($pm->multa_id);
            if ($multa) {
                $multa->update([
                    'estado_pago' => 'PENDIENTE',
                    'usuarioA'    => $usuarioAuth->username,
                    'fechaA'      => now(),
                ]);
            }
        }

        // 3. Desactivar el Pago y registrar la justificación
        $pago->update([
            'estado'      => false,
            'observacion' => trim(($pago->observacion ?? '') . ' [' . $motivoDetalle . ']'),
            'usuarioA'    => $usuarioAuth->username,
            'fechaA'      => now(),
        ]);
    }

    /**
     * Anular cobro directamente si está dentro de la ventana de gracia de 90 segundos (1:30 min).
     */
    public function anularCobroDirecto(int $pagoId, Usuario $usuarioAuth): Pago
    {
        return DB::transaction(function () use ($pagoId, $usuarioAuth) {
            $pago = Pago::with(['pagoObligaciones', 'pagoMultas', 'chofer.persona'])->findOrFail($pagoId);

            if (!$pago->estado) {
                throw new \InvalidArgumentException('El cobro ya se encuentra anulado previamente.');
            }

            // Validar ventana de gracia (90 segundos = 1:30 min)
            $segundosTranscurridos = (int) abs(now()->diffInSeconds($pago->created_at));
            if ($segundosTranscurridos > 90) {
                throw new \InvalidArgumentException("El tiempo de gracia de 1:30 minutos ha expirado ({$segundosTranscurridos} segundos transcurridos). Debe enviar una solicitud de cambio al chofer y jefe de grupo para autorizar la anulación.");
            }

            // Validar permiso: Quien cobró o Administrador
            $rolesUser = $this->roles($usuarioAuth);
            $esAdmin = in_array('Administrador', $rolesUser);
            if ($pago->cobrador_persona_id !== $usuarioAuth->persona_id && !$esAdmin) {
                throw new \InvalidArgumentException('Solo el usuario que registró este cobro o un Administrador pueden anularlo directamente.');
            }

            $this->ejecutarReversionPago(
                pago: $pago,
                motivoDetalle: 'ANULACIÓN DIRECTA EN VENTANA DE GRACIA POR ' . $usuarioAuth->username,
                usuarioAuth: $usuarioAuth
            );

            return $pago->fresh([
                'chofer.persona',
                'cobradorPersona',
                'pagoObligaciones.obligacionChofer.obligacion',
                'pagoMultas.multa',
                'solicitudesCambio',
            ]);
        });
    }

    /**
     * Solicitar cambio o anulación de un pago una vez excedido el tiempo de gracia.
     * Envía notificación inmediata al Chofer (quien debe aceptar/denegar) y al Jefe de Grupo.
     */
    public function solicitarCambioPago(int $pagoId, string $motivo, Usuario $usuarioAuth): SolicitudCambioPago
    {
        return DB::transaction(function () use ($pagoId, $motivo, $usuarioAuth) {
            $pago = Pago::with(['chofer.persona', 'cobradorPersona'])->findOrFail($pagoId);

            if (!$pago->estado) {
                throw new \InvalidArgumentException('No se puede solicitar cambio para un cobro que ya ha sido anulado.');
            }

            // Validar si ya existe solicitud pendiente
            $solicitudExistente = SolicitudCambioPago::where('pago_id', $pagoId)
                ->where('estado', 'PENDIENTE')
                ->exists();

            if ($solicitudExistente) {
                throw new \InvalidArgumentException('Ya existe una solicitud de cambio pendiente de respuesta para este cobro.');
            }

            // Identificar grupo del chofer y Jefe de Grupo
            $grupoId = ChoferAuto::where('chofer_id', $pago->chofer_id)->value('grupo_id');
            $jefePersonaId = null;
            $jefeUsuario = null;

            if ($grupoId) {
                $jefeChoferAuto = ChoferAuto::where('grupo_id', $grupoId)
                    ->whereHas('chofer.persona.usuario.roles', fn($r) => $r->where('nombre', 'Jefe de Grupo'))
                    ->with('chofer.persona.usuario')
                    ->first();

                $jefePersonaId = $jefeChoferAuto?->chofer?->persona_id;
                $jefeUsuario = $jefeChoferAuto?->chofer?->persona?->usuario;
            }

            $solicitud = SolicitudCambioPago::create([
                'pago_id'                => $pago->id,
                'solicitante_persona_id' => $usuarioAuth->persona_id,
                'chofer_id'              => $pago->chofer_id,
                'jefe_persona_id'        => $jefePersonaId,
                'motivo'                 => trim($motivo),
                'estado'                 => 'PENDIENTE',
                'usuarioA'               => $usuarioAuth->username,
                'fechaA'                 => now(),
            ]);

            $choferPersona = $pago->chofer?->persona;
            $choferUsuario = $choferPersona ? Usuario::where('persona_id', $choferPersona->id)->first() : null;
            $solicitanteNombre = $usuarioAuth->nombre_completo;

            // 1. Notificar al Chofer (debe aceptar o denegar)
            if ($choferUsuario) {
                $this->notificacionService->crearNotificacion(
                    usuarioId: $choferUsuario->id,
                    titulo: '⚠️ Solicitud de Cambio/Anulación de Cobro',
                    mensaje: "El tesorero {$solicitanteNombre} solicita anular el cobro #{$pago->id} por Bs. " . number_format($pago->monto_total, 2) . " registrado a tu nombre. Motivo: {$motivo}. Por favor revisa y acepta o deniega esta solicitud.",
                    tipo: 'SOLICITUD_CAMBIO_PAGO',
                    data: [
                        'solicitud_id' => $solicitud->id,
                        'pago_id'      => $pago->id,
                        'monto'        => (float) $pago->monto_total,
                        'motivo'       => $motivo,
                        'solicitante'  => $solicitanteNombre,
                    ]
                );
            }

            // 2. Notificar al Jefe de Grupo (para información y seguimiento)
            if ($jefeUsuario && (!$choferUsuario || $jefeUsuario->id !== $choferUsuario->id)) {
                $this->notificacionService->crearNotificacion(
                    usuarioId: $jefeUsuario->id,
                    titulo: '📢 Aviso: Solicitud de Cambio de Pago en tu Grupo',
                    mensaje: "El tesorero {$solicitanteNombre} inició una solicitud de anulación del cobro #{$pago->id} (Bs. " . number_format($pago->monto_total, 2) . ") del chofer {$choferPersona?->nombre_completo}. Motivo: {$motivo}.",
                    tipo: 'INFO',
                    data: [
                        'solicitud_id' => $solicitud->id,
                        'pago_id'      => $pago->id,
                        'chofer'       => $choferPersona?->nombre_completo,
                    ]
                );
            }

            return $solicitud->load(['pago.chofer.persona', 'solicitantePersona']);
        });
    }

    /**
     * Responder a una solicitud de cambio (Aceptar o Denegar).
     * Si el chofer acepta, se anula el pago y se restauran las deudas.
     */
    public function responderSolicitud(int $solicitudId, string $accion, ?string $observacion, Usuario $usuarioAuth): SolicitudCambioPago
    {
        return DB::transaction(function () use ($solicitudId, $accion, $observacion, $usuarioAuth) {
            $solicitud = SolicitudCambioPago::with([
                'pago.pagoObligaciones',
                'pago.pagoMultas',
                'chofer.persona',
                'solicitantePersona.usuario',
            ])->findOrFail($solicitudId);

            if ($solicitud->estado !== 'PENDIENTE') {
                throw new \InvalidArgumentException('Esta solicitud ya fue resuelta anteriormente con estado: ' . $solicitud->estado);
            }

            $rolesUser = $this->roles($usuarioAuth);
            $esAdmin = in_array('Administrador', $rolesUser);
            $esJefe = in_array('Jefe de Grupo', $rolesUser);
            $esElChofer = ($usuarioAuth->persona_id === $solicitud->chofer?->persona_id);

            if (!$esElChofer && !$esJefe && !$esAdmin) {
                throw new \InvalidArgumentException('No tienes permisos para resolver esta solicitud. Solo el chofer titular, el jefe de grupo o un administrador pueden responderla.');
            }

            $accionNorm = strtoupper($accion);
            if (!in_array($accionNorm, ['ACEPTAR', 'DENEGAR'])) {
                throw new \InvalidArgumentException('La acción debe ser ACEPTAR o DENEGAR.');
            }

            $choferNombre = $solicitud->chofer?->persona?->nombre_completo ?? 'Chofer';
            $pago = $solicitud->pago;
            $tesoreroUsuario = $solicitud->solicitantePersona?->usuario;

            if ($accionNorm === 'ACEPTAR') {
                // Aceptada: Anular pago y restaurar deudas pendientes
                $this->ejecutarReversionPago(
                    pago: $pago,
                    motivoDetalle: 'ANULACIÓN APROBADA POR CHOFER ' . $usuarioAuth->username . ($observacion ? ': ' . $observacion : ''),
                    usuarioAuth: $usuarioAuth
                );

                $solicitud->update([
                    'estado'                    => 'APROBADO',
                    'respuesta_observacion'     => $observacion,
                    'respondido_por_persona_id' => $usuarioAuth->persona_id,
                    'fecha_respuesta'           => now(),
                ]);

                // Notificar al Tesorero
                if ($tesoreroUsuario) {
                    $this->notificacionService->crearNotificacion(
                        usuarioId: $tesoreroUsuario->id,
                        titulo: '✅ Solicitud de Cambio Aprobada',
                        mensaje: "El chofer {$choferNombre} ha ACEPTADO la solicitud de anulación del cobro #{$pago->id} (Bs. " . number_format($pago->monto_total, 2) . "). El cobro fue anulado y las deudas quedaron restauradas.",
                        tipo: 'INFO',
                        data: [
                            'solicitud_id' => $solicitud->id,
                            'pago_id'      => $pago->id,
                            'estado'       => 'APROBADO',
                        ]
                    );
                }
            } else {
                // Denegada: El pago permanece activo
                $solicitud->update([
                    'estado'                    => 'RECHAZADO',
                    'respuesta_observacion'     => $observacion,
                    'respondido_por_persona_id' => $usuarioAuth->persona_id,
                    'fecha_respuesta'           => now(),
                ]);

                // Notificar al Tesorero
                if ($tesoreroUsuario) {
                    $this->notificacionService->crearNotificacion(
                        usuarioId: $tesoreroUsuario->id,
                        titulo: '❌ Solicitud de Cambio Denegada',
                        mensaje: "El chofer {$choferNombre} ha DENEGADO la solicitud de cambio del cobro #{$pago->id}." . ($observacion ? " Motivo: {$observacion}" : ""),
                        tipo: 'ALERTA',
                        data: [
                            'solicitud_id' => $solicitud->id,
                            'pago_id'      => $pago->id,
                            'estado'       => 'RECHAZADO',
                            'motivo'       => $observacion,
                        ]
                    );
                }
            }

            return $solicitud->fresh(['pago', 'chofer.persona', 'solicitantePersona', 'respondidoPorPersona']);
        });
    }

    /**
     * Listar solicitudes de cambio pendientes relevantes para el usuario autenticado.
     */
    public function listarSolicitudesPendientes(Usuario $usuarioAuth)
    {
        $query = SolicitudCambioPago::with([
            'pago.pagoObligaciones.obligacionChofer.obligacion',
            'pago.pagoMultas.multa',
            'chofer.persona',
            'solicitantePersona',
        ])->where('estado', 'PENDIENTE');

        $rolesUser = $this->roles($usuarioAuth);
        $esAdmin = in_array('Administrador', $rolesUser);
        $esJefe = in_array('Jefe de Grupo', $rolesUser);

        // Si es chofer o tesorero (no admin ni jefe global)
        if (!$esAdmin && !$esJefe) {
            $chofer = Chofer::where('persona_id', $usuarioAuth->persona_id)->first();
            if ($chofer) {
                $query->where(function ($q) use ($chofer, $usuarioAuth) {
                    $q->where('chofer_id', $chofer->id)
                      ->orWhere('solicitante_persona_id', $usuarioAuth->persona_id);
                });
            } else {
                $query->where('solicitante_persona_id', $usuarioAuth->persona_id);
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Listar el historial de pagos y cobros realizados.
     */
    public function listarHistorialPagos(array $filters, Usuario $usuarioAuth)
    {
        $query = Pago::with([
            'chofer.persona',
            'cobradorPersona',
            'pagoObligaciones.obligacionChofer.obligacion',
            'pagoMultas.multa',
            'solicitudesCambio.solicitantePersona',
            'solicitudesCambio.respondidoPorPersona',
        ]);

        // Filtrar por estado si se especifica, por defecto no ocultamos anulados para visibilidad
        if (isset($filters['estado'])) {
            $query->where('estado', filter_var($filters['estado'], FILTER_VALIDATE_BOOLEAN));
        }

        $rolesUser = $this->roles($usuarioAuth);
        $esAdmin = in_array('Administrador', $rolesUser);
        $esJefe = in_array('Jefe de Grupo', $rolesUser);
        $esTesorero = in_array('Tesorero', $rolesUser);

        // Si es sólo tesorero, filtrar choferes de su grupo
        if ($esTesorero && !$esAdmin && !$esJefe) {
            $choferTesorero = Chofer::where('persona_id', $usuarioAuth->persona_id)->first();
            if ($choferTesorero) {
                $grupoId = ChoferAuto::where('chofer_id', $choferTesorero->id)->value('grupo_id');
                if ($grupoId) {
                    $choferIdsDelGrupo = ChoferAuto::where('grupo_id', $grupoId)->pluck('chofer_id')->toArray();
                    $query->whereIn('chofer_id', $choferIdsDelGrupo);
                }
            }
        }

        if (!empty($filters['metodo_pago'])) {
            $query->where('metodo_pago', strtoupper($filters['metodo_pago']));
        }

        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->whereHas('chofer.persona', fn($p) => $p->where('primer_nombre', 'like', "%{$q}%")->orWhere('primer_apellido', 'like', "%{$q}%")->orWhere('ci', 'like', "%{$q}%"))
                    ->orWhereHas('cobradorPersona', fn($p) => $p->where('primer_nombre', 'like', "%{$q}%")->orWhere('primer_apellido', 'like', "%{$q}%"));
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }
}
