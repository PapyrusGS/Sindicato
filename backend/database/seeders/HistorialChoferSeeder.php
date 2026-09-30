<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Chofer;
use App\Models\Grupo;
use App\Models\Lugar;
use App\Models\Multa;
use App\Models\Obligacion;
use App\Models\ObligacionChofer;
use App\Models\Pago;
use App\Models\PagoMulta;
use App\Models\PagoObligacion;
use App\Models\Persona;
use App\Models\TipoObligacion;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class HistorialChoferSeeder extends Seeder
{
    /**
     * Crear historial completo de asistencias, obligaciones, deudas, multas y pagos
     * con especial detalle para el chofer Jorge Arce (Grupo B) y datos para el sistema.
     */
    public function run(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // 1. OBTENER ENTIDADES CLAVE DEL GRUPO B
        // ═══════════════════════════════════════════════════════════════
        $grupoB = Grupo::where('nombre', 'Grupo B')->first();
        if (!$grupoB) {
            return;
        }

        // Personas & Choferes del Grupo B
        $personaJorge = Persona::where('ci', '8899001')->first(); // Jorge Arce
        $choferJorge  = $personaJorge?->chofer;

        $personaRaul    = Persona::where('ci', '3344556')->first(); // Raúl Vargas (Jefe de Grupo)
        $personaGonzalo = Persona::where('ci', '4455667')->first(); // Gonzalo Gutiérrez (Inspector)
        $choferGonzalo  = $personaGonzalo?->chofer;

        $personaHugo    = Persona::where('ci', '5566778')->first(); // Hugo Torrez (Tesorero)

        $lugares = Lugar::all()->keyBy('nombre');
        $tiposObligacion = TipoObligacion::all()->keyBy('nombre');

        if (!$choferJorge || !$choferGonzalo || !$personaRaul || !$personaHugo) {
            return;
        }

        $paradasArray = $lugares->values()->all();

        // ═══════════════════════════════════════════════════════════════
        // 2. ASISTENCIAS DE JORGE ARCE (ÚLTIMAS 4 SEMANAS)
        // ═══════════════════════════════════════════════════════════════
        // Limpiar asistencias previas de prueba si existen para evitar duplicados
        Asistencia::where('chofer_id', $choferJorge->id)->delete();

        $asistenciasFechas = [
            // Semana 1
            ['dias_atras' => 28, 'hora' => '07:15:00', 'presente' => true,  'lugar' => 'Obelisco'],
            ['dias_atras' => 27, 'hora' => '07:20:00', 'presente' => true,  'lugar' => 'Villa Fátima'],
            ['dias_atras' => 26, 'hora' => '08:05:00', 'presente' => true,  'lugar' => 'Parada 3'],
            ['dias_atras' => 25, 'hora' => '07:30:00', 'presente' => true,  'lugar' => 'Parada 4'],
            ['dias_atras' => 24, 'hora' => '07:10:00', 'presente' => true,  'lugar' => 'Obelisco'],
            ['dias_atras' => 23, 'hora' => '08:00:00', 'presente' => false, 'lugar' => 'Villa Fátima'], // Falta

            // Semana 2
            ['dias_atras' => 20, 'hora' => '07:12:00', 'presente' => true,  'lugar' => 'Parada 3'],
            ['dias_atras' => 19, 'hora' => '07:25:00', 'presente' => true,  'lugar' => 'Parada 4'],
            ['dias_atras' => 18, 'hora' => '07:18:00', 'presente' => true,  'lugar' => 'Obelisco'],
            ['dias_atras' => 17, 'hora' => '07:40:00', 'presente' => true,  'lugar' => 'Villa Fátima'],
            ['dias_atras' => 16, 'hora' => '07:15:00', 'presente' => true,  'lugar' => 'Parada 3'],

            // Semana 3
            ['dias_atras' => 13, 'hora' => '07:10:00', 'presente' => true,  'lugar' => 'Parada 4'],
            ['dias_atras' => 12, 'hora' => '07:30:00', 'presente' => true,  'lugar' => 'Obelisco'],
            ['dias_atras' => 11, 'hora' => '07:22:00', 'presente' => true,  'lugar' => 'Villa Fátima'],
            ['dias_atras' => 10, 'hora' => '08:10:00', 'presente' => false, 'lugar' => 'Parada 3'], // Falta
            ['dias_atras' => 9,  'hora' => '07:15:00', 'presente' => true,  'lugar' => 'Parada 4'],

            // Semana 4 (Reciente)
            ['dias_atras' => 5,  'hora' => '07:05:00', 'presente' => true,  'lugar' => 'Obelisco'],
            ['dias_atras' => 4,  'hora' => '07:18:00', 'presente' => true,  'lugar' => 'Villa Fátima'],
            ['dias_atras' => 3,  'hora' => '07:25:00', 'presente' => true,  'lugar' => 'Parada 3'],
            ['dias_atras' => 2,  'hora' => '07:10:00', 'presente' => true,  'lugar' => 'Parada 4'],
            ['dias_atras' => 1,  'hora' => '07:15:00', 'presente' => true,  'lugar' => 'Obelisco'],
            ['dias_atras' => 0,  'hora' => '07:08:00', 'presente' => true,  'lugar' => 'Villa Fátima'],
        ];

        foreach ($asistenciasFechas as $item) {
            $lugarObj = $lugares[$item['lugar']] ?? $paradasArray[0];
            $fechaHora = Carbon::now()->subDays($item['dias_atras'])->setTimeFromTimeString($item['hora']);

            Asistencia::create([
                'chofer_id'    => $choferJorge->id,
                'lugar_id'     => $lugarObj->id,
                'inspector_id' => $choferGonzalo->id,
                'fecha_hora'   => $fechaHora,
                'asistencia'   => $item['presente'],
                'estado'       => true,
                'usuarioA'     => 'system',
                'fechaA'       => now(),
            ]);
        }

        // ═══════════════════════════════════════════════════════════════
        // 3. OBLIGACIONES DEL GRUPO B & DEUDAS DE JORGE ARCE
        // ═══════════════════════════════════════════════════════════════
        // Todos los choferes del Grupo B
        $choferesGrupoB = Chofer::whereHas('choferAutos', function ($q) use ($grupoB) {
            $q->where('grupo_id', $grupoB->id)->where('estado', true);
        })->get();

        $tipoCuotaMensual = $tiposObligacion['Cuota Mensual'] ?? null;
        $tipoAporte = $tiposObligacion['Aporte Sindical'] ?? null;
        $tipoExtra = $tiposObligacion['Cuota Extraordinaria'] ?? null;

        // ── 3.1. Obligación 1: Cuota Mensual Enero 2026 (PAGADA) ────────
        $obEnero = Obligacion::firstOrCreate(
            ['concepto' => 'Cuota Mensual - Enero 2026', 'grupo_id' => $grupoB->id],
            [
                'jefe_persona_id'       => $personaRaul->id,
                'tipo_obligacion_id'    => $tipoCuotaMensual?->id,
                'tipo_categoria'        => 'MENSUAL',
                'monto_individual'      => 100.00,
                'monto_total_esperado'  => 100.00 * max(count($choferesGrupoB), 7),
                'fecha_inicio'          => Carbon::now()->subMonths(2)->startOfMonth(),
                'fecha_fin'             => Carbon::now()->subMonths(2)->endOfMonth(),
                'estado'                => true,
                'usuarioA'              => 'system',
                'fechaA'                => now(),
            ]
        );

        $ocEneroJorge = ObligacionChofer::updateOrCreate(
            ['obligacion_id' => $obEnero->id, 'chofer_id' => $choferJorge->id],
            [
                'monto_asignado' => 100.00,
                'monto_pagado'   => 100.00,
                'estado_pago'    => 'PAGADO',
                'fecha_pago'     => Carbon::now()->subMonths(2)->startOfMonth()->addDays(15),
                'estado'         => true,
                'usuarioA'       => 'system',
                'fechaA'         => now(),
            ]
        );

        // ── 3.2. Obligación 2: Aporte Sindical Pro-Sede Enero 2026 (PAGADA)
        $obAporte = Obligacion::firstOrCreate(
            ['concepto' => 'Aporte Sindical Pro-Sede Enero 2026', 'grupo_id' => $grupoB->id],
            [
                'jefe_persona_id'       => $personaRaul->id,
                'tipo_obligacion_id'    => $tipoAporte?->id,
                'tipo_categoria'        => 'AYUDA',
                'monto_individual'      => 50.00,
                'monto_total_esperado'  => 50.00 * max(count($choferesGrupoB), 7),
                'fecha_inicio'          => Carbon::now()->subMonths(2)->startOfMonth()->addDays(5),
                'fecha_fin'             => Carbon::now()->subMonths(2)->endOfMonth(),
                'estado'                => true,
                'usuarioA'              => 'system',
                'fechaA'                => now(),
            ]
        );

        $ocAporteJorge = ObligacionChofer::updateOrCreate(
            ['obligacion_id' => $obAporte->id, 'chofer_id' => $choferJorge->id],
            [
                'monto_asignado' => 50.00,
                'monto_pagado'   => 50.00,
                'estado_pago'    => 'PAGADO',
                'fecha_pago'     => Carbon::now()->subMonths(2)->startOfMonth()->addDays(15),
                'estado'         => true,
                'usuarioA'       => 'system',
                'fechaA'         => now(),
            ]
        );

        // ── 3.3. Obligación 3: Cuota Mensual Febrero 2026 (PARCIAL: debe 50 Bs)
        $obFebrero = Obligacion::firstOrCreate(
            ['concepto' => 'Cuota Mensual - Febrero 2026', 'grupo_id' => $grupoB->id],
            [
                'jefe_persona_id'       => $personaRaul->id,
                'tipo_obligacion_id'    => $tipoCuotaMensual?->id,
                'tipo_categoria'        => 'MENSUAL',
                'monto_individual'      => 100.00,
                'monto_total_esperado'  => 100.00 * max(count($choferesGrupoB), 7),
                'fecha_inicio'          => Carbon::now()->subMonth()->startOfMonth(),
                'fecha_fin'             => Carbon::now()->subMonth()->endOfMonth(),
                'estado'                => true,
                'usuarioA'              => 'system',
                'fechaA'                => now(),
            ]
        );

        $ocFebreroJorge = ObligacionChofer::updateOrCreate(
            ['obligacion_id' => $obFebrero->id, 'chofer_id' => $choferJorge->id],
            [
                'monto_asignado' => 100.00,
                'monto_pagado'   => 50.00,
                'estado_pago'    => 'PARCIAL',
                'fecha_pago'     => Carbon::now()->subMonth()->startOfMonth()->addDays(12),
                'estado'         => true,
                'usuarioA'       => 'system',
                'fechaA'         => now(),
            ]
        );

        // ── 3.4. Obligación 4: Cuota Mensual Vigente (PENDIENTE: debe 100 Bs)
        $obActual = Obligacion::firstOrCreate(
            ['concepto' => 'Cuota Mensual - Vigente', 'grupo_id' => $grupoB->id],
            [
                'jefe_persona_id'       => $personaRaul->id,
                'tipo_obligacion_id'    => $tipoCuotaMensual?->id,
                'tipo_categoria'        => 'MENSUAL',
                'monto_individual'      => 100.00,
                'monto_total_esperado'  => 100.00 * max(count($choferesGrupoB), 7),
                'fecha_inicio'          => Carbon::now()->startOfMonth(),
                'fecha_fin'             => Carbon::now()->endOfMonth(),
                'estado'                => true,
                'usuarioA'              => 'system',
                'fechaA'                => now(),
            ]
        );

        $ocActualJorge = ObligacionChofer::updateOrCreate(
            ['obligacion_id' => $obActual->id, 'chofer_id' => $choferJorge->id],
            [
                'monto_asignado' => 100.00,
                'monto_pagado'   => 0.00,
                'estado_pago'    => 'PENDIENTE',
                'fecha_pago'     => null,
                'estado'         => true,
                'usuarioA'       => 'system',
                'fechaA'         => now(),
            ]
        );

        // ── 3.5. Obligación 5: Fondo de Mantenimiento de Ruta (PENDIENTE: debe 80 Bs)
        $obMantenimiento = Obligacion::firstOrCreate(
            ['concepto' => 'Fondo Extraordinario Mantenimiento de Ruta', 'grupo_id' => $grupoB->id],
            [
                'jefe_persona_id'       => $personaRaul->id,
                'tipo_obligacion_id'    => $tipoExtra?->id,
                'tipo_categoria'        => 'AYUDA',
                'monto_individual'      => 80.00,
                'monto_total_esperado'  => 80.00 * max(count($choferesGrupoB), 7),
                'fecha_inicio'          => Carbon::now()->startOfMonth()->addDays(2),
                'fecha_fin'             => Carbon::now()->endOfMonth(),
                'estado'                => true,
                'usuarioA'              => 'system',
                'fechaA'                => now(),
            ]
        );

        $ocMantenimientoJorge = ObligacionChofer::updateOrCreate(
            ['obligacion_id' => $obMantenimiento->id, 'chofer_id' => $choferJorge->id],
            [
                'monto_asignado' => 80.00,
                'monto_pagado'   => 0.00,
                'estado_pago'    => 'PENDIENTE',
                'fecha_pago'     => null,
                'estado'         => true,
                'usuarioA'       => 'system',
                'fechaA'         => now(),
            ]
        );

        // Asignar también a los demás choferes del Grupo B las obligaciones
        foreach ($choferesGrupoB as $ch) {
            if ($ch->id === $choferJorge->id) continue;

            ObligacionChofer::firstOrCreate(
                ['obligacion_id' => $obEnero->id, 'chofer_id' => $ch->id],
                ['monto_asignado' => 100.00, 'monto_pagado' => 100.00, 'estado_pago' => 'PAGADO', 'fecha_pago' => Carbon::now()->subMonths(2)->addDays(10), 'estado' => true, 'usuarioA' => 'system', 'fechaA' => now()]
            );
            ObligacionChofer::firstOrCreate(
                ['obligacion_id' => $obFebrero->id, 'chofer_id' => $ch->id],
                ['monto_asignado' => 100.00, 'monto_pagado' => 100.00, 'estado_pago' => 'PAGADO', 'fecha_pago' => Carbon::now()->subMonth()->addDays(10), 'estado' => true, 'usuarioA' => 'system', 'fechaA' => now()]
            );
            ObligacionChofer::firstOrCreate(
                ['obligacion_id' => $obActual->id, 'chofer_id' => $ch->id],
                ['monto_asignado' => 100.00, 'monto_pagado' => 0.00, 'estado_pago' => 'PENDIENTE', 'fecha_pago' => null, 'estado' => true, 'usuarioA' => 'system', 'fechaA' => now()]
            );
        }

        // ═══════════════════════════════════════════════════════════════
        // 4. MULTAS Y SANCIONES DE JORGE ARCE
        // ═══════════════════════════════════════════════════════════════
        // Limpiar multas previas de Jorge Arce
        Multa::where('chofer_id', $choferJorge->id)->delete();

        // ── 4.1. Multa Económica 1: Retraso de salida (PAGADA - Bs. 30)
        $multaPagada = Multa::create([
            'chofer_id'        => $choferJorge->id,
            'inspector_id'     => $choferGonzalo->id,
            'lugar_id'         => $lugares['Obelisco']?->id ?? $paradasArray[0]->id,
            'tipo_sancion'     => 'ECONOMICA',
            'motivo'           => 'Retraso de 12 minutos en horario de salida de parada',
            'sancion_detalle'  => 'Demora injustificada afectando el intervalo de la ruta.',
            'monto'            => 30.00,
            'estado_pago'      => 'PAGADO',
            'fecha_infraccion' => Carbon::now()->subDays(20)->setTime(8, 30, 0),
            'estado'           => true,
            'usuarioA'         => 'system',
            'fechaA'           => now(),
        ]);

        // ── 4.2. Multa Económica 2: Falta a turno de fin de semana (PENDIENTE - Bs. 50)
        $multaPendiente = Multa::create([
            'chofer_id'        => $choferJorge->id,
            'inspector_id'     => $choferGonzalo->id,
            'lugar_id'         => $lugares['Villa Fátima']?->id ?? $paradasArray[0]->id,
            'tipo_sancion'     => 'ECONOMICA',
            'motivo'           => 'Falta injustificada a turno rotativo de fin de semana',
            'sancion_detalle'  => 'No se presentó a la primera vuelta asignada a las 07:00 am.',
            'monto'            => 50.00,
            'estado_pago'      => 'PENDIENTE',
            'fecha_infraccion' => Carbon::now()->subDays(10)->setTime(7, 45, 0),
            'estado'           => true,
            'usuarioA'         => 'system',
            'fechaA'           => now(),
        ]);

        // ── 4.3. Sanción de Castigo: Limpieza de vehículo (NO_APLICA)
        Multa::create([
            'chofer_id'        => $choferJorge->id,
            'inspector_id'     => $choferGonzalo->id,
            'lugar_id'         => $lugares['Parada 3']?->id ?? $paradasArray[0]->id,
            'tipo_sancion'     => 'CASTIGO',
            'motivo'           => 'Limpieza deficiente de la unidad en inspección ocular',
            'sancion_detalle'  => 'Se dispone 1 jornada de lavado y acondicionamiento antes de reiniciar recorrido.',
            'monto'            => 0.00,
            'estado_pago'      => 'NO_APLICA',
            'fecha_infraccion' => Carbon::now()->subDays(4)->setTime(9, 15, 0),
            'estado'           => true,
            'usuarioA'         => 'system',
            'fechaA'           => now(),
        ]);

        // ═══════════════════════════════════════════════════════════════
        // 5. HISTORIAL DE PAGOS REALIZADOS EN CAJA POR JORGE ARCE
        // ═══════════════════════════════════════════════════════════════
        // Limpiar pagos previos de Jorge Arce
        $pagosPrevios = Pago::where('chofer_id', $choferJorge->id)->get();
        foreach ($pagosPrevios as $pp) {
            PagoObligacion::where('pago_id', $pp->id)->delete();
            PagoMulta::where('pago_id', $pp->id)->delete();
            $pp->delete();
        }

        // ── 5.1. Pago 1: Recibo de Enero (Bs. 150 en Efectivo) ─────────
        $pago1 = Pago::create([
            'chofer_id'           => $choferJorge->id,
            'cobrador_persona_id' => $personaHugo->id,
            'monto_total'         => 150.00,
            'fecha_pago'          => Carbon::now()->subMonths(2)->startOfMonth()->addDays(15)->setTime(10, 30, 0),
            'metodo_pago'         => 'EFECTIVO',
            'observacion'         => 'Pago completo de cuota mensual y aporte sindical pro-sede correspondiente a enero.',
            'estado'              => true,
            'usuarioA'            => 'system',
            'fechaA'              => now(),
        ]);

        PagoObligacion::create([
            'pago_id'              => $pago1->id,
            'obligacion_chofer_id' => $ocEneroJorge->id,
            'monto_abonado'        => 100.00,
            'estado'               => true,
            'usuarioA'             => 'system',
            'fechaA'               => now(),
        ]);

        PagoObligacion::create([
            'pago_id'              => $pago1->id,
            'obligacion_chofer_id' => $ocAporteJorge->id,
            'monto_abonado'        => 50.00,
            'estado'               => true,
            'usuarioA'             => 'system',
            'fechaA'               => now(),
        ]);

        // ── 5.2. Pago 2: Recibo de Febrero (Bs. 80 por Transferencia QR) ──
        $pago2 = Pago::create([
            'chofer_id'           => $choferJorge->id,
            'cobrador_persona_id' => $personaHugo->id,
            'monto_total'         => 80.00,
            'fecha_pago'          => Carbon::now()->subMonth()->startOfMonth()->addDays(12)->setTime(16, 45, 0),
            'metodo_pago'         => 'TRANSFERENCIA_QR',
            'observacion'         => 'Comprobante QR #45920: Abono parcial de cuota de febrero (Bs. 50) + Cancelación de multa por retraso (Bs. 30).',
            'estado'              => true,
            'usuarioA'            => 'system',
            'fechaA'              => now(),
        ]);

        PagoObligacion::create([
            'pago_id'              => $pago2->id,
            'obligacion_chofer_id' => $ocFebreroJorge->id,
            'monto_abonado'        => 50.00,
            'estado'               => true,
            'usuarioA'             => 'system',
            'fechaA'               => now(),
        ]);

        PagoMulta::create([
            'pago_id'       => $pago2->id,
            'multa_id'      => $multaPagada->id,
            'monto_abonado' => 30.00,
            'estado'        => true,
            'usuarioA'      => 'system',
            'fechaA'        => now(),
        ]);
    }
}
