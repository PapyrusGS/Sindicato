<?php

namespace App\Services;

use App\Models\Chofer;
use App\Models\ChoferAuto;
use App\Models\Multa;
use App\Models\ObligacionChofer;
use App\Models\Pago;
use App\Models\PagoMulta;
use App\Models\PagoObligacion;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class CobroService
{
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
     * Listar el historial de pagos y cobros realizados.
     */
    public function listarHistorialPagos(array $filters, Usuario $usuarioAuth)
    {
        $query = Pago::with([
            'chofer.persona',
            'cobradorPersona',
            'pagoObligaciones.obligacionChofer.obligacion',
            'pagoMultas.multa',
        ])->where('estado', true);

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

        return $query->orderBy('fecha_pago', 'desc')->paginate(20);
    }
}
