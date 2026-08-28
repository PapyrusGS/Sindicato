<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Auto;
use App\Models\Chofer;
use App\Models\ChoferAuto;
use App\Models\Multa;
use App\Models\ObligacionChofer;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Propietario;
use App\Models\Usuario;

class ChoferPerfilService
{
    /**
     * Obtener la información integral del chofer/propietario autenticado.
     */
    public function obtenerMiPerfil(Usuario $usuarioAuth): array
    {
        $persona = Persona::with([
            'usuario.roles',
            'chofer.choferAutos.auto.propietario.persona',
            'chofer.choferAutos.grupo',
            'propietario.autos.choferAutos.chofer.persona',
            'propietario.autos.choferAutos.grupo',
        ])->findOrFail($usuarioAuth->persona_id);

        $esChofer = $persona->chofer !== null;
        $esPropietario = $persona->propietario !== null;

        // 1. Datos Personales & Usuario
        $datosPersonales = [
            'persona_id'      => $persona->id,
            'ci'              => $persona->ci,
            'primer_nombre'   => $persona->primer_nombre,
            'segundo_nombre'  => $persona->segundo_nombre,
            'primer_apellido' => $persona->primer_apellido,
            'segundo_apellido'=> $persona->segundo_apellido,
            'nombre_completo' => $persona->nombre_completo,
            'celular'         => $persona->celular,
            'direccion'       => $persona->direccion,
            'username'        => $persona->usuario?->username ?? '',
            'roles'           => $persona->usuario?->roles->pluck('nombre')->toArray() ?? [],
        ];

        // 2. Vehículos en Conducción Activa (Como Chofer)
        $conduceActualmente = [];
        $grupoPrincipal = null;

        if ($esChofer) {
            $asignacionesChofer = ChoferAuto::with(['auto.propietario.persona', 'grupo'])
                ->where('chofer_id', $persona->chofer->id)
                ->where('estado', true)
                ->get();

            foreach ($asignacionesChofer as $ca) {
                if (!$grupoPrincipal && $ca->grupo) {
                    $grupoPrincipal = [
                        'id'          => $ca->grupo->id,
                        'nombre'      => $ca->grupo->nombre,
                        'descripcion' => $ca->grupo->descripcion,
                    ];
                }

                $auto = $ca->auto;
                $propietarioPersona = $auto?->propietario?->persona;
                $esMiPropioAuto = $propietarioPersona && $propietarioPersona->id === $persona->id;

                $conduceActualmente[] = [
                    'chofer_auto_id'    => $ca->id,
                    'auto_id'           => $auto?->id,
                    'placa'             => $auto?->placa ?? 'N/A',
                    'marca'             => $auto?->marca ?? '',
                    'modelo'            => $auto?->modelo ?? '',
                    'gestion'           => $auto?->gestion ?? '',
                    'es_mi_propio_auto' => $esMiPropioAuto,
                    'propietario'       => [
                        'persona_id'      => $propietarioPersona?->id,
                        'nombre_completo' => $propietarioPersona?->nombre_completo ?? 'N/A',
                        'ci'              => $propietarioPersona?->ci ?? '',
                        'celular'         => $propietarioPersona?->celular ?? '',
                    ],
                    'grupo'             => [
                        'id'     => $ca->grupo?->id,
                        'nombre' => $ca->grupo?->nombre ?? '',
                    ],
                ];
            }
        }

        // 3. Flota de Vehículos Registrados en Propiedad (Como Propietario)
        $vehiculosPropiedad = [];

        if ($esPropietario) {
            $autosPropiedad = Auto::with(['choferAutos.chofer.persona', 'choferAutos.grupo'])
                ->where('propietario_id', $persona->propietario->id)
                ->where('estado', true)
                ->get();

            foreach ($autosPropiedad as $a) {
                $choferesAsignados = [];
                $esConducidoPorMi = false;

                foreach ($a->choferAutos->where('estado', true) as $ca) {
                    $cPersona = $ca->chofer?->persona;
                    $esUstedMismo = $cPersona && $cPersona->id === $persona->id;

                    if ($esUstedMismo) {
                        $esConducidoPorMi = true;
                    }

                    if (!$grupoPrincipal && $ca->grupo) {
                        $grupoPrincipal = [
                            'id'          => $ca->grupo->id,
                            'nombre'      => $ca->grupo->nombre,
                            'descripcion' => $ca->grupo->descripcion,
                        ];
                    }

                    $choferesAsignados[] = [
                        'chofer_id'       => $ca->chofer_id,
                        'persona_id'      => $cPersona?->id,
                        'nombre_completo' => $cPersona?->nombre_completo ?? 'N/A',
                        'ci'              => $cPersona?->ci ?? '',
                        'celular'         => $cPersona?->celular ?? '',
                        'grupo_nombre'    => $ca->grupo?->nombre ?? '',
                        'es_usted_mismo'  => $esUstedMismo,
                    ];
                }

                $estadoConduccion = 'SIN_CHOFER';
                if ($esConducidoPorMi) {
                    $estadoConduccion = 'CONDUCIDO_POR_USTED';
                } elseif (count($choferesAsignados) > 0) {
                    $estadoConduccion = 'CONDUCIDO_POR_OTRO';
                }

                $vehiculosPropiedad[] = [
                    'auto_id'            => $a->id,
                    'placa'              => $a->placa,
                    'marca'              => $a->marca,
                    'modelo'             => $a->modelo,
                    'gestion'            => $a->gestion,
                    'estado_conduccion'  => $estadoConduccion,
                    'es_conducido_por_mi'=> $esConducidoPorMi,
                    'choferes_asignados' => $choferesAsignados,
                ];
            }
        }

        // 4. Resumen
        $manejaSuPropioAuto = collect($conduceActualmente)->pluck('es_mi_propio_auto')->contains(true);

        return [
            'persona'                 => $datosPersonales,
            'grupo'                   => $grupoPrincipal ?? ['id' => null, 'nombre' => 'Sin grupo asignado', 'descripcion' => ''],
            'conduce_actualmente'     => $conduceActualmente,
            'vehiculos_propiedad'     => $vehiculosPropiedad,
            'resumen'                 => [
                'es_chofer'                => $esChofer,
                'es_propietario'           => $esPropietario,
                'total_autos_conduce'      => count($conduceActualmente),
                'total_autos_propiedad'    => count($vehiculosPropiedad),
                'maneja_su_propio_auto'    => $manejaSuPropioAuto,
            ],
        ];
    }

    /**
     * Obtener el historial personal de asistencias, deudas pendientes y pagos realizados del chofer.
     */
    public function obtenerMiHistorial(Usuario $usuarioAuth): array
    {
        $persona = Persona::with('chofer')->findOrFail($usuarioAuth->persona_id);
        $choferId = $persona->chofer?->id;

        if (!$choferId) {
            return [
                'asistencias'           => [],
                'deudas'                => [
                    'obligaciones_pendientes' => [],
                    'multas_pendientes'       => [],
                    'total_deuda_pendiente'   => 0.00,
                ],
                'pagos_realizados'      => [],
            ];
        }

        // 1. Asistencias registradas del chofer
        $asistencias = Asistencia::with(['lugar', 'inspector.persona'])
            ->where('chofer_id', $choferId)
            ->orderBy('fecha_hora', 'desc')
            ->get()
            ->map(fn($a) => [
                'id'               => $a->id,
                'fecha_asistencia' => $a->fecha_hora?->format('d/m/Y H:i') ?? $a->created_at?->format('d/m/Y H:i'),
                'estado'           => $a->asistencia ? 'PRESENTE' : 'FALTA',
                'lugar_nombre'     => $a->lugar?->nombre ?? 'Parada Central',
                'observacion'      => $a->asistencia ? 'Asistencia marcada en parada' : 'Falta registrada en turno',
                'registrado_por'   => $a->inspector?->persona?->nombre_completo ?? 'Inspector de Parada',
            ]);

        // 2. Deudas Pendientes (Cuotas de Grupo y Multas Económicas)
        $obligacionesPendientes = ObligacionChofer::with(['obligacion.grupo'])
            ->where('chofer_id', $choferId)
            ->where('estado_pago', '!=', 'PAGADO')
            ->where('estado', true)
            ->get()
            ->map(fn($oc) => [
                'id'               => $oc->id,
                'concepto'         => $oc->obligacion?->concepto ?? 'Cuota de Grupo',
                'tipo_categoria'   => $oc->obligacion?->tipo_categoria ?? 'MENSUAL',
                'grupo_nombre'     => $oc->obligacion?->grupo?->nombre ?? '',
                'monto_asignado'   => (float) $oc->monto_asignado,
                'monto_pagado'     => (float) $oc->monto_pagado,
                'saldo_pendiente'  => (float) ($oc->monto_asignado - $oc->monto_pagado),
                'estado_pago'      => $oc->estado_pago,
                'fecha_vencimiento'=> $oc->obligacion?->fecha_fin?->format('d/m/Y') ?? 'Sin vencimiento',
            ]);

        $multasPendientes = Multa::with('lugar')
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
                'fecha_infraccion' => $m->fecha_infraccion?->format('d/m/Y H:i') ?? $m->created_at?->format('d/m/Y H:i'),
            ]);

        $totalDeuda = $obligacionesPendientes->sum('saldo_pendiente') + $multasPendientes->sum('monto');

        // 3. Historial de Pagos Realizados por el Chofer
        $pagosRealizados = Pago::with([
            'cobradorPersona',
            'pagoObligaciones.obligacionChofer.obligacion',
            'pagoMultas.multa',
        ])
            ->where('chofer_id', $choferId)
            ->where('estado', true)
            ->orderBy('fecha_pago', 'desc')
            ->get()
            ->map(function ($p) {
                $conceptos = [];
                foreach ($p->pagoObligaciones as $po) {
                    $conceptos[] = "📅 " . ($po->obligacionChofer?->obligacion?->concepto ?? 'Cuota de grupo') . " (Bs. " . number_format($po->monto_abonado, 2) . ")";
                }
                foreach ($p->pagoMultas as $pm) {
                    $conceptos[] = "💰 " . ($pm->multa?->motivo ?? 'Multa económica') . " (Bs. " . number_format($pm->monto_abonado, 2) . ")";
                }

                return [
                    'id'                  => $p->id,
                    'fecha_pago'          => $p->fecha_pago?->format('d/m/Y H:i') ?? $p->created_at?->format('d/m/Y H:i'),
                    'monto_total'         => (float) $p->monto_total,
                    'metodo_pago'         => $p->metodo_pago,
                    'cobrador_nombre'     => $p->cobradorPersona?->nombre_completo ?? 'Tesorero / Caja',
                    'cobrador_ci'         => $p->cobradorPersona?->ci ?? '',
                    'observacion'         => $p->observacion ?? '',
                    'conceptos_cancelados'=> $conceptos,
                ];
            });

        return [
            'asistencias'           => $asistencias,
            'deudas'                => [
                'obligaciones_pendientes' => $obligacionesPendientes,
                'multas_pendientes'       => $multasPendientes,
                'total_deuda_pendiente'   => $totalDeuda,
            ],
            'pagos_realizados'      => $pagosRealizados,
        ];
    }
}
